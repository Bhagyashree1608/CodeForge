<?php
session_start();
include 'api/db.php';
include 'sidebar.php';

// Ensure user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// Handle POST from subject selection page
if(isset($_POST['subject']) && isset($_POST['difficulty'])){
    $_SESSION['quiz_subject'] = $_POST['subject'];
    $_SESSION['quiz_difficulty'] = (int)$_POST['difficulty'];
    $_SESSION['quiz'] = null; // reset previous quiz
}

$subject = $_SESSION['quiz_subject'] ?? null;
$difficulty = $_SESSION['quiz_difficulty'] ?? null;

// Redirect if no subject selected
if(!$subject || !$difficulty){
    header("Location: select_subject.php");
    exit;
}

// Initialize quiz if not already set
if(!isset($_SESSION['quiz']) || empty($_SESSION['quiz']['questions'])){
    $questions = [];
    if($conn){
        $stmt = $conn->prepare("SELECT id, subject, question_text, option1, option2, option3, option4, correct_option, difficulty 
                                FROM questions WHERE subject=? AND difficulty=? ORDER BY RAND() LIMIT 10");
        $stmt->bind_param("si", $subject, $difficulty);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()){
            $questions[] = $row;
        }
        $stmt->close();
    }

    if(empty($questions)){
        die("No questions available for this subject.");
    }

    $_SESSION['quiz'] = [
        'questions'=>$questions,
        'current'=>0,
        'xp'=>0,
        'streak'=>0,
        'lives'=>3,
        'correct_count'=>0,
        'wrong_count'=>0
    ];
}

$currentIndex = $_SESSION['quiz']['current'];
$totalQuestions = count($_SESSION['quiz']['questions']);
$currentQuestion = $_SESSION['quiz']['questions'][$currentIndex];
$level = floor($_SESSION['quiz']['xp'] / 100) + 1;
$nextLevelXP = $level * 100;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gamified Quiz</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
body { background:#f4f6f8; font-family:sans-serif; }
.card { border-radius:15px; transition: transform 0.3s; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
.card:hover { transform: scale(1.02); }
.option { cursor:pointer; transition: all 0.2s; padding:12px; border-radius:8px; margin:5px 0; background:#eee; font-weight:bold;}
.option.correct { background:#a6e6a6 !important; }
.option.wrong { background:#f8a6a6 !important; }
.option:hover { background:#ddd; }
#nextBtn { display:none; margin-top:15px; }
.progress-bar { transition: width 0.5s; font-weight:bold; text-align:center; }
.timer { font-weight:bold; font-size:1.1em; margin-bottom:10px; }
.stats span { margin-right:15px; }
#streak-badges span { margin-right:5px; margin-bottom:5px; }
.quiz-info { font-weight:bold; font-size:1.1em; }
.xp-level-bar { margin-bottom:15px; height:25px; border-radius:12px; overflow:hidden; background:#ddd; }
.xp-level-bar .bar { height:100%; text-align:center; font-weight:bold; line-height:25px; color:#fff; transition: width 0.5s; }
.bar-success { background:#28a745; }
</style>
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg p-4">

                <!-- Quiz Info -->
                <div class="d-flex justify-content-between mb-3 quiz-info">
                    <div>Subject: <span id="quiz-subject"><?php echo htmlspecialchars(ucfirst($subject)); ?></span></div>
                    <div>Level: <span id="quiz-level"><?php echo $level; ?></span></div>
                </div>

                <!-- Stats -->
                <div class="d-flex justify-content-between mb-3 stats">
                    <div>Lives: <span id="lives"><?php echo str_repeat('❤', $_SESSION['quiz']['lives']); ?></span></div>
                    <div>Streak: <span id="streak"><?php echo $_SESSION['quiz']['streak']; ?></span></div>
                    <div>XP: <span id="xp"><?php echo $_SESSION['quiz']['xp']; ?></span></div>
                </div>

                <!-- XP Level Bar -->
                <div class="xp-level-bar">
                    <div id="xp-bar" class="bar bar-success" style="width:<?php echo ($_SESSION['quiz']['xp']%100); ?>%">
                        <?php echo $_SESSION['quiz']['xp']; ?> / <?php echo $nextLevelXP; ?> XP
                    </div>
                </div>

                <!-- Badges -->
                <div id="streak-badges" class="d-flex flex-wrap mb-3"></div>

                <!-- Timer -->
                <div class="timer" id="timer">Time left: 30s</div>

                <!-- Question -->
                <h5 id="question-text"><?php echo ($currentIndex+1).'. '.$currentQuestion['question_text']; ?></h5>

                <!-- Options -->
                <div id="options-container">
                    <?php for($i=1;$i<=4;$i++): ?>
                        <div class="option" data-value="<?php echo $i; ?>"><?php echo $currentQuestion['option'.$i]; ?></div>
                    <?php endfor; ?>
                </div>

                <!-- Next Button -->
                <button class="btn btn-primary mt-3" id="nextBtn">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- Sounds -->
<audio id="correct-sound" src="sounds/correct.mp3"></audio>
<audio id="wrong-sound" src="sounds/wrong.mp3"></audio>

<script>
let quizData = <?php echo json_encode($_SESSION['quiz']); ?>;
let currentIndex = <?php echo $currentIndex; ?>;
let totalQuestions = <?php echo $totalQuestions; ?>;
let lives = <?php echo $_SESSION['quiz']['lives']; ?>;
let streak = <?php echo $_SESSION['quiz']['streak']; ?>;
let xp = <?php echo $_SESSION['quiz']['xp']; ?>;
let nextLevelXP = <?php echo $nextLevelXP; ?>;
let timerDuration = 30;
let timer;

const correctSound = document.getElementById('correct-sound');
const wrongSound = document.getElementById('wrong-sound');

const levelEl = document.getElementById('quiz-level');
const xpBar = document.getElementById('xp-bar');

function loadQuestion() {
    if(currentIndex >= totalQuestions || lives <= 0){
        fetch('update_quiz_stats.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({
                xp: xp, streak: streak, lives: lives,
                correct_count: quizData.correct_count,
                wrong_count: quizData.wrong_count
            })
        }).finally(()=>{
            window.location.href='quiz_result.php';
        });
        return;
    }

    const q = quizData.questions[currentIndex];
    document.getElementById('question-text').innerText = (currentIndex+1)+'. '+q.question_text;
    const container = document.getElementById('options-container');
    container.innerHTML = '';
    for(let i=1;i<=4;i++){
        const div = document.createElement('div');
        div.className='option';
        div.dataset.value=i;
        div.innerText = q['option'+i];
        div.addEventListener('click', ()=> handleClick(div,q));
        container.appendChild(div);
    }
    document.getElementById('nextBtn').style.display='none';
    startTimer();
}

function startTimer(){
    let timeLeft = timerDuration;
    const timerEl = document.getElementById('timer');
    timerEl.textContent = `Time left: ${timeLeft}s`;
    timer = setInterval(()=>{
        timeLeft--;
        timerEl.textContent = `Time left: ${timeLeft}s`;
        if(timeLeft<=0){
            clearInterval(timer);
            nextQuestion();
        }
    },1000);
}

function handleClick(opt,q){
    clearInterval(timer);
    const correct = q.correct_option;
    document.querySelectorAll('.option').forEach(o=>o.style.pointerEvents='none');

    if(opt.dataset.value==correct){
        opt.classList.add('correct');
        correctSound.play();
        streak++;
        xp += 10;
        quizData.correct_count = (quizData.correct_count||0)+1;
    } else {
        opt.classList.add('wrong');
        wrongSound.play();
        streak = 0;
        lives--;
        quizData.wrong_count = (quizData.wrong_count||0)+1;
        document.querySelectorAll('.option').forEach(o=>{ if(o.dataset.value==correct) o.classList.add('correct'); });
    }

    updateStats();
    updateBadges();
    document.getElementById('nextBtn').style.display='inline-block';
}

function updateStats(){
    document.getElementById('lives').innerText = '❤'.repeat(lives);
    document.getElementById('streak').innerText = streak;
    document.getElementById('xp').innerText = xp;

    // Update level and XP bar
    let level = Math.floor(xp / 100) + 1;
    levelEl.innerText = level;
    let percent = Math.min(100, (xp%100));
    xpBar.style.width = percent+'%';
    xpBar.innerText = xp+' / '+(level*100)+' XP';
}

function updateBadges(){
    const container = document.getElementById('streak-badges');
    container.innerHTML = '';
    if(streak>=5){
        const span = document.createElement('span');
        span.className="badge bg-warning text-dark";
        span.innerText="⭐ Rising Star";
        container.appendChild(span);
    }
    if(streak>=10){
        const span = document.createElement('span');
        span.className="badge bg-primary";
        span.innerText="🏆 Quiz Master";
        container.appendChild(span);
    }
    if(streak>=20){
        const span = document.createElement('span');
        span.className="badge bg-danger";
        span.innerText="🔥 Champion";
        container.appendChild(span);
    }
}

document.getElementById('nextBtn').addEventListener('click', nextQuestion);

function nextQuestion(){
    currentIndex++;
    quizData.current = currentIndex;
    loadQuestion();
}

window.addEventListener('load', ()=>{
    updateStats();
    updateBadges();
    loadQuestion();
});
</script>
</body>
</html>

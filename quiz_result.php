<?php
session_start();
include 'sidebar.php'; 
include 'api/db.php';

if(!isset($_SESSION['user_id']) || !isset($_SESSION['quiz'])){
    header("Location: quiz.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$quiz = $_SESSION['quiz'];
$totalQuestions = count($quiz['questions'] ?? []);
$correct = $quiz['correct_count'] ?? 0;
$wrong = $quiz['wrong_count'] ?? 0;
$xpGained = $quiz['xp'] ?? 0;
$streak = $quiz['streak'] ?? 0;
$lives = $quiz['lives'] ?? 0;
$subject = $quiz['subject'] ?? 'General';

// 1️⃣ Store quiz attempt
$stmt = $conn->prepare("INSERT INTO quiz_attempts (user_id, subject, score, total_questions) VALUES (?,?,?,?)");
$stmt->bind_param("isii", $user_id, $subject, $correct, $totalQuestions);
$stmt->execute();
$stmt->close();

// 2️⃣ Update user XP
$conn->query("UPDATE users SET xp = xp + $xpGained WHERE id=$user_id");

// 3️⃣ Fetch updated XP
$res = $conn->query("SELECT xp FROM users WHERE id=$user_id");
$user = $res->fetch_assoc();
$currentXP = $user['xp'] ?? 0;

// 4️⃣ Update level based on XP
if ($currentXP < 100) $level = 1;
elseif ($currentXP < 300) $level = 2;
else $level = 3;

$stmt = $conn->prepare("UPDATE users SET level=? WHERE id=?");
$stmt->bind_param("ii", $level, $user_id);
$stmt->execute();
$stmt->close();

$levelName = ($level==1)?'Beginner':(($level==2)?'Intermediate':'Pro');

// Clear quiz session
unset($_SESSION['quiz']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Quiz Result</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
  background: linear-gradient(135deg,#6a11cb 0%,#2575fc 100%);
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  font-family: 'Segoe UI', sans-serif;
}
.result-card {
  background:#fff;
  border-radius:20px;
  padding:40px 30px;
  box-shadow:0 10px 20px rgba(0,0,0,0.15);
  width:100%;
  max-width:500px;
  text-align:center;
}
.result-card h2 {
  font-size:2rem;
  margin-bottom:25px;
  color:#333;
  font-weight:700;
}
.stats {
  font-size:1.1rem;
  margin:12px 0;
  padding:12px;
  border-radius:12px;
  background:#f9f9f9;
}
.stats strong {
  color:#444;
}
.xp-earned {
  background: #e8f5e9;
  color:#43a047;
  font-weight:700;
  font-size:1.2rem;
}
.total-xp {
  background:#e3f2fd;
  color:#1976d2;
  font-weight:700;
  font-size:1.2rem;
}
.take-quiz-btn {
  margin-top:20px;
  border-radius:50px;
  padding:12px 30px;
  font-size:1rem;
}
.hearts {
  font-size:1.5rem;
  color:red;
}
</style>
</head>
<body>
<div class="result-card">
    <h2>🎉 Quiz Completed!</h2>
    
    <div class="stats"><strong>Total Questions:</strong> <?php echo $totalQuestions; ?></div>
    <div class="stats text-success"><strong>Correct:</strong> <?php echo $correct; ?> ✅</div>
    <div class="stats text-danger"><strong>Wrong:</strong> <?php echo $wrong; ?> ❌</div>
    
    <div class="stats xp-earned"><strong>XP Earned This Quiz:</strong> <?php echo $xpGained; ?> ⭐</div>
    <div class="stats total-xp"><strong>Total XP:</strong> <?php echo $currentXP; ?> 💎</div>
    
    <div class="stats"><strong>Max Streak:</strong> 🔥 <?php echo $streak; ?></div>
    <div class="stats"><strong>Lives Remaining:</strong> 
        <span class="hearts"><?php echo str_repeat('❤', $lives); ?></span>
    </div>
    
    <a href="quiz.php" class="btn btn-primary take-quiz-btn">🚀 Take Another Quiz</a>
</div>
</body>
</html>

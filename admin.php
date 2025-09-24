<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// ===== Handle Adding Subject =====
include 'api/db.php';
$subjectMessage = '';
if(isset($_POST['new_subject'])){
    $name = $_POST['new_subject'];
    $code = strtolower(str_replace(' ','_',$name));
    $stmt = $conn->prepare("INSERT INTO subjects (name, code) VALUES (?, ?)");
    $stmt->bind_param("ss",$name,$code);
    if($stmt->execute()) $subjectMessage = "Subject '$name' added!";
    else $subjectMessage = "Error adding subject.";
    $stmt->close();
}

// ===== Handle Adding Question Pack =====
$packMessage = '';
if(isset($_POST['pack_title'], $_POST['pack_subject'])){
    $title = $_POST['pack_title'];
    $subject_id = $_POST['pack_subject'];
    $fileName = strtolower(str_replace(' ','_',$title)) . '.json';
    if(!file_exists('data')) mkdir('data',0777,true);
    file_put_contents('data/'.$fileName, json_encode([]));
    $stmt = $conn->prepare("INSERT INTO question_packs (title, subject_id, json_file) VALUES (?,?,?)");
    $stmt->bind_param("sis",$title,$subject_id,$fileName);
    if($stmt->execute()) $packMessage = "Pack '$title' added!";
    else $packMessage = "Error adding pack.";
    $stmt->close();
}

// ===== Fetch Stats =====
$totalUsers = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'];
$totalSubjects = $conn->query("SELECT COUNT(*) as cnt FROM subjects")->fetch_assoc()['cnt'];
$totalPacks = $conn->query("SELECT COUNT(*) as cnt FROM question_packs")->fetch_assoc()['cnt'];
$totalQuestions = $conn->query("SELECT COUNT(*) as cnt FROM questions")->fetch_assoc()['cnt'];
$totalAttempts = $conn->query("SELECT COUNT(*) as cnt FROM quiz_attempts")->fetch_assoc()['cnt'];

// ===== Fetch Lists =====
$subjects = $conn->query("SELECT * FROM subjects ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$packs = $conn->query("SELECT qp.*, s.name as subject_name FROM question_packs qp JOIN subjects s ON s.id=qp.subject_id ORDER BY qp.id DESC")->fetch_all(MYSQLI_ASSOC);
$users = $conn->query("SELECT * FROM users ORDER BY xp DESC")->fetch_all(MYSQLI_ASSOC);
$attempts = $conn->query("SELECT qa.*, u.username FROM quiz_attempts qa JOIN users u ON u.id=qa.user_id ORDER BY qa.attempted_on DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel - QuizQuest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{background:#121212;color:#fff;font-family:'Segoe UI',sans-serif;}
.sidebar{width:250px;background:#1f1f1f;position:fixed;min-height:100vh;padding-top:20px;}
.sidebar h2{text-align:center;color:#ff5722;margin-bottom:20px;}
.sidebar a{display:block;color:#fff;padding:12px 20px;text-decoration:none;transition:0.2s;}
.sidebar a:hover{background:#ff5722;color:#fff;border-radius:8px;transform:scale(1.03);}
.main-content{margin-left:250px;padding:30px;}
.card{background:#1f1f1f;border:none;border-radius:15px;padding:20px;margin-bottom:20px;transition:0.3s;}
.card:hover{transform:translateY(-5px) scale(1.02);box-shadow:0 15px 35px rgba(255,87,34,0.4);}
.card h5{font-weight:bold;}
#subjects .col-md-4, #packs .col-md-4{margin-bottom:20px;}
.card-category, .card-pack{background:#2c2c2c;border-radius:15px;padding:15px;text-align:center;transition:0.3s;}
.card-category:hover, .card-pack:hover{transform:scale(1.05);box-shadow:0 10px 25px rgba(255,87,34,0.4);}
.btn-primary, .btn-success, .btn-danger{border-radius:10px;font-weight:bold;}
h3{text-align:center;margin-bottom:25px;}
.table td, .table th{vertical-align:middle;}
</style>
</head>
<body>
<div class="sidebar">
<h2>🎮 Admin Panel</h2>
<a href="#dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
<a href="#manage-subjects"><i class="bi bi-book"></i> Manage Subjects</a>
<a href="#manage-packs"><i class="bi bi-folder-plus"></i> Manage Question Packs</a>
<a href="#manage-questions"><i class="bi bi-question-circle"></i> Manage Questions</a>
<a href="#users"><i class="bi bi-people"></i> Users</a>
<a href="#attempts"><i class="bi bi-list-check"></i> Quiz Attempts</a>
<a href="#leaderboard"><i class="bi bi-trophy"></i> Leaderboard</a>
<a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<div class="main-content">
<!-- Dashboard -->
<div id="dashboard">
<h3>📊 Dashboard</h3>
<div class="row">
<div class="col-md-2"><div class="card text-center"><h5>Users</h5><p style="font-size:24px;color:#ff5722;"><?= $totalUsers ?></p></div></div>
<div class="col-md-2"><div class="card text-center"><h5>Subjects</h5><p style="font-size:24px;color:#ff5722;"><?= $totalSubjects ?></p></div></div>
<div class="col-md-2"><div class="card text-center"><h5>Packs</h5><p style="font-size:24px;color:#ff5722;"><?= $totalPacks ?></p></div></div>
<div class="col-md-2"><div class="card text-center"><h5>Questions</h5><p style="font-size:24px;color:#ff5722;"><?= $totalQuestions ?></p></div></div>
<div class="col-md-2"><div class="card text-center"><h5>Attempts</h5><p style="font-size:24px;color:#ff5722;"><?= $totalAttempts ?></p></div></div>
</div>
</div>

<!-- Manage Subjects -->
<div id="manage-subjects" class="mt-5">
<h3>🗂 Manage Subjects</h3>
<?php if($subjectMessage!=''): ?><div class="alert alert-success"><?= $subjectMessage ?></div><?php endif; ?>
<form method="POST" class="mb-4 d-flex">
<input type="text" name="new_subject" class="form-control me-2" placeholder="Add new subject" required>
<button type="submit" class="btn btn-primary">Add Subject</button>
</form>
<div class="row" id="subjects">
<?php foreach($subjects as $sub): ?>
<div class="col-md-3">
<div class="card-category">
<h5><?= $sub['name'] ?></h5>
<p>Code: <?= $sub['code'] ?></p>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

<!-- Manage Question Packs -->
<div id="manage-packs" class="mt-5">
<h3>📦 Manage Question Packs</h3>
<?php if($packMessage!=''): ?><div class="alert alert-success"><?= $packMessage ?></div><?php endif; ?>
<form method="POST" class="mb-4 d-flex gap-2">
<select class="form-select" name="pack_subject" required>
<option value="">Select Subject</option>
<?php foreach($subjects as $sub): ?>
<option value="<?= $sub['id'] ?>"><?= $sub['name'] ?></option>
<?php endforeach; ?>
</select>
<input type="text" name="pack_title" class="form-control" placeholder="Pack Title" required>
<button type="submit" class="btn btn-success">Add Pack</button>
</form>
<div class="row" id="packs">
<?php foreach($packs as $p): ?>
<div class="col-md-4">
<div class="card-pack">
<h5><?= $p['title'] ?></h5>
<p>Subject: <?= $p['subject_name'] ?></p>
<p>File: <?= $p['json_file'] ?></p>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

<!-- Users -->
<div id="users" class="mt-5">
<h3>👥 Users</h3>
<table class="table table-dark table-striped">
<thead>
<tr><th>ID</th><th>Username</th><th>Email</th><th>XP</th><th>Level</th></tr>
</thead>
<tbody>
<?php foreach($users as $u): ?>
<tr><td><?= $u['id'] ?></td><td><?= $u['username'] ?></td><td><?= $u['email'] ?></td><td><?= $u['xp'] ?></td><td><?= $u['level'] ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Quiz Attempts -->
<div id="attempts" class="mt-5">
<h3>📖 Quiz Attempts</h3>
<table class="table table-dark table-striped">
<thead>
<tr><th>ID</th><th>User</th><th>Subject</th><th>Total Qs</th><th>Correct</th><th>Date</th></tr>
</thead>
<tbody>
<?php foreach($attempts as $a): ?>
<tr><td><?= $a['id'] ?></td><td><?= $a['username'] ?></td><td><?= $a['subject'] ?></td><td><?= $a['total_questions'] ?></td><td><?= $a['correct_answers'] ?></td><td><?= $a['attempted_on'] ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Leaderboard -->
<div id="leaderboard" class="mt-5">
<h3>🏆 Leaderboard</h3>
<table class="table table-dark table-striped">
<thead>
<tr><th>Rank</th><th>Username</th><th>XP</th><th>Level</th></tr>
</thead>
<tbody>
<?php $rank=1; foreach($users as $u): ?>
<tr><td><?= $rank++ ?></td><td><?= $u['username'] ?></td><td><?= $u['xp'] ?></td><td><?= $u['level'] ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</div>

<script>
// Smooth scroll
document.querySelectorAll('.sidebar a[href^="#"]').forEach(link=>{
    link.addEventListener('click', e=>{
        e.preventDefault();
        document.querySelector(link.getAttribute('href')).scrollIntoView({behavior:'smooth'});
    });
});
</script>
</body>
</html>
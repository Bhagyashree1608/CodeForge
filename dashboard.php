<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) header("Location: admin_login.php");
include 'api/db.php';

// ===== Helper function to safely fetch single value =====
function fetch_single_value($conn, $query) {
    $result = $conn->query($query);
    if(!$result) die("Query failed: " . $conn->error);
    $row = $result->fetch_assoc();
    return $row['cnt'] ?? 0;
}

// ===== Top Stats =====
$totalUsers = fetch_single_value($conn, "SELECT COUNT(*) as cnt FROM users");
$totalSubjects = fetch_single_value($conn, "SELECT COUNT(*) as cnt FROM subjects");
$totalQuestions = fetch_single_value($conn, "SELECT COUNT(*) as cnt FROM questions");
$totalAttempts = fetch_single_value($conn, "SELECT COUNT(*) as cnt FROM quiz_attempts");

// ===== Recent Users =====
$recentUsers = [];
$recentUsersQuery = "SELECT * FROM users ORDER BY created_at DESC LIMIT 5";
if($result = $conn->query($recentUsersQuery)){
    $recentUsers = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $recentUsers = [];
}

// ===== Recent Quiz Attempts =====
$recentAttempts = [];
$recentAttemptsQuery = "
    SELECT qa.*, u.username 
    FROM quiz_attempts qa 
    JOIN users u ON u.id = qa.user_id
    ORDER BY qa.attempted_on DESC 
    LIMIT 5
";
if($result = $conn->query($recentAttemptsQuery)){
    $recentAttempts = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $recentAttempts = [];
}

// ===== Subject-wise Question Count =====
$subjects = [];
$subjectsQuery = "
    SELECT s.id, s.name, 
           (SELECT COUNT(*) FROM questions q WHERE q.subject = s.name) AS question_count
    FROM subjects s
    ORDER BY s.id DESC
";
if($result = $conn->query($subjectsQuery)){
    $subjects = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $subjects = [];
}

$targetQuestions = 20; // adjust target questions per subject
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: #f5f5f5; color: #333; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 0; }
.sidebar { width: 240px; background: #fff; position: fixed; min-height: 100vh; padding-top: 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.1); z-index: 1000; }
.sidebar h2 { text-align: center; color: #ff5722; margin-bottom: 20px; }
.sidebar a { display: block; color: #333; padding: 14px 20px; text-decoration: none; transition: 0.2s; font-weight: 500; }
.sidebar a:hover { background: #ff5722; color: #fff; border-radius: 8px; }
.main-content { margin-left: 240px; padding: 30px; }
.card { border-radius: 15px; padding: 20px; margin-bottom: 20px; text-align: center; transition: 0.3s; background: #fff; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
.card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(255,87,34,0.2); }
.card h5 { color: #ff5722; font-weight: bold; margin-bottom: 10px; }
.stat-number {font-size:28px; font-weight:bold; color:#333;}
.table th, .table td { vertical-align: middle; }
.progress { height: 20px; border-radius: 10px; }
.progress-bar { background: #ff5722; }
h3 { color: #ff5722; margin-bottom: 25px; text-align: center; }
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
<h3>📊 Admin Dashboard</h3>

<!-- Top Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <h5>Total Users</h5>
            <p class="stat-number"><?= $totalUsers ?></p>
            <small>Registered Users</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <h5>Total Subjects</h5>
            <p class="stat-number"><?= $totalSubjects ?></p>
            <small>Available Subjects</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <h5>Total Questions</h5>
            <p class="stat-number"><?= $totalQuestions ?></p>
            <small>All Subjects</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <h5>Total Attempts</h5>
            <p class="stat-number"><?= $totalAttempts ?></p>
            <small>All Users Combined</small>
        </div>
    </div>
</div>

<!-- Subject Progress -->
<div class="card mb-4">
<h5>📚 Subject Progress</h5>
<?php foreach($subjects as $sub): 
    $percent = $targetQuestions>0 ? min(100, round(($sub['question_count']/$targetQuestions)*100)) : 0;
?>
<div class="mb-3">
    <label><strong><?= $sub['name'] ?></strong> (<?= $sub['question_count'] ?> / <?= $targetQuestions ?> questions)</label>
    <div class="progress">
        <div class="progress-bar" role="progressbar" style="width: <?= $percent ?>%;" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"><?= $percent ?>%</div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Recent Users -->
<div class="card mb-4">
<h5>🧑‍💻 Recent Users</h5>
<table class="table table-striped">
<thead>
<tr><th>ID</th><th>Username</th><th>Email</th><th>Level</th><th>XP</th><th>Joined On</th></tr>
</thead>
<tbody>
<?php foreach($recentUsers as $u): ?>
<tr>
    <td><?= $u['id'] ?></td>
    <td><?= $u['username'] ?></td>
    <td><?= $u['email'] ?></td>
    <td><?= $u['level'] ?></td>
    <td><?= $u['xp'] ?></td>
    <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Recent Quiz Attempts -->
<div class="card mb-4">
<h5>📖 Recent Quiz Attempts</h5>
<table class="table table-striped">
<thead>
<tr><th>User</th><th>Subject</th><th>Total Questions</th><th>Correct Answers</th><th>Date</th></tr>
</thead>
<tbody>
<?php foreach($recentAttempts as $a): ?>
<tr>
    <td><?= $a['username'] ?></td>
    <td><?= ucfirst($a['subject']) ?></td>
    <td><?= $a['total_questions'] ?></td>
    <td><?= $a['correct_answers'] ?></td>
    <td><?= date('d M Y H:i', strtotime($a['attempted_on'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</div>
</body>
</html>

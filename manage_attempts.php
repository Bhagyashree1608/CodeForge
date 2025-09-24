<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) header("Location: admin_login.php");
include 'api/db.php';

// Fetch all quiz attempts with user and subject details
$sql = "SELECT qa.id, u.username, s.name AS subject, qa.score, qa.total_questions, qa.attempted_on 
        FROM quiz_attempts qa
        JOIN users u ON qa.user_id = u.id
        JOIN subjects s ON qa.subject_id = s.id
        ORDER BY qa.attempted_on DESC";

$result = $conn->query($sql);
$attempts = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <style>
body {
    background: #f5f5f5;
    color: #333;
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
    padding: 0;
}
.sidebar {
    width: 240px;
    background: #fff;
    position: fixed;
    min-height: 100vh;
    padding-top: 20px;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    z-index: 1000;
}
.sidebar h2 {
    text-align: center;
    color: #ff5722;
    margin-bottom: 20px;
}
.sidebar a {
    display: block;
    color: #333;
    padding: 14px 20px;
    text-decoration: none;
    transition: 0.2s;
    font-weight: 500;
}
.sidebar a:hover {
    background: #ff5722;
    color: #fff;
    border-radius: 8px;
}
.main-content {
    margin-left: 240px;
    padding: 30px;
}
.card {
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    text-align: center;
    transition: 0.3s;
    background: #fff;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(255,87,34,0.2);
}
.card h5 {
    color: #ff5722;
    font-weight: bold;
    margin-bottom: 10px;
}
.table th, .table td {
    vertical-align: middle;
}
h3 {
    color: #ff5722;
    margin-bottom: 25px;
    text-align: center;
}
</style>

<meta charset="UTF-8">
<title>Manage Quiz Attempts</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
<?php include 'sidebar2.php'; ?>  /* Include sidebar styling here */
.main-content { margin-left: 240px; padding: 30px; }
.card { border-radius:15px; padding:20px; margin-bottom:20px; background:#fff; box-shadow:0 5px 20px rgba(0,0,0,0.05); }
.card h5 { color:#ff5722; font-weight:bold; margin-bottom:15px; text-align:center; }
.table th, .table td { vertical-align: middle; }
h3 { color:#ff5722; margin-bottom: 25px; text-align: center; }
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
<h3>📋 Manage Quiz Attempts</h3>

<div class="card p-3">
<table class="table table-striped table-bordered">
<thead class="table-dark">
<tr>
<th>ID</th>
<th>User</th>
<th>Subject</th>
<th>Score</th>
<th>Total Questions</th>
<th>Attempted On</th>
</tr>
</thead>
<tbody>
<?php if(!empty($attempts)): ?>
<?php foreach($attempts as $a): ?>
<tr>
<td><?= $a['id'] ?></td>
<td><?= $a['username'] ?></td>
<td><?= $a['subject'] ?></td>
<td><?= $a['score'] ?></td>
<td><?= $a['total_questions'] ?></td>
<td><?= date('d M Y, H:i', strtotime($a['attempted_on'])) ?></td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="6" class="text-center">No quiz attempts found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

</div>
</body>
</html>
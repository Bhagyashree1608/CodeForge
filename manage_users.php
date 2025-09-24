<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) header("Location: admin_login.php");
include 'api/db.php';

// Fetch all users
$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
// sidebar.php - include this at the top of every admin module
include "sidebar2.php";
?>
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
    cursor: pointer;
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
.stat-number {
    font-size: 28px;
    font-weight: bold;
    color: #333;
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
<title>Manage Users</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
<?php include 'sidebar.php'; ?>

/* Table styling */
.table-container {
    background: #fff;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}
.table-container h5 {
    color: #ff5722;
    font-weight: bold;
    margin-bottom: 15px;
}
.table th, .table td {
    vertical-align: middle;
}

</style>
</head>
<body>
<div class="main-content">
<h3>🧑‍💻 Manage Users</h3>

<div class="table-container">
<h5>Registered Users</h5>
<?php if(!empty($users)): ?>
<table class="table table-striped table-hover">
<thead>
<tr>
<th>ID</th>
<th>Username</th>
<th>Email</th>
<th>Level</th>
<th>XP</th>
<th>Joined On</th>
</tr>
</thead>
<tbody>
<?php foreach($users as $u): ?>
<tr>
<td><?= $u['id'] ?></td>
<td><?= htmlspecialchars($u['username']) ?></td>
<td><?= htmlspecialchars($u['email']) ?></td>
<td><?= $u['level'] ?></td>
<td><?= $u['xp'] ?></td>
<td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p>No users registered yet.</p>
<?php endif; ?>
</div>

</div>
</body>
</html>
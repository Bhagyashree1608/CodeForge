<?php
session_start();
include 'api/db.php';

// Only allow admin
if(!isset($_SESSION['admin_logged_in'])){
    header("Location: admin_login.php");
    exit;
}

// Fetch all users for leaderboard
$users = [];
$res = $conn->query("SELECT username, profile_pic, xp, level FROM users ORDER BY xp DESC");
while($row = $res->fetch_assoc()){
    if (is_null($row['xp'])) $row['xp'] = 0;
    if (is_null($row['level']) || $row['level'] < 1) $row['level'] = 1;
    $users[] = $row;
}

// XP calculation function
$xpPerLevel = 100;
function getXPProgress($xp, $level, $xpPerLevel) {
    $currentLevelXPStart = ($level - 1) * $xpPerLevel;
    $nextLevelXP = $level * $xpPerLevel;
    $progressPercent = (($xp - $currentLevelXPStart) / ($nextLevelXP - $currentLevelXPStart)) * 100;
    if ($progressPercent > 100) $progressPercent = 100;
    return [
        'currentXP' => $xp,
        'nextLevelXP' => $nextLevelXP,
        'progressPercent' => $progressPercent
    ];
}
include "sidebar2.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Leaderboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body {
    background: #f5f5f5;
    color: #333;
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
    padding: 0;
}
/* Sidebar styles */
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

/* Main content */
.main-content {
    margin-left: 240px; /* Adjust for sidebar */
    padding: 30px;
}

/* Card styles */
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

/* Table and leaderboard styles */
.table th, .table td { vertical-align: middle; }
.profile-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}
.progress-bar { transition: width 0.5s; font-weight: bold; }
.badge { font-size: 0.75rem; }
.table tbody tr.highlight {
    background: #ffd700 !important;
    font-weight: bold;
}
#leaderboardSearch {
    max-width: 300px;
    margin-bottom: 15px;
}
</style>
</head>
<body>

<div class="main-content">
    <h3 class="mb-3 text-center">🏆 Admin Leaderboard</h3>

    <!-- Search Box -->
    <input type="text" id="leaderboardSearch" class="form-control" placeholder="Search by username or level...">

    <div class="table-responsive mt-3">
        <table class="table table-bordered table-hover align-middle" id="leaderboardTable">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Avatar</th>
                    <th>Username</th>
                    <th>Level</th>
                    <th>XP</th>
                    <th>Badges</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $index => $user):
                    $userProgress = getXPProgress($user['xp'], $user['level'], $xpPerLevel);
                    $badges = [];
                    if($user['xp'] >= 50) $badges[] = "⭐ Rising Star";
                    if($user['xp'] >= 100) $badges[] = "🏆 Quiz Master";
                    if($user['xp'] >= 200) $badges[] = "🔥 Champion";
                ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><img src="uploads/<?= htmlspecialchars($user['profile_pic']); ?>" class="profile-pic"></td>
                    <td class="username"><?= htmlspecialchars($user['username']); ?></td>
                    <td class="level"><?= $user['level']; ?></td>
                    <td style="width:250px;">
                        <div class="progress" style="height:20px;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: <?= $userProgress['progressPercent']; ?>%;">
                                 <?= $userProgress['currentXP']; ?> / <?= $userProgress['nextLevelXP']; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php foreach($badges as $b): ?>
                            <span class="badge bg-warning text-dark me-1 mb-1"><?= $b ?></span>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// ---------------- Dynamic Search ----------------
$(document).ready(function(){
    $("#leaderboardSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#leaderboardTable tbody tr").filter(function() {
            $(this).toggle(
                $(this).find(".username").text().toLowerCase().indexOf(value) > -1 ||
                $(this).find(".level").text().toLowerCase().indexOf(value) > -1
            );
        });
    });
});
</script>
</body>
</html>
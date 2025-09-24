<?php
session_start();
include 'api/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// ---------------- Fetch current XP for logged-in user ----------------
$stmt = $conn->prepare("SELECT username, profile_pic, xp, level FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$currentUser = $result->fetch_assoc();
$stmt->close();

// If user record is found:
if ($currentUser) {
    // Default XP = 0 if null
    if (is_null($currentUser['xp'])) {
        $currentUser['xp'] = 0;
        $stmt = $conn->prepare("UPDATE users SET xp=0 WHERE id=?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }

    // Default Level = 1 if null
    if (is_null($currentUser['level']) || $currentUser['level'] < 1) {
        $currentUser['level'] = 1;
        $stmt = $conn->prepare("UPDATE users SET level=1 WHERE id=?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }
} else {
    // if no user found, force logout
    header("Location: logout.php");
    exit;
}

// ---------------- DO NOT increase XP here automatically ----------------
// XP increment should happen after quiz submission, not on dashboard load.

// ---------------- Fetch all users for leaderboard ----------------
$users = [];
$res = $conn->query("SELECT username, profile_pic, xp, level FROM users ORDER BY xp DESC");
while($row = $res->fetch_assoc()){
    if (is_null($row['xp'])) $row['xp'] = 0;
    if (is_null($row['level']) || $row['level'] < 1) $row['level'] = 1;
    $users[] = $row;
}

// ---------------- Fetch subjects dynamically ----------------
$subjects = [];
$subResult = $conn->query("SELECT DISTINCT subject FROM questions");
while($row = $subResult->fetch_assoc()){
    $subjects[] = $row['subject'];
}

// ---------------- XP Calculation Function ----------------
$xpPerLevel = 100; // XP required per level

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

$currentUserProgress = getXPProgress($currentUser['xp'], $currentUser['level'], $xpPerLevel);
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gamified Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body {
    background: #3b2e2e url('images/leather-texture.jpg') repeat;
    color: #fff;
    font-family: 'Arial', sans-serif;
}

/* Sidebar */
.sidebar {
    height: 100vh;
    background: rgba(30,30,47,0.95);
    color: #fff;
    padding-top: 20px;
}
.sidebar a {
    color: #fff;
    display: block;
    padding: 10px 15px;
    border-radius: 8px;
    text-decoration: none;
    margin-bottom: 5px;
}
.sidebar a:hover {
    background: #5a4b4b;
}

/* Profile Picture */
.profile-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

/* Cards */
.card {
    border-radius: 15px;
    background: rgba(0,0,0,0.5);
    box-shadow: 0 4px 12px rgba(0,0,0,0.5);
    color: #fff; /* Ensure card text is white */
}

/* Progress Bars */
.progress-bar {
    transition: width 0.5s;
    font-weight: bold;
    color: #fff !important; /* Force text color white */
}

/* Badges */
.badge {
    font-size: 0.75rem;
    color: #000 !important; /* Badge text black for contrast on yellow badges */
}

/* Table */
.table thead {
    background: rgba(0,0,0,0.6);
    color: #fff; /* Table header text white */
}
.table tbody tr:nth-child(odd) {
    background: rgba(0,0,0,0.3);
    color: #fff; /* Odd rows text white */
}
.table tbody tr:nth-child(even) {
    background: rgba(0,0,0,0.4);
    color: #fff; /* Even rows text white */
}
.table tbody tr.highlight {
    background: #ff6f61 !important;
    color: #fff !important; /* Highlighted row text white */
}

/* Search Box */
#leaderboardSearch {
    max-width: 300px;
    margin-bottom: 15px;
    color: #000; /* Text in input field */
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .sidebar { height: auto; }
    .profile-pic { width: 35px; height: 35px; }
    .table td, .table th { font-size: 0.85rem; color: #fff; }
}

</style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar d-flex flex-column">
            <div class="d-flex align-items-center mb-3 px-2">
                <img src="uploads/<?php echo htmlspecialchars($currentUser['profile_pic']); ?>" alt="avatar" class="profile-pic me-2">
                <div>
                    <div><?php echo htmlspecialchars($currentUser['username']); ?></div>
                    <small>Level <?php echo $currentUser['level']; ?></small>
                </div>
            </div>
            <a href="index.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            <a href="quiz.php"><i class="bi bi-journal-text me-2"></i> Start Quiz</a>
            <a href="achievment.php"><i class="bi bi-award me-2"></i> Achievements</a>
            <a href="#"><i class="bi bi-gear me-2"></i> Settings</a>
            <a href="analytics.php"><i class="bi bi-box-arrow-right me-2"></i> Analysis</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <h3 class="mb-3">Welcome, <?php echo htmlspecialchars($currentUser['username']); ?>!</h3>

            <!-- XP Bar -->
            <div class="mb-3">
                <p>XP: <?php echo $currentUserProgress['currentXP']; ?> / <?php echo $currentUserProgress['nextLevelXP']; ?> | Level: <?php echo $currentUser['level']; ?></p>
                <div class="progress" style="height:25px;">
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width:<?php echo $currentUserProgress['progressPercent']; ?>%">
                         <?php echo $currentUserProgress['currentXP']; ?> / <?php echo $currentUserProgress['nextLevelXP']; ?> XP
                    </div>
                </div>
            </div>

            <!-- Subject Selection Card -->
            <div class="card shadow-lg p-4 mb-4 text-center">
                <h5>Select a Subject to Start Quiz</h5>
                <p>🎯 Choose your subject in a dedicated page!</p>
                <a href="subject_selection.php" class="btn btn-primary mt-2">Go to Subject Selection</a>
            </div>

            <!-- Leaderboard -->
            <div class="card shadow-lg p-4">
                <h4 class="mb-3 text-white">Leaderboard</h4>
                
                <!-- Search box -->
                <input type="text" id="leaderboardSearch" class="form-control" placeholder="Search by username or level...">

                <div class="table-responsive mt-2">
                    <table class="table table-borderless text-white align-middle" id="leaderboardTable">
                        <thead>
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
                                $highlight = ($user['username'] == $currentUser['username']) ? "highlight" : "";
                                $badges = [];
                                if($user['xp'] >= 50) $badges[] = "⭐ Rising Star";
                                if($user['xp'] >= 100) $badges[] = "🏆 Quiz Master";
                                if($user['xp'] >= 200) $badges[] = "🔥 Champion";
                            ?>
                                <tr class="<?php echo $highlight; ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td><img src="uploads/<?php echo htmlspecialchars($user['profile_pic']); ?>" class="profile-pic"></td>
                                    <td class="username"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="level"><?php echo $user['level']; ?></td>
                                    <td style="width:250px;">
                                        <div class="progress" style="height:20px;">
                                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" style="width: <?php echo $userProgress['progressPercent']; ?>%; ">
                                                <?php echo $userProgress['currentXP']; ?> / <?php echo $userProgress['nextLevelXP']; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php foreach($badges as $b): ?>
                                            <span class="badge bg-warning text-dark me-1 mb-1"><?php echo $b; ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
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

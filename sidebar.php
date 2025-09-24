<?php
// sidebar.php
if(!isset($_SESSION)) session_start();
include 'api/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

// Logged-in user info
$stmt = $conn->prepare("SELECT username, profile_pic, xp, level FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$currentUser = $result->fetch_assoc();
$stmt->close();
?>

<!-- Bootstrap CSS & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* Wider Fixed Sidebar */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 280px; /* increased width */
    height: 100vh; /* full height */
    background: rgba(30, 30, 47, 0.95);
    color: #fff;
    padding-top: 20px;
    display: flex;
    flex-direction: column;
    z-index: 1000;
    overflow-y: auto;
}

/* Sidebar Links */
.sidebar a {
    color: #fff;
    display: flex;
    align-items: center;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    margin-bottom: 8px;
    transition: background 0.2s;
    font-size: 1rem;
}
.sidebar a i {
    font-size: 1.3rem;
    margin-right: 12px;
}
.sidebar a:hover {
    background: #5a4b4b;
}

/* User Section: Avatar + Name Inline */
.sidebar .user-info {
    display: flex;
    align-items: center;
    padding: 0 20px 20px 20px;
    margin-bottom: 20px;
}
.sidebar .user-info img {
    width: 60px; /* avatar size */
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
    margin-right: 15px;
}
.sidebar .user-info .user-details {
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.sidebar .user-info .username {
    font-weight: bold;
    font-size: 1.1rem;
    margin-bottom: 4px;
}
.sidebar .user-info .level {
    font-size: 0.9rem;
    color: #ccc;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }
    .sidebar .user-info img {
        width: 50px;
        height: 50px;
        margin-right: 10px;
    }
}
</style>

<div class="sidebar">
    <div class="user-info">
        <img src="uploads/<?php echo htmlspecialchars($currentUser['profile_pic']); ?>" alt="avatar">
        <div class="user-details">
            <div class="username"><?php echo htmlspecialchars($currentUser['username']); ?></div>
            <div class="level">Level <?php echo $currentUser['level']; ?></div>
        </div>
    </div>

    <a href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="quiz.php"><i class="bi bi-journal-text"></i> Start Quiz</a>
    <a href="#"><i class="bi bi-award"></i> Achievements</a>
    <a href="#"><i class="bi bi-gear"></i> Settings</a>
    <a href="analytics.php"><i class="bi bi-bar-chart-line"></i> Analysis</a>
    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>
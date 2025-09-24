<?php
session_start();
include 'api/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user stats
$stmt = $conn->prepare("SELECT xp FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$xp = $user['xp'] ?? 0;
$level = floor($xp / 100) + 1;
$nextLevelXP = $level * 100;
$currentProgress = $xp % 100;

// Fetch badges earned
$stmt = $conn->prepare("SELECT badge_name, badge_icon, earned_on FROM badges WHERE user_id=? ORDER BY earned_on DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$badges = [];
while($row = $result->fetch_assoc()){
    $badges[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Achievements Dashboard</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
body { background: #121212; color: #fff; font-family: 'Segoe UI', sans-serif; }
.main-content { margin-left: 280px; padding: 40px 20px; min-height: 100vh; }
.card { border-radius: 20px; background: #1f1f1f; padding: 20px; margin-bottom: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
.progress { height: 25px; border-radius: 12px; background: #333; }
.progress-bar { background: linear-gradient(90deg, #ff5722, #ffc107); font-weight: bold; text-align: center; transition: width 1s; }
.badge-container { display: flex; flex-wrap: wrap; gap: 10px; }
.badge-card { background: #2c2c2c; border-radius: 15px; padding: 15px; display: flex; align-items: center; transition: transform 0.3s; cursor: pointer; }
.badge-card:hover { transform: scale(1.05); }
.badge-card i { font-size: 2rem; margin-right: 10px; color: #ffcc00; }
.stats { display: flex; justify-content: space-around; margin-bottom: 20px; }
.stats div { text-align: center; }
h3 { text-align: center; margin-bottom: 25px; }
@media(max-width:992px){ .main-content { margin-left:0; padding:20px 10px; } }
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h3>🏆 Your Achievements</h3>
    
    <div class="stats">
        <div>
            <h5>Level</h5>
            <p><?php echo $level; ?></p>
        </div>
        <div>
            <h5>XP</h5>
            <p><?php echo $xp; ?></p>
        </div>
        <div>
            <h5>Next Level</h5>
            <p><?php echo $nextLevelXP; ?> XP</p>
        </div>
    </div>

    <div class="card">
        <h5>Progress to Next Level</h5>
        <div class="progress">
            <div class="progress-bar" style="width: <?php echo $currentProgress; ?>%">
                <?php echo $currentProgress; ?>%
            </div>
        </div>
    </div>

    <div class="card">
        <h5>Badges Earned</h5>
        <div class="badge-container">
            <?php if(!empty($badges)): ?>
                <?php foreach($badges as $b): ?>
                    <div class="badge-card">
                        <i class="<?php echo $b['badge_icon']; ?>"></i>
                        <div>
                            <strong><?php echo $b['badge_name']; ?></strong><br>
                            <small>Earned on: <?php echo date('d M Y', strtotime($b['earned_on'])); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No badges earned yet. Start quizzing to earn badges! 🎯</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>

<?php
session_start();
include 'api/db.php';

// Only allow logged-in users
if(!isset($_SESSION['user_id'])){
    echo json_encode([]);
    exit;
}

// 1️⃣ Engagement over time (last 7 days)
$days = [];
$quizzesPerDay = [];
$accuracyPerDay = [];

for($i=6; $i>=0; $i--){
    $date = date('Y-m-d', strtotime("-$i days"));
    $days[] = date('D', strtotime($date)); // Mon, Tue...
    
    $stmt = $conn->prepare("SELECT score, total_questions FROM quiz_attempts WHERE DATE(created_at)=?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $totalQuizzes = 0;
    $totalAccuracy = 0;
    $count = 0;
    while($row = $res->fetch_assoc()){
        $totalQuizzes++;
        if($row['total_questions']>0){
            $totalAccuracy += ($row['score'] / $row['total_questions']) * 100;
        }
        $count++;
    }
    $quizzesPerDay[] = $totalQuizzes;
    $accuracyPerDay[] = $count ? round($totalAccuracy / $count, 2) : 0;
}

// 2️⃣ Subject-wise performance
$subjects = [];
$subjectAttempts = [];

$res = $conn->query("SELECT subject, COUNT(*) as attempts FROM quiz_attempts GROUP BY subject");
while($row = $res->fetch_assoc()){
    $subjects[] = $row['subject'];
    $subjectAttempts[] = $row['attempts'];
}

// 3️⃣ Top active users (last 7 days)
$topUsers = [];
$res = $conn->query("
    SELECT u.username, COUNT(q.id) as quizzes 
    FROM quiz_attempts q 
    JOIN users u ON u.id=q.user_id
    WHERE q.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY q.user_id 
    ORDER BY quizzes DESC
    LIMIT 5
");
while($row = $res->fetch_assoc()){
    $topUsers[] = ['username'=>$row['username'], 'quizzes'=>$row['quizzes']];
}

// Return as JSON
echo json_encode([
    'days' => $days,
    'quizzesPerDay' => $quizzesPerDay,
    'accuracyPerDay' => $accuracyPerDay,
    'subjects' => $subjects,
    'subjectAttempts' => $subjectAttempts,
    'topUsers' => $topUsers
]);

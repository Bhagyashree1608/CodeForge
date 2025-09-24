<?php
session_start();
include 'api/db.php';

if(!isset($_SESSION['user_id'])){
    http_response_code(403);
    echo json_encode(['error'=>'Unauthorized']);
    exit;
}

// Get JSON from fetch
$data = json_decode(file_get_contents("php://input"), true);

// Update session quiz stats
$_SESSION['quiz']['xp'] = $data['xp'];
$_SESSION['quiz']['streak'] = $data['streak'];
$_SESSION['quiz']['lives'] = $data['lives'];
$_SESSION['quiz']['correct_count'] = $data['correct_count'];
$_SESSION['quiz']['wrong_count'] = $data['wrong_count'];

// Also update XP in database in real-time
$stmt = $conn->prepare("UPDATE users SET xp=? WHERE id=?");
$stmt->bind_param("ii", $data['xp'], $_SESSION['user_id']);
$stmt->execute();
$stmt->close();

echo json_encode(['status'=>'ok']);

<?php
session_start();
$data = json_decode(file_get_contents('php://input'), true);

if(!isset($_SESSION['quiz_badges'])){
    $_SESSION['quiz_badges'] = [];
}

// Merge new badges without duplicates
if(!empty($data['badges'])){
    $_SESSION['quiz_badges'] = array_unique(array_merge($_SESSION['quiz_badges'], $data['badges']));
}

echo json_encode(['status'=>'ok']);
?>

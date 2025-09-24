<?php
session_start();
if(!isset($_SESSION['admin_logged_in'])) header("Location: admin_login.php");
include 'api/db.php';

$message = '';

// ===== Add New Subject =====
if(isset($_POST['new_subject'])){
    $name = trim($_POST['new_subject']);
    if($name != ''){
        // Check if subject already exists
        $check = $conn->prepare("SELECT id FROM subjects WHERE name=?");
        if($check){
            $check->bind_param("s", $name);
            $check->execute();
            $check->store_result();
            if($check->num_rows > 0){
                $message = "Subject '$name' already exists!";
            } else {
                $code = strtolower(str_replace(' ','_',$name));
                $stmt = $conn->prepare("INSERT INTO subjects (name, code) VALUES (?, ?)");
                if($stmt){
                    $stmt->bind_param("ss", $name, $code);
                    if($stmt->execute()){
                        $message = "Subject '$name' added successfully!";
                    } else {
                        $message = "Error adding subject!";
                    }
                    $stmt->close();
                } else {
                    $message = "Error preparing statement!";
                }
            }
            $check->close();
        } else {
            $message = "Error preparing check statement!";
        }
    }
}
include "sidebar2.php";
// ===== Upload JSON Questions =====
if(isset($_POST['subject_id']) && isset($_FILES['json_file'])){
    $subject_id = $_POST['subject_id'];

    // Get subject name from subjects table
    $subjectResult = $conn->prepare("SELECT name FROM subjects WHERE id=?");
    $subjectResult->bind_param("i", $subject_id);
    $subjectResult->execute();
    $subjectResult->bind_result($subjectName);
    $subjectResult->fetch();
    $subjectResult->close();

    $file = $_FILES['json_file'];

    if($file['type'] == 'application/json' || pathinfo($file['name'], PATHINFO_EXTENSION) === 'json'){
        $fileName = strtolower(str_replace(' ','_',$file['name']));
        if(!file_exists('data')) mkdir('data',0777,true);
        move_uploaded_file($file['tmp_name'], 'data/'.$fileName);

        $jsonData = json_decode(file_get_contents('data/'.$fileName), true);

        if(is_array($jsonData)){
            $inserted = 0;
            foreach($jsonData as $q){
                if(isset($q['question_text'], $q['option1'], $q['option2'], $q['option3'], $q['option4'], $q['correct_option'], $q['difficulty'])){
                    $stmt = $conn->prepare("INSERT INTO questions (subject, question_text, option1, option2, option3, option4, correct_option, difficulty) VALUES (?,?,?,?,?,?,?,?)");
                    if($stmt){
                        $stmt->bind_param(
                            "sssssssi",
                            $subjectName,
                            $q['question_text'],
                            $q['option1'],
                            $q['option2'],
                            $q['option3'],
                            $q['option4'],
                            $q['correct_option'],
                            $q['difficulty']
                        );
                        $stmt->execute();
                        $stmt->close();
                        $inserted++;
                    } else {
                        $message = "Error preparing SQL statement: ".$conn->error;
                        break;
                    }
                }
            }
            $message = $inserted > 0 ? "Questions uploaded successfully ($inserted added)!" : "No valid questions found in JSON!";
        } else {
            $message = "Invalid JSON format!";
        }
    } else {
        $message = "Only JSON files are allowed!";
    }
}

// ===== Fetch Subjects =====
$result = $conn->query("SELECT * FROM subjects ORDER BY id DESC");
$subjects = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
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
<title>Manage Subjects</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
<?php include 'sidebar.php'; ?>

/* Subject Cards */
.subject-card {
    background:#fff;
    border-radius:12px;
    padding:20px;
    min-width:180px;
    max-width:220px;
    text-align:center;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
    transition:0.3s;
}
.subject-card:hover {
    transform:translateY(-5px);
    box-shadow:0 15px 35px rgba(0,0,0,0.15);
}
.subject-card h6 {color:#ff5722; font-weight:bold; margin-bottom:10px;}
.subject-card .badge {font-size:0.85em;}
.subjects-container {display:flex; flex-wrap:wrap; gap:15px;}
.btn-upload {width:auto;}
</style>
</head>
<body>

<div class="main-content">
<h3>🗂 Manage Subjects</h3>

<?php if($message != ''): ?>
<div class="alert alert-info"><?= $message ?></div>
<?php endif; ?>

<!-- Add New Subject -->
<div class="card p-3 mb-4">
<h5>Add New Subject</h5>
<form method="POST" class="d-flex gap-2 flex-wrap">
<input type="text" name="new_subject" class="form-control" placeholder="Subject Name" required>
<button type="submit" class="btn btn-primary">Add Subject</button>
</form>
</div>

<!-- Upload Questions -->
<div class="card p-3 mb-4">
<h5>Upload Questions (JSON)</h5>
<form method="POST" enctype="multipart/form-data" class="d-flex flex-column gap-2">
<select name="subject_id" class="form-select" required>
<option value="">Select Subject</option>
<?php foreach($subjects as $s): ?>
<option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
<?php endforeach; ?>
</select>
<input type="file" name="json_file" accept=".json" required>
<button type="submit" class="btn btn-success btn-upload">Upload Questions</button>
</form>
</div>

<!-- Existing Subjects -->
<div class="card p-3">
<h5>Existing Subjects</h5>
<div class="subjects-container">
<?php foreach($subjects as $s): ?>
<div class="subject-card">
<h6><?= $s['name'] ?></h6>
<span class="badge bg-primary rounded-pill"><?= $s['code'] ?></span>
</div>
<?php endforeach; ?>
</div>
</div>

</div>
</body>
</html>
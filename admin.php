<?php
session_start();
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true){
    header("Location: admin.php");
    exit;
}

$error = '';
if(isset($_POST['email'], $_POST['password'])){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Hardcoded admin credentials
    $admin_email = 'admin@gmail.com';
    $admin_password = 'admin12345';

    if($email === $admin_email && $password === $admin_password){
        $_SESSION['admin_logged_in'] = true;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - QuizQuest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#121212;color:#fff;display:flex;justify-content:center;align-items:center;height:100vh;font-family:'Segoe UI',sans-serif;}
.login-box{background:#1f1f1f;padding:30px;border-radius:15px;width:350px;box-shadow:0 10px 25px rgba(255,87,34,0.4);}
h3{text-align:center;margin-bottom:20px;color:#ff5722;}
input{border-radius:10px;}
button{border-radius:10px;}
.alert{border-radius:10px;}
</style>
</head>
<body>
<div class="login-box">
<h3>🎮 Admin Login</h3>
<?php if($error != ''): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>
<form method="POST">
<div class="mb-3">
<input type="email" name="email" class="form-control" placeholder="Email" required>
</div>
<div class="mb-3">
<input type="password" name="password" class="form-control" placeholder="Password" required>
</div>
<div class="d-grid">
<button type="submit" class="btn btn-primary">Login</button>
</div>
</form>
</div>
</body>
</html>
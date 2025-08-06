<?php
session_start();
include 'db_connect.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!$username || !$password) {
        $errors[] = "Username and password required.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Login</title>
<style>
  body { font-family: Arial, sans-serif; background: #f2f2f2; padding: 20px; }
  .container { max-width: 400px; margin: auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
  h2 { text-align: center; color: #333; }
  input[type=text], input[type=password] { width: 100%; padding: 8px; margin: 8px 0 15px; border: 1px solid #ccc; border-radius: 4px; }
  button { width: 100%; background: #007BFF; color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
  button:hover { background: #0056b3; }
  .errors { color: red; margin-bottom: 15px; }
  p { text-align: center; }
  a { color: #007BFF; text-decoration: none; }
  a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="container">
<h2>Login</h2>
<?php if ($errors): ?>
    <div class="errors">
        <ul>
        <?php foreach ($errors as $error): ?>
            <li><?=htmlspecialchars($error)?></li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<form method="POST">
    <label>Username</label>
    <input type="text" name="username" required autofocus>
    
    <label>Password</label>
    <input type="password" name="password" required>
    
    <button type="submit">Login</button>
</form>
<p>No account? <a href="signup.php">Signup here</a>.</p>
</div>
</body>
</html>

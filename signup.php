<?php
session_start();
include 'db_connect.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (!$username || !$password || !$confirm) {
        $errors[] = "All fields are required.";
    } elseif ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    } else {
        try {
            // Check if username exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = "Username already taken.";
            } else {
                // Insert new user
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                if ($stmt->execute([$username, $hash])) {
                    header("Location: login.php");
                    exit;
                } else {
                    $errors[] = "Signup failed. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Signup</title>
<style>
  body { font-family: Arial, sans-serif; background: #f9f9f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
  .container { max-width: 400px; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center; }
  h2 { color: #333; margin-bottom: 20px; }
  input[type=text], input[type=password] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
  button { width: 100%; background: #28a745; color: white; padding: 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
  button:hover { background: #1e7e34; }
  .errors { color: red; margin-bottom: 15px; }
  p { margin-top: 15px; }
  a { color: #28a745; text-decoration: none; }
  a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="container">
<h2>Signup</h2>
<?php if ($errors): ?>
    <div class="errors">
        <ul>
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<form method="POST">
    <label>Username</label>
    <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>

    <label>Password</label>
    <input type="password" name="password" required>

    <label>Confirm Password</label>
    <input type="password" name="confirm_password" required>

    <button type="submit">Signup</button>
</form>
<p>Already have an account? <a href="login.php">Login here</a>.</p>
</div>
</body>
</html>
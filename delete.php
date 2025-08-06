<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

$id = $_GET['id'] ?? null;
if ($id) {
    // Fetch profile picture to delete the file
    $stmt = $conn->prepare("SELECT profile_picture FROM biodata WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $biodata = $stmt->fetch();
    
    if ($biodata && $biodata['profile_picture'] && file_exists($biodata['profile_picture'])) {
        unlink($biodata['profile_picture']);
    }

    // Delete education first
    $conn->prepare("DELETE FROM educational_qualification WHERE biodata_id = ?")->execute([$id]);

    // Delete biodata
    $stmt = $conn->prepare("DELETE FROM biodata WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
}

header("Location: index.php");
exit;
?>
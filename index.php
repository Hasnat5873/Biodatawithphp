<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// Fetch all biodata with username and education info
$stmt = $conn->query("
    SELECT b.*, u.username 
    FROM biodata b 
    JOIN users u ON b.user_id = u.id 
    ORDER BY b.created_at DESC
");
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch education grouped by biodata_id
$eduStmt = $conn->query("SELECT * FROM educational_qualification");
$educations = [];
foreach ($eduStmt->fetchAll() as $edu) {
    $educations[$edu['biodata_id']][] = $edu;
}

// Fetch user's most recent profile picture
$stmt = $conn->prepare("SELECT profile_picture FROM biodata WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();
$profilePicture = $profile && $profile['profile_picture'] ? $profile['profile_picture'] : 'uploads/placeholder.png';
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Marriage Biodata</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #fafafa; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        h2 { color: #2c3e50; margin: 0; }
        .profile-img-header { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .top-links { margin-bottom: 20px; text-align: center; }
        .top-links a { margin: 0 15px; color: #3498db; font-weight: 600; text-decoration: none; }
        .top-links a:hover { text-decoration: underline; }
        table { border-collapse: collapse; width: 100%; background: white; box-shadow: 0 4px 20px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; }
        th, td { border: 1px solid #e0e0e0; padding: 12px; text-align: left; vertical-align: top; }
        th { background-color: #3498db; color: white; font-weight: 600; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        .edu-list { font-size: 0.9em; color: #555; margin-top: 4px; }
        .edu-list div { margin-bottom: 5px; }
        .profile-img { max-width: 50px; height: auto; border-radius: 4px; }
        .action-container { position: relative; display: inline-block; }
        .action-btn { 
            background: #6c757d; 
            color: white; 
            padding: 8px 12px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 14px; 
        }
        .action-btn:hover { background: #5a6268; }
        .action-menu { 
            display: none; 
            position: absolute; 
            top: 100%; 
            left: 0; 
            background: #fff; 
            border: 1px solid #e0e0e0; 
            border-radius: 4px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            z-index: 10; 
        }
        .action-menu a { 
            display: block; 
            padding: 8px 12px; 
            text-decoration: none; 
            color: #333; 
            font-size: 14px; 
        }
        .action-menu a.edit-btn { background: #f1c40f; color: white; }
        .action-menu a.edit-btn:hover { background: #d4ac0d; }
        .action-menu a.delete-btn { background: #e74c3c; color: white; }
        .action-menu a.delete-btn:hover { background: #c0392b; }
        .action-container:hover .action-menu { display: block; }
    </style>
    <script>
        function toggleActionMenu(id) {
            const menu = document.getElementById(`action-menu-${id}`);
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }

        // Close all action menus when clicking outside
        document.addEventListener('click', function(event) {
            const containers = document.querySelectorAll('.action-container');
            containers.forEach(container => {
                if (!container.contains(event.target)) {
                    const menu = container.querySelector('.action-menu');
                    menu.style.display = 'none';
                }
            });
        });
    </script>
</head>
<body>
<div class="header">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h2>
    <img src="<?= htmlspecialchars($profilePicture) ?>" alt="Profile Picture" class="profile-img-header">
</div>
<div class="top-links">
    <a href="logout.php">Logout</a>
    <a href="add.php">Add New Biodata</a>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Profile Picture</th>
        <th>User</th>
        <th>Full Name</th>
        <th>Father Name</th>
        <th>Mother Name</th>
        <th>DOB</th>
        <th>Gender</th>
        <th>Marital Status</th>
        <th>Religion</th>
        <th>Height</th>
        <th>Occupation</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Educational Qualifications</th>
        <th>About</th>
        <th>Last Updated</th>
        <th>Actions</th>
    </tr>
    <?php if ($records): ?>
        <?php foreach ($records as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td>
                    <?php if ($row['profile_picture']): ?>
                        <img src="<?= htmlspecialchars($row['profile_picture']) ?>" alt="Profile Picture" class="profile-img">
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['fullName']) ?></td>
                <td><?= htmlspecialchars($row['fatherName']) ?></td>
                <td><?= htmlspecialchars($row['motherName']) ?></td>
                <td><?= htmlspecialchars($row['dob']) ?></td>
                <td><?= htmlspecialchars($row['gender']) ?></td>
                <td><?= htmlspecialchars($row['maritalStatus']) ?></td>
                <td><?= htmlspecialchars($row['religion']) ?></td>
                <td><?= htmlspecialchars($row['height']) ?></td>
                <td><?= htmlspecialchars($row['occupation']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td>
                    <?php
                    if (!empty($educations[$row['id']])) {
                        echo '<div class="edu-list">';
                        foreach ($educations[$row['id']] as $edu) {
                            echo '<div><strong>' . htmlspecialchars($edu['degree']) . '</strong>, ' .
                                htmlspecialchars($edu['institution']) .
                                (!empty($edu['year']) ? ', ' . htmlspecialchars($edu['year']) : '') .
                                (!empty($edu['result']) ? ' (' . htmlspecialchars($edu['result']) . ')' : '') .
                                '</div>';
                        }
                        echo '</div>';
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </td>
                <td><?= htmlspecialchars($row['about']) ?></td>
                <td><?= htmlspecialchars($row['updated_at']) ?></td>
                <td class="actions">
                    <div class="action-container">
                        <button class="action-btn" onclick="toggleActionMenu(<?= $row['id'] ?>)">Action</button>
                        <div class="action-menu" id="action-menu-<?= $row['id'] ?>">
                            <a href="edit.php?id=<?= $row['id'] ?>" class="edit-btn">Edit</a>
                            <a href="delete.php?id=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="18">No biodata found. <a href="add.php">Add new</a>.</td></tr>
    <?php endif; ?>
</table>
</body>
</html>
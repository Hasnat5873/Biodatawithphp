<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM biodata WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$biodata = $stmt->fetch();
if (!$biodata) {
    header("Location: index.php");
    exit;
}

$eduStmt = $conn->prepare("SELECT * FROM educational_qualification WHERE biodata_id = ?");
$eduStmt->execute([$id]);
$educations = $eduStmt->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName      = trim($_POST['fullName']);
    $fatherName    = trim($_POST['fatherName']);
    $motherName    = trim($_POST['motherName']);
    $dob           = $_POST['dob'];
    $gender        = $_POST['gender'];
    $maritalStatus = $_POST['maritalStatus'];
    $religion      = $_POST['religion'];
    $height        = $_POST['height'];
    $occupation    = trim($_POST['occupation']);
    $email         = trim($_POST['email']);
    $phone         = trim($_POST['phone']);
    $about         = trim($_POST['about']);
    $education     = $_POST['education'] ?? [];

    $profilePicture = $biodata['profile_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $file = $_FILES['profile_picture'];

        if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $uploadPath = 'uploads/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Delete old image if exists
                if ($profilePicture && file_exists($profilePicture)) {
                    unlink($profilePicture);
                }
                $profilePicture = $uploadPath;
            } else {
                $errors[] = "Failed to upload profile picture.";
            }
        } else {
            $errors[] = "Invalid file type or size. Allowed: JPEG, PNG, GIF, max 2MB.";
        }
    }

    if (!$fullName || !$dob || !$gender || !$occupation || !$email || !$phone) {
        $errors[] = "Please fill all required fields.";
    }

    if (!$errors) {
        try {
            $stmt = $conn->prepare("
                UPDATE biodata SET
                fullName = :fullName,
                fatherName = :fatherName,
                motherName = :motherName,
                dob = :dob,
                gender = :gender,
                maritalStatus = :maritalStatus,
                religion = :religion,
                height = :height,
                occupation = :occupation,
                email = :email,
                phone = :phone,
                about = :about,
                profile_picture = :profile_picture,
                updated_at = NOW()
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute([
                ':fullName' => $fullName,
                ':fatherName' => $fatherName,
                ':motherName' => $motherName,
                ':dob' => $dob,
                ':gender' => $gender,
                ':maritalStatus' => $maritalStatus,
                ':religion' => $religion,
                ':height' => $height,
                ':occupation' => $occupation,
                ':email' => $email,
                ':phone' => $phone,
                ':about' => $about,
                ':profile_picture' => $profilePicture,
                ':id' => $id,
                ':user_id' => $_SESSION['user_id']
            ]);

            $conn->prepare("DELETE FROM educational_qualification WHERE biodata_id = ?")->execute([$id]);

            $eduStmt = $conn->prepare("
                INSERT INTO educational_qualification (biodata_id, degree, institution, year, result)
                VALUES (:biodata_id, :degree, :institution, :year, :result)
            ");

            foreach ($education as $edu) {
                if (trim($edu['degree']) !== '') {
                    $eduStmt->execute([
                        ':biodata_id' => $id,
                        ':degree' => trim($edu['degree']),
                        ':institution' => trim($edu['institution']),
                        ':year' => trim($edu['year']),
                        ':result' => trim($edu['result']),
                    ]);
                }
            }

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {
            $errors[] = "Error updating data: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Edit Biodata</title>
<style>
  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f4f7fa;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
  }
  .container {
    max-width: 800px;
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    text-align: center;
  }
  .header img { max-width: 150px; height: auto; margin-bottom: 20px; }
  h2 {
    color: #2c3e50;
    margin-bottom: 20px;
  }
  a.back-link {
    display: inline-block;
    margin-bottom: 20px;
    color: #3498db;
    text-decoration: none;
    font-weight: 500;
  }
  a.back-link:hover {
    text-decoration: underline;
  }
  form {
    text-align: left;
  }
  label {
    display: block;
    margin: 15px 0 5px;
    font-weight: 600;
    color: #34495e;
  }
  input[type=text], input[type=date], input[type=number], input[type=email], input[type=file], select, textarea {
    width: 100%;
    padding: 10px;
    margin: 5px 0 10px;
    border: 1px solid #dcdcdc;
    border-radius: 6px;
    box-sizing: border-box;
    font-size: 14px;
  }
  .current-img { max-width: 100px; height: auto; margin: 10px 0; }
  textarea {
    resize: vertical;
    min-height: 100px;
  }
  button {
    background: #3498db;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    width: 100%;
    margin-top: 20px;
  }
  button:hover {
    background: #2980b9;
  }
  .errors {
    color: #e74c3c;
    margin-bottom: 15px;
    text-align: center;
  }
  .edu-row {
    border: 1px solid #eee;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 6px;
    background: #fafafa;
    position: relative;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }
  .remove-btn {
    grid-column: span 2;
    background: #e74c3c;
    color: white;
    border: none;
    padding: 8px 12px;
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
    margin-top: 10px;
    width: fit-content;
    justify-self: end;
  }
  .remove-btn:hover {
    background: #c0392b;
  }
  #add-edu-btn {
    background: #2ecc71;
    width: fit-content;
    padding: 10px 20px;
  }
  #add-edu-btn:hover {
    background: #27ae60;
  }
  hr {
    border: 0;
    border-top: 1px solid #eee;
    margin: 20px 0;
  }
</style>
<script>
function addEducationRow(data = {}) {
    const container = document.getElementById('education-container');
    const div = document.createElement('div');
    div.className = 'edu-row';
    div.innerHTML = `
        <label>Degree:<input type="text" name="education[][degree]" value="${data.degree || ''}" required></label>
        <label>Institution:<input type="text" name="education[][institution]" value="${data.institution || ''}" required></label>
        <label>Year:<input type="text" name="education[][year]" value="${data.year || ''}"></label>
        <label>Result:<input type="text" name="education[][result]" value="${data.result || ''}"></label>
        <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove</button>
    `;
    container.appendChild(div);
}

window.onload = () => {
    const educations = <?= json_encode($educations) ?>;
    if (educations.length === 0) {
        addEducationRow();
    } else {
        educations.forEach(row => addEducationRow(row));
    }
};
</script>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="uploads/logo.png" alt="Website Logo">
    </div>
    <h2>Edit Biodata</h2>
    <a href="index.php" class="back-link">Back to List</a>

    <?php if ($errors): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Profile Picture</label>
        <?php if ($biodata['profile_picture']): ?>
            <img src="<?= htmlspecialchars($biodata['profile_picture']) ?>" alt="Profile Picture" class="current-img">
        <?php endif; ?>
        <input type="file" name="profile_picture" accept="image/jpeg,image/png,image/gif">

        <label>Full Name *</label>
        <input type="text" name="fullName" required value="<?= htmlspecialchars($_POST['fullName'] ?? $biodata['fullName']) ?>">

        <label>Father's Name</label>
        <input type="text" name="fatherName" value="<?= htmlspecialchars($_POST['fatherName'] ?? $biodata['fatherName']) ?>">

        <label>Mother's Name</label>
        <input type="text" name="motherName" value="<?= htmlspecialchars($_POST['motherName'] ?? $biodata['motherName']) ?>">

        <label>Date of Birth *</label>
        <input type="date" name="dob" required value="<?= htmlspecialchars($_POST['dob'] ?? $biodata['dob']) ?>">

        <label>Gender *</label>
        <select name="gender" required>
            <option value="">Select</option>
            <?php
            $genders = ['Male', 'Female', 'Other'];
            $selectedGender = $_POST['gender'] ?? $biodata['gender'];
            foreach ($genders as $g) {
                $sel = $selectedGender === $g ? 'selected' : '';
                echo "<option value=\"$g\" $sel>$g</option>";
            }
            ?>
        </select>

        <label>Marital Status</label>
        <select name="maritalStatus">
            <option value="">Select</option>
            <?php
            $statuses = ['Never Married', 'Divorced', 'Widowed'];
            $selectedStatus = $_POST['maritalStatus'] ?? $biodata['maritalStatus'];
            foreach ($statuses as $s) {
                $sel = $selectedStatus === $s ? 'selected' : '';
                echo "<option value=\"$s\" $sel>$s</option>";
            }
            ?>
        </select>

        <label>Religion</label>
        <select name="religion">
            <option value="">Select</option>
            <?php
            $religions = ['Hindu', 'Muslim', 'Christian', 'Other'];
            $selectedReligion = $_POST['religion'] ?? $biodata['religion'];
            foreach ($religions as $r) {
                $sel = $selectedReligion === $r ? 'selected' : '';
                echo "<option value=\"$r\" $sel>$r</option>";
            }
            ?>
        </select>

        <label>Height (cm)</label>
        <input type="number" name="height" min="100" max="250" value="<?= htmlspecialchars($_POST['height'] ?? $biodata['height']) ?>">

        <label>Occupation *</label>
        <input type="text" name="occupation" required value="<?= htmlspecialchars($_POST['occupation'] ?? $biodata['occupation']) ?>">

        <label>Email *</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $biodata['email']) ?>" required>

        <label>Phone *</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? $biodata['phone']) ?>" required>

        <label>About Yourself</label>
        <textarea name="about" rows="4"><?= htmlspecialchars($_POST['about'] ?? $biodata['about']) ?></textarea>

        <hr>
        <h3>Educational Qualifications</h3>
        <div id="education-container"></div>
        <button type="button" id="add-edu-btn" onclick="addEducationRow()">Add More</button>

        <button type="submit">Update Biodata</button>
    </form>
</div>
</body>
</html>
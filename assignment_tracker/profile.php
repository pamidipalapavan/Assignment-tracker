<?php
include 'includes/header.php';

$success = '';
$error = '';

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle profile details update
    if (isset($_POST['name'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $college = $_POST['college'];
        $department = $_POST['department'];
        $year = $_POST['year'];
        $bio = $_POST['bio'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        $dob = $_POST['dob'];
        $courses = $_POST['courses'];
        $skills = $_POST['skills'];

        $profile_pic = $user['profile_pic'];

        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, college = ?, department = ?, year_of_study = ?, bio = ?, address = ?, phone = ?, dob = ?, courses = ?, skills = ?, profile_pic = ? WHERE id = ?");
        $stmt->execute([$name, $email, $college, $department, $year, $bio, $address, $phone, $dob, $courses, $skills, $profile_pic, $_SESSION['user_id']]);
        $success = "Profile updated successfully.";

        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }

    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['size'] > 0) {
        $file = $_FILES['profile_pic'];
        $upload_dir = 'assets/uploads/';
        $file_name = preg_replace('/[^A-Za-z0-9\-_\.]/', '_', $file['name']);
        $file_path = $upload_dir . $file_name;

        // Check if upload directory exists, create if not
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $error = "Failed to create upload directory: $upload_dir";
            }
        }

        // Check if directory is writable
        if (!$error && !is_writable($upload_dir)) {
            $error = "Upload directory is not writable: $upload_dir";
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png'];
        if (!$error && !in_array($file['type'], $allowed_types)) {
            $error = "Only JPG and PNG files are allowed for profile pictures.";
        }

        // Proceed with file upload if no errors
        if (!$error) {
            if ($file['size'] > 2000000) { // 2MB limit
                $error = "Profile picture size exceeds 2MB.";
            } elseif (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Delete the old profile picture if it exists and is not the default
                if ($user['profile_pic'] != 'default.jpg' && file_exists($user['profile_pic'])) {
                    unlink($user['profile_pic']);
                }
                $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                $stmt->execute([$file_path, $_SESSION['user_id']]);
                $success = "Profile picture updated successfully.";
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                // Redirect to refresh the page
                header("Location: profile.php");
                exit();
            } else {
                $error = "Failed to upload profile picture. Possible permission issue or file path error.";
            }
        }
    }

    // Handle profile picture removal
    if (isset($_POST['remove_pic'])) {
        if ($user['profile_pic'] != 'default.jpg' && file_exists($user['profile_pic'])) {
            unlink($user['profile_pic']);
        }
        $stmt = $conn->prepare("UPDATE users SET profile_pic = 'default.jpg' WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $success = "Profile picture removed successfully.";
        
        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        // Redirect to refresh the page
        header("Location: profile.php");
        exit();
    }
}
?>

<div class="profile-container">
    <div class="profile-header">
        <img src="<?php echo htmlspecialchars($user['profile_pic'] == 'default.jpg' ? 'https://via.placeholder.com/150' : $user['profile_pic']); ?>" alt="Profile Picture" class="profile-img">
        <form id="picForm" method="POST" enctype="multipart/form-data" class="pic-form">
            <input type="file" id="file-input" name="profile_pic" accept="image/jpeg,image/png" style="display:none" onchange="document.getElementById('picForm').submit(); updateFileName(this)">
            <button type="button" class="pic-btn" onclick="document.getElementById('file-input').click()">Change Profile Picture</button>
            <span id="file-name" class="file-name"></span>
            <?php if ($user['profile_pic'] != 'default.jpg'): ?>
                <button type="submit" name="remove_pic" class="pic-btn remove-btn">Remove</button>
            <?php endif; ?>
        </form>
    </div>

    <h2 class="profile-title">Profile Details</h2>

    <?php if ($success): ?>
        <p class="success-msg"><?php echo $success; ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error-msg"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="profile-form">
        <div class="profile-details">
            <div class="detail-column">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="input-field">

                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="input-field">

                <label for="college">College</label>
                <input type="text" id="college" name="college" value="<?php echo htmlspecialchars($user['college'] ?? ''); ?>" class="input-field">

                <label for="department">Department</label>
                <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" class="input-field">

                <label for="year">Year of Study</label>
                <select id="year" name="year" class="select-field">
                    <option value="1st Year" <?php echo ($user['year_of_study'] == '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                    <option value="2nd Year" <?php echo ($user['year_of_study'] == '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                    <option value="3rd Year" <?php echo ($user['year_of_study'] == '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                    <option value="4th Year" <?php echo ($user['year_of_study'] == '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                </select>

                <label for="bio">Bio</label>
                <textarea id="bio" name="bio" rows="4" class="textarea-field"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>

            <div class="detail-column">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="4" class="textarea-field"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>

                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="input-field">

                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>" class="input-field">

                <label for="courses">Courses Enrolled</label>
                <textarea id="courses" name="courses" rows="4" class="textarea-field"><?php echo htmlspecialchars($user['courses'] ?? ''); ?></textarea>

                <label for="skills">Skills</label>
                <input type="text" id="skills" name="skills" value="<?php echo htmlspecialchars($user['skills'] ?? ''); ?>" class="input-field">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="submit-btn">Update Profile</button>
        </div>
    </form>
</div>

<script>
function updateFileName(input) {
    const fileName = input.files[0] ? input.files[0].name : 'No file chosen';
    document.getElementById('file-name').textContent = fileName;
}
</script>

<?php include 'includes/footer.php'; ?>
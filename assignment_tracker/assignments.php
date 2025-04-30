<?php
include 'includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle file upload
    if (isset($_FILES['file'])) {
        $title = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
        $file = $_FILES['file'];
        $upload_dir = 'assets/uploads/';
        $file_path = $upload_dir . basename($file['name']);

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

        // Proceed with file upload if no errors
        if (!$error) {
            if ($file['size'] > 5000000) { // 5MB limit
                $error = "File size exceeds 5MB.";
            } elseif (move_uploaded_file($file['tmp_name'], $file_path)) {
                $stmt = $conn->prepare("INSERT INTO assignments (user_id, title, file_path, upload_date, status) VALUES (?, ?, ?, NOW(), 'Submitted')");
                $stmt->execute([$_SESSION['user_id'], $title, $file_path]);
                $success = "Assignment uploaded successfully.";
            } else {
                $error = "Failed to upload file. Possible permission issue or file path error.";
            }
        }
    }

    // Handle assignment deletion
    if (isset($_POST['delete_assignment'])) {
        $assignment_id = $_POST['assignment_id'];
        
        // Fetch the assignment to get the file path
        $stmt = $conn->prepare("SELECT file_path FROM assignments WHERE id = ? AND user_id = ?");
        $stmt->execute([$assignment_id, $_SESSION['user_id']]);
        $assignment = $stmt->fetch();

        if ($assignment) {
            // Delete the file from the server
            if (file_exists($assignment['file_path'])) {
                unlink($assignment['file_path']);
            }

            // Delete the record from the database
            $stmt = $conn->prepare("DELETE FROM assignments WHERE id = ? AND user_id = ?");
            $stmt->execute([$assignment_id, $_SESSION['user_id']]);
            $success = "Assignment deleted successfully.";
        } else {
            $error = "Assignment not found or you don't have permission to delete it.";
        }
    }
}

// Fetch assignments
$stmt = $conn->prepare("SELECT * FROM assignments WHERE user_id = ? ORDER BY upload_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$assignments = $stmt->fetchAll();
?>

<div class="container">
    <h1>📚 Assignments</h1>

    <?php if ($success): ?>
        <p class="success-msg"><?php echo $success; ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error-msg"><?php echo $error; ?></p>
    <?php endif; ?>

    <div class="search-upload">
        <div class="search-bar">
            <input type="text" placeholder="Search assignments..." id="searchInput" oninput="filterAssignments()">
        </div>
        <div class="upload-section">
            <form method="POST" enctype="multipart/form-data">
                <label for="file-upload"><strong>Upload Assignment</strong></label>
                <input type="file" id="file-upload" name="file" required>
                <button type="submit">Upload</button>
            </form>
        </div>
    </div>

    <div class="assignment-list" id="assignmentList">
        <?php foreach ($assignments as $assignment): ?>
            <div class="assignment-item">
                <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                <p>Uploaded: <?php echo date('F d, Y', strtotime($assignment['upload_date'])); ?> | Status: <strong style="color: #27ae60;"><?php echo $assignment['status']; ?></strong></p>
                <div class="assignment-actions">
                    <a href="<?php echo $assignment['file_path']; ?>" target="_blank">View File</a>
                    <button class="download-button" onclick="window.location.href='<?php echo $assignment['file_path']; ?>'">Download</button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this assignment?');">
                        <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                        <button type="submit" name="delete_assignment" class="delete-button">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function filterAssignments() {
    const query = document.getElementById("searchInput").value.toLowerCase();
    const items = document.querySelectorAll(".assignment-item");

    items.forEach(item => {
        const title = item.querySelector("h3").textContent.toLowerCase();
        item.style.display = title.includes(query) ? "block" : "none";
    });
}
</script>

<?php include 'includes/footer.php'; ?>
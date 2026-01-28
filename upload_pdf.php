<?php
require_once 'security.php';
require_once 'db.php';

// Send security headers
send_security_headers();

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/login.php");
    exit();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (!in_array($category, ['sell', 'purchase'])) {
        $message = 'Invalid category selected.';
        $message_type = 'error';
    } elseif (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please select a PDF file to upload.';
        $message_type = 'error';
    } else {
        $file = $_FILES['pdf_file'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];

        // Check if it's a PDF
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if ($file_ext !== 'pdf') {
            $message = 'Only PDF files are allowed.';
            $message_type = 'error';
        } elseif ($file_size > 10 * 1024 * 1024) { // 10MB limit
            $message = 'File size must be less than 10MB.';
            $message_type = 'error';
        } else {
            // Generate unique filename
            $unique_name = time() . '_' . uniqid() . '.pdf';
            $upload_dir = "uploads/$category/";
            $file_path = $upload_dir . $unique_name;

            if (move_uploaded_file($file_tmp, $file_path)) {
                // Save to database
                $table = $category . '_pdfs';
                $stmt = $conn->prepare("INSERT INTO $table (filename, original_name, file_path, file_size, description) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssds", $unique_name, $file_name, $file_path, $file_size, $description);

                if ($stmt->execute()) {
                    $message = 'PDF uploaded successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Database error: ' . $stmt->error;
                    $message_type = 'error';
                    // Remove uploaded file if DB insert failed
                    unlink($file_path);
                }
                $stmt->close();
            } else {
                $message = 'Failed to upload file.';
                $message_type = 'error';
            }
        }
    }
}

$conn->close();

// Redirect back with message
header("Location: billpdf.php?message=" . urlencode($message) . "&type=" . $message_type);
exit();
?>
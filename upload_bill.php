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

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_type = $_POST['bill_type'] ?? '';
    $bill_date = $_POST['bill_date'] ?? '';
    $bill_no = trim($_POST['bill_no'] ?? '');
    $party_name = trim($_POST['party_name'] ?? '');
    $total_amount = $_POST['total_amount'] ?? 0;

    if (!in_array($bill_type, ['SALE', 'PURCHASE'])) {
        $message = 'Invalid bill type selected.';
        $message_type = 'error';
    } elseif (empty($bill_date) || empty($bill_no) || empty($party_name) || $total_amount <= 0) {
        $message = 'Please fill all required fields.';
        $message_type = 'error';
    } elseif (!isset($_FILES['bill_file']) || $_FILES['bill_file']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please select a bill file to upload.';
        $message_type = 'error';
    } else {
        $file = $_FILES['bill_file'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];

        // Check file type
        $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_types)) {
            $message = 'Only PDF, JPG, JPEG, PNG files are allowed.';
            $message_type = 'error';
        } elseif ($file_size > 10 * 1024 * 1024) { // 10MB limit
            $message = 'File size must be less than 10MB.';
            $message_type = 'error';
        } else {
            // Generate unique filename
            $unique_name = time() . '_' . uniqid() . '.' . $file_ext;
            $upload_dir = "uploads/bills/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_path = $upload_dir . $unique_name;

            if (move_uploaded_file($file_tmp, $file_path)) {
                // Save to database
                $stmt = $conn->prepare("INSERT INTO bills (user_id, bill_type, bill_date, bill_no, party_name, total_amount, bill_file) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssds", $user_id, $bill_type, $bill_date, $bill_no, $party_name, $total_amount, $unique_name);

                if ($stmt->execute()) {
                    $message = 'Bill uploaded successfully!';
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
header("Location: upload_bill_form.php?message=" . urlencode($message) . "&type=" . $message_type);
exit();
?>
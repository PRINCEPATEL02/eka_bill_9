<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/login.php");
    exit();
}

require_once "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_id = isset($_POST['company_id']) ? (int)$_POST['company_id'] : null;
    $payment_date = $_POST['payment_date'];
    $payment_amount = (float)$_POST['payment_amount'];
    $payment_method = $_POST['payment_method'];
    $reference_number = $_POST['reference_number'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Validate required fields
    if (!$company_id || !$payment_date || !$payment_amount || !$payment_method) {
        die("All required fields must be filled.");
    }

    // Insert payment into database
    $insert_query = "INSERT INTO payments (company_id, payment_date, payment_amount, payment_method, reference_number, notes) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("isdsss", $company_id, $payment_date, $payment_amount, $payment_method, $reference_number, $notes);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();

        // Redirect back to ledger with success message
        header("Location: ledger.php?company_id=$company_id&payment_added=1");
        exit();
    } else {
        die("Error adding payment: " . $stmt->error);
    }
} else {
    // If not POST, redirect to payment form
    header("Location: payment_form.php");
    exit();
}
?>
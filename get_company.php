<?php
require_once "db.php";

header('Content-Type: application/json');

$id = $_GET['id'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'error' => 'Company ID is required']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $company = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'company' => $company
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Company not found'
    ]);
}

$stmt->close();
$conn->close();
?>
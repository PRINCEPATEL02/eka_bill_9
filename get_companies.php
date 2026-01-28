<?php
require_once "db.php";

header('Content-Type: application/json');

$query = "SELECT id, company_name FROM companies ORDER BY company_name ASC";
$result = $conn->query($query);

if ($result) {
    $companies = [];
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
    echo json_encode([
        'success' => true,
        'companies' => $companies
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $conn->error
    ]);
}

$conn->close();
?>
<?php
require_once 'security.php';
require_once 'db.php';

// Send security headers
send_security_headers();

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$pp_required = floatval($input['pp_required'] ?? 0);
$hdpe_required = intval($input['hdpe_required'] ?? 0);
$ms_required = floatval($input['ms_required'] ?? 0);

// Validate input
if ($pp_required <= 0 || $hdpe_required <= 0 || $ms_required <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid recipe data']);
    exit();
}

try {
    // Update the recipe with id = 1
    $stmt = $conn->prepare("UPDATE recipes SET pp = ?, hdpe = ?, ms_wire = ? WHERE id = 1");
    $stmt->bind_param("ddd", $pp_required, $hdpe_required, $ms_required);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Recipe updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update recipe']);
    }

    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
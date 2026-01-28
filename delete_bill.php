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

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $table = isset($_GET['table']) ? $_GET['table'] : 'bills'; // Default to bills, but allow purchases

    if ($table === 'purchases') {
        // Handle purchase deletion
        $stmt = $conn->prepare("SELECT items, quantity FROM purchases WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $purchase = $result->fetch_assoc();
        $stmt->close();

        if ($purchase) {
            $item = $purchase['items'];
            $quantity = (float)$purchase['quantity'];

            // Reduce stock levels for purchased materials
            if (in_array($item, ['PP', 'HDPE', 'MS-WIRE'])) {
                $stock_update = [];
                if ($item === 'PP') {
                    $stock_update['pp_stock_kg'] = $quantity;
                } elseif ($item === 'HDPE') {
                    $stock_update['hdpe_stock_sheets'] = $quantity;
                } elseif ($item === 'MS-WIRE') {
                    $stock_update['ms_wire_stock_kg'] = $quantity;
                }

                if (!empty($stock_update)) {
                    $column = key($stock_update);
                    $value = $stock_update[$column];
                    $stock_stmt = $conn->prepare("UPDATE stock_levels SET $column = $column - ? WHERE id = 1");
                    $stock_stmt->bind_param("d", $value);
                    $stock_stmt->execute();
                    $stock_stmt->close();
                }
            }

            // Delete from purchases
            $stmt = $conn->prepare("DELETE FROM purchases WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $message = 'Purchase deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Purchase not found or access denied.';
            $message_type = 'error';
        }
    } else {
        // Handle bill deletion (existing logic)
        // Get bill details including quantity and items for stock restoration
        $stmt = $conn->prepare("SELECT quantity, items FROM bills WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $bill = $result->fetch_assoc();
        $stmt->close();

        if ($bill) {

            // Restore stock levels before deleting the bill (for side brush items)
            $items = json_decode($bill['items'], true);
            $brush_quantity = 0;

            if ($items) {
                foreach ($items as $item) {
                    if (stripos($item['description'], 'side brush') !== false) {
                        $brush_quantity += (float)$item['quantity'];
                    }
                }
            }

            // Debug logging
            error_log("DEBUG: Deleting bill ID $id, brush quantity: $brush_quantity");

            if ($brush_quantity > 0) {
                // Get recipe for "One Side Brush" (first row in recipes table)
                $recipe_stmt = $conn->prepare("SELECT pp, hdpe, ms_wire FROM recipes ORDER BY id ASC LIMIT 1");
                $recipe_stmt->execute();
                $recipe_result = $recipe_stmt->get_result();
                $recipe = $recipe_result->fetch_assoc();
                $recipe_stmt->close();

                if ($recipe) {
                    $pp_per_brush = (float)$recipe['pp'];
                    $hdpe_per_brush = (float)$recipe['hdpe'];
                    $ms_per_brush = (float)$recipe['ms_wire'];

                    // Calculate stock to restore
                    $pp_to_restore = $brush_quantity * $pp_per_brush;
                    $hdpe_to_restore = $brush_quantity * $hdpe_per_brush;
                    $ms_to_restore = $brush_quantity * $ms_per_brush;

                    // Debug logging
                    error_log("DEBUG: Recipe data found - PP: $pp_per_brush, HDPE: $hdpe_per_brush, MS: $ms_per_brush");
                    error_log("DEBUG: Restoring stock - PP: $pp_to_restore, HDPE: $hdpe_to_restore, MS: $ms_to_restore");

                    // Update stock levels (add back the consumed stock)
                    $update_stmt = $conn->prepare("UPDATE stock_levels SET pp_stock_kg = pp_stock_kg + ?, hdpe_stock_sheets = hdpe_stock_sheets + ?, ms_wire_stock_kg = ms_wire_stock_kg + ? WHERE id = 1");
                    $update_stmt->bind_param("ddd", $pp_to_restore, $hdpe_to_restore, $ms_to_restore);
                    $result = $update_stmt->execute();
                    $update_stmt->close();

                    if ($result) {
                        error_log("DEBUG: Stock restoration successful");
                    } else {
                        error_log("DEBUG: Stock restoration failed");
                    }
                } else {
                    error_log("DEBUG: No recipe found for 'One Side Brush'");
                }
            } else {
                error_log("DEBUG: No side brush items found in bill");
            }

            // Delete from database
            $stmt = $conn->prepare("DELETE FROM bills WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $message = 'Bill deleted successfully!';
            $message_type = 'success';
        } else {
            $message = 'Bill not found or access denied.';
            $message_type = 'error';
        }
    }
} else {
    $message = 'Invalid request.';
    $message_type = 'error';
}

$conn->close();

// Determine redirect based on source
$source = isset($_GET['source']) ? $_GET['source'] : 'dashboard';
if ($source === 'ledger') {
    header("Location: ledger.php?message=" . urlencode($message) . "&type=" . $message_type);
} else {
    header("Location: dashboard.php?message=" . urlencode($message) . "&type=" . $message_type);
}
exit();
?>
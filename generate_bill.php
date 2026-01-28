<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: bill_form.html");
    exit;
}

// Get form data
$invoice_number = trim($_POST["invoice_number"] ?? "");
$invoice_date = $_POST["invoice_date"] ?? "";
$company_id = $_POST["company_id"] ?? null;
$tax_type = $_POST["tax_type"] ?? "SGST/CGST";

// Buyer details
$buyer_name = trim($_POST["buyer_name"] ?? "");
$buyer_address = trim($_POST["buyer_address"] ?? "");
$buyer_mobile = trim($_POST["buyer_mobile"] ?? "");
$buyer_gst = trim($_POST["buyer_gst"] ?? "");
$buyer_state = trim($_POST["buyer_state"] ?? "");

// Consignee details
$consignee_name = trim($_POST["consignee_name"] ?? "");
$consignee_address = trim($_POST["consignee_address"] ?? "");
$consignee_mobile = trim($_POST["consignee_mobile"] ?? "");
$consignee_gst = trim($_POST["consignee_gst"] ?? "");
$consignee_state = trim($_POST["consignee_state"] ?? "");

// Tax rates
$cgst_rate = floatval($_POST["cgst_rate"] ?? 0);
$sgst_rate = floatval($_POST["sgst_rate"] ?? 0);
$igst_rate = floatval($_POST["igst_rate"] ?? 0);
$freight = floatval($_POST["freight"] ?? 0);
$rounding_adjustment = floatval($_POST["round_off"] ?? 0);

// Bank details
$bank_name = trim($_POST["bank_name"] ?? "");
$bank_account = trim($_POST["bank_account"] ?? "");
$bank_ifsc = trim($_POST["bank_ifsc"] ?? "");
$bank_branch = trim($_POST["bank_branch"] ?? "");

// Terms
$terms = trim($_POST["terms"] ?? "");

// Items
$item_descriptions = $_POST["item_description"] ?? [];
$item_hsns = $_POST["item_hsn"] ?? [];
$item_quantities = $_POST["item_quantity"] ?? [];
$item_units = $_POST["item_unit"] ?? [];
$item_rates = $_POST["item_rate"] ?? [];

// Validate required fields
if (empty($invoice_number) || empty($invoice_date) || empty($buyer_name) || 
    empty($item_descriptions) || count($item_descriptions) == 0) {
    die("Error: Required fields are missing.");
}

// Build items array and calculate totals
$items = [];
$total_amount = 0;

for ($i = 0; $i < count($item_descriptions); $i++) {
    $quantity = floatval($item_quantities[$i] ?? 0);
    $rate = floatval($item_rates[$i] ?? 0);
    $amount = $quantity * $rate;
    $total_amount += $amount;
    
    $items[] = [
        'description' => $item_descriptions[$i],
        'hsn' => $item_hsns[$i] ?? '',
        'quantity' => $quantity,
        'unit' => $item_units[$i] ?? 'NOS',
        'rate' => $rate,
        'amount' => $amount
    ];
}

// Calculate taxes
$cgst_amount = 0;
$sgst_amount = 0;
$igst_amount = 0;

if ($tax_type === "SGST/CGST") {
    $cgst_amount = $total_amount * ($cgst_rate / 100);
    $sgst_amount = $total_amount * ($sgst_rate / 100);
} else {
    $igst_amount = $total_amount * ($igst_rate / 100);
}

$grand_total = $total_amount + $cgst_amount + $sgst_amount + $igst_amount + $freight + $rounding_adjustment;

// Convert amount to words
require_once "number_to_words.php";
$amount_in_words = convertNumberToWords($grand_total);

// Prepare items as JSON
$items_json = json_encode($items);
$total_quantity = array_sum($item_quantities);
$average_unit_price = $total_quantity > 0 ? $total_amount / $total_quantity : 0;

// Insert into database
$stmt = $conn->prepare(
    "INSERT INTO bills (
        invoice_number, invoice_date, company_id,
        buyer_name, buyer_address, buyer_mobile, buyer_gst, buyer_state,
        consignee_name, consignee_address, consignee_mobile, consignee_gst, consignee_state,
        tax_type, cgst_rate, sgst_rate, igst_rate, freight, rounding_adjustment,
        bank_name, bank_account, bank_ifsc, bank_branch, terms,
        items, quantity, unit_price, total_amount, cgst_amount, sgst_amount, igst_amount, grand_total, amount_in_words
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

// Handle null company_id
if (empty($company_id)) {
    $company_id = null;
} else {
    $company_id = intval($company_id);
}

// 31 parameters total - format string must match exactly
// Format: ss(2) + i(1) + sssss(5) + sssss(5) + s(1) + ddddd(5) + ssssss(6) + sdddd(4) + s(1) = 31
$stmt->bind_param(
    "ssisssssssssssdddddssssssddddddds",
    $invoice_number,      // s
    $invoice_date,        // s
    $company_id,          // i
    $buyer_name,          // s
    $buyer_address,       // s
    $buyer_mobile,        // s
    $buyer_gst,           // s
    $buyer_state,         // s
    $consignee_name,      // s
    $consignee_address,   // s
    $consignee_mobile,    // s
    $consignee_gst,       // s
    $consignee_state,     // s
    $tax_type,            // s
    $cgst_rate,           // d
    $sgst_rate,           // d
    $igst_rate,           // d
    $freight,             // d
    $rounding_adjustment, // d
    $bank_name,           // s
    $bank_account,        // s
    $bank_ifsc,           // s
    $bank_branch,         // s
    $terms,               // s
    $items_json,          // s
    $total_quantity,      // d
    $average_unit_price,  // d
    $total_amount,        // d
    $cgst_amount,         // d
    $sgst_amount,         // d
    $igst_amount,         // d
    $grand_total,         // d
    $amount_in_words      // s
);

if ($stmt->execute()) {
    $bill_id = $conn->insert_id;
    $stmt->close();

    // Reduce stock levels based on sold side brush items
    // Calculate brush quantity from items
    $brush_quantity = 0;
    foreach ($items as $item) {
        if (stripos($item['description'], 'side brush') !== false) {
            $brush_quantity += $item['quantity'];
        }
    }

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

            // Calculate total consumption
            $total_pp_consumed = $brush_quantity * $pp_per_brush;
            $total_hdpe_consumed = $brush_quantity * $hdpe_per_brush;
            $total_ms_consumed = $brush_quantity * $ms_per_brush;

            // Update stock levels
            $stock_stmt = $conn->prepare("UPDATE stock_levels SET pp_stock_kg = pp_stock_kg - ?, hdpe_stock_sheets = hdpe_stock_sheets - ?, ms_wire_stock_kg = ms_wire_stock_kg - ? WHERE id = 1");
            $stock_stmt->bind_param("ddd", $total_pp_consumed, $total_hdpe_consumed, $total_ms_consumed);
            $stock_stmt->execute();
            $stock_stmt->close();
        }
    }

    $conn->close();

    // Redirect to view bill page
    header("Location: view_bill.php?id=" . $bill_id);
    exit;
} else {
    die("Error saving bill: " . $stmt->error);
}
?>
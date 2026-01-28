<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: estimate_form.php");
    exit;
}

// Get form data
$estimate_number = "XX/2025-26"; // Default estimate number
$estimate_date = $_POST["estimate_date"] ?? "";
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
$round_off = floatval($_POST["round_off"] ?? 0);

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
if (empty($estimate_date) || empty($buyer_name) ||
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

$grand_total = $total_amount + $cgst_amount + $sgst_amount + $igst_amount + $freight + $round_off;

// Convert amount to words
require_once "number_to_words.php";
$amount_in_words = convertNumberToWords($grand_total);

// Prepare items as JSON
$items_json = json_encode($items);

// Insert into database (assuming estimates table exists, similar to bills)
$stmt = $conn->prepare(
    "INSERT INTO estimates (
        estimate_number, estimate_date, company_id,
        buyer_name, buyer_address, buyer_mobile, buyer_gst, buyer_state,
        consignee_name, consignee_address, consignee_mobile, consignee_gst, consignee_state,
        tax_type, cgst_rate, sgst_rate, igst_rate, freight, round_off,
        bank_name, bank_account, bank_ifsc, bank_branch, terms,
        items, total_amount, cgst_amount, sgst_amount, igst_amount, grand_total, amount_in_words
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

// Handle null company_id
if (empty($company_id)) {
    $company_id = null;
} else {
    $company_id = intval($company_id);
}

// 31 parameters total - format string must match exactly
// Format: ss(2) + i(1) + sssss(5) + sssss(5) + s(1) + ddddd(5) + ssssss(6) + ddddd(5) + s(1) = 31
$stmt->bind_param(
    "ssissssssssssssddddssssssddddds",
    $estimate_number,      // s
    $estimate_date,        // s
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
    $round_off,           // d
    $bank_name,           // s
    $bank_account,        // s
    $bank_ifsc,           // s
    $bank_branch,         // s
    $terms,               // s
    $items_json,          // s
    $total_amount,        // d
    $cgst_amount,         // d
    $sgst_amount,         // d
    $igst_amount,         // d
    $grand_total,         // d
    $amount_in_words      // s
);

if ($stmt->execute()) {
    $estimate_id = $conn->insert_id;
    $stmt->close();
    $conn->close();

    // Redirect to view estimate page (assuming view_estimate.php exists)
    header("Location: view_estimate.php?id=" . $estimate_id);
    exit;
} else {
    die("Error saving estimate: " . $stmt->error);
}
?>
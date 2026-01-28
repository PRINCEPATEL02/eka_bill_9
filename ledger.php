<?php
require_once 'security.php';

// Send security headers
send_security_headers();

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/login.php");
    exit();
}

require_once "db.php";

// Handle POST requests for adding/updating/deleting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : null;

    if (isset($_POST['add_bill']) && $company_id) {
        $invoice_date = $_POST['invoice_date'];
        $invoice_number = $_POST['invoice_number'];
        $amount = (float)$_POST['amount'];
        

        $insert_query = "INSERT INTO bills (company_id, invoice_date, invoice_number, grand_total) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("issd", $company_id, $invoice_date, $invoice_number, $amount);
        $stmt->execute();
        $stmt->close();

        // Redirect to refresh the page with success message
        header("Location: ledger.php?company_id=$company_id&success=1");
        exit();
    }

    if (isset($_POST['add_payment']) && $company_id) {
        $payment_date = $_POST['payment_date'];
        $payment_amount = (float)$_POST['payment_amount'];
        $payment_method = $_POST['payment_method'];
        $reference_number = $_POST['reference_number'] ?? '';
        $notes = $_POST['notes'] ?? '';

        $insert_query = "INSERT INTO payments (company_id, payment_date, payment_amount, payment_method, reference_number, notes) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("isdsss", $company_id, $payment_date, $payment_amount, $payment_method, $reference_number, $notes);
        $stmt->execute();
        $stmt->close();

        // Redirect to refresh the page with success message
        header("Location: ledger.php?company_id=$company_id&success=1");
        exit();
    }



    if (isset($_POST['delete_payment_id']) && $company_id) {
        $payment_id = (int)$_POST['delete_payment_id'];
        $delete_query = "DELETE FROM payments WHERE id = ? AND company_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $payment_id, $company_id);
        $stmt->execute();
        $stmt->close();

        // Redirect to refresh the page
        header("Location: ledger.php?company_id=$company_id");
        exit();
    }
}

// Get company ID from GET parameter for details view
$company_id = isset($_GET['company_id']) ? (int)$_GET['company_id'] : null;

// Pagination setup
$limit = 10;
$bill_page = isset($_GET['bill_page']) ? max(1, (int)$_GET['bill_page']) : 1;
$payment_page = isset($_GET['payment_page']) ? max(1, (int)$_GET['payment_page']) : 1;

$bill_offset = ($bill_page - 1) * $limit;
$payment_offset = ($payment_page - 1) * $limit;

if ($company_id) {
    // Show detailed view for specific company
    $company_query = "SELECT * FROM companies WHERE id = ?";
    $stmt = $conn->prepare($company_query);
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $company_result = $stmt->get_result();
    $company = $company_result->fetch_assoc();
    $stmt->close();

    if (!$company) {
        die("Company not found.");
    }

    // Get total counts for pagination
    $bill_total_query = "SELECT COUNT(*) as total FROM bills WHERE company_id = ?";
    $stmt = $conn->prepare($bill_total_query);
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $bill_total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    $payment_total_query = "SELECT COUNT(*) as total FROM payments WHERE company_id = ?";
    $stmt = $conn->prepare($payment_total_query);
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $payment_total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    $bill_pages = ceil($bill_total / $limit);
    $payment_pages = ceil($payment_total / $limit);

    // Ensure page doesn't exceed total pages
    $bill_page = min($bill_page, $bill_pages ?: 1);
    $payment_page = min($payment_page, $payment_pages ?: 1);

    // Get paginated bills for this company
    $bills_query = "
        SELECT * FROM bills
        WHERE company_id = ? OR buyer_name = (SELECT company_name FROM companies WHERE id = ?)
        ORDER BY invoice_date DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($bills_query);
    $stmt->bind_param("iiii", $company_id, $company_id, $limit, $bill_offset);
    $stmt->execute();
    $bills_result = $stmt->get_result();
    $bills = [];
    while ($bill = $bills_result->fetch_assoc()) {
        $bills[] = $bill;
    }
    $stmt->close();

    // Get paginated payments for this company
    $payments_query = "SELECT * FROM payments WHERE company_id = ? ORDER BY payment_date DESC LIMIT $limit OFFSET $payment_offset";
    $stmt = $conn->prepare($payments_query);
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $payments_result = $stmt->get_result();
    $payments = [];
    while ($payment = $payments_result->fetch_assoc()) {
        $payments[] = $payment;
    }
    $stmt->close();

    // Calculate totals (need to get all records for totals, not just paginated)
    $total_bills_query = "SELECT SUM(grand_total) as total FROM bills WHERE company_id = ? OR buyer_name = (SELECT company_name FROM companies WHERE id = ?)";
    $stmt = $conn->prepare($total_bills_query);
    $stmt->bind_param("ii", $company_id, $company_id);
    $stmt->execute();
    $total_bills = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    $total_payments_query = "SELECT SUM(payment_amount) as total FROM payments WHERE company_id = ?";
    $stmt = $conn->prepare($total_payments_query);
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $total_payments = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();

    $outstanding = $total_bills - $total_payments;

} else {
    // Show overview of all companies
    $companies_query = "
        SELECT
            c.id,
            c.company_name,
            COALESCE(SUM(b.grand_total), 0) as total_bills,
            COALESCE(SUM(p.payment_amount), 0) as total_payments,
            (COALESCE(SUM(b.grand_total), 0) - COALESCE(SUM(p.payment_amount), 0)) as outstanding
        FROM companies c
        LEFT JOIN bills b ON c.id = b.company_id
        LEFT JOIN payments p ON c.id = p.company_id
        GROUP BY c.id, c.company_name
        ORDER BY c.company_name ASC
    ";
    $companies_result = $conn->query($companies_query);
    $companies = [];
    while ($company = $companies_result->fetch_assoc()) {
        $companies[] = $company;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="image.png">

    <link rel="manifest" href="manifest.json">

    <link rel="apple-touch-icon" href="icon-180.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ledger Management</title>
    <style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --warning-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --danger-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        --card-bg: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        --table-header-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        --border-radius: 15px;
        --transition: all 0.3s ease;
    }

    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 20px;
        background: var(--primary-gradient);
        min-height: 100vh;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    h1,
    h2,
    h3 {
        color: #333;
    }

    .btn {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 15px;
        cursor: pointer;
        border-radius: 4px;
        text-decoration: none;
        display: inline-block;
    }

    .btn:hover {
        background-color: #0056b3;
    }

    .btn-secondary {
        background-color: #6c757d;
    }

    .btn-secondary:hover {
        background-color: #545b62;
    }

    .summary {
        margin-bottom: 20px;
    }

    .summary-inline {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .summary-item {
        padding: 10px;
        border-radius: 4px;
    }

    .bills-item {
        background-color: #e9ecef;
    }

    .payments-item {
        background-color: #d4edda;
    }

    .outstanding-item {
        background-color: #fff3cd;
    }

    .outstanding-positive {
        background-color: #f8d7da;
    }

    .outstanding-zero {
        background-color: #d1ecf1;
    }

    .amount {
        font-weight: bold;
        font-size: 1.2em;
    }

    .form-actions {
        margin-bottom: 20px;
    }

    .form-container {
        border: 1px solid #ddd;
        padding: 20px;
        border-radius: 4px;
        background-color: #f9f9f9;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .required {
        color: red;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left;
    }

    th {
        background-color: #f2f2f2;
        font-weight: bold;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .company-link {
        color: #007bff;
        text-decoration: none;
    }

    .company-link:hover {
        text-decoration: underline;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        margin-top: 20px;
    }

    .pagination a {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 10px 15px;
        border-radius: 20px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .pagination a:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .pagination a.disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .pagination span {
        font-weight: 600;
        color: #555;
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        body {
            padding: 10px;
        }

        .container {
            padding: 15px;
            margin: 0;
        }

        h1,
        h2,
        h3 {
            font-size: 1.2em;
        }

        .summary-inline {
            flex-direction: column;
            gap: 10px;
        }

        .summary-item {
            padding: 8px;
            text-align: center;
        }

        .amount {
            font-size: 1em;
        }

        .form-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            font-size: 16px;
            /* Prevent zoom on iOS */
        }

        table {
            font-size: 14px;
            overflow-x: auto;
            display: block;
            white-space: nowrap;
        }

        th,
        td {
            padding: 8px 4px;
            min-width: 100px;
        }

        .btn {
            padding: 8px 12px;
            font-size: 14px;
        }

        .pagination {
            flex-wrap: wrap;
            gap: 10px;
        }

        .pagination a {
            padding: 8px 12px;
            font-size: 14px;
        }

        .pagination span {
            font-size: 14px;
        }
    }

    @media (max-width: 480px) {
        .container {
            padding: 10px;
        }

        h1 {
            font-size: 1.1em;
        }

        h2,
        h3 {
            font-size: 1em;
        }

        table th,
        table td {
            padding: 6px 2px;
            font-size: 12px;
        }

        .btn {
            padding: 6px 10px;
            font-size: 12px;
        }

        .summary-item {
            padding: 6px;
        }

        .amount {
            font-size: 0.9em;
        }
    }
    </style>
    <!-- Add this once in your HTML (put near the end of <body>) -->
    <style>
    /* simple overlay for user warnings */
    #site-warning-overlay {
        position: fixed;
        left: 50%;
        top: 10%;
        transform: translateX(-50%);
        z-index: 99999;
        background: rgba(0, 0, 0, 0.85);
        color: #fff;
        padding: 12px 18px;
        border-radius: 8px;
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.4);
        display: none;
        max-width: 90%;
        text-align: center;
        pointer-events: none;
    }
    </style>

    <div id="site-warning-overlay" aria-live="polite"></div>

    <script>
    (() => {
        const overlay = document.getElementById('site-warning-overlay');

        // show overlay message (throttled)
        let lastShown = 0;
        const showMessage = (msg) => {
            const now = Date.now();
            // don't spam the user: show at most once per 1.2s
            if (now - lastShown < 1200) return;
            lastShown = now;
            overlay.textContent = msg;
            overlay.style.display = 'block';
            overlay.style.opacity = '1';
            // hide after 1.8 seconds
            setTimeout(() => overlay.style.display = 'none', 1800);
        };

        // central block function
        const blockWithMsg = (e, msg) => {
            try {
                e.preventDefault();
                e.stopPropagation();
            } catch (err) {
                /* ignore */
            }
            showMessage(msg || 'This action is disabled on this page.');
            return false;
        };

        // Helper: lowercase key for comparisons (fallbacks handled)
        const key = (e) => (e.key || '').toString();

        // keyboard handler
        window.addEventListener('keydown', (e) => {
            // Disallow all Alt-only sequences (commonly used with menus)
            if (e.altKey && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
                return blockWithMsg(e, 'Alt shortcuts are disabled.');
            }

            // Common dev tools shortcuts
            if (key(e) === 'F12') return blockWithMsg(e, 'Developer tools are disabled.');
            if (e.ctrlKey && e.shiftKey && ['I', 'i', 'J', 'j', 'C', 'c', 'K', 'k'].includes(key(e))) {
                return blockWithMsg(e, 'Developer tools are disabled.');
            }
            if ((e.ctrlKey && !e.shiftKey && (key(e) === 'u' || key(e) === 'U')) ||
                (e.ctrlKey && !e.shiftKey && (key(e) === 's' || key(e) === 'S')) ||
                (e.ctrlKey && !e.shiftKey && (key(e) === 'p' || key(e) === 'P'))) {
                return blockWithMsg(e, 'This keyboard action is disabled.');
            }

            // copy / cut / paste prevention
            if (e.ctrlKey && (key(e) === 'c' || key(e) === 'C')) return blockWithMsg(e,
                'Copying is disabled.');
            if (e.ctrlKey && (key(e) === 'x' || key(e) === 'X')) return blockWithMsg(e,
                'Cutting is disabled.');
            if (e.ctrlKey && (key(e) === 'v' || key(e) === 'V')) return blockWithMsg(e,
                'Pasting is disabled.');

            // Print Screen & variations — many browsers/OSes expose it as 'PrintScreen' or 'Print'
            if (key(e) === 'PrintScreen' || key(e) === 'Print') return blockWithMsg(e,
                'Screenshots are disabled.');
            if (e.metaKey && e.shiftKey && ['3', '4', '5', '6', 'S', 's'].includes(key(e)))
                return blockWithMsg(e, 'Screenshots are disabled.');
            if (e.metaKey && e.key && e.key.toLowerCase() === 's' && e.shiftKey) return blockWithMsg(e,
                'Screenshots are disabled.');

            // Windows Game Bar (Win+G) — metaKey true on Windows & mac for "Win"/"Cmd"
            if (e.metaKey && (key(e) === 'g' || key(e) === 'G')) return blockWithMsg(e,
                'This shortcut is disabled.');

            // Prevent select-all (Ctrl/Cmd + A) if you want:
            if ((e.ctrlKey || e.metaKey) && (key(e) === 'a' || key(e) === 'A')) {
                return blockWithMsg(e, 'Select-all is disabled.');
            }

            // Fallback: block common devtools keyboard codes using event.code (some browsers)
            if (e.code && (e.code === 'F12' || e.code.startsWith('F') && parseInt(e.code.slice(1)) >= 1)) {
                // optional: do not block harmless F-keys; keep this conservative.
            }

        }, {
            capture: true,
            passive: false
        }); // passive:false so preventDefault works

        // block context menu
        window.addEventListener('contextmenu', (e) => blockWithMsg(e, 'Right-click is disabled.'), {
            capture: true,
            passive: false
        });

        // block selection, dragging, and copy/cut events
        window.addEventListener('selectstart', (e) => blockWithMsg(e, 'Selecting is disabled.'), {
            capture: true,
            passive: false
        });
        window.addEventListener('dragstart', (e) => blockWithMsg(e, 'Dragging is disabled.'), {
            capture: true,
            passive: false
        });
        window.addEventListener('copy', (e) => blockWithMsg(e, 'Copying is disabled.'), {
            capture: true,
            passive: false
        });
        window.addEventListener('cut', (e) => blockWithMsg(e, 'Cutting is disabled.'), {
            capture: true,
            passive: false
        });
        window.addEventListener('paste', (e) => blockWithMsg(e, 'Pasting is disabled.'), {
            capture: true,
            passive: false
        });

        // block keys fired when page has focus lost/regained and on keyup for redundancy
        window.addEventListener('keyup', (e) => {
            if (key(e) === 'PrintScreen' || key(e) === 'Print') {
                // keyup handler for some browsers which only expose PrintScreen on keyup
                blockWithMsg(e, 'Screenshots are disabled.');
            }
        }, {
            capture: true,
            passive: false
        });

        // optional: keyboard navigation via browser menu (prevent Ctrl+Shift+M (device toolbar), or other combos)
        // add combos here if you discover more in your testing.

        // VISUAL: simple DOM mutation that hides overlay on page blur or visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) overlay.style.display = 'none';
        });

    })();
    </script>
</head>

<body>
    <div class="container">
        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div
            style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-weight: bold;">
            Operation completed successfully!
        </div>
        <?php endif; ?>

        <?php if ($company_id): ?>
        <!-- Detailed view for specific company -->
        <div style="text-align: center; margin-bottom: 20px;">
            <a href="ledger.php" class="btn btn-secondary">← Back to Company List</a>
            <button class="btn btn-secondary" onclick="exportToExcel()">Export to Excel</button>
        </div>
        <h1><?php echo htmlspecialchars($company['company_name']); ?> - Ledger Details</h1>

        <div class="summary">
            <h3>Summary</h3>
            <div class="summary-inline">
                <span class="summary-item bills-item">
                    <strong>Total Bills:</strong>
                    <span class="amount">₹<?php echo number_format($total_bills, 2); ?></span>
                </span>
                <span class="summary-item payments-item">
                    <strong>Total Payments:</strong>
                    <span class="amount">₹<?php echo number_format($total_payments, 2); ?></span>
                </span>
                <span
                    class="summary-item outstanding-item <?php echo $outstanding > 0 ? 'outstanding-positive' : 'outstanding-zero'; ?>">
                    <strong>Outstanding Amount:</strong>
                    <span class="amount">₹<?php echo number_format($outstanding, 2); ?></span>
                </span>
            </div>

        </div>




        <h2>Bills</h2>
        <div class="form-actions">
            <button class="btn btn-secondary" onclick="showAddBillForm()">Add Bill</button>
        </div>

        <div id="addBillForm" style="display: none; margin-bottom: 20px;">
            <form method="POST" action="ledger.php?company_id=<?php echo $company_id; ?>">
                <div class="form-container">
                    <h3>Add New Bill</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="invoice_date">Invoice Date<span class="required">*</span></label>
                            <input type="date" id="invoice_date" name="invoice_date" required />
                        </div>
                        <div class="form-group">
                            <label for="invoice_number">Invoice Number<span class="required">*</span></label>
                            <input type="text" id="invoice_number" name="invoice_number" required />
                        </div>
                        <div class="form-group">
                            <label for="amount">Amount (₹)<span class="required">*</span></label>
                            <input type="number" id="amount" name="amount" step="0.01" min="0" required />
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_bill" class="btn">Add Bill</button>
                        <button type="button" class="btn btn-secondary" onclick="hideAddBillForm()">Cancel</button>
                    </div>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bills as $bill): ?>
                <tr>
                    <td><?php echo htmlspecialchars($bill['invoice_number']); ?></td>
                    <td><?php echo htmlspecialchars($bill['invoice_date']); ?></td>
                    <td>₹<?php echo number_format($bill['grand_total'], 2); ?></td>

                    <td>
                        <button class="btn btn-secondary"
                            onclick="deleteBill(<?php echo $bill['id']; ?>)">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($bill_pages > 1): ?>
        <div class="pagination">
            <?php if ($bill_page > 1): ?>
            <a
                href="?company_id=<?php echo $company_id; ?>&bill_page=<?php echo $bill_page - 1; ?>&payment_page=<?php echo $payment_page; ?>">&larr;
                Previous</a>
            <?php else: ?>
            <a href="#" class="disabled">&larr; Previous</a>
            <?php endif; ?>
            <span>Page <?php echo $bill_page; ?> of <?php echo $bill_pages; ?></span>
            <?php if ($bill_page < $bill_pages): ?>
            <a
                href="?company_id=<?php echo $company_id; ?>&bill_page=<?php echo $bill_page + 1; ?>&payment_page=<?php echo $payment_page; ?>">Next
                &rarr;</a>
            <?php else: ?>
            <a href="#" class="disabled">Next &rarr;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <h2>Payments</h2>
        <div class="form-actions">
            <button class="btn btn-secondary" onclick="showAddPaymentForm()">Add Payment</button>
        </div>

        <div id="addPaymentForm" style="display: none; margin-bottom: 20px;">
            <form method="POST" action="ledger.php?company_id=<?php echo $company_id; ?>">
                <div class="form-container">
                    <h3>Add New Payment</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="payment_date">Payment Date<span class="required">*</span></label>
                            <input type="date" id="payment_date" name="payment_date" required />
                        </div>
                        <div class="form-group">
                            <label for="payment_amount">Amount (₹)<span class="required">*</span></label>
                            <input type="number" id="payment_amount" name="payment_amount" step="0.01" min="0"
                                required />
                        </div>
                        <div class="form-group">
                            <label for="payment_method">Payment Method<span class="required">*</span></label>
                            <select id="payment_method" name="payment_method" required>
                                <option value="Cash">Cash</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Online Transfer">Online Transfer</option>
                                <option value="UPI">UPI</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="reference_number">Reference Number</label>
                            <input type="text" id="reference_number" name="reference_number" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_payment" class="btn">Add Payment</button>
                        <button type="button" class="btn btn-secondary" onclick="hideAddPaymentForm()">Cancel</button>
                    </div>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                    <td>₹<?php echo number_format($payment['payment_amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                    <td><?php echo htmlspecialchars($payment['reference_number'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($payment['notes'] ?? ''); ?></td>
                    <td>

                        <button class="btn btn-secondary"
                            onclick="deletePayment(<?php echo $payment['id']; ?>)">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($payment_pages > 1): ?>
        <div class="pagination">
            <?php if ($payment_page > 1): ?>
            <a
                href="?company_id=<?php echo $company_id; ?>&bill_page=<?php echo $bill_page; ?>&payment_page=<?php echo $payment_page - 1; ?>">&larr;
                Previous</a>
            <?php else: ?>
            <a href="#" class="disabled">&larr; Previous</a>
            <?php endif; ?>
            <span>Page <?php echo $payment_page; ?> of <?php echo $payment_pages; ?></span>
            <?php if ($payment_page < $payment_pages): ?>
            <a
                href="?company_id=<?php echo $company_id; ?>&bill_page=<?php echo $bill_page; ?>&payment_page=<?php echo $payment_page + 1; ?>">Next
                &rarr;</a>
            <?php else: ?>
            <a href="#" class="disabled">Next &rarr;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Overview of all companies -->
        <h1>Ledger Management - Company Overview</h1>
        <table>
            <thead>
                <tr>
                    <th>Company Name</th>
                    <th>Total Bills</th>
                    <th>Total Payments</th>
                    <th>Outstanding Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($companies as $company): ?>
                <tr>
                    <td>
                        <a href="ledger.php?company_id=<?php echo $company['id']; ?>" class="company-link">
                            <?php echo htmlspecialchars($company['company_name']); ?>
                        </a>
                    </td>
                    <td>₹<?php echo number_format($company['total_bills'], 2); ?></td>
                    <td>₹<?php echo number_format($company['total_payments'], 2); ?></td>
                    <td
                        class="<?php echo $company['outstanding'] > 0 ? 'outstanding-positive' : 'outstanding-zero'; ?>">
                        ₹<?php echo number_format($company['outstanding'], 2); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="text-align: center; margin-top: 20px;">
            <a href="home.php" class="btn btn-secondary">Back to Home Page</a>
        </div>
        <?php endif; ?>
    </div>

    <script src="js/xlsx.full.min.js"></script>
    <script>
    function exportToExcel() {
        const companyName = '<?php echo htmlspecialchars($company['company_name']); ?>';
        const totalBills = '<?php echo number_format($total_bills, 2); ?>';
        const totalPayments = '<?php echo number_format($total_payments, 2); ?>';
        const outstanding = '<?php echo number_format($outstanding, 2); ?>';

        // Create combined data for single sheet
        const combinedData = [
            ['Summary'],
            ['Company Name', companyName],
            ['Total Bills', '₹' + totalBills],
            ['Total Payments', '₹' + totalPayments],
            ['Outstanding Amount', '₹' + outstanding],
            [], // Empty row
            ['Bills'],
            ['Invoice Number', 'Date', 'Amount', 'Status']
        ];

        <?php foreach ($bills as $bill): ?>
        combinedData.push([
            '<?php echo htmlspecialchars($bill['invoice_number']); ?>',
            '<?php echo htmlspecialchars($bill['invoice_date']); ?>',
            '₹<?php echo number_format($bill['grand_total'], 2); ?>',
            'Bill'
        ]);
        <?php endforeach; ?>

        combinedData.push([]); // Empty row
        combinedData.push(['Payments']);
        combinedData.push(['Date', 'Amount', 'Method', 'Reference', 'Notes']);

        <?php foreach ($payments as $payment): ?>
        combinedData.push([
            '<?php echo htmlspecialchars($payment['payment_date']); ?>',
            '₹<?php echo number_format($payment['payment_amount'], 2); ?>',
            '<?php echo htmlspecialchars($payment['payment_method']); ?>',
            '<?php echo htmlspecialchars($payment['reference_number'] ?? ''); ?>',
            '<?php echo htmlspecialchars($payment['notes'] ?? ''); ?>'
        ]);
        <?php endforeach; ?>

        // Create workbook
        const wb = XLSX.utils.book_new();

        // Single sheet with all data
        const ws = XLSX.utils.aoa_to_sheet(combinedData);
        XLSX.utils.book_append_sheet(wb, ws, 'Ledger');

        // Generate filename and download
        const filename = companyName.replace(/[^a-zA-Z0-9]/g, '_') + '_Ledger_' + new Date().toISOString().split('T')[
            0] + '.xlsx';
        XLSX.writeFile(wb, filename);
    }

    function showAddBillForm() {
        document.getElementById('addBillForm').style.display = 'block';
        loadCompanies();
    }

    function hideAddBillForm() {
        document.getElementById('addBillForm').style.display = 'none';
    }

    function loadCompanies() {
        fetch('get_companies.php')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('buyer_name');
                // Clear existing options except the first one
                select.innerHTML = '<option value="">-- Select Company --</option>';
                if (data.success && data.companies) {
                    data.companies.forEach(company => {
                        const option = document.createElement('option');
                        option.value = company.company_name;
                        option.textContent = company.company_name;
                        option.setAttribute('data-id', company.id);
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error loading companies:', error));
    }

    function loadCompanyDetails() {
        const select = document.getElementById('buyer_name');
        const selectedOption = select.options[select.selectedIndex];
        const companyId = selectedOption.getAttribute('data-id');

        if (companyId) {
            fetch('get_company.php?id=' + companyId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.company) {
                        document.getElementById('buyer_address').value = data.company.address || '';
                        document.getElementById('buyer_gst').value = data.company.gst || '';
                        document.getElementById('buyer_state').value = data.company.state || '';
                    }
                })
                .catch(error => console.error('Error loading company details:', error));
        } else {
            // Clear fields if no company selected
            document.getElementById('buyer_address').value = '';
            document.getElementById('buyer_gst').value = '';
            document.getElementById('buyer_state').value = '';
        }
    }

    // Add event listener for buyer name dropdown
    document.addEventListener('DOMContentLoaded', function() {
        const buyerSelect = document.getElementById('buyer_name');
        if (buyerSelect) {
            buyerSelect.addEventListener('change', loadCompanyDetails);
        }
    });

    function showAddPaymentForm() {
        document.getElementById('addPaymentForm').style.display = 'block';
    }

    function hideAddPaymentForm() {
        document.getElementById('addPaymentForm').style.display = 'none';
    }

    // Placeholder functions for edit and delete (can be implemented later)


    function deleteBill(id) {
        if (confirm('Are you sure you want to delete this bill?')) {
            window.location.href = 'delete_bill.php?id=' + id + '&source=ledger';
        }
    }



    function deletePayment(id) {
        if (confirm('Are you sure you want to delete this payment?')) {
            // Create a form to submit delete request
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'ledger.php?company_id=<?php echo $company_id; ?>';
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_payment_id';
            input.value = id;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
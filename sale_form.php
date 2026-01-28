<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bill_number = trim($_POST['bill_number']);
    $company_name = trim($_POST['company_name']);
    $sale_date = $_POST['sale_date'];
    $item = $_POST['item'];
    $tax_type = $_POST['tax_type'] ?? 'SGST/CGST';
    $amount_per_unit = floatval($_POST['amount_per_unit']);
    $quantity = floatval($_POST['quantity']);
    $total_amount = $amount_per_unit * $quantity;
    $gst_rate = 18; // 18% GST
    $gst_amount = $total_amount * ($gst_rate / 100);
    $total_with_gst = $total_amount + $gst_amount;
    $rounding_adjustment = floatval($_POST['rounding_adjustment'] ?? 0);
    $final_total = $total_with_gst + $rounding_adjustment;

    // Calculate tax rates and amounts based on tax_type
    if ($tax_type == 'SGST/CGST') {
        $cgst_rate = 9;
        $sgst_rate = 9;
        $igst_rate = 0;
        $cgst_amount = $gst_amount / 2;
        $sgst_amount = $gst_amount / 2;
        $igst_amount = 0;
    } else {
        $cgst_rate = 0;
        $sgst_rate = 0;
        $igst_rate = 18;
        $cgst_amount = 0;
        $sgst_amount = 0;
        $igst_amount = $gst_amount;
    }

    // Format items as JSON
    $items_json = json_encode([["description" => $item, "quantity" => $quantity, "unit" => "NOS", "rate" => $amount_per_unit, "amount" => $total_amount]]);

    $response = [];
    $stmt = $conn->prepare("INSERT INTO bills (invoice_number, invoice_date, buyer_name, tax_type, cgst_rate, sgst_rate, igst_rate,rounding_adjustment, items, quantity, unit_price, total_amount, cgst_amount, sgst_amount, igst_amount, grand_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssddddsddddddd", $bill_number, $sale_date, $company_name, $tax_type, $cgst_rate, $sgst_rate, $igst_rate, $rounding_adjustment, $items_json, $quantity, $amount_per_unit, $total_amount, $cgst_amount, $sgst_amount, $igst_amount, $final_total);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = "Sale record added successfully!";
    } else {
        $response['status'] = 'error';
        $response['message'] = "Error adding sale record.";
    }
    $stmt->close();

    // Reduce stock levels based on sold side brush items
    // Calculate brush quantity from items
    $items = json_decode($items_json, true);
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

    // Check if it's an AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        $conn->close();
        exit;
    } else {
        // For non-AJAX requests, set message variables
        $message = $response['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="image.png">

    <link rel="manifest" href="manifest.json">

    <link rel="apple-touch-icon" href="icon-180.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Sale Record</title>
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

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
        color: #333;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.95);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        backdrop-filter: blur(10px);
    }

    .header {
        background: var(--primary-gradient);
        color: white;
        padding: 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
        animation: float 20s infinite linear;
    }

    @keyframes float {
        0% {
            transform: translateX(-50%) translateY(-50%) rotate(0deg);
        }

        100% {
            transform: translateX(-50%) translateY(-50%) rotate(360deg);
        }
    }

    .header h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 10px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        position: relative;
        z-index: 1;
    }

    .header p {
        font-size: 1.2rem;
        opacity: 0.9;
        position: relative;
        z-index: 1;
    }

    .section {
        padding: 40px;
        background: white;
    }

    .section h2 {
        font-size: 2rem;
        color: #333;
        margin-bottom: 30px;
        text-align: center;
        position: relative;
    }

    .section h2::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: var(--primary-gradient);
        border-radius: 2px;
    }

    .form-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 40px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }

    .form-group {
        margin-bottom: 25px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #555;
        font-size: 1rem;
    }

    input,
    select {
        width: 100%;
        padding: 15px;
        border: 2px solid #e1e5e9;
        border-radius: 10px;
        font-size: 1rem;
        transition: var(--transition);
        background: #f8f9fa;
    }

    input:focus,
    select:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .calculated {
        background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
        border-color: #dee2e6;
    }

    .btn {
        padding: 15px 30px;
        background: var(--primary-gradient);
        color: white;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
        transition: var(--transition);
        display: inline-block;
        text-decoration: none;
        margin-right: 15px;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .btn-secondary {
        background: var(--warning-gradient);
    }

    .btn-secondary:hover {
        background: var(--warning-gradient);
    }

    .message {
        background: var(--success-gradient);
        color: white;
        padding: 15px;
        border-radius: var(--border-radius);
        margin-bottom: 30px;
        text-align: center;
        font-weight: 600;
    }

    .error {
        background: var(--danger-gradient);
    }

    @media (max-width: 768px) {
        .section {
            padding: 20px;
        }

        .form-container {
            padding: 20px;
        }

        .header h1 {
            font-size: 2rem;
        }

        input,
        select {
            padding: 12px;
        }

        .btn {
            padding: 12px 25px;
            font-size: 0.9rem;
            margin-right: 0;
            margin-bottom: 10px;
            display: block;
            width: 100%;
            text-align: center;
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
        <div class="header">
            <h1>Add Sale Record</h1>
            <p>Enter sale transaction details</p>
        </div>

        <div class="section">
            <div class="form-container">
                <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? '' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="bill_number">Bill Number *</label>
                        <input type="text" id="bill_number" name="bill_number" required>
                    </div>

                    <div class="form-group">
                        <label for="company_name">Company Name *</label>
                        <select id="company_name" name="company_name" required>
                            <option value="">Select Company</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sale_date">Date *</label>
                        <input type="date" id="sale_date" name="sale_date" value="<?php echo date('Y-m-d'); ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="item">Item *</label>
                        <select id="item" name="item" required>
                            <option value="">Select Item</option>
                            <option value="Side Brush">Side Brush</option>
                            <option value="Main Brush">Main Brush</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tax_type">Tax Type *</label>
                        <select id="tax_type" name="tax_type" required>
                            <option value="SGST/CGST">SGST/CGST</option>
                            <option value="IGST">IGST</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="amount_per_unit">Amount per Unit (₹) *</label>
                        <input type="number" id="amount_per_unit" name="amount_per_unit" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="total_amount">Total Amount (₹)</label>
                        <input type="number" id="total_amount" name="total_amount" step="0.01" readonly
                            class="calculated">
                    </div>

                    <div class="form-group">
                        <label for="gst_amount">GST Amount (18%)</label>
                        <input type="number" id="gst_amount" name="gst_amount" step="0.01" readonly class="calculated">
                    </div>

                    <div class="form-group">
                        <label for="total_with_gst">Total Amount with GST (18%)</label>
                        <input type="number" id="total_with_gst" name="total_with_gst" step="0.01" readonly
                            class="calculated">
                    </div>

                    <div class="form-group">
                        <label for="rounding_adjustment">Rounding Adjustment (₹)</label>
                        <input type="number" id="rounding_adjustment" name="rounding_adjustment" step="0.01" value="0">
                    </div>

                    <div class="form-group">
                        <label for="final_total">Final Total with Rounding</label>
                        <input type="number" id="final_total" name="final_total" step="0.01" readonly
                            class="calculated">
                    </div>

                    <button type="submit" class="btn">Add Sale Record</button>
                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    <a href="home.php" class="btn btn-secondary">🏠 Go to Home</a>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        loadCompanies();
    });

    function loadCompanies() {
        fetch('get_companies.php')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('company_name');
                if (data.success && data.companies) {
                    data.companies.forEach(company => {
                        const option = document.createElement('option');
                        option.value = company.company_name;
                        option.textContent = company.company_name;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading companies:', error);
            });
    }

    document.getElementById('amount_per_unit').addEventListener('input', calculate);
    document.getElementById('quantity').addEventListener('input', calculate);
    document.getElementById('rounding_adjustment').addEventListener('input', calculate);

    function calculate() {
        const amount = parseFloat(document.getElementById('amount_per_unit').value) || 0;
        const qty = parseFloat(document.getElementById('quantity').value) || 0;
        const roundingAdjustment = parseFloat(document.getElementById('rounding_adjustment').value) || 0;
        const total = amount * qty;
        const gst = total * 0.18;
        const totalWithGst = total + gst;
        const finalTotal = totalWithGst + roundingAdjustment;

        document.getElementById('total_amount').value = total.toFixed(2);
        document.getElementById('gst_amount').value = gst.toFixed(2);
        document.getElementById('total_with_gst').value = totalWithGst.toFixed(2);
        document.getElementById('final_total').value = finalTotal.toFixed(2);
    }

    // AJAX form submission
    document.querySelector('form').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        const formData = new FormData(this);

        fetch('sale_form.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                window.alert(data.message);
                // Clear form on success
                if (data.status === 'success') {
                    document.querySelector('form').reset();
                    // Reset calculated fields
                    document.getElementById('total_amount').value = '';
                    document.getElementById('gst_amount').value = '';
                    document.getElementById('total_with_gst').value = '';
                    document.getElementById('final_total').value = '';
                    // Reset date to today
                    document.getElementById('sale_date').value = '<?php echo date('Y-m-d'); ?>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.alert('An error occurred. Please try again.');
            });
    });
    </script>



</body>

</html>
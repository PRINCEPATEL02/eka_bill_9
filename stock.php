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

// Handle manual stock addition
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $manual_pp = floatval($_POST['manual_pp'] ?? 0);
    $manual_hdpe = intval($_POST['manual_hdpe'] ?? 0);
    $manual_ms = floatval($_POST['manual_ms'] ?? 0);

    // Update manual stock in database
    $stmt = $conn->prepare("UPDATE stock_levels SET pp_stock_kg = pp_stock_kg + ?, hdpe_stock_sheets = hdpe_stock_sheets + ?, ms_wire_stock_kg = ms_wire_stock_kg + ? WHERE id = 1");
    $stmt->bind_param("did", $manual_pp, $manual_hdpe, $manual_ms);
    $stmt->execute();
    $stmt->close();

    // Redirect to prevent duplicate submissions on refresh
    header("Location: stock.php?success=1");
    exit();
}

// Fetch stock quantities from database
$pp_stock = 0;
$hdpe_stock = 0;
$ms_stock = 0;

// Default recipe values
$pp_per_brush = 1.8;
$hdpe_per_brush = 1.0;
$ms_per_brush = 1.8;

// Fetch latest recipe for display in form
$latest_recipe = null;

try {
    // Read initial stock from stock_levels table
    $stock_stmt = $conn->prepare("SELECT pp_stock_kg, hdpe_stock_sheets, ms_wire_stock_kg FROM stock_levels WHERE id = 1");
    $stock_stmt->execute();
    $stock_result = $stock_stmt->get_result();
    $stock_data = $stock_result->fetch_assoc();
    $stock_stmt->close();

    if ($stock_data) {
        $pp_stock = (float)$stock_data['pp_stock_kg'];
        $hdpe_stock = (int)$stock_data['hdpe_stock_sheets'];
        $ms_stock = (float)$stock_data['ms_wire_stock_kg'];
    }

    // Get latest recipe for consumption calculation
    $recipe_stmt = $conn->prepare("SELECT pp, hdpe, ms_wire FROM recipes ORDER BY id DESC LIMIT 1");
    $recipe_stmt->execute();
    $recipe_result = $recipe_stmt->get_result();
    $latest_recipe = $recipe_result->fetch_assoc();
    $recipe_stmt->close();

    if ($latest_recipe) {
        $pp_per_brush = (float)$latest_recipe['pp'];
        $hdpe_per_brush = (float)$latest_recipe['hdpe'];
        $ms_per_brush = (float)$latest_recipe['ms_wire'];
    }

    // Stock levels are already maintained in real-time by generate_bill.php and delete_bill.php
    // No need to recalculate here - just display current stock from database

} catch (Exception $e) {
    // Handle error silently or log it
    error_log("Error fetching stock data: " . $e->getMessage());
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

    <title>Stock Management</title>
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
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.95);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        backdrop-filter: blur(10px);
        text-align: center;
        padding: 60px 40px;
    }

    .coming-soon-icon {
        font-size: 5rem;
        margin-bottom: 30px;
        display: block;
    }

    h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: #333;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }

    .subtitle {
        font-size: 1.5rem;
        color: #666;
        margin-bottom: 40px;
        font-weight: 300;
    }

    .features {
        margin-bottom: 40px;
    }

    .features h3 {
        font-size: 1.8rem;
        margin-bottom: 20px;
        color: #333;
    }

    .feature-list {
        display: flex;
        justify-content: center;
        gap: 30px;
        flex-wrap: wrap;
        margin-bottom: 40px;
    }

    .feature-item {
        background: var(--card-bg);
        padding: 20px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        min-width: 150px;
    }

    .feature-item .icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
        display: block;
    }

    .feature-item h4 {
        font-size: 1.2rem;
        color: #333;
        margin-bottom: 5px;
    }

    .feature-item p {
        color: #666;
        font-size: 0.9rem;
    }

    .stock-section {
        margin-bottom: 40px;
    }

    .stock-section h2 {
        font-size: 2rem;
        margin-bottom: 20px;
        color: #333;
    }

    .stock-grid {
        display: flex;
        justify-content: center;
        gap: 30px;
        flex-wrap: wrap;
    }

    .stock-item {
        background: var(--card-bg);
        padding: 20px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        min-width: 150px;
        text-align: center;
    }

    .stock-item .icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
    }

    .stock-item h4 {
        font-size: 1.2rem;
        color: #333;
        margin-bottom: 10px;
    }

    .stock-quantity {
        font-size: 1.5rem;
        font-weight: bold;
        color: #667eea;
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

    .manual-stock-form {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }

    .form-row {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 20px;
    }

    .labels-row {
        display: flex;
        justify-content: space-between;
        gap: 20px;
    }

    .inputs-row {
        display: flex;
        justify-content: space-between;
        gap: 20px;
    }

    .labels-row label {
        font-weight: 600;
        color: #555;
        font-size: 1rem;
        flex: 1;
        text-align: center;
    }

    .inputs-row input {
        flex: 1;
        padding: 12px;
        border: 2px solid #e1e5e9;
        border-radius: 10px;
        font-size: 1rem;
        transition: var(--transition);
        background: #f8f9fa;
    }

    .inputs-row input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-group {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .form-group label {
        font-weight: 600;
        color: #555;
        font-size: 1rem;
        text-align: center;
    }

    .form-group input {
        width: 100%;
        padding: 12px;
        border: 2px solid #e1e5e9;
        border-radius: 10px;
        font-size: 1rem;
        transition: var(--transition);
        background: #f8f9fa;
    }

    .form-group input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .back-link {
        display: inline-block;
        padding: 15px 30px;
        background: var(--primary-gradient);
        color: white;
        text-decoration: none;
        border: none;
        border-radius: 25px;
        font-weight: 600;
        transition: var(--transition);
    }

    .back-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
    }

    .modal-content {
        background-color: white;
        margin: 15% auto;
        padding: 30px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        width: 90%;
        max-width: 500px;
        position: relative;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        position: absolute;
        top: 10px;
        right: 20px;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
    }

    .modal-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        margin-top: 20px;
        margin-left: 10px;
    }


    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        background: var(--primary-gradient);
        color: white;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .btn-secondary {
        background: var(--warning-gradient);
        color: white;
    }

    .btn-secondary:hover {
        background: var(--warning-gradient);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .button-row {
        display: flex;
        justify-content: center;
        gap: 20px;
        flex-wrap: wrap;
        margin-top: 20px;
    }

    @media (max-width: 768px) {
        body {
            padding: 10px;
        }

        .container {
            padding: 40px 20px;
            max-width: 100%;
        }

        h1 {
            font-size: 2rem;
        }

        .subtitle {
            font-size: 1.2rem;
        }

        .stock-section h2 {
            font-size: 1.5rem;
        }

        .stock-grid {
            flex-direction: column;
            align-items: center;
        }

        .stock-item {
            min-width: 250px;
            margin-bottom: 20px;
        }

        .feature-list {
            flex-direction: column;
            align-items: center;
        }

        .feature-item {
            min-width: 250px;
            margin-bottom: 20px;
        }

        .form-row {
            flex-direction: column;
            gap: 15px;
        }

        .modal-content {
            margin: 20% auto;
            padding: 20px;
            width: 95%;
            max-width: none;
        }

        .modal-buttons {
            flex-direction: column;
            gap: 10px;
        }

        .btn,
        .back-link {
            width: 100%;
            padding: 15px;
            font-size: 1rem;
        }
    }

    @media (max-width: 480px) {
        body {
            padding: 5px;
        }

        .container {
            padding: 20px 10px;
        }

        h1 {
            font-size: 1.8rem;
        }

        .subtitle {
            font-size: 1rem;
        }


    }

    .stock-item,
    .feature-item {
        min-width: 200px;
        padding: 15px;
    }

    .stock-item .icon,
    .feature-item .icon {
        font-size: 2rem;
    }

    .stock-item h4,
    .feature-item h4 {
        font-size: 1rem;
    }

    .stock-quantity {
        font-size: 1.3rem;
    }

    .form-group label {
        font-size: 0.9rem;
    }

    .form-group input {
        padding: 10px;
        font-size: 0.9rem;
    }

    .modal-content {
        margin: 10% auto;
        padding: 15px;
    }

    .modal-content h2 {
        font-size: 1.5rem;
    }

    .btn,
    .back-link {
        padding: 12px;
        font-size: 0.9rem;
    }

    /* Appended from recipe.php */
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

    .back-btn {
        background: var(--warning-gradient);
        color: white;
        padding: 15px 30px;
        border-radius: 25px;
        text-decoration: none;
        font-size: 1rem;
        font-weight: 600;
        transition: var(--transition);
        display: inline-block;
        border: none;
        cursor: pointer;
        margin-bottom: 30px;
    }

    .back-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .recipe-section {
        margin-top: 40px;
        padding: 30px;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .recipe-form h3 {
        font-size: 1.5rem;
        color: #333;
        margin-bottom: 20px;
        text-align: center;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-weight: 600;
        margin-bottom: 8px;
        color: #555;
    }

    .form-group input {
        padding: 12px;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        font-size: 1rem;
        transition: var(--transition);
    }

    .form-group input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn {
        background: var(--primary-gradient);
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .btn-secondary {
        background: var(--success-gradient);
    }

    .btn-small {
        padding: 8px 16px;
        font-size: 0.9rem;
    }

    .btn-danger {
        background: var(--danger-gradient);
    }

    .recipes-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .recipes-table th,
    .recipes-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e1e5e9;
    }

    .recipes-table th {
        background: var(--table-header-bg);
        color: white;
        font-weight: 600;
    }

    .recipes-table tbody tr:hover {
        background: rgba(102, 126, 234, 0.05);
    }

    .production-result {
        margin-top: 30px;
        padding: 20px;
        background: var(--card-bg);
        border-radius: 10px;
    }

    .result-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .result-item {
        background: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .result-value {
        font-size: 2rem;
        font-weight: bold;
        color: #667eea;
        margin-top: 10px;
    }

    @media (max-width: 768px) {
        .section {
            padding: 20px;
        }

        .header h1 {
            font-size: 2rem;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
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
        <h1>Stock Management</h1>
        <p class="subtitle">Track inventory and product recipes</p>

        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div
            style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-weight: bold;">
            Manual stock added successfully!
        </div>
        <?php endif; ?>

        <!-- Current Stock Levels -->
        <div class="stock-section">
            <h2>Current Stock Levels</h2>
            <div class="stock-grid">
                <div class="stock-item">
                    <span class="icon">🧵</span>
                    <h4>PP Stock</h4>
                    <div class="stock-quantity" id="pp-stock"><?php echo $pp_stock; ?> kg</div>
                </div>
                <div class="stock-item">
                    <span class="icon">📄</span>
                    <h4>HDPE Sheet</h4>
                    <div class="stock-quantity" id="hdpe-stock"><?php echo $hdpe_stock; ?> sheets</div>
                </div>
                <div class="stock-item">
                    <span class="icon">🔧</span>
                    <h4>MS Wire</h4>
                    <div class="stock-quantity" id="ms-stock"><?php echo $ms_stock; ?> kg</div>
                </div>
            </div>
        </div>

        <!-- Manual Stock Addition -->
        <div class="button-row">
            <button id="openModalBtn" class="back-link">Add Manual Stock</button>
            <a href="home.php" class="back-link">← Back to Home</a>
        </div>

        <!-- Modal -->
        <div id="stockModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Add Manual Stock</h2>
                <form method="POST" id="manualStockForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="manual_pp">PP Quantity (kg)</label>
                            <input type="number" id="manual_pp" name="manual_pp" min="0" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label for="manual_hdpe">HDPE Quantity (sheets)</label>
                            <input type="number" id="manual_hdpe" name="manual_hdpe" min="0" step="1" value="0">
                        </div>
                        <div class="form-group">
                            <label for="manual_ms">MS-Wire Quantity (kg)</label>
                            <input type="number" id="manual_ms" name="manual_ms" min="0" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="modal-buttons">
                        <button type="button" class="btn" id="cancelBtn">Cancel</button>
                        <button type="submit" class="btn">Submit</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="section">
            <h2>Product Recipes</h2>
            <!-- Product Recipe Management -->
            <div class="recipe-section">
                <div class="recipe-form">
                    <h3>One Side Brush Recipe</h3>
                    <form id="brush-recipe-form">
                        <div class="form-grid">
                            <div class="form-group"><label for="pp-required">PP Required
                                    (kg)</label><input type="number" id="pp-required" step="0.01" min="0"
                                    value="<?php echo $latest_recipe ? $latest_recipe['pp'] : '1.8'; ?>" />
                            </div>
                            <div class="form-group"><label for="hdpe-required">HDPE Sheet Required
                                    (sheets)</label><input type="number" id="hdpe-required" step="1" min="0"
                                    value="<?php echo $latest_recipe ? $latest_recipe['hdpe'] : '1'; ?>" />
                            </div>
                            <div class="form-group"><label for="ms-required">MS Wire Required
                                    (kg)</label><input type="number" id="ms-required" step="0.01" min="0"
                                    value="<?php echo $latest_recipe ? $latest_recipe['ms_wire'] : '1.8'; ?>" />
                            </div>
                        </div>
                        <div class="form-actions"><button type="button" class="btn"
                                onclick="calculateProduction()">Calculate Production
                                Capacity</button><button type="button" class="btn btn-secondary"
                                onclick="saveRecipe()">Save Recipe</button>
                        </div>

                    </form>
                </div>
                <div class="production-result" id="production-result" style="display: none;">
                    <h3>Production Capacity</h3>
                    <div class="result-grid">
                        <div class="result-item">
                            <h4>Maximum Brushes</h4>
                            <div class="result-value" id="max-brushes">0</div>
                        </div>
                        <div class="result-item">
                            <h4>Limiting Factor</h4>
                            <div class="result-value" id="limiting-factor">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Get the modal
    var modal = document.getElementById("stockModal");

    // Get the button that opens the modal
    var btn = document.getElementById("openModalBtn");

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // Get the cancel button
    var cancelBtn = document.getElementById("cancelBtn");

    // When the user clicks the button, open the modal
    btn.onclick = function() {
        modal.style.display = "block";
    }

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }

    // When the user clicks on cancel button, close the modal
    cancelBtn.onclick = function() {
        modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Recipe functions
    function calculateProduction() {
        const ppRequired = parseFloat(document.getElementById('pp-required').value) || 0;
        const hdpeRequired = parseInt(document.getElementById('hdpe-required').value) || 0;
        const msRequired = parseFloat(document.getElementById('ms-required').value) || 0;

        const ppStock = parseFloat(document.getElementById('pp-stock').textContent.replace(' kg', '')) || 0;
        const hdpeStock = parseInt(document.getElementById('hdpe-stock').textContent.replace(' sheets', '')) || 0;
        const msStock = parseFloat(document.getElementById('ms-stock').textContent.replace(' kg', '')) || 0;

        if (ppRequired <= 0 || hdpeRequired <= 0 || msRequired <= 0) {
            alert('Please enter valid recipe requirements');
            return;
        }

        const maxByPP = Math.floor(ppStock / ppRequired);
        const maxByHDPE = Math.floor(hdpeStock / hdpeRequired);
        const maxByMS = Math.floor(msStock / msRequired);

        const maxBrushes = Math.min(maxByPP, maxByHDPE, maxByMS);

        let limitingFactors = [];
        if (maxBrushes === maxByPP) limitingFactors.push('PP');
        if (maxBrushes === maxByHDPE) limitingFactors.push('HDPE');
        if (maxBrushes === maxByMS) limitingFactors.push('MS Wire');

        let limitingFactor = limitingFactors.join(', ');

        document.getElementById('max-brushes').textContent = maxBrushes;
        document.getElementById('limiting-factor').textContent = limitingFactor;
        document.getElementById('production-result').style.display = 'block';
    }

    function saveRecipe() {
        const ppRequired = parseFloat(document.getElementById('pp-required').value) || 0;
        const hdpeRequired = parseInt(document.getElementById('hdpe-required').value) || 0;
        const msRequired = parseFloat(document.getElementById('ms-required').value) || 0;

        if (ppRequired <= 0 || hdpeRequired <= 0 || msRequired <= 0) {
            alert('Please enter valid recipe requirements');
            return;
        }

        const data = {
            pp_required: ppRequired,
            hdpe_required: hdpeRequired,
            ms_required: msRequired
        };

        fetch('save_recipe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Recipe saved successfully');
                } else {
                    alert('Failed to save recipe: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the recipe');
            });
    }
    </script>

</body>

</html>
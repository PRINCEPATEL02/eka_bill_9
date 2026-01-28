<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/login.php");
    exit();
}

require_once "db.php";

$message = '';
$message_class = '';

// Handle delete request
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM companies WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message = "Company deleted successfully.";
            $message_class = "success";
        } else {
            $message = "Error deleting company: " . $stmt->error;
            $message_class = "error";
        }
        $stmt->close();
    } else {
        $message = "Database error: " . $conn->error;
        $message_class = "error";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get and sanitize input
    $company_name = trim($_POST["company_name"] ?? "");
    $address      = trim($_POST["address"] ?? "");
    $gst_number   = trim($_POST["gst_number"] ?? "");
    $mobile       = trim($_POST["mobile"] ?? "");
    $state        = trim($_POST["state"] ?? "");
    $tax_type     = trim($_POST["tax_type"] ?? "");

    $response = [];
    $errors = [];

    // Validate company_name: 2-40 characters
    if (empty($company_name)) {
        $errors[] = "Company name is required.";
    } elseif (strlen($company_name) < 2 || strlen($company_name) > 40) {
        $errors[] = "Company name must be between 2 and 40 characters.";
    }

    // Validate address: 5-150 characters
    if (empty($address)) {
        $errors[] = "Address is required.";
    } elseif (strlen($address) < 5 || strlen($address) > 150) {
        $errors[] = "Address must be between 5 and 150 characters.";
    }

    // Validate gst_number: exactly 15 alphanumeric characters
    if (empty($gst_number)) {
        $errors[] = "GST number is required.";
    } elseif (!preg_match('/^[A-Z0-9]{15}$/', $gst_number)) {
        $errors[] = "GST number must be exactly 15 alphanumeric characters (A-Z, 0-9).";
    }

    // Validate mobile: +91 followed by space and 10 digits
    if (empty($mobile)) {
        $errors[] = "Mobile number is required.";
    } elseif (!preg_match('/^\+91 \d{10}$/', $mobile)) {
        $errors[] = "Mobile number must be in the format +91 followed by a space and 10 digits.";
    }

    // Validate state: 2-40 characters
    if (empty($state)) {
        $errors[] = "State is required.";
    } elseif (strlen($state) < 2 || strlen($state) > 40) {
        $errors[] = "State must be between 2 and 40 characters.";
    }

    // Validate tax_type
    if (empty($tax_type)) {
        $errors[] = "Tax type is required.";
    }

    if (!empty($errors)) {
        $response['status'] = 'error';
        $response['message'] = implode(" ", $errors);
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO companies (company_name, address, gst_number, mobile, state, tax_type) VALUES (?, ?, ?, ?, ?, ?)"
        );

        if ($stmt) {
            $stmt->bind_param("ssssss", $company_name, $address, $gst_number, $mobile, $state, $tax_type);
            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = "Company registered successfully.";
            } else {
                $response['status'] = 'error';
                $response['message'] = "Error saving data: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['status'] = 'error';
            $response['message'] = "Database error: " . $conn->error;
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
        $message_class = $response['status'];
    }
    $conn->close();
}

// Fetch all companies for display
$companies = [];
$result = $conn->query("SELECT id, company_name, address, gst_number, mobile, state, tax_type FROM companies ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
    $result->free();
}
$conn->close();

// Handle AJAX request for companies
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_SERVER["REQUEST_METHOD"] === "GET") {
    header('Content-Type: application/json');
    echo json_encode($companies);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/png" href="image.png">

    <link rel="manifest" href="manifest.json">

    <link rel="apple-touch-icon" href="icon-180.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Company Registration</title>
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

    .header .btn {
        margin-top: 20px;
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

    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        max-width: 1000px;
        margin: 0 auto;
    }

    .menu-item {
        background: white;
        padding: 30px;
        border-radius: var(--border-radius);
        text-align: center;
        box-shadow: var(--shadow);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        border: 2px solid transparent;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .menu-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-gradient);
    }

    .menu-item:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .menu-item:nth-child(1)::before {
        background: var(--success-gradient);
    }

    .menu-item:nth-child(2)::before {
        background: var(--warning-gradient);
    }

    .menu-item:nth-child(3)::before {
        background: var(--danger-gradient);
    }

    .menu-item:nth-child(4)::before {
        background: var(--primary-gradient);
    }

    .menu-item:nth-child(5)::before {
        background: var(--success-gradient);
    }

    .menu-item .icon {
        font-size: 3rem;
        margin-bottom: 15px;
        display: block;
    }

    .menu-item h3 {
        font-size: 1.5rem;
        color: #333;
        margin-bottom: 10px;
        font-weight: 600;
    }

    .menu-item p {
        color: #666;
        margin: 0;
        font-size: 1rem;
    }

    .logout-btn {
        background: var(--danger-gradient);
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
        margin-top: 30px;
    }

    .logout-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    @media (max-width: 768px) {
        .section {
            padding: 20px;
        }

        .header h1 {
            font-size: 2rem;
        }

        .menu-grid {
            grid-template-columns: 1fr;
        }

        .menu-item {
            padding: 20px;
        }

        .menu-item .icon {
            font-size: 2.5rem;
        }
    }

    /* Form specific styles */
    .form-container {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }

    .form-container h2 {
        margin-bottom: 20px;
        font-size: 1.8rem;
        color: #333;
        text-align: center;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #555;
        font-size: 0.95rem;
    }

    .required {
        color: #ef4444;
    }

    input,
    textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: var(--transition);
        background: white;
        box-sizing: border-box;
    }

    input:focus,
    textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    button {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 25px;
        background: var(--primary-gradient);
        color: white;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    #message {
        margin: 20px 0;
        padding: 15px;
        border-radius: var(--border-radius);
        text-align: center;
        font-weight: 600;
    }

    .success {
        background: var(--success-gradient);
        color: white;
    }

    .error {
        background: var(--danger-gradient);
        color: white;
    }

    .radio-group {
        display: flex;
        gap: 20px;
        margin-top: 8px;
    }

    .radio-label {
        display: flex;
        align-items: center;
        cursor: pointer;
        font-weight: normal;
    }

    .radio-label input[type="radio"] {
        width: auto;
        margin-right: 8px;
        cursor: pointer;
        accent-color: #667eea;
    }

    .radio-label span {
        font-size: 0.95rem;
        color: #555;
    }

    .back-link {
        display: block;
        text-align: center;
        margin-top: 20px;
        text-decoration: none;
        color: #667eea;
        font-weight: 600;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    /* Table styles */
    .table-container {
        margin-top: 40px;
        max-width: 100%;
        overflow-x: auto;
    }

    .table-container h2 {
        margin-bottom: 20px;
        font-size: 1.8rem;
        color: #333;
        text-align: center;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--shadow);
    }

    th,
    td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }

    th {
        background: var(--table-header-bg);
        color: white;
        font-weight: 600;
    }

    tr:hover {
        background: #f9f9f9;
    }

    .delete-btn {
        background: var(--danger-gradient);
        color: white;
        padding: 8px 16px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        transition: var(--transition);
    }

    .delete-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    </style>
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

    <!-- Add this once in your HTML (put near the end of <body>) -->
    <!-- <style>
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

            if (e.ctrlKey && (key(e) === 'x' || key(e) === 'X')) return blockWithMsg(e,
                'Cutting is disabled.');


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
    </script> -->

</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🏢 Company Registration</h1>
            <p>Add new companies to your database</p>
        </div>

        <div class="section">
            <div class="form-container">
                <form id="companyForm" action="company_form.php" method="POST">
                    <div class="form-group">
                        <label for="company_name">Company Name<span class="required">*</span></label>
                        <input type="text" id="company_name" name="company_name" required />
                    </div>
                    <div class="form-group">
                        <label for="address">Address<span class="required">*</span></label>
                        <textarea id="address" name="address" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="gst_number">GST Number<span class="required">*</span></label>
                        <input type="text" id="gst_number" name="gst_number" required />
                    </div>
                    <div class="form-group">
                        <label for="mobile">Mobile Number<span class="required">*</span></label>
                        <input type="tel" id="mobile" name="mobile" maxlength="15" required />
                    </div>
                    <div class="form-group">
                        <label for="state">State<span class="required">*</span></label>
                        <input type="text" id="state" name="state" required />
                    </div>
                    <div class="form-group">
                        <label>Tax Type<span class="required">*</span></label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="tax_type" value="SGST/CGST" checked />
                                <span>SGST/CGST</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="tax_type" value="IGST" />
                                <span>IGST</span>
                            </label>
                        </div>
                    </div>
                    <button type="submit">Save Company</button>
                </form>
                <a href="home.php" class="back-link">Back to Home</a>
            </div>

            <div class="table-container">
                <h2>All Companies</h2>
                <?php if (!empty($message)): ?>
                <div id="message" class="<?php echo htmlspecialchars($message_class); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>
                <table id="companiesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Company Name</th>
                            <th>Address</th>
                            <th>GST Number</th>
                            <th>Mobile</th>
                            <th>State</th>
                            <th>Tax Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="companiesTableBody">
                        <?php if (!empty($companies)): ?>
                        <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($company['id']); ?></td>
                            <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                            <td><?php echo htmlspecialchars($company['address']); ?></td>
                            <td><?php echo htmlspecialchars($company['gst_number']); ?></td>
                            <td><?php echo htmlspecialchars($company['mobile']); ?></td>
                            <td><?php echo htmlspecialchars($company['state']); ?></td>
                            <td><?php echo htmlspecialchars($company['tax_type']); ?></td>
                            <td>
                                <a href="?delete=<?php echo $company['id']; ?>" class="delete-btn"
                                    onclick="return confirm('Are you sure you want to delete this company?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">No companies found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    // Validation functions
    function validateCompanyName(name) {
        if (!name.trim()) return "Company name is required.";
        if (name.length < 2 || name.length > 40) return "Company name must be between 2 and 40 characters.";
        return "";
    }

    function validateAddress(address) {
        if (!address.trim()) return "Address is required.";
        if (address.length < 5 || address.length > 150) return "Address must be between 5 and 150 characters.";
        return "";
    }

    function validateGST(gst) {
        if (!gst.trim()) return "GST number is required.";
        if (!/^[A-Z0-9]{15}$/.test(gst)) return "GST number must be exactly 15 alphanumeric characters (A-Z, 0-9).";
        return "";
    }

    function validateMobile(mobile) {
        if (!mobile.trim()) return "Mobile number is required.";
        if (!/^\+91 \d{10}$/.test(mobile))
            return "Mobile number must be in the format +91 followed by a space and 10 digits.";
        return "";
    }

    function validateState(state) {
        if (!state.trim()) return "State is required.";
        if (state.length < 2 || state.length > 40) return "State must be between 2 and 40 characters.";
        return "";
    }

    function validateTaxType(taxType) {
        if (!taxType) return "Tax type is required.";
        return "";
    }

    // Function to load companies dynamically
    function loadCompanies() {
        fetch('company_form.php', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('companiesTableBody');
                tbody.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(company => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${company.id}</td>
                            <td>${company.company_name}</td>
                            <td>${company.address}</td>
                            <td>${company.gst_number}</td>
                            <td>${company.mobile}</td>
                            <td>${company.state}</td>
                            <td>${company.tax_type}</td>
                            <td>
                                <a href="?delete=${company.id}" class="delete-btn" onclick="return confirm('Are you sure you want to delete this company?')">Delete</a>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                } else {
                    tbody.innerHTML =
                        '<tr><td colspan="8" style="text-align: center;">No companies found.</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading companies:', error);
            });
    }

    // Load companies on page load
    document.addEventListener('DOMContentLoaded', loadCompanies);

    document.getElementById('companyForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        const companyName = document.getElementById('company_name').value;
        const address = document.getElementById('address').value;
        const gstNumber = document.getElementById('gst_number').value;
        const mobile = document.getElementById('mobile').value;
        const state = document.getElementById('state').value;
        const taxType = document.querySelector('input[name="tax_type"]:checked') ? document.querySelector(
            'input[name="tax_type"]:checked').value : '';

        // Client-side validation
        const errors = [];
        errors.push(validateCompanyName(companyName));
        errors.push(validateAddress(address));
        errors.push(validateGST(gstNumber));
        errors.push(validateMobile(mobile));
        errors.push(validateState(state));
        errors.push(validateTaxType(taxType));

        const filteredErrors = errors.filter(error => error !== "");
        if (filteredErrors.length > 0) {
            window.alert(filteredErrors.join(" "));
            return;
        }

        const formData = new FormData(this);

        fetch('company_form.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                window.alert(data.message);
                // Clear form on success and reload companies
                if (data.status === 'success') {
                    document.getElementById('companyForm').reset();
                    loadCompanies(); // Reload the table
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
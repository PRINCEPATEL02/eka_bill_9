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

// Fetch recently added bills (latest 5)
$recent_bills = [];
try {
    $stmt = $conn->prepare("SELECT * FROM bills WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($bill = $result->fetch_assoc()) {
        $recent_bills[] = $bill;
    }
    $stmt->close();
} catch (Exception $e) {
    $recent_bills = [];
    error_log("Error fetching recent bills: " . $e->getMessage());
}

// Fetch all bills
$all_bills = [];
try {
    $stmt = $conn->prepare("SELECT * FROM bills WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($bill = $result->fetch_assoc()) {
        $all_bills[] = $bill;
    }
    $stmt->close();
} catch (Exception $e) {
    $all_bills = [];
    error_log("Error fetching all bills: " . $e->getMessage());
}

$conn->close();

// Handle messages
$message = $_GET['message'] ?? '';
$message_type = $_GET['type'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="image.png">

    <link rel="manifest" href="manifest.json">

    <link rel="apple-touch-icon" href="icon-180.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Management</title>
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

    .upload-btn {
        background: var(--success-gradient);
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

    .upload-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .bills-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .bills-table th,
    .bills-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #e1e5e9;
    }

    .bills-table th {
        background: var(--table-header-bg);
        color: white;
        font-weight: 600;
        font-size: 1rem;
    }

    .bills-table tbody tr:hover {
        background: rgba(102, 126, 234, 0.05);
    }

    .bills-table tbody tr:last-child td {
        border-bottom: none;
    }

    .delete-btn {
        background: var(--danger-gradient);
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
        display: inline-block;
    }

    .delete-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .no-bills {
        text-align: center;
        padding: 40px;
        color: #666;
        font-size: 1.2rem;
    }

    .bill-type {
        font-weight: 600;
        color: #333;
    }

    .bill-date {
        color: #666;
        font-size: 0.9rem;
    }

    .party-name {
        color: #667eea;
        font-weight: 500;
    }

    .bill-amount {
        font-weight: 600;
        color: #28a745;
    }

    .filter-btn {
        background: var(--warning-gradient);
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        margin: 5px;
    }

    .filter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .filter-btn.active {
        background: var(--primary-gradient);
    }

    @media (max-width: 768px) {
        .section {
            padding: 20px;
        }

        .header h1 {
            font-size: 2rem;
        }

        .bills-table {
            font-size: 0.9rem;
        }

        .bills-table th,
        .bills-table td {
            padding: 10px;
        }

        .delete-btn {
            padding: 6px 12px;
            font-size: 0.8rem;
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
        <div class="header">
            <h1>📄 Bill Management</h1>
            <p>Upload, view, and manage your bills</p>
        </div>

        <div class="section">
            <a href="upload_bill_form.php" class="upload-btn">➕ Upload New Bill</a>

            <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <h2>Recently Added Bills</h2>
            <?php if (empty($recent_bills)): ?>
            <div class="no-bills">
                <p>No recent bills found.</p>
            </div>
            <?php else: ?>
            <table class="bills-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Bill No</th>
                        <th>Party Name</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_bills as $bill): ?>
                    <tr>
                        <td>
                            <div class="bill-type"><?php echo htmlspecialchars($bill['bill_type']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($bill['bill_no']); ?></td>
                        <td>
                            <div class="party-name"><?php echo htmlspecialchars($bill['party_name']); ?></div>
                        </td>
                        <td>
                            <div class="bill-date"><?php echo date('d M Y', strtotime($bill['bill_date'])); ?></div>
                        </td>
                        <td>
                            <div class="bill-amount">₹<?php echo number_format($bill['total_amount'], 2); ?></div>
                        </td>
                        <td>
                            <a href="delete_bill.php?id=<?php echo $bill['id']; ?>" class="delete-btn"
                                onclick="return confirm('Delete this bill?')">🗑️ Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <h2>All Bills</h2>
            <div>
                <button class="filter-btn active" onclick="filterBills('all')">All</button>
                <button class="filter-btn" onclick="filterBills('SALE')">Sales Bills</button>
                <button class="filter-btn" onclick="filterBills('PURCHASE')">Purchase Bills</button>
            </div>
            <?php if (empty($all_bills)): ?>
            <div class="no-bills">
                <p>No bills found.</p>
            </div>
            <?php else: ?>
            <table class="bills-table" id="all-bills-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Bill No</th>
                        <th>Party Name</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_bills as $bill): ?>
                    <tr class="bill-row" data-type="<?php echo $bill['bill_type']; ?>">
                        <td>
                            <div class="bill-type"><?php echo htmlspecialchars($bill['bill_type']); ?></div>
                        </td>
                        <td><?php echo htmlspecialchars($bill['bill_no']); ?></td>
                        <td>
                            <div class="party-name"><?php echo htmlspecialchars($bill['party_name']); ?></div>
                        </td>
                        <td>
                            <div class="bill-date"><?php echo date('d M Y', strtotime($bill['bill_date'])); ?></div>
                        </td>
                        <td>
                            <div class="bill-amount">₹<?php echo number_format($bill['total_amount'], 2); ?></div>
                        </td>
                        <td>
                            <a href="delete_bill.php?id=<?php echo $bill['id']; ?>" class="delete-btn"
                                onclick="return confirm('Delete this bill?')">🗑️ Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function filterBills(type) {
        const rows = document.querySelectorAll('.bill-row');
        const buttons = document.querySelectorAll('.filter-btn');

        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        rows.forEach(row => {
            if (type === 'all' || row.dataset.type === type) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    </script>
</body>

</html>
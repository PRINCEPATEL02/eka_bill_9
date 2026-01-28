<?php
require_once 'security.php';

// Send security headers
send_security_headers();

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/login.php");
    exit();
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

    <title>Bill Management System</title>
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

        // Stock Management Functions
        let currentStock = {
            pp: 0,
            hdpe: 0,
            ms: 0
        };

        let recipe = {
            pp: 0.5,
            hdpe: 1,
            ms: 0.2
        };

        // Load stock from localStorage
        function loadStock() {
            const savedStock = localStorage.getItem('brushStock');
            if (savedStock) {
                currentStock = JSON.parse(savedStock);
            }

            const savedRecipe = localStorage.getItem('brushRecipe');
            if (savedRecipe) {
                recipe = JSON.parse(savedRecipe);
                document.getElementById('pp-required').value = recipe.pp;
                document.getElementById('hdpe-required').value = recipe.hdpe;
                document.getElementById('ms-required').value = recipe.ms;
            }
        }

        // Calculate production capacity
        window.calculateProduction = function() {
            const ppRequired = parseFloat(document.getElementById('pp-required').value) || 0;
            const hdpeRequired = parseFloat(document.getElementById('hdpe-required').value) || 0;
            const msRequired = parseFloat(document.getElementById('ms-required').value) || 0;

            if (ppRequired <= 0 || hdpeRequired <= 0 || msRequired <= 0) {
                showMessage('Please enter valid recipe quantities');
                return;
            }

            const maxByPP = Math.floor(currentStock.pp / ppRequired);
            const maxByHDPE = Math.floor(currentStock.hdpe / hdpeRequired);
            const maxByMS = Math.floor(currentStock.ms / msRequired);

            const maxBrushes = Math.min(maxByPP, maxByHDPE, maxByMS);

            let limitingFactor = 'None';
            if (maxBrushes === maxByPP) limitingFactor = 'PP Stock';
            else if (maxBrushes === maxByHDPE) limitingFactor = 'HDPE Sheet';
            else if (maxBrushes === maxByMS) limitingFactor = 'MS Wire';

            document.getElementById('max-brushes').textContent = maxBrushes;
            document.getElementById('limiting-factor').textContent = limitingFactor;
            document.getElementById('production-result').style.display = 'block';

            showMessage(`You can produce ${maxBrushes} brushes with current stock`);
        };

        // Save recipe
        window.saveRecipe = function() {
            const ppRequired = parseFloat(document.getElementById('pp-required').value) || 0;
            const hdpeRequired = parseFloat(document.getElementById('hdpe-required').value) || 0;
            const msRequired = parseFloat(document.getElementById('ms-required').value) || 0;

            if (ppRequired <= 0 || hdpeRequired <= 0 || msRequired <= 0) {
                showMessage('Please enter valid recipe quantities');
                return;
            }

            recipe = {
                pp: ppRequired,
                hdpe: hdpeRequired,
                ms: msRequired
            };

            localStorage.setItem('brushRecipe', JSON.stringify(recipe));
            showMessage('Recipe saved successfully!');
        };

        // Update stock
        window.updateStock = function() {
            const ppStock = parseFloat(document.getElementById('update-pp').value) || 0;
            const hdpeStock = parseFloat(document.getElementById('update-hdpe').value) || 0;
            const msStock = parseFloat(document.getElementById('update-ms').value) || 0;

            if (ppStock < 0 || hdpeStock < 0 || msStock < 0) {
                showMessage('Stock quantities cannot be negative');
                return;
            }

            currentStock = {
                pp: ppStock,
                hdpe: hdpeStock,
                ms: msStock
            };

            localStorage.setItem('brushStock', JSON.stringify(currentStock));

            // Clear form
            document.getElementById('update-pp').value = '';
            document.getElementById('update-hdpe').value = '';
            document.getElementById('update-ms').value = '';

            showMessage('Stock updated successfully!');
        };

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', loadStock);

    })();
    </script>

</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📄 Bill Management System</h1>
            <p>Manage companies and generate tax invoices</p>
        </div>

        <div class="section">
            <h2>Quick Actions</h2>
            <div class="menu-grid">
                <a href="company_form.php" class="menu-item">
                    <span class="icon">➕</span>
                    <h3>Register Company</h3>
                    <p>Add new companies to your database</p>
                </a>
                <a href="bill_form.php" class="menu-item">
                    <span class="icon">🧾</span>
                    <h3>Create Tax Invoice</h3>
                    <p>Generate professional tax invoices</p>
                </a>
                <a href="estimate_form.php" class="menu-item">
                    <span class="icon">📋</span>
                    <h3>Generate Estimate</h3>
                    <p>Create detailed estimates for clients</p>
                </a>
                <a href="purchase_form.php" class="menu-item">
                    <span class="icon">📥</span>
                    <h3>Add Purchase</h3>
                    <p>Record purchase transactions</p>
                </a>
                <a href="sale_form.php" class="menu-item">
                    <span class="icon">📤</span>
                    <h3>Add Sale</h3>
                    <p>Record sales transactions</p>
                </a>
                <a href="dashboard.php" class="menu-item">
                    <span class="icon">📊</span>
                    <h3>Financial Dashboard</h3>
                    <p>View financial reports and analytics</p>
                </a>
                <a href="ledger.php" class="menu-item">
                    <span class="icon">📒</span>
                    <h3>Ledger Management</h3>
                    <p>Track company payments and outstanding amounts</p>
                </a>
                <a href="stock.php" class="menu-item">
                    <span class="icon">📦</span>
                    <h3>Stock Management</h3>
                    <p>Track available stock for PP, HDPE Sheet, MS Wire - Coming Soon</p>
                </a>
                <a href="billpdf.php" class="menu-item">
                    <span class="icon">🗂️</span>
                    <h3>All Bill Store</h3>
                    <p>sell and purchases bill here</p>
                </a>


                <!-- Stock Update Section -->
            </div>
            <div style="text-align: center;">
                <button class="logout-btn" onclick="window.location.href='logout.php'">
                    🚪 Logout
                </button>
            </div>
        </div>
    </div>


</body>

</html>
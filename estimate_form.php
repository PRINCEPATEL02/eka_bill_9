<?php
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
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Estimate</title>
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

    .form-container {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        padding: 40px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #555;
        font-size: 1rem;
    }

    .required {
        color: #ef4444;
    }

    input,
    textarea,
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
    textarea:focus,
    select:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

    .btn-add {
        background: var(--success-gradient);
        margin-top: 20px;
    }

    .btn-remove {
        background: var(--danger-gradient);
        padding: 8px;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        position: absolute;
        top: -12px;
        right: -12px;
    }

    .form-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 40px;
        flex-wrap: wrap;
    }

    /* Accordion Styles */
    .accordion-item {
        border: 2px solid #e1e5e9;
        border-radius: var(--border-radius);
        margin-bottom: 20px;
        overflow: hidden;
        background: white;
    }

    .accordion-header {
        background: var(--card-bg);
        padding: 20px 25px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 1.2rem;
        font-weight: 700;
        transition: var(--transition);
    }

    .accordion-header:hover {
        background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
    }

    .accordion-header .icon {
        transition: transform 0.3s ease;
        font-size: 1.5rem;
    }

    .accordion-item.active .accordion-header .icon {
        transform: rotate(180deg);
    }

    .accordion-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s ease-out, padding 0.4s ease-out;
        padding: 0 25px;
    }

    .accordion-item.active .accordion-content {
        max-height: 3000px;
        padding: 25px;
    }

    /* Item Row Styles */
    .item-row {
        border: 2px solid #e1e5e9;
        border-radius: var(--border-radius);
        padding: 20px;
        margin-bottom: 20px;
        position: relative;
        background: #f8f9fa;
    }

    .buyer-details,
    .consignee-details {
        background: #f8f9fa;
        padding: 20px;
        border-radius: var(--border-radius);
        border: 2px solid #e1e5e9;
    }

    .buyer-details h3,
    .consignee-details h3 {
        margin-bottom: 20px;
        color: #333;
        font-size: 1.3rem;
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

        .form-grid {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
            align-items: center;
        }

        .btn {
            width: 200px;
        }

        textarea {
            word-wrap: break-word;
            overflow-wrap: break-word;
            resize: vertical;
            max-width: 100%;
            box-sizing: border-box;
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
            <h1>📋 ESTIMATE</h1>
            <p>Fill in the details below to generate a new estimate</p>
        </div>

        <div class="section">
            <div class="form-container">
                <form id="estimateForm" method="POST" action="generate_estimate.php">
                    <div class="accordion-item active">
                        <div class="accordion-header">
                            <span>1. Estimate & Buyer Details</span>
                            <span class="icon">▼</span>
                        </div>
                        <div class="accordion-content">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="company_select">Select Saved Company</label>
                                    <select id="company_select" name="company_id">
                                        <option value="">-- Select or Enter Manually --</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="estimate_date">Estimate Date<span class="required">*</span></label>
                                    <input type="date" id="estimate_date" name="estimate_date" required />
                                </div>
                                <div class="form-group">
                                    <label>Estimate Number</label>
                                    <input type="text" value="XX/2025-26" readonly />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <div class="accordion-header">
                            <span>2. Billing & Shipping Address</span>
                            <span class="icon">▼</span>
                        </div>
                        <div class="accordion-content">
                            <div class="form-grid">
                                <div class="buyer-details">
                                    <h3>Buyer Details (Bill To)</h3>
                                    <div class="form-group">
                                        <label for="buyer_name">Company Name<span class="required">*</span></label>
                                        <input type="text" id="buyer_name" name="buyer_name" required />
                                    </div>
                                    <div class="form-group">
                                        <label for="buyer_address">Address<span class="required">*</span></label>
                                        <textarea id="buyer_address" name="buyer_address" rows="3" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="buyer_mobile">Mobile Number<span class="required">*</span></label>
                                        <input type="tel" id="buyer_mobile" name="buyer_mobile" required />
                                    </div>
                                    <div class="form-group">
                                        <label for="buyer_gst">GST Number<span class="required">*</span></label>
                                        <input type="text" id="buyer_gst" name="buyer_gst" required />
                                    </div>
                                    <div class="form-group">
                                        <label for="buyer_state">State<span class="required">*</span></label>
                                        <input type="text" id="buyer_state" name="buyer_state" required />
                                    </div>
                                </div>

                                <div class="consignee-details">
                                    <h3>Consignee Details (Ship To)</h3>
                                    <div class="form-group">
                                        <label><input type="checkbox" id="same_as_buyer" checked /> Same as
                                            Buyer</label>
                                    </div>
                                    <div class="form-group">
                                        <label for="consignee_name">Company Name<span class="required">*</span></label>
                                        <input type="text" id="consignee_name" name="consignee_name" required />
                                    </div>
                                    <div class="form-group">
                                        <label for="consignee_address">Address<span class="required">*</span></label>
                                        <textarea id="consignee_address" name="consignee_address" rows="3"
                                            required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="consignee_mobile">Mobile Number<span
                                                class="required">*</span></label>
                                        <input type="tel" id="consignee_mobile" name="consignee_mobile" required />
                                    </div>
                                    <div class="form-group">
                                        <label for="consignee_gst">GST Number<span class="required">*</span></label>
                                        <input type="text" id="consignee_gst" name="consignee_gst" required />
                                    </div>
                                    <div class="form-group">
                                        <label for="consignee_state">State<span class="required">*</span></label>
                                        <input type="text" id="consignee_state" name="consignee_state" required />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <div class="accordion-header">
                            <span>3. Items / Products</span>
                            <span class="icon">▼</span>
                        </div>
                        <div class="accordion-content">
                            <div id="itemsContainer">
                                <div class="item-row">
                                    <button type="button" class="btn-remove" onclick="removeItem(this)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                            fill="currentColor" viewBox="0 0 16 16">
                                            <path
                                                d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z" />
                                            <path
                                                d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" />
                                        </svg>
                                    </button>
                                    <div class="form-group">
                                        <label>Description<span class="required">*</span></label>
                                        <input type="text" name="item_description[]" list="description_options"
                                            required />
                                        <datalist id="description_options">
                                            <option value="Main Brush">
                                            <option value="Side Brush">
                                        </datalist>
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>HSN/SAC<span class="required">*</span></label>
                                            <input type="text" name="item_hsn[]" value="9603" readonly required />
                                        </div>
                                        <div class="form-group">
                                            <label>Qty<span class="required">*</span></label>
                                            <input type="number" name="item_quantity[]" step="0.01" min="0" required />
                                        </div>
                                        <div class="form-group">
                                            <label>Unit<span class="required">*</span></label>
                                            <input type="text" name="item_unit[]" value="NOS" readonly required />
                                        </div>
                                        <div class="form-group">
                                            <label>Rate (₹)<span class="required">*</span></label>
                                            <input type="number" name="item_rate[]" step="0.01" min="0" required />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-add" onclick="addItem()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    viewBox="0 0 16 16">
                                    <path
                                        d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4" />
                                </svg>
                                Add Item
                            </button>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <div class="accordion-header">
                            <span>4. Taxes & Other Charges</span>
                            <span class="icon">▼</span>
                        </div>
                        <div class="accordion-content">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="tax_type">Tax Type<span class="required">*</span></label>
                                    <select id="tax_type" name="tax_type" required>
                                        <option value="SGST/CGST">SGST/CGST</option>
                                        <option value="IGST">IGST</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="cgst_rate">CGST Rate (%)</label>
                                    <input type="number" id="cgst_rate" name="cgst_rate" value="9" step="0.01"
                                        min="0" />
                                </div>
                                <div class="form-group">
                                    <label for="sgst_rate">SGST Rate (%)</label>
                                    <input type="number" id="sgst_rate" name="sgst_rate" value="9" step="0.01"
                                        min="0" />
                                </div>
                                <div class="form-group">
                                    <label for="igst_rate">IGST Rate (%)</label>
                                    <input type="number" id="igst_rate" name="igst_rate" value="18" step="0.01"
                                        min="0" />
                                </div>
                                <div class="form-group">
                                    <label for="freight">Freight & Forwarding (₹)</label>
                                    <input type="number" id="freight" name="freight" value="0" step="0.01" min="0" />
                                </div>
                                <div class="form-group">
                                    <label for="round_off">Round Off (₹)</label>
                                    <input type="number" id="round_off" name="round_off" value="0" step="0.01" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <div class="accordion-header">
                            <span>5. Bank Details & Terms</span>
                            <span class="icon">▼</span>
                        </div>
                        <div class="accordion-content">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="bank_name">Bank Name</label>
                                    <input type="text" id="bank_name" name="bank_name" value="Bank of Baroda" />
                                </div>
                                <div class="form-group">
                                    <label for="bank_account">Account Number</label>
                                    <input type="text" id="bank_account" name="bank_account" value="01540200001092" />
                                </div>
                                <div class="form-group">
                                    <label for="bank_ifsc">IFSC Code</label>
                                    <input type="text" id="bank_ifsc" name="bank_ifsc" value="BARB0VISNAG" />
                                </div>
                                <div class="form-group">
                                    <label for="bank_branch">Branch</label>
                                    <input type="text" id="bank_branch" name="bank_branch" value="VISNAGAR - 384315" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="terms">Terms (one per line)</label>
                                <textarea id="terms" name="terms" rows="4">Goods will be dispatched after 100% payment.
Goods once sold will not be taken back.
Interest @ 18% p.a. will be charged if the payment is not made within the stipulated time.</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">Generate Estimate</button>
                        <a href="home.php" class="btn btn-secondary">Back to Home</a>
                        <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('estimate_date').value = today;

        loadCompanies();

        document.getElementById('company_select').addEventListener('change', function() {
            const companyId = this.value;
            if (companyId) {
                loadCompanyDetails(companyId);
            }
        });

        document.getElementById('same_as_buyer').addEventListener('change', function() {
            if (this.checked) {
                copyBuyerToConsignee();
            }
        });

        document.getElementById('tax_type').addEventListener('change', function() {
            const isIgst = this.value === 'IGST';
            document.getElementById('cgst_rate').value = isIgst ? 0 : 9;
            document.getElementById('sgst_rate').value = isIgst ? 0 : 9;
            document.getElementById('igst_rate').value = isIgst ? 18 : 0;
        });

        // Accordion functionality
        const accordionItems = document.querySelectorAll('.accordion-item');
        accordionItems.forEach(item => {
            const header = item.querySelector('.accordion-header');
            header.addEventListener('click', () => {
                item.classList.toggle('active');
            });
        });
    });

    function loadCompanies() {
        fetch('get_companies.php')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('company_select');
                if (data.success && data.companies) {
                    data.companies.forEach(company => {
                        const option = document.createElement('option');
                        option.value = company.id;
                        option.textContent = company.company_name;
                        select.appendChild(option);
                    });
                }
            });
    }

    function loadCompanyDetails(companyId) {
        fetch(`get_company.php?id=${companyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.company) {
                    const c = data.company;
                    document.getElementById('buyer_name').value = c.company_name || '';
                    document.getElementById('buyer_mobile').value = c.mobile || '';
                    document.getElementById('buyer_address').value = c.address || '';
                    document.getElementById('buyer_gst').value = c.gst_number || '';
                    document.getElementById('buyer_state').value = c.state || '';
                    document.getElementById('tax_type').value = c.tax_type || 'SGST/CGST';
                    document.getElementById('tax_type').dispatchEvent(new Event('change'));
                    if (document.getElementById('same_as_buyer').checked) {
                        copyBuyerToConsignee();
                    }
                }
            });
    }

    function copyBuyerToConsignee() {
        document.getElementById('consignee_name').value = document.getElementById('buyer_name').value;
        document.getElementById('consignee_mobile').value = document.getElementById('buyer_mobile').value;
        document.getElementById('consignee_address').value = document.getElementById('buyer_address').value;
        document.getElementById('consignee_gst').value = document.getElementById('buyer_gst').value;
        document.getElementById('consignee_state').value = document.getElementById('buyer_state').value;
    }

    function addItem() {
        const container = document.getElementById('itemsContainer');
        const itemRow = document.createElement('div');
        itemRow.className = 'item-row';
        itemRow.innerHTML = `
        <button type="button" class="btn-remove" onclick="removeItem(this)">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
        </button>
        <div class="form-group"><label>Description<span class="required">*</span></label><input type="text" name="item_description[]" list="description_options" required /></div>
        <div class="form-grid">
            <div class="form-group"><label>HSN/SAC<span class="required">*</span></label><input type="text" name="item_hsn[]" value="9603" readonly required /></div>
            <div class="form-group"><label>Qty<span class="required">*</span></label><input type="number" name="item_quantity[]" step="0.01" min="0" required /></div>
            <div class="form-group"><label>Unit<span class="required">*</span></label><input type="text" name="item_unit[]" value="NOS" readonly required /></div>
            <div class="form-group"><label>Rate (₹)<span class="required">*</span></label><input type="number" name="item_rate[]" step="0.01" min="0" required /></div>
        </div>
      `;
        container.appendChild(itemRow);
    }

    function removeItem(button) {
        const container = document.getElementById('itemsContainer');
        if (container.children.length > 1) {
            button.closest('.item-row').remove();
        } else {
            alert('At least one item is required.');
        }
    }
    </script>
</body>

</html>
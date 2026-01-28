<?php
require_once "db.php";
$estimate_id = $_GET['id'] ?? 0;

if (!$estimate_id) { die("Estimate ID is required"); }

$stmt = $conn->prepare("SELECT * FROM estimates WHERE id = ?");
$stmt->bind_param("i", $estimate_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows == 0) { die("Estimate not found"); }

$estimate = $result->fetch_assoc();
$items = json_decode($estimate['items'], true);

$estimate_date = date('d-m-Y', strtotime($estimate['estimate_date']));
$filename_date = date('Y.m.d', strtotime($estimate['estimate_date']));
$terms_array = array_filter(array_map('trim', explode("\n", $estimate['terms'])));

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="image.png">

    <link rel="manifest" href="manifest.json">

    <link rel="apple-touch-icon" href="icon-180.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Estimate - <?php echo htmlspecialchars($estimate['estimate_number']); ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
    /* =========================
   GLOBAL - SCREEN
========================= */

    * {
        box-sizing: border-box;
    }

    body {
        font-family: "Segoe UI", Arial, sans-serif;
        margin: 0;
        background: #f2f2f2;
    }

    /* A4 FIXED PAGE */
    .page {
        width: 210mm;
        min-height: 297mm;
        margin: 0 auto;
        background: #fff;
        padding: 1mm;
        border: 0;
    }

    /* MAIN BORDER BOX */
    .main-box {
        border: 0.6mm solid #000;
        padding: 10mm;
    }

    /* =========================
   PRINT MODE — REAL A4
========================= */

    @media print {

        html,
        body {
            margin: 0;
            width: 210mm;
            height: auto;
            background: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        @page {
            size: A4 portrait;
            margin: 6mm;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0;
            padding: 8mm;
        }

        .main-box {
            border: 0.6mm solid #000;
            padding: 8mm;
            page-break-inside: avoid;
        }

        .no-print {
            display: none !important;
        }
    }

    /* =========================
   HEADER (TABLE BASED)
========================= */

    .header-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 1mm;

    }

    .header-table td {
        border: 0.6mm solid #000;

    }

    .header-logo {
        width: 32mm;
        text-align: center;
        padding: 4mm;
        padding-right: 5mm;
    }

    .header-green {
        background: linear-gradient(180deg, #cfe9c8, #b7e0a8);
        text-align: center;
        padding: 4mm;
    }

    .header-title {
        font-size: 9mm;
        font-weight: 800;
        margin: 0;
    }

    .header-address {
        font-size: 3.3mm;
        font-weight: 600;
    }


    /* =========================
   INVOICE TITLE
========================= */

    .invoice-title {

        border: 0.6mm solid #000;
        border-top: 0;
        text-align: center;
        padding: 3mm;
        font-size: 6mm;
        font-weight: 700;
        background: linear-gradient(#cfe7ff, #97c9f7);
        border-top: 0.6mm solid #000;
    }


    /* =========================
   META PANEL
========================= */

    .meta-table {
        border-collapse: collapse;
        margin-left: auto;
        font-size: 3.5mm;
        margin-top: 2mm;
    }

    .meta-table td {
        border: 0.4mm solid #000;
        padding: 2mm 3mm;
    }

    .meta-table td:first-child {
        font-weight: 700;
        background: #eaf3ff;
    }


    /* =========================
   BUYER / CONSIGNEE (TABLE!)
========================= */

    .party-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0.5mm;
        margin-top: 2mm;
    }

    .party-table td {
        border: 0.6mm solid #000;
        vertical-align: top;
    }

    .party-title {
        text-align: center;
        padding: 2.5mm;
        font-weight: 700;
        background: linear-gradient(#cfe3f7, #9fc3e8);
        border-bottom: 0.6mm solid #000;
    }

    .party-body {
        padding: 3mm;
        font-size: 3.3mm;
        font-weight: 700;
        line-height: 1.25;
    }

    /* ========= NEW — LABEL ALIGNMENT ======== */

    .pb-row {
        display: flex;
        margin-bottom: 2px;
    }

    .pb-label {
        width: 95px;
        font-weight: 600;
    }

    .pb-value {
        flex: 1;
        line-height: 1.25;
        font-weight: 500;
    }


    /* =========================
   ITEMS TABLE
========================= */

    .items-table {
        width: 100%;
        border-collapse: collapse;
        border: 0.6mm solid #000;
        margin-top: 2mm;
        font-size: 3.2mm;
    }

    .items-table th {
        border: 0.4mm solid #000;
        padding: 2.5mm;
        background: linear-gradient(#f7e6b5, #f3d87d);
        font-weight: 700;
        text-align: center;
    }

    .items-table td {
        border: 0.4mm solid #000;
        padding: 2.5mm;
    }

    .right {
        text-align: right;
    }

    .center {
        text-align: center;
    }


    /* =========================
   TOTALS TABLE
========================= */

    .totals-table {
        width: 40%;
        border-collapse: collapse;
        border: 0.6mm solid #000;
        margin-top: 2mm;
        margin-left: auto;
        font-size: 3.2mm;

    }

    .totals-table td {
        border-bottom: 0.3mm solid #bbb;
        padding: 2mm 3mm;
        font-size: 3mm;
        font-weight: 600;
    }

    .totals-table td:last-child {
        font-weight: 700;
        text-align: right;
    }

    .grand td {
        background: linear-gradient(#cfe9cf, #b1dbab);
        font-size: 4mm;
        font-weight: 700;
    }


    /* =========================
   TEXT BLOCKS
========================= */

    .amount-words {
        margin-top: 3mm;
        padding: 3mm;
        text-align: center;
        border: 0.5mm solid #8fc78e;
        background: linear-gradient(#eaf7e7, #cfe9cf);
        text-transform: uppercase;
        font-weight: 700;
    }


    /* =========================
   FOOTER (TABLE)
========================= */

    .footer-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0.5mm;
        margin-top: 2mm;
        table-layout: fixed;
    }

    .footer-table td {
        border: 0.6mm solid #000;
        padding: 3mm;
        font-size: 3.2mm;
        text-align: center;
        font-weight: 700;

    }

    .footer-table td:nth-child(1) {
        width: 35%;


    }

    .footer-table td:nth-child(2) {
        width: 35%;

    }

    .footer-table td:nth-child(3) {
        width: 28%;
        vertical-align: bottom;
    }

    .items-table,
    .totals-table,
    .footer-table {
        page-break-inside: avoid;
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

    <div class="top-actions" style="text-align:right;margin-bottom:10px;">
        <button class="action-btn print-btn" onclick="printEstimate()">🖨️ Print</button>
        <button class="action-btn new-btn" onclick="window.location.href='estimate_form.php'">➕ Create New
            Estimate</button>
    </div>

    <div class="page">
        <div class="main-box">

            <!-- HEADER -->
            <table class="header-table">
                <tr>
                    <td class="header-logo">
                        <img src="image.png">
                    </td>
                    <td class="header-green">
                        <h1 class="header-title">EKA MANUFACTURING</h1>
                        <div class="header-address">
                            47-D, VRUNDAVAN VIBHAG, HERITAGE TOWNSHIP, NEAR KADA CHOKDI<br>
                            VISNAGAR, MAHESANA, GUJARAT - 384315<br>
                            MOBILE: 9558348763 / 9427323574<br>
                            EMAIL: ekamanufacturing2024@gmail.com<br>
                            GSTIN: 24FCFPP8490F1Z8
                        </div>
                    </td>
                </tr>
            </table>

            <div class="invoice-title">ESTIMATE</div>

            <!-- META -->
            <div class="meta-row">
                <table class="meta-table">
                    <tr>
                        <td>DATE :</td>
                        <td><?php echo $estimate_date; ?></td>
                    </tr>
                    <tr>
                        <td>ESTIMATE NO :</td>
                        <td><?php echo $estimate['estimate_number']; ?></td>
                    </tr>
                </table>
            </div>

            <!-- BUYER & CONSIGNEE -->
            <table class="party-table">
                <tr>
                    <td>
                        <div class="party-title">BUYER (BILL TO)</div>
                        <div class="party-body">

                            <div class="pb-row">
                                <span class="pb-label">NAME:</span>
                                <span class="pb-value"><?php echo htmlspecialchars($estimate['buyer_name']); ?></span>
                            </div>

                            <div class="pb-row">
                                <span class="pb-label">ADDRESS:</span>
                                <span
                                    class="pb-value"><?php echo nl2br(htmlspecialchars($estimate['buyer_address'])); ?></span>
                            </div>

                            <div class="pb-row">
                                <span class="pb-label">MOBILE:</span>
                                <span class="pb-value"><?php echo htmlspecialchars($estimate['buyer_mobile']); ?></span>
                            </div>

                            <div class="pb-row">
                                <span class="pb-label">GSTIN:</span>
                                <span class="pb-value"><?php echo htmlspecialchars($estimate['buyer_gst']); ?></span>
                            </div>

                            <div class="pb-row">
                                <span class="pb-label">STATE:</span>
                                <span class="pb-value"><?php echo htmlspecialchars($estimate['buyer_state']); ?></span>
                            </div>

                        </div>
                    </td>

                    <td>
                        <div class="party-title">CONSIGNEE (SHIP TO)</div>
                        <div class="party-body">

                            <div class="pb-row">
                                <span class="pb-label">NAME:</span>
                                <span
                                    class="pb-value"><?php echo htmlspecialchars($estimate['consignee_name']); ?></span>
                            </div>

                            <div class="pb-row">
                                <span class="pb-label">ADDRESS:</span>
                                <span
                                    class="pb-value"><?php echo nl2br(htmlspecialchars($estimate['consignee_address'])); ?></span>
                            </div>

                            <div class="pb-row">
                                <span class="pb-label">MOBILE:</span>
                                <span
                                    class="pb-value"><?php echo htmlspecialchars($estimate['consignee_mobile']); ?></span>
                            </div>

                            <div class="pb-row">
                                <span class="pb-label">GSTIN:</span>
                                <span
                                    class="pb-value"><?php echo htmlspecialchars($estimate['consignee_gst']); ?></span>
                            </div>

                            <div class="pb-row">
                                <span class="pb-label">STATE:</span>
                                <span
                                    class="pb-value"><?php echo htmlspecialchars($estimate['consignee_state']); ?></span>
                            </div>

                        </div>
                    </td>
                </tr>
            </table>

            <!-- ITEMS -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>SL</th>
                        <th>DESCRIPTION</th>
                        <th>HSN</th>
                        <th>QTY</th>
                        <th>UNIT</th>
                        <th>RATE</th>
                        <th>AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sl=1; foreach($items as $it): ?>
                    <tr>
                        <td class="center"><?php echo $sl++; ?></td>
                        <td><?php echo htmlspecialchars($it['description']); ?></td>
                        <td class="center"><?php echo $it['hsn']; ?></td>
                        <td class="center"><?php echo $it['quantity']; ?></td>
                        <td class="center"><?php echo $it['unit']; ?></td>
                        <td class="right">₹ <?php echo number_format($it['rate'],2); ?></td>
                        <td class="right">₹ <?php echo number_format($it['amount'],2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- TOTALS -->
            <table class="totals-table">
                <tr>
                    <td>Total Amount</td>
                    <td>₹ <?php echo number_format($estimate['total_amount'],2); ?></td>
                </tr>

                <?php if($estimate['tax_type']=="SGST/CGST"): ?>
                <?php if($estimate['cgst_amount']>0): ?>
                <tr>
                    <td>CGST</td>
                    <td>₹ <?php echo number_format($estimate['cgst_amount'],2); ?></td>
                </tr>
                <?php endif; ?>
                <?php if($estimate['sgst_amount']>0): ?>
                <tr>
                    <td>SGST</td>
                    <td>₹ <?php echo number_format($estimate['sgst_amount'],2); ?></td>
                </tr>
                <?php endif; ?>
                <?php else: ?>
                <?php if($estimate['igst_amount']>0): ?>
                <tr>
                    <td>IGST</td>
                    <td>₹ <?php echo number_format($estimate['igst_amount'],2); ?></td>
                </tr>
                <?php endif; ?>
                <?php endif; ?>

                <?php if($estimate['freight']>0): ?>
                <tr>
                    <td>Freight</td>
                    <td>₹ <?php echo number_format($estimate['freight'],2); ?></td>
                </tr>
                <?php endif; ?>

                <?php if($estimate['round_off']!=0): ?>
                <tr>
                    <td>Round Off</td>
                    <td>₹ <?php echo number_format($estimate['round_off'],2); ?></td>
                </tr>
                <?php endif; ?>

                <tr class="grand">
                    <td>Grand Total</td>
                    <td>₹ <?php echo number_format($estimate['grand_total'],2); ?></td>
                </tr>
            </table>

            <!-- AMOUNT IN WORDS -->
            <div class="amount-words">
                <?php echo htmlspecialchars($estimate['amount_in_words']); ?>
            </div>

            <!-- FOOTER -->
            <table class="footer-table">
                <tr>
                    <td>
                        <h3><strong>Bank Details:</strong><br>
                        </h3>
                        Bank: <?php echo $estimate['bank_name']; ?><br>
                        A/C: <?php echo $estimate['bank_account']; ?><br>
                        IFSC: <?php echo $estimate['bank_ifsc']; ?><br>
                        <?php echo $estimate['bank_branch']; ?>
                    </td>

                    <td>
                        <h3><strong>Terms & Conditions</strong></h3>
                        <ol>
                            <?php foreach($terms_array as $t): ?>
                            <li><?php echo htmlspecialchars($t); ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </td>

                    <td>
                        <h3>Authorized Signature</h3>
                    </td>
                </tr>
            </table>

        </div><!-- main-box -->
    </div><!-- page -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
    async function printEstimate() {
        const {
            jsPDF
        } = window.jspdf;

        // Construct the filename
        const date = '<?php echo $filename_date; ?>';
        const amount = '<?php echo number_format($estimate['grand_total'], 0, '', ''); ?>';
        const company = '<?php echo htmlspecialchars($estimate['buyer_name']); ?>';
        const filename = 'Estimate_' + date + '_RS.' + amount + '_' + company + '.pdf';

        // Get the estimate content
        const estimateElement = document.querySelector('.page');

        try {
            // Use html2canvas to capture the estimate as image
            const canvas = await html2canvas(estimateElement, {
                scale: 2,
                useCORS: true,
                allowTaint: true
            });

            // Calculate PDF dimensions (A4)
            const imgWidth = 210; // A4 width in mm
            const pageHeight = 297; // A4 height in mm
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            // Create PDF
            const pdf = new jsPDF('p', 'mm', 'a4');

            // Fit entire content on one page by scaling if necessary
            const finalHeight = Math.min(imgHeight, pageHeight);
            pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, imgWidth, finalHeight);

            // Save the PDF with custom filename
            pdf.save(filename);
        } catch (error) {
            console.error('Error generating PDF:', error);
            // Fallback to regular print
            window.print();
        }
    }
    </script>

</body>

</html>
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

require_once "db.php";

// Handle delete requests
if ((isset($_GET['delete']) && isset($_GET['type']) && isset($_GET['id'])) || (isset($_POST['delete']) && isset($_POST['type']) && isset($_POST['id']))) {
    $id = (int)($_POST['id'] ?? $_GET['id']);
    $type = $_POST['type'] ?? $_GET['type'];

    $response = [];
    if ($type === 'purchase') {
        $stmt = $conn->prepare("DELETE FROM purchases WHERE id = ?");
    } elseif ($type === 'sale') {
        // Redirect to delete_bill.php for proper stock restoration
        header("Location: delete_bill.php?id=" . $id . "&source=dashboard");
        exit();
    }

    if (isset($stmt)) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // Recalculate totals after deletion
            $purchases = $conn->query("SELECT SUM(total_amount) as total FROM purchases")->fetch_assoc()['total'] ?? 0;
            $sales = $conn->query("SELECT SUM(total_amount) as total FROM sales")->fetch_assoc()['total'] ?? 0;
            $profit = $sales - $purchases;

            $response['status'] = 'success';
            $response['message'] = "Record deleted successfully.";
            $response['totals'] = [
                'purchases' => number_format($purchases, 2),
                'sales' => number_format($sales, 2),
                'profit' => number_format($profit, 2),
                'profit_class' => $profit >= 0 ? 'profit' : 'loss'
            ];
        } else {
            $response['status'] = 'error';
            $response['message'] = "Error deleting record.";
        }
        $stmt->close();
    } else {
        $response['status'] = 'error';
        $response['message'] = "Invalid type.";
    }

    // Check if it's an AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        $conn->close();
        exit();
    } else {
        // For non-AJAX requests, set message variables
        $message = $response['message'];
    }
}

// Fetch data for dashboard
$purchases = $conn->query("SELECT SUM(total_amount) as total FROM purchases")->fetch_assoc()['total'] ?? 0;
$sales = $conn->query("SELECT SUM(grand_total) as total FROM bills")->fetch_assoc()['total'] ?? 0;
$profit = $sales - $purchases;

// Pagination setup
$limit = 10;
$purchase_page = isset($_GET['purchase_page']) ? max(1, (int)$_GET['purchase_page']) : 1;
$sale_page = isset($_GET['sale_page']) ? max(1, (int)$_GET['sale_page']) : 1;

$purchase_offset = ($purchase_page - 1) * $limit;
$sale_offset = ($sale_page - 1) * $limit;

$purchase_total = $conn->query("SELECT COUNT(*) as total FROM purchases")->fetch_assoc()['total'] ?? 0;
$sale_total = $conn->query("SELECT COUNT(*) as total FROM bills")->fetch_assoc()['total'] ?? 0;

$purchase_pages = ceil($purchase_total / $limit);
$sale_pages = ceil($sale_total / $limit);

// Ensure page doesn't exceed total pages
$purchase_page = min($purchase_page, $purchase_pages ?: 1);
$sale_page = min($sale_page, $sale_pages ?: 1);

$purchase_details = $conn->query("SELECT * FROM purchases ORDER BY purchase_date DESC LIMIT $limit OFFSET $purchase_offset");
$sale_details = $conn->query("SELECT * FROM bills ORDER BY invoice_date DESC LIMIT $limit OFFSET $sale_offset");

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

    <title>Financial Dashboard</title>
    <script src="js/jspdf.umd.min.js"></script>
    <script src="js/jspdf.plugin.autotable.min.js"></script>
    <script src="js/xlsx.full.min.js"></script>
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

    .summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        padding: 40px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    .card {
        background: white;
        padding: 30px;
        border-radius: var(--border-radius);
        text-align: center;
        box-shadow: var(--shadow);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        border: 2px solid transparent;
    }

    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-gradient);
    }

    .card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .card:nth-child(1)::before {
        background: var(--success-gradient);
    }

    .card:nth-child(2)::before {
        background: var(--warning-gradient);
    }

    .card:nth-child(3)::before {
        background: var(--danger-gradient);
    }

    .card h3 {
        font-size: 1.2rem;
        color: #666;
        margin-bottom: 15px;
        font-weight: 600;
    }

    .card p {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
    }

    .card .profit {
        background: var(--success-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .card .loss {
        background: var(--danger-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
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

    .filter-container {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: var(--border-radius);
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .filter-row {
        display: flex;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 15px;
    }

    .filter-row:last-child {
        margin-bottom: 0;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        min-width: 200px;
        flex: 1;
    }

    .filter-group label {
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    .filter-group input {
        padding: 10px 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: var(--transition);
        background: white;
    }

    .filter-group input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .date-range {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .date-range input {
        flex: 1;
    }

    .date-range::before {
        content: '📅';
        font-size: 1.2rem;
        margin-right: 5px;
    }

    .filter-icon {
        font-size: 1.2rem;
        margin-right: 8px;
    }

    .download-container {
        display: flex;
        gap: 15px;
        align-items: center;
        justify-content: center;
        margin-top: 20px;
    }

    .download-select {
        padding: 10px 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.9rem;
        background: white;
        transition: var(--transition);
    }

    .download-select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .download-btn {
        background: var(--primary-gradient);
        color: white;
        padding: 10px 20px;
        border-radius: 20px;
        border: none;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .download-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        margin-top: 20px;
    }

    .pagination a {
        background: var(--primary-gradient);
        color: white;
        padding: 10px 15px;
        border-radius: 20px;
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
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

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--shadow);
    }

    thead {
        background: var(--table-header-bg);
        color: white;
    }

    th {
        padding: 20px 15px;
        text-align: left;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    td {
        padding: 15px;
        border-bottom: 1px solid #eee;
        transition: var(--transition);
    }

    tbody tr {
        transition: var(--transition);
    }

    tbody tr:nth-child(even) {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    tbody tr:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: scale(1.01);
    }

    .delete-btn {
        background: var(--danger-gradient);
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        text-decoration: none;
        font-size: 0.8rem;
        font-weight: 600;
        transition: var(--transition);
        display: inline-block;
        border: none;
        cursor: pointer;
    }

    .delete-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .edit-btn {
        background: var(--warning-gradient);
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        text-decoration: none;
        font-size: 0.8rem;
        font-weight: 600;
        transition: var(--transition);
        display: inline-block;
        border: none;
        cursor: pointer;
        margin-right: 5px;
    }

    .edit-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
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
        margin: 5% auto;
        padding: 30px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        width: 90%;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
        position: relative;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #eee;
    }

    .modal-header h2 {
        margin: 0;
        color: #333;
        font-size: 1.8rem;
    }

    .close {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: var(--transition);
    }

    .close:hover {
        color: #333;
        transform: scale(1.2);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #555;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: var(--transition);
        background: white;
        box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .form-row .form-group {
        margin-bottom: 0;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #eee;
    }

    .btn {
        padding: 12px 25px;
        border: none;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: var(--primary-gradient);
        color: white;
    }

    .btn-primary:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: scale(1.05);
    }

    /* Hide modal on mobile when too small */
    @media (max-width: 600px) {
        .modal-content {
            margin: 10px;
            padding: 20px;
            width: calc(100% - 20px);
        }

        .form-row {
            grid-template-columns: 1fr;
        }
    }

    .message {
        background: var(--success-gradient);
        color: white;
        padding: 15px;
        border-radius: var(--border-radius);
        margin: 20px 40px;
        text-align: center;
        font-weight: 600;
    }

    /* Responsive Design for All Devices */

    /* Large Desktop (1440px and up) */
    @media (min-width: 1440px) {
        .container {
            max-width: 1600px;
        }

        .header h1 {
            font-size: 4rem;
        }

        .card p {
            font-size: 3rem;
        }
    }

    /* Desktop (1024px to 1439px) */
    @media (min-width: 1024px) and (max-width: 1439px) {
        .container {
            max-width: 1200px;
        }

        .summary {
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            padding: 35px;
        }

        .section {
            padding: 35px;
        }
    }

    /* Tablet Landscape (769px to 1023px) */
    @media (min-width: 769px) and (max-width: 1023px) {
        .container {
            max-width: 900px;
        }

        .summary {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            padding: 30px;
        }

        .section {
            padding: 40px;
        }

        .header h1 {
            font-size: 2.5rem;
        }

        .card p {
            font-size: 2rem;
        }

        table {
            font-size: 0.85rem;
        }
    }

    /* Tablet Portrait (601px to 768px) */
    @media (min-width: 601px) and (max-width: 768px) {
        .summary {
            grid-template-columns: 1fr;
            padding: 25px;
        }

        .section {
            padding: 25px;
        }

        .header {
            padding: 25px;
        }

        .header h1 {
            font-size: 2.2rem;
        }

        .card {
            padding: 25px;
        }

        .card p {
            font-size: 1.8rem;
        }

        .section h2 {
            font-size: 1.6rem;
        }

        table {
            font-size: 0.8rem;
        }

        th,
        td {
            padding: 12px 8px;
        }

        .delete-btn {
            padding: 6px 12px;
            font-size: 0.75rem;
        }
    }

    /* Mobile Large (481px to 600px) */
    @media (min-width: 481px) and (max-width: 600px) {
        .container {
            margin: 10px;
        }

        .summary {
            grid-template-columns: 1fr;
            padding: 20px;
            gap: 15px;
        }

        .section {
            padding: 20px;
        }

        .header {
            padding: 20px;
        }

        .header h1 {
            font-size: 2rem;
        }

        .header p {
            font-size: 1.1rem;
        }

        .card {
            padding: 20px;
        }

        .card h3 {
            font-size: 1.1rem;
        }

        .card p {
            font-size: 1.6rem;
        }

        .section h2 {
            font-size: 1.4rem;
        }

        /* Make table horizontally scrollable on mobile */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            min-width: 800px;
            /* Ensure table doesn't shrink too much */
            font-size: 0.75rem;
        }

        th,
        td {
            padding: 10px 6px;
            white-space: nowrap;
        }

        .delete-btn {
            padding: 5px 10px;
            font-size: 0.7rem;
            min-width: 60px;
        }
    }

    /* Mobile Small (320px to 480px) */
    @media (max-width: 480px) {
        body {
            padding: 10px;
        }

        .container {
            margin: 5px;
            border-radius: 10px;
        }

        .summary {
            grid-template-columns: 1fr;
            padding: 15px;
            gap: 10px;
        }

        .section {
            padding: 15px;
        }

        .header {
            padding: 15px;
        }

        .header h1 {
            font-size: 1.8rem;
        }

        .header p {
            font-size: 1rem;
        }

        .card {
            padding: 15px;
        }

        .card h3 {
            font-size: 1rem;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 1.4rem;
        }

        .section h2 {
            font-size: 1.3rem;
            margin-bottom: 20px;
        }

        /* Make date range stack vertically on small screens */
        .date-range {
            flex-direction: column;
            gap: 5px;
        }

        .date-range input {
            width: 100%;
        }

        /* Enhanced table responsiveness */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0;
            padding: 0 80px 0 0;
            position: relative;
        }

        table {
            min-width: 800px;
            font-size: 0.7rem;
            margin-top: 15px;
            table-layout: auto;
            /* Changed from fixed to auto for better content fitting */
        }

        th,
        td {
            padding: 8px 4px;
            white-space: normal;
            /* Allow text wrapping */
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
        }

        th {
            font-size: 0.65rem;
        }

        /* Set fixed width for action column */
        th:last-child,
        td:last-child {
            width: 80px;
            position: sticky;
            right: 0;
            background: white;
            border-left: 2px solid #ddd;
            z-index: 2;
            white-space: nowrap;
            /* Keep action column text nowrap */
        }

        .table-container thead th:last-child {
            background: #667eea;
            color: white;
        }

        .table-container tbody tr:hover td:last-child {
            background: white !important;
        }

        .delete-btn {
            padding: 4px 8px;
            font-size: 0.65rem;
            min-width: 50px;
            border-radius: 15px;
        }

        .message {
            margin: 15px;
            padding: 12px;
            font-size: 0.9rem;
        }
    }

    /* Extra Small Mobile (320px and below) */
    @media (max-width: 320px) {
        .header h1 {
            font-size: 1.6rem;
        }

        .card p {
            font-size: 1.2rem;
        }

        table {
            font-size: 0.65rem;
        }

        .delete-btn {
            padding: 3px 6px;
            font-size: 0.6rem;
            min-width: 45px;
        }
    }

    /* Touch-friendly interactions for mobile */
    @media (hover: none) and (pointer: coarse) {
        .card:hover {
            transform: none;
            /* Remove hover effects on touch devices */
        }

        .delete-btn:hover {
            transform: none;
        }

        .delete-btn:active {
            transform: scale(0.95);
            /* Add active state for touch feedback */
        }

        tbody tr:hover {
            background: inherit;
            /* Remove hover effects on touch devices */
            color: inherit;
            transform: none;
        }
    }

    /* High DPI displays */
    @media (-webkit-min-device-pixel-ratio: 2),
    (min-resolution: 192dpi) {
        .header::before {
            background-size: 50px 50px;
        }
    }

    /* Print styles */
    @media print {
        body {
            background: white;
            color: black;
        }

        .container {
            box-shadow: none;
            border: 1px solid #ccc;
        }

        .delete-btn {
            display: none;
            /* Hide delete buttons when printing */
        }

        .card:hover,
        tbody tr:hover {
            background: inherit;
            transform: none;
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
            <h1>Financial Dashboard</h1>
            <p>Monitor your business performance at a glance</p>
        </div>

        <?php if (isset($message)) echo "<div class='message'>$message</div>"; ?>

        <div class="summary">
            <div class="card">
                <h3>Total Purchases</h3>
                <p>₹<?php echo number_format($purchases, 2); ?></p>
            </div>
            <div class="card">
                <h3>Total Sales</h3>
                <p>₹<?php echo number_format($sales, 2); ?></p>
            </div>
            <div class="card">
                <h3>Profit/Loss</h3>
                <p class="<?php echo $profit >= 0 ? 'profit' : 'loss'; ?>">₹<?php echo number_format($profit, 2); ?></p>
            </div>
        </div>

        <div class="section" id="purchases-section">
            <h2>Recent Purchases</h2>
            <div class="filter-container">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="purchase-bill-filter"><span class="filter-icon">📄</span> Bill Number</label>
                        <input type="text" id="purchase-bill-filter" placeholder="Enter bill number...">
                    </div>
                    <div class="filter-group">
                        <label for="purchase-supplier-filter"><span class="filter-icon">🏢</span> Supplier</label>
                        <input type="text" id="purchase-supplier-filter" placeholder="Enter supplier name...">
                    </div>
                    <div class="filter-group">
                        <label for="purchase-item-filter"><span class="filter-icon">📦</span> Item</label>
                        <input type="text" id="purchase-item-filter" placeholder="Enter item name...">
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Date Range</label>
                        <div class="date-range">
                            <input type="date" id="purchase-date-from" placeholder="From date">
                            <input type="date" id="purchase-date-to" placeholder="To date">
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Bill Number</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>Total Amount</th>
                            <th>GST Type</th>
                            <th>Gst Rate</th>
                            <th>GST Amount</th>
                            <th>Total with GST</th>
                            <th>Rounding Adjustment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $purchase_details->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['bill_number']; ?></td>
                            <td><?php echo $row['company_name']; ?></td>
                            <td><?php echo $row['purchase_date']; ?></td>
                            <td><?php echo $row['items']; ?></td>
                            <td><?php echo $row['quantity'] ?? 1; ?></td>
                            <td>₹<?php echo number_format($row['amount_per_unit'] ?? $row['total_amount'], 2); ?></td>
                            <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td><?php echo $row['tax_type'] ?? 'SGST/CGST'; ?></td>
                            <td><?php echo number_format($row['gst_percent'] ?? 0, 2); ?>%</td>
                            <td>₹<?php echo number_format($row['gst_amount'] ?? 0, 2); ?></td>
                            <td>₹<?php echo number_format($row['total_with_gst'] ?? $row['total_amount'], 2); ?></td>
                            <td>₹<?php echo number_format($row['rounding_adjustment'] ?? 0, 2); ?></td>
                            <td>

                                <button class="delete-btn"
                                    onclick="deleteRecord('purchase', <?php echo $row['id']; ?>, this)">🗑️
                                    Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="download-container">
                <button class="download-btn"
                    onclick="downloadExcel(document.querySelector('#purchases-section table'), 'purchase')">
                    <span>⬇️</span> Download Purchases
                </button>
            </div>
            <?php if ($purchase_pages > 1): ?>
            <div class="pagination">
                <?php if ($purchase_page > 1): ?>
                <a href="?purchase_page=<?php echo $purchase_page - 1; ?>&sale_page=<?php echo $sale_page; ?>">&larr;
                    Previous</a>
                <?php else: ?>
                <a href="#" class="disabled">&larr; Previous</a>
                <?php endif; ?>
                <span>Page <?php echo $purchase_page; ?> of <?php echo $purchase_pages; ?></span>
                <?php if ($purchase_page < $purchase_pages): ?>
                <a href="?purchase_page=<?php echo $purchase_page + 1; ?>&sale_page=<?php echo $sale_page; ?>">Next
                    &rarr;</a>
                <?php else: ?>
                <a href="#" class="disabled">Next &rarr;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="section" id="sales-section">
            <h2>Recent Sales</h2>
            <div class="filter-container">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="sale-bill-filter"><span class="filter-icon">📄</span> Bill Number</label>
                        <input type="text" id="sale-bill-filter" placeholder="Enter bill number...">
                    </div>
                    <div class="filter-group">
                        <label for="sale-customer-filter"><span class="filter-icon">👤</span> Customer</label>
                        <input type="text" id="sale-customer-filter" placeholder="Enter customer name...">
                    </div>
                    <div class="filter-group">
                        <label for="sale-item-filter"><span class="filter-icon">📦</span> Item</label>
                        <input type="text" id="sale-item-filter" placeholder="Enter item name...">
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Date Range</label>
                        <div class="date-range">
                            <input type="date" id="sale-date-from" placeholder="From date">
                            <input type="date" id="sale-date-to" placeholder="To date">
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice Number</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Amount</th>
                            <th>GST Type</th>
                            <th>CGST</th>
                            <th>SGST</th>
                            <th>IGST</th>
                            <th>Grand Total</th>
                            <th>Rounding Adjustment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $sale_details->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['invoice_number']; ?></td>
                            <td><?php echo $row['buyer_name']; ?></td>
                            <td><?php echo $row['invoice_date']; ?></td>
                            <td><?php
                                $items = json_decode($row['items'], true);
                                if ($items && is_array($items)) {
                                    $itemNames = array_column($items, 'description');
                                    echo implode(', ', $itemNames);
                                } else {
                                    echo 'Bill Items';
                                }
                            ?></td>
                            <td><?php echo number_format($row['quantity'], 2); ?></td>
                            <td>₹<?php echo number_format($row['unit_price'], 2); ?></td>
                            <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td><?php echo $row['tax_type'] ?? 'SGST/CGST'; ?></td>
                            <td>₹<?php echo number_format($row['cgst_amount'], 2); ?></td>
                            <td>₹<?php echo number_format($row['sgst_amount'], 2); ?></td>
                            <td>₹<?php echo number_format($row['igst_amount'], 2); ?></td>
                            <td>₹<?php echo number_format($row['grand_total'], 2); ?></td>
                            <td>₹<?php echo number_format($row['rounding_adjustment'] ?? 0, 2); ?></td>
                            <td>

                                <button class="delete-btn"
                                    onclick="deleteRecord('sale', <?php echo $row['id']; ?>, this)">🗑️
                                    Delete</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="download-container">
                <button class="download-btn"
                    onclick="downloadExcel(document.querySelector('#sales-section table'), 'sale')">
                    <span>⬇️</span> Download Sales
                </button>
            </div>
            <?php if ($sale_pages > 1): ?>
            <div class="pagination">
                <?php if ($sale_page > 1): ?>
                <a href="?purchase_page=<?php echo $purchase_page; ?>&sale_page=<?php echo $sale_page - 1; ?>">&larr;
                    Previous</a>
                <?php else: ?>
                <a href="#" class="disabled">&larr; Previous</a>
                <?php endif; ?>
                <span>Page <?php echo $sale_page; ?> of <?php echo $sale_pages; ?></span>
                <?php if ($sale_page < $sale_pages): ?>
                <a href="?purchase_page=<?php echo $purchase_page; ?>&sale_page=<?php echo $sale_page + 1; ?>">Next
                    &rarr;</a>
                <?php else: ?>
                <a href="#" class="disabled">Next &rarr;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <script>
        function deleteRecord(type, id, button) {
            if (confirm('Are you sure you want to delete this record?')) {
                // For both sale and purchase bills, redirect to delete_bill.php with table parameter
                if (type === 'sale') {
                    window.location.href = `delete_bill.php?id=${id}`;
                } else if (type === 'purchase') {
                    window.location.href = `delete_bill.php?id=${id}&table=purchases`;
                }
            }
        }

        function updateTotals(totals) {
            // Update purchase total
            const purchaseCard = document.querySelector('.summary .card:nth-child(1) p');
            if (purchaseCard) {
                purchaseCard.textContent = '₹' + totals.purchases;
            }

            // Update sales total
            const salesCard = document.querySelector('.summary .card:nth-child(2) p');
            if (salesCard) {
                salesCard.textContent = '₹' + totals.sales;
            }

            // Update profit/loss total
            const profitCard = document.querySelector('.summary .card:nth-child(3) p');
            if (profitCard) {
                profitCard.textContent = '₹' + totals.profit;
                profitCard.className = totals.profit_class;
            }
        }

        function showMessage(message, type) {
            // Remove existing message
            const existingMessage = document.querySelector('.message');
            if (existingMessage) {
                existingMessage.remove();
            }

            // Create new message
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.textContent = message;

            // Insert after header
            const header = document.querySelector('.header');
            header.insertAdjacentElement('afterend', messageDiv);

            // Auto-hide after 3 seconds
            setTimeout(() => {
                messageDiv.remove();
            }, 3000);
        }

        // Filter functionality for purchases
        document.getElementById('purchase-bill-filter').addEventListener('input', filterPurchases);
        document.getElementById('purchase-supplier-filter').addEventListener('input', filterPurchases);
        document.getElementById('purchase-item-filter').addEventListener('input', filterPurchases);
        document.getElementById('purchase-date-from').addEventListener('input', filterPurchases);
        document.getElementById('purchase-date-to').addEventListener('input', filterPurchases);

        function filterPurchases() {
            const billFilter = document.getElementById('purchase-bill-filter').value.toLowerCase();
            const supplierFilter = document.getElementById('purchase-supplier-filter').value.toLowerCase();
            const itemFilter = document.getElementById('purchase-item-filter').value.toLowerCase();
            const dateFrom = document.getElementById('purchase-date-from').value;
            const dateTo = document.getElementById('purchase-date-to').value;

            const table = document.querySelector('#purchases-section table');
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const billNumber = row.cells[0].textContent.toLowerCase();
                const supplier = row.cells[1].textContent.toLowerCase();
                const dateStr = row.cells[2].textContent.trim();
                const item = row.cells[3].textContent.toLowerCase();

                const matchesBill = billNumber.includes(billFilter);
                const matchesSupplier = supplier.includes(supplierFilter);
                const matchesItem = item.includes(itemFilter);

                // Date range filtering
                let matchesDateRange = true;
                if (dateFrom || dateTo) {
                    const rowDate = new Date(dateStr);
                    if (dateFrom && rowDate < new Date(dateFrom)) {
                        matchesDateRange = false;
                    }
                    if (dateTo && rowDate > new Date(dateTo)) {
                        matchesDateRange = false;
                    }
                }

                if (matchesBill && matchesSupplier && matchesItem && matchesDateRange) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Filter functionality for sales
        document.getElementById('sale-bill-filter').addEventListener('input', filterSales);
        document.getElementById('sale-customer-filter').addEventListener('input', filterSales);
        document.getElementById('sale-item-filter').addEventListener('input', filterSales);
        document.getElementById('sale-date-from').addEventListener('input', filterSales);
        document.getElementById('sale-date-to').addEventListener('input', filterSales);

        function filterSales() {
            const billFilter = document.getElementById('sale-bill-filter').value.toLowerCase();
            const customerFilter = document.getElementById('sale-customer-filter').value.toLowerCase();
            const itemFilter = document.getElementById('sale-item-filter').value.toLowerCase();
            const dateFrom = document.getElementById('sale-date-from').value;
            const dateTo = document.getElementById('sale-date-to').value;

            const table = document.querySelector('#sales-section table');
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const billNumber = row.cells[0].textContent.toLowerCase();
                const customer = row.cells[1].textContent.toLowerCase();
                const dateStr = row.cells[2].textContent.trim();
                const item = row.cells[3].textContent.toLowerCase();

                const matchesBill = billNumber.includes(billFilter);
                const matchesCustomer = customer.includes(customerFilter);
                const matchesItem = item.includes(itemFilter);

                // Date range filtering
                let matchesDateRange = true;
                if (dateFrom || dateTo) {
                    const rowDate = new Date(dateStr);
                    if (dateFrom && rowDate < new Date(dateFrom)) {
                        matchesDateRange = false;
                    }
                    if (dateTo && rowDate > new Date(dateTo)) {
                        matchesDateRange = false;
                    }
                }

                if (matchesBill && matchesCustomer && matchesItem && matchesDateRange) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Download functionality
        function downloadTable(type) {
            const format = document.getElementById(`${type}-download-format`).value;
            const table = document.querySelector(`#${type}s-section table`);

            if (format === 'pdf') {
                downloadPDF(table, type);
            } else if (format === 'excel') {
                downloadExcel(table, type);
            }
        }

        function downloadPDF(table, type) {
            if (!window.jsPDF) {
                alert('PDF library not loaded. Please check your internet connection and try again.');
                return;
            }

            const jsPDF = window.jsPDF;
            const doc = new jsPDF('l'); // Landscape orientation for better fit

            // Add title
            doc.setFontSize(18);
            doc.text(`${type.charAt(0).toUpperCase() + type.slice(1)} Records`, 14, 20);

            // Get table data efficiently
            const headers = Array.from(table.querySelectorAll('thead th')).slice(0, -1).map(th => th.textContent
                .trim());
            const data = Array.from(table.querySelectorAll('tbody tr')).map(row =>
                Array.from(row.querySelectorAll('td')).slice(0, -1).map(td => td.textContent.trim().replace(
                    '₹',
                    'Rs. '))
            );

            if (data.length === 0) {
                alert('No data available to export.');
                return;
            }

            // Add table to PDF
            doc.autoTable({
                head: [headers],
                body: data,
                startY: 30,
                styles: {
                    fontSize: 6,
                    cellPadding: 1,
                },
                headStyles: {
                    fillColor: [102, 126, 234],
                    textColor: 255,
                },
                alternateRowStyles: {
                    fillColor: [245, 247, 250],
                },
            });

            // Save the PDF
            doc.save(`${type}_records_${new Date().toISOString().split('T')[0]}.pdf`);
        }

        function downloadExcel(table, type) {
            const headers = [];
            const data = [];

            // Get headers
            const headerCells = table.querySelectorAll('thead th');
            headerCells.forEach((cell, index) => {
                // Skip the last column (Action)
                if (index < headerCells.length - 1) {
                    headers.push(cell.textContent.trim());
                }
            });

            // Get visible data rows (filtered)
            const rows = Array.from(table.querySelectorAll('tbody tr')).filter(row => row.style.display !==
                'none');
            rows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td');
                cells.forEach((cell, index) => {
                    // Skip the last column (Action)
                    if (index < cells.length - 1) {
                        rowData.push(cell.textContent.trim().replace('₹', 'Rs. '));
                    }
                });
                data.push(rowData);
            });

            if (data.length === 0) {
                alert('No data available to export. Please adjust your filters.');
                return;
            }

            // Create worksheet
            const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);

            // Create workbook
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, `${type.charAt(0).toUpperCase() + type.slice(1)} Records`);

            // Save the Excel file
            XLSX.writeFile(wb, `${type}_records_${new Date().toISOString().split('T')[0]}.xlsx`);
        }
        </script>

        <div class="download-container">
            <a href="index.php" class="download-btn" style="padding: 15px 20px;">
                <span>🏠</span> Go to Home
            </a>
        </div>

        <!-- Edit Modal for Purchases -->
        <div id="purchase-edit-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Edit Purchase Record</h2>
                    <span class="close" onclick="closeModal('purchase-edit-modal')">&times;</span>
                </div>
                <form id="purchase-edit-form">
                    <input type="hidden" id="purchase-edit-id" name="id">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="purchase-edit-bill-number">Bill Number</label>
                            <input type="text" id="purchase-edit-bill-number" name="bill_number" required>
                        </div>
                        <div class="form-group">
                            <label for="purchase-edit-company-name">Supplier</label>
                            <input type="text" id="purchase-edit-company-name" name="company_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="purchase-edit-date">Purchase Date</label>
                            <input type="date" id="purchase-edit-date" name="purchase_date" required>
                        </div>
                        <div class="form-group">
                            <label for="purchase-edit-items">Items</label>
                            <input type="text" id="purchase-edit-items" name="items" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="purchase-edit-quantity">Quantity</label>
                            <input type="number" id="purchase-edit-quantity" name="quantity" step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label for="purchase-edit-amount">Amount per Unit</label>
                            <input type="number" id="purchase-edit-amount" name="amount_per_unit" step="0.01" min="0"
                                required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="purchase-edit-total-amount">Total Amount</label>
                            <input type="number" id="purchase-edit-total-amount" name="total_amount" step="0.01" min="0"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="purchase-edit-gst-amount">GST Amount</label>
                            <input type="number" id="purchase-edit-gst-amount" name="gst_amount" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            onclick="closeModal('purchase-edit-modal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Purchase</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Modal for Sales -->
        <div id="sale-edit-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Edit Sale Record</h2>
                    <span class="close" onclick="closeModal('sale-edit-modal')">&times;</span>
                </div>
                <form id="sale-edit-form">
                    <input type="hidden" id="sale-edit-id" name="id">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sale-edit-invoice-number">Invoice Number</label>
                            <input type="text" id="sale-edit-invoice-number" name="invoice_number" required>
                        </div>
                        <div class="form-group">
                            <label for="sale-edit-buyer-name">Customer</label>
                            <input type="text" id="sale-edit-buyer-name" name="buyer_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sale-edit-date">Invoice Date</label>
                            <input type="date" id="sale-edit-date" name="invoice_date" required>
                        </div>
                        <div class="form-group">
                            <label for="sale-edit-items">Items (JSON)</label>
                            <textarea id="sale-edit-items" name="items" required></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sale-edit-total-amount">Total Amount</label>
                            <input type="number" id="sale-edit-total-amount" name="total_amount" step="0.01" min="0"
                                <input type="number" id="sale-edit-cgst-amount" name="cgst_amount" step="0.01" min="0"
                                required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sale-edit-sgst-amount">SGST Amount</label>
                            <input type="number" id="sale-edit-sgst-amount" name="sgst_amount" step="0.01" min="0"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="sale-edit-igst-amount">IGST Amount</label>
                            <input type="number" id="sale-edit-igst-amount" name="igst_amount" step="0.01" min="0"
                                required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sale-edit-grand-total">Grand Total</label>
                            <input type="number" id="sale-edit-grand-total" name="grand_total" step="0.01" min="0"
                                required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            onclick="closeModal('sale-edit-modal')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Sale</button>
                    </div>
                </form>
            </div>
        </div>
</body>
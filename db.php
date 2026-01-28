<?php
// Database connection settings
$db_host = "localhost";      // usually localhost
$db_port = 3306;             // default MySQL port (change if different)
$db_user = "root";           // default XAMPP user
$db_pass = "";               // default XAMPP password is empty
$db_name = "if0_40593324_ekamanu";     // database name (we will create this)

// Try to connect to MySQL server
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    $error_msg = "Connection failed: " . $conn->connect_error;
    $error_msg .= "<br><br><strong>Troubleshooting:</strong>";
    $error_msg .= "<br>1. Make sure MySQL server is running (check XAMPP/WAMP control panel)";
    $error_msg .= "<br>2. Verify database 'company_db' exists in phpMyAdmin";
    $error_msg .= "<br>3. Check if MySQL port is 3306 (or update \$db_port in db.php)";
    $error_msg .= "<br>4. Verify username and password are correct";
    die($error_msg);
}
?>
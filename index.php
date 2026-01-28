<?php
require_once 'security.php';

// Send security headers
send_security_headers();

// Redirect to login.php
header("Location: pages/login.php");
exit();
?>
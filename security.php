<?php
// Security functions for Bill Management System

require_once 'config.php';

// Send security headers
function send_security_headers() {
    // Content Security Policy (CSP)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https:; connect-src 'self'; frame-src https://www.google.com; object-src 'none'; base-uri 'self'; form-action 'self';");

    // HTTP Strict Transport Security (HSTS) - only if HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    }

    // X-Frame-Options
    header("X-Frame-Options: DENY");

    // X-Content-Type-Options
    header("X-Content-Type-Options: nosniff");

    // Referrer-Policy
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // Permissions-Policy (formerly Feature-Policy)
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

    // Remove X-Powered-By header
    header_remove("X-Powered-By");
}

// Start secure session with proper configuration
function start_secure_session() {
    if (session_status() == PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');

        session_start();

        // Regenerate session ID to prevent session fixation
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }

        // Check for session hijacking
        if (isset($_SESSION['user_agent'])) {
            if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
                session_destroy();
                header("Location: login.php");
                exit();
            }
        } else {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
    }
}

// Generate CSRF token
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validate_csrf_token($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        // Token is valid, regenerate for next request
        unset($_SESSION['csrf_token']);
        return true;
    }
    return false;
}

// Rate limiting function
function check_rate_limit($ip, $action, $max_attempts = 5, $time_window = 900, $reset = false) { // 15 minutes
    $key = "rate_limit_{$action}_{$ip}";

    if ($reset) {
        // Reset attempts and first_attempt
        unset($_SESSION[$key]);
        return false;
    }

    $attempts = $_SESSION[$key]['attempts'] ?? 0;
    $first_attempt = $_SESSION[$key]['first_attempt'] ?? time();

    // Reset if time window has passed
    if (time() - $first_attempt > $time_window) {
        $_SESSION[$key] = ['attempts' => 1, 'first_attempt' => time()];
        return false;
    }

    // Increment attempts
    $_SESSION[$key]['attempts'] = ++$attempts;

    return $attempts > $max_attempts;
}

// Sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Verify reCAPTCHA response
function verify_recaptcha($response) {
    global $recaptcha_secret_key;

    if (empty($response)) {
        return false;
    }

    // Prepare the POST request to Google's reCAPTCHA API
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $recaptcha_secret_key,
        'response' => $response,
        'remoteip' => get_client_ip()
    ];

    // Use cURL to send the request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        log_event('reCAPTCHA API request failed with HTTP code: ' . $http_code, 'ERROR');
        return false;
    }

    $response_data = json_decode($result, true);

    if (!$response_data) {
        log_event('Invalid JSON response from reCAPTCHA API', 'ERROR');
        return false;
    }

    if (isset($response_data['success']) && $response_data['success'] === true) {
        // For reCAPTCHA v3, check the score
        if (isset($response_data['score'])) {
            $score = $response_data['score'];
            $threshold = 0.5; // Minimum score threshold
            if ($score >= $threshold) {
                return true;
            } else {
                log_event('reCAPTCHA verification failed: score too low (' . $score . ')', 'WARNING');
                return false;
            }
        }
        // For reCAPTCHA v2, success is sufficient
        return true;
    } else {
        $error_codes = $response_data['error-codes'] ?? [];
        log_event('reCAPTCHA verification failed with error codes: ' . implode(', ', $error_codes), 'WARNING');
        return false;
    }
}

// Hash password using bcrypt
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Verify user credentials
function verify_user_credentials($username, $password) {
    global $conn;

    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE LOWER(username) = LOWER(?)");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (verify_password($password, $user['password'])) {
            return true;
        }
    }

    return false;
}

// Get user ID by username
function get_user_id($username) {
    global $conn;

    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        return $user['id'];
    }

    return null;
}

// Log security events
function log_event($message, $level = 'INFO') {
    $log_file = __DIR__ . '/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $log_entry = "[$timestamp] [$level] [$ip] [$user_agent] $message" . PHP_EOL;

    // Append to log file
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Check if user is logged in
function require_login() {
    start_secure_session();

    if (!isset($_SESSION['user_id'])) {
        header("Location: pages/login.php");
        exit();
    }

    // Check session timeout (30 minutes)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
        session_destroy();
        header("Location: pages/login.php");
        exit();
    }

    // Update last activity
    $_SESSION['login_time'] = time();
}

// Logout function
function logout() {
    start_secure_session();

    // Log the logout event
    $username = $_SESSION['username'] ?? 'unknown';
    log_event("User '$username' logged out", 'INFO');

    // Destroy session
    session_destroy();

    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}

// Generate random password
function generate_random_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

// Validate email format
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Escape output for HTML
function escape_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Check if request is AJAX
function is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Get client IP address
function get_client_ip() {
    $ip_headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($ip_headers as $header) {
        if (isset($_SERVER[$header])) {
            $ip = trim($_SERVER[$header]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}
?>
<?php
require_once 'config.php';
require_once 'db.php';
require_once 'security.php';

// New user details
$username = 'PrincePatel';
$password = 'Ekamanu@24';

// Hash the password
$hashed_password = hash_password($password);

// Insert into database
$stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $hashed_password);

if ($stmt->execute()) {
    echo "User '$username' added successfully.";
} else {
    echo "Error adding user: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
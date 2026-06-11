<?php
// Set default timezone
date_default_timezone_set('Asia/Kolkata');

// Enable full error reporting during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// JSON response header
header('Content-Type: application/json');

// Optional: Allow cross-origin requests (useful during dev)
header('Access-Control-Allow-Origin: *');

// Include database connection
include '../../database/index.php';

// Validate POST data
$required_fields = ['old_ssin', 'ssin', 'name', 'dsno'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(["error" => "Missing or empty field: $field"]);
        exit();
    }
}

// Sanitize input
$old_ssin = trim($_POST['old_ssin']);
$ssin     = trim($_POST['ssin']);
$name     = trim($_POST['name']);
$dsno     = trim($_POST['dsno']);

// Prepare update statement
$stmt = $conn->prepare("UPDATE ds_record SET ssin = ?, name = ?, dsno = ? WHERE ssin = ?");
if (!$stmt) {
    echo json_encode(["error" => "Prepare failed: " . $conn->error]);
    exit();
}

$stmt->bind_param("ssss", $ssin, $name, $dsno, $old_ssin);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["status" => "Success"]);
    } else {
        echo json_encode(["error" => "No rows updated. SSIN may not exist or no changes were made."]);
    }
} else {
    echo json_encode(["error" => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();

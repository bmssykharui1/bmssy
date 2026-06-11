<?php
// Force JSON response
header('Content-Type: application/json');

// Start output buffering
ob_start();

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Optional: Log PHP errors to file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');

// Include DB
include '../../database/index.php';
date_default_timezone_set('Asia/Kolkata');

// Ensure POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// Collect and sanitize inputs
$ssin       = trim($_POST['ssin'] ?? '');
$name       = trim($_POST['name'] ?? '');
$dsno       = trim($_POST['dsno'] ?? '');
$periodFrom = trim($_POST['periodFrom'] ?? '');
$periodTo   = trim($_POST['periodTo'] ?? '');

// ✅ Extract only DS number (ignore extra date text)
if (preg_match('/^\d+/', $dsno, $matches)) {
    $dsno = $matches[0];
}

// Validate required fields
if (!$ssin || !$name || !$dsno || !$periodFrom || !$periodTo) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

// Get beneficiary_id
$beneficiary_id = null;
$stmt = $conn->prepare("SELECT id FROM beneficiaries WHERE approved_ssin = ?");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Beneficiary query failed: " . $conn->error]);
    exit;
}
$stmt->bind_param("s", $ssin);
$stmt->execute();
$stmt->bind_result($beneficiary_id);
$stmt->fetch();
$stmt->close();

if (!$beneficiary_id) {
    echo json_encode(["status" => "error", "message" => "No matching beneficiary found."]);
    exit;
}

// Get ds_date
$ds_date = null;
$stmt = $conn->prepare("SELECT DATE(created_at) FROM ds_record WHERE dsno = ?");
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "DS record query failed: " . $conn->error]);
    exit;
}
$stmt->bind_param("s", $dsno);
$stmt->execute();
$stmt->bind_result($ds_date);
$stmt->fetch();
$stmt->close();

if (!$ds_date) {
    echo json_encode(["status" => "error", "message" => "No matching DS record found for DSNO: " . $dsno]);
    exit;
}

// Insert into pf_update
$status      = "Accepted";
$date        = date("Y-m-d");
$last_update = date("Y-m-d H:i:s");

$stmt = $conn->prepare("INSERT INTO pf_update 
    (beneficiary_name, approved_ssin, status, date, beneficiary_id, period_form, period_to, ds_no, ds_date, last_update) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Insert prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param(
    "ssssisssss",
    $name,
    $ssin,
    $status,
    $date,
    $beneficiary_id,
    $periodFrom,
    $periodTo,
    $dsno,
    $ds_date,
    $last_update
);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Insert failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
exit;
?>

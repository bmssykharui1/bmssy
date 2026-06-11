<?php
date_default_timezone_set('Asia/Kolkata');
include '../../database/index.php';
header('Content-Type: application/json');

// Validate inputs
if (!isset($_POST['ssin'], $_POST['name'], $_POST['dsno'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing parameters: 'ssin', 'name', or 'dsno'"]);
    exit();
}

$ssin = $_POST['ssin'];
$name = $_POST['name'];
$dsno = $_POST['dsno'];

// Check for duplicate SSIN
$check = $conn->prepare("SELECT id FROM ds_record WHERE ssin = ?");
if (!$check) {
    http_response_code(500);
    echo json_encode(["error" => "Prepare failed: " . $conn->error]);
    exit();
}
$check->bind_param("s", $ssin);
$check->execute();
$checkResult = $check->get_result();

if ($checkResult && $checkResult->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["error" => "Duplicate Entry: SSIN already exists"]);
    $check->close();
    $conn->close();
    exit();
}
$check->close();

// Get current IST time
$created_at = date("Y-m-d H:i:s");

// Insert new record
$insert = $conn->prepare("INSERT INTO ds_record (ssin, name, dsno, created_at) VALUES (?, ?, ?, ?)");
if (!$insert) {
    http_response_code(500);
    echo json_encode(["error" => "Prepare failed: " . $conn->error]);
    $conn->close();
    exit();
}
$insert->bind_param("ssss", $ssin, $name, $dsno, $created_at);

if ($insert->execute()) {
    echo json_encode([
        "status" => "Success",
        "ssin" => $ssin,
        "name" => $name,
        "dsno" => $dsno,
        "created_at" => $created_at
    ]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Insert failed: " . $insert->error]);
}

$insert->close();
$conn->close();
?>

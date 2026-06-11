<?php
header('Content-Type: application/json');
include '../../database/index.php';

// Set timezone
date_default_timezone_set("Asia/Kolkata");

$ssin = $_POST['ssin'] ?? '';
$name = $_POST['name'] ?? '';
$reason = $_POST['reason'] ?? '';

if (!$ssin || !$reason) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

// 1️⃣ Find beneficiary by approved_ssin
$stmt = $conn->prepare("SELECT id, date_of_attaining_60, phone_no FROM beneficiaries WHERE approved_ssin = ?");
$stmt->bind_param("s", $ssin);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "No beneficiary found for SSIN"]);
    exit;
}

$beneficiary = $result->fetch_assoc();
$beneficiary_id = $beneficiary['id'];
$date_attaining_60 = $beneficiary['date_of_attaining_60'];
$phone_no = $beneficiary['phone_no'];

// 2️⃣ Insert into pf_update
$status = "Rejected";
$today_date = date("Y-m-d");       // only date
$last_update = date("Y-m-d H:i:s"); // full datetime

$insert = $conn->prepare("
    INSERT INTO pf_update 
        (beneficiary_name, approved_ssin, status, reason, date, beneficiary_id, last_update) 
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$insert->bind_param("sssssis", 
    $name, 
    $ssin, 
    $status, 
    $reason, 
    $today_date, 
    $beneficiary_id, 
    $last_update
);

if ($insert->execute()) {
    // 3️⃣ Update beneficiary status → inactive
    $update = $conn->prepare("UPDATE beneficiaries SET status = 'inactive' WHERE approved_ssin = ?");
    $update->bind_param("i", $ssin);
    $update->execute();

    echo json_encode([
        "status" => "success", 
        "message" => "PF update inserted successfully & beneficiary set inactive",
        "beneficiary_id" => $beneficiary_id,
        "date_of_attaining_60" => $date_attaining_60,
        "phone_no" => $phone_no
    ]);
} else {
    echo json_encode(["status" => "error", "message" => $insert->error]);
}

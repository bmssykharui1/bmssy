<?php
date_default_timezone_set('Asia/Kolkata');
include '../../database/index.php';
header('Content-Type: application/json');

if (!isset($_POST['ssin'])) {
    echo json_encode(["error" => "SSIN not provided"]);
    exit();
}

$ssin = $_POST['ssin'];

$stmt = $conn->prepare("SELECT ssin, name, dsno FROM ds_record WHERE ssin = ?");
$stmt->bind_param("s", $ssin);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(["error" => "Record not found"]);
}

$stmt->close();
$conn->close();

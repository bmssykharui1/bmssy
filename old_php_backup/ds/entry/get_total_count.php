<?php
date_default_timezone_set('Asia/Kolkata');
include '../../database/index.php';

header('Content-Type: application/json');

// Get today's start and end timestamps
$todayStart = date('Y-m-d') . " 00:00:00";
$todayEnd = date('Y-m-d') . " 23:59:59";

// Prepare and execute query
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM ds_record WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $todayStart, $todayEnd);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode(["total" => $row['total']]);
} else {
    echo json_encode(["error" => "Failed to count records"]);
}

$stmt->close();
$conn->close();
?>

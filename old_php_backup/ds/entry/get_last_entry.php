<?php
date_default_timezone_set('Asia/Kolkata');
include '../../database/index.php';
header('Content-Type: application/json');

$result = $conn->query("SELECT ssin, name, dsno, created_at FROM ds_record ORDER BY id DESC LIMIT 1");

if ($result && $result->num_rows > 0) {
    echo json_encode($result->fetch_assoc());
} else {
    echo json_encode(["error" => "No records found"]);
}

$conn->close();
?>

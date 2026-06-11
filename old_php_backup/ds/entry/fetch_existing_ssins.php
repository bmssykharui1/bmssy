<?php
header('Content-Type: application/json');
include '../../database/index.php'; // ✅ Ensure this path is correct and connection is valid

// ✅ Fetch all existing SSINs from ds_record
$sql = "SELECT ssin FROM ds_record";
$result = $conn->query($sql);

$ssins = [];

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $ssins[] = $row['ssin'];
  }
}

// ✅ Output JSON
echo json_encode(['ssins' => $ssins]);

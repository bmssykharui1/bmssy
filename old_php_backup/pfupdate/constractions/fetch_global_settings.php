<?php
// Include your database connection
include "../../database/index.php";

header('Content-Type: application/json');

$response = array();

// Check if connection variable exists (adjust '$conn' to match your database/index.php variable, e.g., $link or $mysqli)
if (!isset($conn)) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Fetch dates where id is 1
// Note: You mentioned the column is named 'period_form' (not 'period_from'), so we use that.
$sql = "SELECT period_form, period_to FROM global_settings WHERE id = 1 LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // We map 'period_form' from database to 'period_from' for the JavaScript to use
    $response['period_from'] = $row['period_form']; 
    $response['period_to'] = $row['period_to'];
} else {
    $response['period_from'] = "";
    $response['period_to'] = "";
}

echo json_encode($response);
?>
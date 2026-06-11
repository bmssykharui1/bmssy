<?php
// Include your database connection
include '../../database/index.php';

// Set the header to tell the browser this is JSON data
header('Content-Type: application/json');

// Check if connection variable exists
if (!isset($conn)) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Fetch the settings (ensure limit 1 to get a single row)
$query = "SELECT period_form, period_to FROM global_settings LIMIT 1";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Create the response array
    // Note: We provide 'period_from' (standard spelling) AND 'period_form' (your DB spelling)
    // to ensure compatibility with whatever variable name you use in JS.
    $response = [
        "period_form" => $row['period_form'], // Database column name
        "period_from" => $row['period_form'], // Correct English spelling for JS compatibility
        "period_to"   => $row['period_to']
    ];
} else {
    // Return empty strings if no data found
    $response = [
        "period_form" => "",
        "period_from" => "",
        "period_to"   => ""
    ];
}

// Output the JSON
echo json_encode($response);

// Close connection
$conn->close();
?>
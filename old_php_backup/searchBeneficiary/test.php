<?php
// This is a simple test script to check if the data is being fetched correctly

// Simulating fetched data from the database (replace with your actual database logic)
$data = [
    [
        "date" => "01 JAN 2025 8:30 AM",
        "last_update" => "7:30 AM",
        "period_form" => "01 JAN 2020",
        "period_to" => "01 DEC 2024",
        "amount" => 660
    ],
    [
        "date" => "15 FEB 2025 9:00 AM",
        "last_update" => "9:15 AM",
        "period_form" => "15 FEB 2020",
        "period_to" => "15 FEB 2025",
        "amount" => 275
    ]
];

// Simulate a success response
$response = [
    "success" => true,
    "data" => $data
];

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>

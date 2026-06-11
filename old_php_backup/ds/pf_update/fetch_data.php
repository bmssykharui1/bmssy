<?php
include '../../database/index.php';
date_default_timezone_set("Asia/Kolkata");

// Define the date range
$startDate = '2025-08-01';
$endDate = date('Y-m-d'); // today's date

// Prepare SQL: Fetch from ds_record where ssin is NOT in pf_update between date range
$sql = "
    SELECT * 
    FROM ds_record 
    WHERE ssin NOT IN (
        SELECT approved_ssin 
        FROM pf_update 
        WHERE date BETWEEN ? AND ?
    )
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();


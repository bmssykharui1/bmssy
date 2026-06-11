<?php
include "../database/index.php";

header('Content-Type: application/json');

$currentYear = date("Y");
$currentMonth = date("m");

// Set date range for the current month
$startDate = "$currentYear-$currentMonth-01";
$endDate = date("Y-m-t"); // Last day of the month

// Query to count total rejected SSINs (both 142 and 242)
$query = $conn->prepare("SELECT COUNT(*) FROM pf_update WHERE status = 'Rejected' AND date BETWEEN ? AND ?");
$query->bind_param("ss", $startDate, $endDate);
$query->execute();
$query->bind_result($totalRejected);
$query->fetch();
$query->close();

// Return total count
echo json_encode(["totalRejected" => $totalRejected]);
?>

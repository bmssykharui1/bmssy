<?php
include "../database/index.php";

header('Content-Type: application/json');

$currentDate = date("Y-m-d"); // Today
$yesterdayDate = date("Y-m-d", strtotime("-1 day")); // Yesterday
$currentMonthStart = date("Y-m-01"); // Start of the month
$currentMonthEnd = date("Y-m-t"); // End of the month

// Function to get the count based on the date range
function getActiveSSINCount($conn, $startDate, $endDate) {
    $query = $conn->prepare("SELECT COUNT(*) FROM beneficiaries WHERE status = 'active' AND created_at BETWEEN ? AND ?");
    $query->bind_param("ss", $startDate, $endDate);
    $query->execute();
    $query->bind_result($count);
    $query->fetch();
    $query->close();
    return $count;
}

// Fetch counts
$totalToday = getActiveSSINCount($conn, $currentDate, $currentDate);
$totalYesterday = getActiveSSINCount($conn, $yesterdayDate, $yesterdayDate);
$totalThisMonth = getActiveSSINCount($conn, $currentMonthStart, $currentMonthEnd);

// Return JSON response
echo json_encode([
    "totalToday" => $totalToday,
    "totalYesterday" => $totalYesterday,
    "totalThisMonth" => $totalThisMonth
]);
?>

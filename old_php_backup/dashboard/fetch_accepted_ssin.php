<?php
include "../database/index.php";

header('Content-Type: application/json');

$currentYear = date("Y");
$currentMonth = date("m");

// Define the start and end date for the current month
$startDate = "$currentYear-$currentMonth-01";
$endDate = "$currentYear-$currentMonth-31";

// Query for count of SSINs starting with 142 with status Accepted
$query142 = $conn->prepare("SELECT COUNT(*) AS total FROM pf_update WHERE approved_ssin LIKE '142%' AND status = 'Accepted' AND date BETWEEN ? AND ?");
$query142->bind_param("ss", $startDate, $endDate);
$query142->execute();
$result142 = $query142->get_result();
$row142 = $result142->fetch_assoc();
$acceptedssincount142 = $row142['total'] ?? 0;

// Query for count of SSINs starting with 242 with status Accepted
$query242 = $conn->prepare("SELECT COUNT(*) AS total FROM pf_update WHERE approved_ssin LIKE '242%' AND status = 'Accepted' AND date BETWEEN ? AND ?");
$query242->bind_param("ss", $startDate, $endDate);
$query242->execute();
$result242 = $query242->get_result();
$row242 = $result242->fetch_assoc();
$acceptedssincount242 = $row242['total'] ?? 0;

echo json_encode([
    "acceptedssincount142" => $acceptedssincount142,
    "acceptedssincount242" => $acceptedssincount242
]);
?>

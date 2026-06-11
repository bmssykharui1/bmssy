<?php
include "../database/index.php";

header('Content-Type: application/json');

// Check database connection
if (!$conn) {
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

// Query to count SSINs starting with 142
$query142 = "SELECT COUNT(*) AS total FROM beneficiaries WHERE approved_ssin LIKE '142%' AND status = 'active'";
$result142 = $conn->query($query142);

if ($result142) {
    $row142 = $result142->fetch_assoc();
    $count142 = $row142['total'] ?? 0;
} else {
    $count142 = "Error: " . $conn->error; // Debugging
}

// Query to count SSINs starting with 242
$query242 = "SELECT COUNT(*) AS total FROM beneficiaries WHERE approved_ssin LIKE '242%' AND status = 'active'";
$result242 = $conn->query($query242);

if ($result242) {
    $row242 = $result242->fetch_assoc();
    $count242 = $row242['total'] ?? 0;
} else {
    $count242 = "Error: " . $conn->error; // Debugging
}

// Return the JSON response
echo json_encode([
    "count_142" => $count142,
    "count_242" => $count242
]);
?>

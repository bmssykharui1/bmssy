<?php
header('Content-Type: application/json');

include('../../../database/index.php');

// Query to get the count of SSIN numbers that start with 142 or 242 and have status 'active'
$sql = "SELECT 
            SUBSTRING(approved_ssin, 1, 3) AS ssin_prefix, 
            COUNT(*) AS total 
        FROM beneficiaries 
        WHERE (approved_ssin LIKE '142%' OR approved_ssin LIKE '242%') 
          AND status = 'active'
        GROUP BY ssin_prefix";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        "ssin_prefix" => $row["ssin_prefix"],
        "total" => (int)$row["total"]
    ];
}

$conn->close();

echo json_encode($data);
?>

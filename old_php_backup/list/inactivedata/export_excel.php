<?php
include '../../database/index.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=inactive_beneficiaries.xls");
header("Pragma: no-cache");
header("Expires: 0");

$filterType = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$searchQuery = isset($_GET['search_query']) ? $_GET['search_query'] : '';

// Base SQL query
$sql = "SELECT b.id, b.beneficiary_name, b.approved_ssin, b.date_of_attaining_60, 
               b.phone_no, b.status, 
               COALESCE(
                   (SELECT reason FROM pf_update 
                    WHERE pf_update.approved_ssin = b.approved_ssin 
                    ORDER BY pf_update.date DESC LIMIT 1), 'N/A'
               ) AS reason
        FROM beneficiaries b
        WHERE b.status = 'Inactive'";

// Apply SSIN filtering (142 or 242)
if ($filterType == "142") {
    $sql .= " AND b.approved_ssin LIKE '142%'";
} elseif ($filterType == "242") {
    $sql .= " AND b.approved_ssin LIKE '242%'";
}

// Apply search filter
if (!empty($searchQuery)) {
    $sql .= " AND (b.beneficiary_name LIKE ? OR b.approved_ssin LIKE ? OR 
                   EXISTS (
                       SELECT 1 FROM pf_update 
                       WHERE pf_update.approved_ssin = b.approved_ssin 
                       AND pf_update.reason LIKE ?
                   ))";
    $stmt = $conn->prepare($sql);
    $searchParam = "%$searchQuery%";
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

// Print column headers
echo "ID\tName\tSSIN\tDate of Attaining 60\tPhone No\tReason\tStatus\n";

// Print data rows
while ($row = $result->fetch_assoc()) {
    echo htmlspecialchars($row['id']) . "\t" . 
         htmlspecialchars($row['beneficiary_name']) . "\t" . 
         htmlspecialchars($row['approved_ssin']) . "\t" . 
         htmlspecialchars($row['date_of_attaining_60']) . "\t" . 
         htmlspecialchars($row['phone_no']) . "\t" . 
         htmlspecialchars($row['reason']) . "\t" . 
         "Inactive\n";
}

$stmt->close();
$conn->close();
?>

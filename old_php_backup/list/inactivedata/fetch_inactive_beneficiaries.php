<?php
include '../../database/index.php';

$filterType = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';
$searchQuery = isset($_GET['search_query']) ? $_GET['search_query'] : '';

// Base SQL query
$sql = "SELECT b.id, b.beneficiary_name, b.approved_ssin, b.date_of_attaining_60, 
               b.phone_no, b.status, 
               (SELECT reason FROM pf_update 
                WHERE pf_update.approved_ssin = b.approved_ssin 
                ORDER BY pf_update.date DESC LIMIT 1) AS reason
        FROM beneficiaries b
        WHERE b.status = 'Inactive'";

// Apply SSIN filtering (142 or 242)
if ($filterType == "142") {
    $sql .= " AND b.approved_ssin LIKE '142%'";
} elseif ($filterType == "242") {
    $sql .= " AND b.approved_ssin LIKE '242%'";
}

// Apply search filter (searches Name, SSIN, and Reason)
if (!empty($searchQuery)) {
    $sql .= " AND (b.beneficiary_name LIKE ? OR b.approved_ssin LIKE ? OR 
                   (SELECT reason FROM pf_update WHERE pf_update.approved_ssin = b.approved_ssin 
                    ORDER BY pf_update.date DESC LIMIT 1) LIKE ?)";
}

// Prepare statement
$stmt = $conn->prepare($sql);

// Bind parameters if search query exists
if (!empty($searchQuery)) {
    $searchParam = "%$searchQuery%";
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['beneficiary_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['approved_ssin']) . "</td>";
        echo "<td>" . htmlspecialchars($row['date_of_attaining_60']) . "</td>";
        echo "<td>" . htmlspecialchars($row['phone_no']) . "</td>";
        echo "<td>" . htmlspecialchars($row['reason'] ? $row['reason'] : 'N/A') . "</td>";
        echo "<td><span class='badge bg-danger'>Inactive</span></td>";
        echo "<td><button class='btn btn-success activateBtn' 
                          data-id='" . $row['id'] . "' 
                          data-name='" . htmlspecialchars($row['beneficiary_name']) . "' 
                          data-ssin='" . $row['approved_ssin'] . "' 
                          data-phone='" . $row['phone_no'] . "' 
                          data-reason='" . htmlspecialchars($row['reason'] ? $row['reason'] : 'N/A') . "'>
                          <i class='fas fa-user-check'></i> Activate
                  </button></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='8' class='text-center'>No inactive beneficiaries found</td></tr>";
}

$stmt->close();
$conn->close();
?>

<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../../database/index.php';

// Allow large SELECT queries (InfinityFree restriction fix)
$conn->query("SET SQL_BIG_SELECTS=1");

$filter_type = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : ''; // Dropdown value (142 or 242)
$period_form = isset($_GET['period_form']) ? trim($_GET['period_form']) : ''; // Period From
$period_to   = isset($_GET['period_to']) ? trim($_GET['period_to']) : '';     // Period To

// Build WHERE conditions for subquery
$where = ["status = 'Accepted'"];

if (!empty($period_form) && !empty($period_to)) {
    $where[] = "(date BETWEEN '" . $conn->real_escape_string($period_form) . "' 
                AND '" . $conn->real_escape_string($period_to) . "')";
}

if (!empty($filter_type)) {
    $where[] = "approved_ssin LIKE '" . $conn->real_escape_string($filter_type) . "%'";
}

$where_sql = implode(" AND ", $where);

// Optimized: filter pf_update first, then join to reduce row scan
$sql = "
    SELECT pf.beneficiary_name, 
           pf.approved_ssin, 
           pf.period_form, 
           pf.period_to, 
           pf.date AS update_date, 
           b.date_of_attaining_60
    FROM (
        SELECT * 
        FROM pf_update 
        WHERE $where_sql
    ) AS pf
    LEFT JOIN beneficiaries b 
        ON pf.approved_ssin = b.approved_ssin
    ORDER BY pf.date DESC
";

$result = $conn->query($sql);

// Check for SQL errors
if (!$result) {
    die("<tr><td colspan='6' style='color:red;'>SQL Error: " . htmlspecialchars($conn->error) . "</td></tr>");
}

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['beneficiary_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['approved_ssin']) . "</td>";
        echo "<td>" . (!empty($row['date_of_attaining_60']) ? date("d-m-Y", strtotime($row['date_of_attaining_60'])) : '') . "</td>";
        echo "<td>" . (!empty($row['period_form']) ? date("d-m-Y", strtotime($row['period_form'])) : '') . "</td>";
        echo "<td>" . (!empty($row['period_to']) ? date("d-m-Y", strtotime($row['period_to'])) : '') . "</td>";
        echo "<td>" . (!empty($row['update_date']) ? date("d-m-Y", strtotime($row['update_date'])) : '') . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6' class='text-center'>No Data Available</td></tr>";
}
?>

<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
include '../../database/index.php';

// ✅ Check DB connection
if (!$conn) {
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// ✅ Today’s date
$today = date("Y-m-d");

// ✅ Step 0: Enable big selects
@$conn->query("SET SQL_BIG_SELECTS=1");

$data = [];   // Final array for DataTables

// ✅ Step 1: Fetch all approved_ssin from ds_record (to exclude later)
$existingSSINs = [];
$ssinResult = $conn->query("SELECT ssin FROM ds_record");
if ($ssinResult && $ssinResult->num_rows > 0) {
    while ($row = $ssinResult->fetch_assoc()) {
        $existingSSINs[$row['ssin']] = true;
    }
}

// ✅ Step 2: Fetch accepted records from pf_update for July 2025
$acceptedQuery = "
    SELECT p.approved_ssin
    FROM pf_update p
    WHERE p.status = 'Accepted'
      AND p.date BETWEEN '2025-07-01' AND '2025-07-31'
";
$acceptedResult = $conn->query($acceptedQuery);

$validSSINs = [];
if ($acceptedResult && $acceptedResult->num_rows > 0) {
    while ($row = $acceptedResult->fetch_assoc()) {
        $ssin = $row['approved_ssin'];
        // ✅ Exclude if it already exists in ds_record
        if (!isset($existingSSINs[$ssin])) {
            $validSSINs[] = $conn->real_escape_string($ssin);
        }
    }
}

// ✅ Step 3: Query 1 → July Valid Beneficiaries
if (!empty($validSSINs)) {
    $ssinList = "'" . implode("','", $validSSINs) . "'";

    $beneficiaryQuery = "
        SELECT approved_ssin, beneficiary_name, phone_no
        FROM beneficiaries
        WHERE approved_ssin IN ($ssinList)
    ";

    $beneficiaryResult = $conn->query($beneficiaryQuery);

    if ($beneficiaryResult && $beneficiaryResult->num_rows > 0) {
        while ($row = $beneficiaryResult->fetch_assoc()) {
            // ✅ Skip if exists in ds_record
            if (!isset($existingSSINs[$row['approved_ssin']])) {
                $data[] = [
                    "approved_ssin"     => $row['approved_ssin'],
                    "beneficiary_name"  => $row['beneficiary_name'],
                    "phone"             => $row['phone_no'] ?? '',
                    "source"            => "July Valid"
                ];
            }
        }
    }
}

// ✅ Step 4: Query 2 → Active + (142% or 242%) SSIN + Not in pf_update OR last update > 3 months
$beneficiaryQuery2 = "
    SELECT b.id, b.approved_ssin, b.beneficiary_name, b.phone_no
    FROM beneficiaries b
    LEFT JOIN (
        SELECT approved_ssin, MAX(date) AS last_date
        FROM pf_update
        GROUP BY approved_ssin
    ) p ON p.approved_ssin = b.approved_ssin
    WHERE b.status = 'active'
      AND (b.approved_ssin LIKE '142%' OR b.approved_ssin LIKE '242%')
      AND (
          p.last_date IS NULL
          OR p.last_date < DATE_SUB('$today', INTERVAL 3 MONTH)
      )
    ORDER BY b.id DESC
";
$beneficiaryResult2 = $conn->query($beneficiaryQuery2);

if ($beneficiaryResult2 && $beneficiaryResult2->num_rows > 0) {
    while ($row = $beneficiaryResult2->fetch_assoc()) {
        // ✅ Skip if exists in ds_record
        if (!isset($existingSSINs[$row['approved_ssin']])) {
            $data[] = [
                "approved_ssin"     => $row['approved_ssin'],
                "beneficiary_name"  => $row['beneficiary_name'],
                "phone"             => $row['phone_no'] ?? '',
                "source"            => "Inactive Old"
            ];
        }
    }
}

// ✅ Return in DataTables format
echo json_encode(["data" => $data]);
$conn->close();

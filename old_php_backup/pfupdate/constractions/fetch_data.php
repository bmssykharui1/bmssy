<?php
include '../../database/index.php'; // Database connection

// 1. Fetch the global_settings period_to date (id = 1)
$globalSettingsQuery = "SELECT period_to FROM global_settings WHERE id = 1 LIMIT 1";
$globalSettingsResult = $conn->query($globalSettingsQuery);
$globalPeriodTo = null;

if ($globalSettingsResult && $row = $globalSettingsResult->fetch_assoc()) {
    $globalPeriodTo = $row['period_to'];
}

// 2. Main Query: Fetch all active beneficiaries starting with '242%'
// (Removed the old 3-month logic to keep it fast and clean)
$query = "SELECT id, beneficiary_name, approved_ssin
          FROM beneficiaries
          WHERE status = 'active'
          AND approved_ssin LIKE '242%'
          ORDER BY id DESC";

$result = $conn->query($query);
$data = array();

while ($row = $result->fetch_assoc()) {
    $approvedSSIN = $row['approved_ssin'];

    // 3. Find the latest pf_update row by ID for this approved_ssin
    $pfUpdateQuery = "SELECT period_to 
                      FROM pf_update 
                      WHERE approved_ssin = '$approvedSSIN' 
                      ORDER BY id DESC 
                      LIMIT 1";
                      
    $pfUpdateResult = $conn->query($pfUpdateQuery);
    $pfPeriodTo = null;
    
    if ($pfUpdateResult && $pfRow = $pfUpdateResult->fetch_assoc()) {
        $pfPeriodTo = $pfRow['period_to'];
    }

    // --- 4. THE WORKING LOGIC: MATCH HOLE HIDE, NA MATCH HOLE SHOW ---
    if ($pfPeriodTo !== null) {
        // Safe date comparison using strtotime
        if (strtotime($pfPeriodTo) === strtotime($globalPeriodTo)) {
            continue; // Exact MATCH hoyeche => HIDE (Skip this record)
        }
    }

    // 5. Include the data if it's NULL or NOT EQUAL to global date
    $data[] = array(
        "id" => $row['id'],
        "beneficiary_name" => $row['beneficiary_name'],
        "approved_ssin" => $row['approved_ssin'],
        "latest_period_to" => $pfPeriodTo 
    );
}

// Send final fixed JSON data
echo json_encode(["data" => $data]); 
$conn->close();
?>
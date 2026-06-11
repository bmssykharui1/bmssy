<?php
include '../../database/index.php'; // Database connection

// 1. global_settings theke id=1 er period_to date ta nao
$globalSettingsQuery = "SELECT period_to FROM global_settings WHERE id = 1 LIMIT 1";
$globalSettingsResult = $conn->query($globalSettingsQuery);
$globalPeriodTo = null;

if ($globalSettingsResult && $row = $globalSettingsResult->fetch_assoc()) {
    $globalPeriodTo = $row['period_to']; // Ekhane '2026-03-31' asbe
}

// 2. beneficiaries table theke active r 142% ssin wala data nao
$query = "SELECT id, beneficiary_name, approved_ssin
          FROM beneficiaries
          WHERE status = 'active'
          AND approved_ssin LIKE '142%'
          ORDER BY id DESC";

$result = $conn->query($query);
$data = array();

while ($row = $result->fetch_assoc()) {
    $approvedSSIN = $row['approved_ssin'];

    // 3. pf_update table theke ei ssin er "latest" (sobtheke notun) data ta nao
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

    // --- MAIN LOGIC (HIDE if EQUAL) ---
    if ($pfPeriodTo !== null) {
        // Date gulo ke time-e convert kore exact match kora hocche
        if (strtotime($pfPeriodTo) === strtotime($globalPeriodTo)) {
            continue; // Jodi period_to r global date ekebare same hoy, tahole data HIDE korbe
        }
    }

    // Jodi date match NA kore, ba pf_update-e kono data na thake, tahole SHOW korbe
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
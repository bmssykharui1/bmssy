<?php
// 1. Setup Environment
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide PHP errors from output (corrupts JSON)
header('Content-Type: application/json'); // Tell browser this is JSON

include '../../database/index.php'; // Database connection
date_default_timezone_set('Asia/Kolkata'); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. Retrieve & Sanitize Data
    $beneficiary_name = trim($_POST['beneficiary_name'] ?? '');
    $approved_ssin    = trim($_POST['approved_ssin'] ?? '');
    $status           = trim($_POST['status'] ?? 'Rejected'); 
    $reason           = trim($_POST['reason'] ?? '');

    $date        = date("Y-m-d");
    $last_update = date("Y-m-d H:i:s");

    // 3. Validation
    if (empty($approved_ssin) || empty($reason)) {
        echo json_encode(["status" => "error", "message" => "SSIN and Reason are required"]);
        exit;
    }

    try {
        // 4. Fetch Beneficiary ID (Required for Foreign Key in pf_update)
        $beneficiary_id = null;
        $id_query = "SELECT id FROM beneficiaries WHERE approved_ssin = ?";
        $stmt_id = $conn->prepare($id_query);
        
        if ($stmt_id) {
            $stmt_id->bind_param("s", $approved_ssin);
            $stmt_id->execute();
            $stmt_id->bind_result($fetched_id);
            if ($stmt_id->fetch()) {
                $beneficiary_id = $fetched_id;
            }
            $stmt_id->close();
        }

        if (!$beneficiary_id) {
            throw new Exception("SSIN not found in beneficiaries table.");
        }

        // 5. Insert Rejection Record into pf_update
        $insert_query = "INSERT INTO pf_update (beneficiary_name, approved_ssin, status, date, reason, last_update, beneficiary_id) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_query);
        if (!$stmt) {
            throw new Exception("Prepare INSERT failed: " . $conn->error);
        }

        // Bind: s=string, i=integer (ID is integer)
        $stmt->bind_param("ssssssi", $beneficiary_name, $approved_ssin, $status, $date, $reason, $last_update, $beneficiary_id);

        if ($stmt->execute()) {
            $stmt->close();

            // 6. Update Status in beneficiaries table
            $update_query = "UPDATE beneficiaries SET status = 'inactive' WHERE approved_ssin = ?";
            $stmt2 = $conn->prepare($update_query);
            
            if (!$stmt2) {
                throw new Exception("Prepare UPDATE failed: " . $conn->error);
            }

            $stmt2->bind_param("s", $approved_ssin);
            
            if ($stmt2->execute()) {
                echo json_encode(["status" => "success", "message" => "Beneficiary rejected successfully"]);
            } else {
                throw new Exception("Execute UPDATE failed: " . $stmt2->error);
            }
            $stmt2->close();

        } else {
            throw new Exception("Execute INSERT failed: " . $stmt->error);
        }

    } catch (Exception $e) {
        // Log error internally and send clean message to user
        error_log("Rejection Error: " . $e->getMessage());
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
}
?>
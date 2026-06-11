<?php
include '../../database/index.php'; 

// Set proper content type for AJAX
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata'); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Retrieve Data (Handle 'period_from' vs 'period_form' naming)
    $beneficiary_name = $_POST['beneficiary_name'] ?? '';
    $approved_ssin    = $_POST['approved_ssin'] ?? '';
    
    // Check for 'period_from' (sent by JS) OR 'period_form' (database column name)
    $period_form_input = $_POST['period_from'] ?? $_POST['period_form'] ?? ''; 
    $period_to_input   = $_POST['period_to'] ?? '';

    $date        = date("Y-m-d"); 
    $last_update = date("Y-m-d H:i:s"); 

    // Debugging - Log received values
    file_put_contents("debug.log", print_r($_POST, true));

    // 2. Identify Missing Fields Specifically
    $missing_fields = [];

    if (empty($beneficiary_name)) {
        $missing_fields[] = "beneficiary_name";
    }
    if (empty($approved_ssin)) {
        $missing_fields[] = "approved_ssin";
    }
    if (empty($period_form_input)) {
        $missing_fields[] = "period_from (Start Date)";
    }
    if (empty($period_to_input)) {
        $missing_fields[] = "period_to (End Date)";
    }

    // If there are missing fields, stop and tell the user which ones
    if (!empty($missing_fields)) {
        echo json_encode([
            "status" => "error", 
            "message" => "Missing required fields: " . implode(", ", $missing_fields)
        ]);
        exit;
    }

    // 3. Convert dates safely
    $period_form = date("Y-m-d", strtotime($period_form_input));
    $period_to   = date("Y-m-d", strtotime($period_to_input));

    // 4. Get Beneficiary ID
    $query = "SELECT id FROM beneficiaries WHERE approved_ssin = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Prepare lookup failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("s", $approved_ssin);
    $stmt->execute();
    $stmt->bind_result($beneficiary_id);
    $stmt->fetch();
    $stmt->close();

    if (!$beneficiary_id) {
        echo json_encode(["status" => "error", "message" => "SSIN not found in beneficiaries table"]);
        exit;
    }

    // 5. Insert Data
    // Note: ensure column name 'period_form' matches your DB exactly
    $insert_query = "INSERT INTO pf_update 
                     (beneficiary_name, approved_ssin, status, date, beneficiary_id, period_form, period_to, last_update) 
                     VALUES (?, ?, 'accepted', ?, ?, ?, ?, ?)";
    
    // Log the query structure for debugging
    file_put_contents("debug.log", "SQL Query: " . $insert_query . "\n", FILE_APPEND);

    $stmt = $conn->prepare($insert_query);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Prepare insert failed: " . $conn->error]);
        exit;
    }

    // s = string, i = integer. Order: Name(s), SSIN(s), Date(s), ID(i), P_Form(s), P_To(s), Last_Up(s)
    $stmt->bind_param("sssisss", $beneficiary_name, $approved_ssin, $date, $beneficiary_id, $period_form, $period_to, $last_update);

    // Log values before executing
    file_put_contents("debug.log", "Inserting: ID=$beneficiary_id, From=$period_form, To=$period_to\n", FILE_APPEND);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database Error: " . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
}

$conn->close();
?>
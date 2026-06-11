<?php
// 1. Include Database & Set Headers
include '../../database/index.php'; 
header('Content-Type: application/json'); // Crucial for AJAX
date_default_timezone_set('Asia/Kolkata'); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. Retrieve Data (Safe Handling)
    $beneficiary_name = $_POST['beneficiary_name'] ?? '';
    $approved_ssin    = $_POST['approved_ssin'] ?? '';
    
    // Check for 'period_from' (JS input) OR 'period_form' (DB column name)
    $period_form_input = $_POST['period_from'] ?? $_POST['period_form'] ?? ''; 
    $period_to_input   = $_POST['period_to'] ?? '';

    $date        = date("Y-m-d"); 
    $last_update = date("Y-m-d H:i:s"); 

    // 3. Validation: Identify EXACTLY which fields are missing
    $missing_fields = [];

    if (empty($beneficiary_name)) {
        $missing_fields[] = "beneficiary_name";
    }
    if (empty($approved_ssin)) {
        $missing_fields[] = "approved_ssin";
    }
    if (empty($period_form_input)) {
        $missing_fields[] = "Period From (Start Date)";
    }
    if (empty($period_to_input)) {
        $missing_fields[] = "Period To (End Date)";
    }

    // Stop execution and return specific error if fields are missing
    if (!empty($missing_fields)) {
        echo json_encode([
            "status" => "error", 
            "message" => "Missing required fields: " . implode(", ", $missing_fields)
        ]);
        exit;
    }

    // 4. Convert dates safely
    $period_form = date("Y-m-d", strtotime($period_form_input));
    $period_to   = date("Y-m-d", strtotime($period_to_input));

    // 5. Get Beneficiary ID
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

    // 6. Insert Data
    $insert_query = "INSERT INTO pf_update 
                     (beneficiary_name, approved_ssin, status, date, beneficiary_id, period_form, period_to, last_update) 
                     VALUES (?, ?, 'accepted', ?, ?, ?, ?, ?)";
    
    // Debugging (Optional)
    // file_put_contents("debug.log", "SQL Query: " . $insert_query . "\n", FILE_APPEND);

    $stmt = $conn->prepare($insert_query);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Prepare insert failed: " . $conn->error]);
        exit;
    }

    // s = string, i = integer. Order matches SQL query
    // Name(s), SSIN(s), Date(s), ID(i), P_Form(s), P_To(s), Last_Up(s)
    $stmt->bind_param("sssisss", $beneficiary_name, $approved_ssin, $date, $beneficiary_id, $period_form, $period_to, $last_update);

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
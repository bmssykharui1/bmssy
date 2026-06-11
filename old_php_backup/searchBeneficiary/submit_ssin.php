<?php
header('Content-Type: application/json');
include '../database/index.php'; // Include your database connection file

date_default_timezone_set('Asia/Kolkata'); // Set timezone to Kolkata

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ssin = $_POST['ssin'];
    $name = strtoupper($_POST['name']);
    $date_of_attaining_60 = $_POST['date'];
    $phone_no = $_POST['phone'];
    $status = 'active'; // Default status
    $last_update = date('Y-m-d H:i:s'); // Current timestamp
    $created_at = date('Y-m-d'); // Store today's date in created_at

    // Ensure required fields are not empty
    if (empty($date_of_attaining_60) || empty($phone_no)) {
        echo json_encode(['error' => 'Date of attaining 60 and Phone number are required']);
        exit;
    }

    // Check if SSIN already exists to prevent duplicate entries
    $stmt = $conn->prepare("SELECT approved_ssin FROM beneficiaries WHERE approved_ssin = ?");
    $stmt->bind_param("s", $ssin);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo json_encode(['error' => 'SSIN already exists']);
        exit;
    }
    
    $stmt->close();

    // Insert new record with created_at (today's date)
    $stmt = $conn->prepare("INSERT INTO beneficiaries (approved_ssin, beneficiary_name, date_of_attaining_60, phone_no, status, last_update, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $ssin, $name, $date_of_attaining_60, $phone_no, $status, $last_update, $created_at);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Data added successfully']);
    } else {
        echo json_encode(['error' => 'Failed to add data']);
    }

    $stmt->close();
    $conn->close();
}
?>

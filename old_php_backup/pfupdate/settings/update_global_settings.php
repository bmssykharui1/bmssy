<?php
// Include your database connection
include '../../database/index.php';

// Set proper content type for AJAX
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Retrieve POST data
    $period_form = $_POST['period_form'] ?? '';
    $period_to   = $_POST['period_to'] ?? '';

    // 1. Basic Validation
    if (empty($period_form) || empty($period_to)) {
        echo json_encode(["status" => "error", "message" => "Missing required date fields"]);
        exit;
    }

    // 2. Format Dates (Safety check)
    // Ensures we are storing YYYY-MM-DD regardless of input format
    $period_form = date("Y-m-d", strtotime($period_form));
    $period_to   = date("Y-m-d", strtotime($period_to));

    // 3. Check if Row Exists
    $checkQuery = "SELECT id FROM global_settings WHERE id = 1";
    $result = $conn->query($checkQuery);

    if ($result->num_rows > 0) {
        // --- UPDATE Logic ---
        $query = "UPDATE global_settings SET period_form = ?, period_to = ? WHERE id = 1";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "Prepare update failed: " . $conn->error]);
            exit;
        }
        
        $stmt->bind_param("ss", $period_form, $period_to);
    } else {
        // --- INSERT Logic (Fallback if row doesn't exist) ---
        $query = "INSERT INTO global_settings (id, period_form, period_to) VALUES (1, ?, ?)";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "Prepare insert failed: " . $conn->error]);
            exit;
        }
        
        $stmt->bind_param("ss", $period_form, $period_to);
    }

    // 4. Execute
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Settings updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database execution failed: " . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
}

$conn->close();
?>
<?php
include '../../database/index.php'; // Include your database connection file

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = $_POST['id'];

    // Update status to "Active"
    $query = "UPDATE beneficiaries SET status = 'Active' WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request";
}
?>

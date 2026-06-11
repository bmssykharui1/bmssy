<?php
// Set file path for the log
$logFile = 'error_logs.txt';

// Check if error details are posted
if (isset($_POST['error_message'])) {
    // Get the error details from the POST request
    $errorMessage = $_POST['error_message'];
    $stackTrace = isset($_POST['stack_trace']) ? $_POST['stack_trace'] : 'No stack trace provided';

    // Format the error message with timestamp
    $logEntry = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $errorMessage . "\nStack Trace: " . $stackTrace . "\n\n";

    // Write the error message to the log file
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // Return a success response to the client
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'No error message provided']);
}
?>

<?php
// 1. Define the path for the log file (creates a txt file in the same folder as db.php)
$log_file = __DIR__ . '/site_traffic_log.txt';

// 2. Gather visitor information
$current_time = date('Y-m-d H:i:s');
$visitor_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN IP';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN AGENT';

// 3. Construct the exact URL they visited
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$requested_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// 4. Format the log entry
$log_entry = "[$current_time] | IP: $visitor_ip | URL: $requested_url | Bot/Browser: $user_agent\n";

// 5. Append the entry to the log file safely
@file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

// ... Your existing db.php connection code goes below here ...



    $conn = new mysqli("sql213.infinityfree.com", "if0_37856532", "MamataBMSSY2024", "if0_37856532_bmssy");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    ?>
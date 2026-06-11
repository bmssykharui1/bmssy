<?php
header('Content-Type: application/json');

// Function to get CPU load (works on Linux)
function getCpuLoad() {
    $load = sys_getloadavg();
    return round($load[0], 2); // Get 1-minute load average
}

// Function to get RAM usage (Linux method)
function getRamUsage() {
    $data = @file_get_contents("/proc/meminfo");
    if ($data) {
        $lines = explode("\n", $data);
        $memTotal = (int) filter_var($lines[0], FILTER_SANITIZE_NUMBER_INT);
        $memFree = (int) filter_var($lines[1], FILTER_SANITIZE_NUMBER_INT);
        $memAvailable = (int) filter_var($lines[2], FILTER_SANITIZE_NUMBER_INT);
        
        $memUsed = $memTotal - $memAvailable;
        $ramUsage = round(($memUsed / $memTotal) * 100, 2);
        return $ramUsage;
    }
    return 0; // Default to 0 if unable to read
}

echo json_encode([
    "cpu_usage" => getCpuLoad(),
    "ram_usage" => getRamUsage()
]);
?>

<?php
// Log all errors to a file (display is disabled on InfinityFree)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);

header('Content-Type: application/json');

// Safely include the DB connection
require_once __DIR__ . '/../database/index.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $ssin = $_POST['ssin'] ?? null;

    if (!$ssin || strlen($ssin) !== 12 || !ctype_digit($ssin)) {
        throw new Exception("Invalid SSIN provided: $ssin");
    }

    // === MAIN QUERY ===
    $stmt = $conn->prepare("SELECT beneficiary_name, date_of_attaining_60, approved_ssin, phone_no, status, last_update FROM beneficiaries WHERE approved_ssin = ?");
    if (!$stmt) {
        throw new Exception("Main query prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $ssin);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        throw new Exception("Failed to fetch result: " . $stmt->error);
    }

    if ($row = $result->fetch_assoc()) {
        $response = [
            'exists' => true,
            'ssin' => $row['approved_ssin'],
            'name' => $row['beneficiary_name'],
            'date_of_attaining_60' => date('d-m-Y', strtotime($row['date_of_attaining_60'])),
            'phone_no' => $row['phone_no'],
            'status' => $row['status'],
            'last_update' => $row['last_update'],
            'pf_updates' => [],
            'total_amount' => 0
        ];

        // === PF UPDATE QUERY ===
        $stmt2 = $conn->prepare("SELECT period_form AS period_from, period_to, last_update FROM pf_update WHERE approved_ssin = ? AND status = 'Accepted'");
        if (!$stmt2) {
            throw new Exception("PF update query prepare failed: " . $conn->error);
        }

        $stmt2->bind_param("s", $ssin);
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        $totalAmount = 0;

        while ($pfRow = $result2->fetch_assoc()) {
            try {
                $from = new DateTime($pfRow['period_from']);
                $to = new DateTime($pfRow['period_to']);
                $to->modify('+1 day');

                $interval = $from->diff($to);
                $months = ($interval->y * 12) + $interval->m;
                if ($interval->d > 0) $months++;

                $amount = $months * 55;
                $totalAmount += $amount;

                $response['pf_updates'][] = [
                    'period_from' => date('d-m-Y', strtotime($pfRow['period_from'])),
                    'period_to' => date('d-m-Y', strtotime($pfRow['period_to'])),
                    'last_update' => $pfRow['last_update'],
                    'months' => $months,
                    'amount' => $amount
                ];
            } catch (Exception $e) {
                error_log("PF row parsing error: " . $e->getMessage());
            }
        }

        $response['total_amount'] = $totalAmount;

        echo json_encode($response);

        $stmt2->close();
    } else {
        echo json_encode(['exists' => false]);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log("Fatal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

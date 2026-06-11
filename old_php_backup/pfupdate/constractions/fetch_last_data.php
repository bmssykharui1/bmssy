<?php
include '../../database/index.php';

$query = "SELECT beneficiary_name, approved_ssin, status, 
                 COALESCE(period_form, 'Not Available') AS period_form, 
                 COALESCE(period_to, 'Not Available') AS period_to, 
                 COALESCE(reason, 'No Reason Provided') AS reason  -- ✅ Include reason
          FROM pf_update
          ORDER BY last_update DESC
          LIMIT 1";  // ✅ Only fetch the latest record

$result = $conn->query($query);
$data = $result->fetch_assoc() ?: [];

echo json_encode(["data" => $data]);  // Return as JSON
$conn->close();
?>

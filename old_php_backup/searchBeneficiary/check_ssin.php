<?php
header('Content-Type: application/json');
include '../database/index.php'; // Include your database connection file

error_reporting(E_ALL);
ini_set('display_errors', 1);

$ssin = $_POST['ssin'];

// Step 1: Query beneficiaries table for the SSIN details
$queryBeneficiary = "SELECT beneficiary_name, remark, date_of_attaining_60, phone_no, status, last_update FROM beneficiaries WHERE approved_ssin = ?";
$stmtBeneficiary = $conn->prepare($queryBeneficiary);
$stmtBeneficiary->bind_param("s", $ssin);
$stmtBeneficiary->execute();
$resultBeneficiary = $stmtBeneficiary->get_result();

// Step 2: Query pf_update table for period_form and period_to for the SSIN
$queryPF = "SELECT period_form, period_to FROM pf_update WHERE approved_ssin = ? AND status = 'Accepted'";
$stmtPF = $conn->prepare($queryPF);
$stmtPF->bind_param("s", $ssin);
$stmtPF->execute();
$resultPF = $stmtPF->get_result();

if ($rowBeneficiary = $resultBeneficiary->fetch_assoc()) {
  // Step 3: If beneficiary data is found, fetch PF data
  $response = [
    "success" => true,
    "name" => $rowBeneficiary["beneficiary_name"],
    "remark" => $rowBeneficiary["remark"],
    "date60" => date("d M Y", strtotime($rowBeneficiary["date_of_attaining_60"])),
    "phone" => $rowBeneficiary["phone_no"],
    "status" => $rowBeneficiary["status"],
    "last_update" => $rowBeneficiary["last_update"]
  ];

  if ($rowPF = $resultPF->fetch_assoc()) {
    // Step 4: Format and add period_form and period_to to the response
    $periodForm = date("d M Y", strtotime($rowPF['period_form']));
    $periodTo = date("d M Y", strtotime($rowPF['period_to']));
    $response['period_form'] = "<span class='bg-success text-white px-2 py-1 rounded'>$periodForm</span>";
    $response['period_to'] = "<span class='bg-success text-white px-2 py-1 rounded'>$periodTo</span>";
  } else {
    // If no data in pf_update
    $response['period_form'] = "Not available";
    $response['period_to'] = "Not available";
  }

  // Return the response
  echo json_encode($response);
} else {
  // If no beneficiary found
  echo json_encode(["success" => false]);
}
?>

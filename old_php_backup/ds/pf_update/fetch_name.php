<?php
// fetch_name.php
include '../../database/index.php';

if (isset($_GET['ssin'])) {
    $ssin = $_GET['ssin'];
    $stmt = $conn->prepare("SELECT beneficiary_name FROM beneficiaries WHERE approved_ssin = ?");
    $stmt->bind_param("s", $ssin);
    $stmt->execute();
    $stmt->bind_result($name);
    if ($stmt->fetch()) {
        echo htmlspecialchars($name);
    } else {
        echo "";
    }
    $stmt->close();
}
?>

<?php
include '../../database/index.php';

$id = $_POST['id'];
$name = $_POST['name'];
$ssin = $_POST['ssin'];
$dob = $_POST['dob'];
$phone = $_POST['phone'];

date_default_timezone_set('Asia/Kolkata');
$last_update = date('Y-m-d H:i:s');

$query = "UPDATE beneficiaries SET beneficiary_name='$name', approved_ssin='$ssin', date_of_attaining_60='$dob', phone_no='$phone', last_update='$last_update' WHERE id='$id'";

if (mysqli_query($conn, $query)) {
    echo "success";
} else {
    echo "error";
}
?>

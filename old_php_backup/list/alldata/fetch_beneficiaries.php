<?php
include '../../database/index.php';

$sql = "SELECT * FROM beneficiaries WHERE status = 'active' ORDER BY last_update DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $datetime = new DateTime($row['last_update']);
        $formattedDate = $datetime->format('d F Y h.i.s A'); 
        echo "<tr>";
        echo "<td>" . $row['beneficiary_name'] . "</td>";
        echo "<td>" . $row['approved_ssin'] . "</td>";
        echo "<td>" . $row['date_of_attaining_60'] . "</td>";
        echo "<td>" . $row['phone_no'] . "</td>";
        echo "<td>" . $formattedDate . "</td>";
        echo "<td>
                <button class='btn btn-info btn-sm updateBtn' 
                    data-id='{$row['id']}' 
                    data-name='{$row['beneficiary_name']}' 
                    data-ssin='{$row['approved_ssin']}' 
                    data-dob='{$row['date_of_attaining_60']}' 
                    data-phone='{$row['phone_no']}'>
                    <i class='fas fa-edit'></i> Update
                </button>
              </td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6' class='text-center'>No Data Available</td></tr>";
}
?>

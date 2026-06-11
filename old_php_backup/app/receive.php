<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = $_POST['data'];
    echo "Hello from PHP! You sent: " . $input;
} else {
    echo "Only POST method allowed";
}
?>

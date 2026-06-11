<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $text = $_POST['text'] ?? '';
    echo "Server received: " . $text;
} else {
    echo "Send POST request only!";
}
?>

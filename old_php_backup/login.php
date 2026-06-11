<?php


session_start();
include "./database/index.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $remember = isset($_POST["remember"]);

    // Get user details from database
    $query = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hashed_password = $row["password"];

        // Verify the entered password with the hashed password
        if (password_verify($password, $hashed_password)) {
            
            // SECURITY FIX: Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            $_SESSION["user_id"] = $row["id"];
            $_SESSION["user_name"] = $row["name"];

            // If "Remember Me" is checked
            if ($remember) {
                // SECURITY FIX: Never store the password/hash in a cookie.
                // We also add `true, true` at the end to make the cookie HttpOnly and Secure.
                setcookie("remembered_email", $email, time() + (86400 * 30), "/", "", true, true);
            } else {
                setcookie("remembered_email", "", time() - 3600, "/", "", true, true);
            }

            header("Location: /dashboard");
            exit();
        } else {
            header("Location: /?error=wrong_password");
            exit();
        }
    } else {
        header("Location: /?error=invalid_user");
        exit();
    }
}
?>
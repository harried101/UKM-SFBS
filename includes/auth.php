<?php
session_start();
require_once("db_connect.php"); // connect DB

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $matric = strtolower(trim($_POST['matric'])); 
    $password = trim($_POST['password']);

    if (empty($matric) || empty($password)) {
        $_SESSION['error'] = "Please enter matric number and password.";
        header("Location: index.php");
        exit();
    }

    // Prepare SQL query
    $sql = "SELECT * FROM users WHERE matric_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $matric);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Check password
        if (password_verify($password, $user['password'])) {

            // Store session data
            $_SESSION['user'] = $user;

            // ROLE HANDLING
            $firstLetter = $matric[0];  // get first character

            if ($firstLetter == 'a') {
                header("Location: student/dashboard.php");
                exit();
            }

            if ($firstLetter == 'k') {
                header("Location: admin/dashboard.php");
                exit();
            }

            // If not A or K
            $_SESSION['error'] = "Invalid role detected.";
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['error'] = "Incorrect password.";
            header("Location: index.php");
            exit();
        }

    } else {
        $_SESSION['error'] = "Matric number not found.";
        header("Location: index.php");
        exit();
    }
}
?>

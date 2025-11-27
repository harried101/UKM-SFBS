<?php
session_start();
require_once("db_connect.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $matric = strtolower(trim($_POST['matric']));
    $password = trim($_POST['password']);

    if (empty($matric) || empty($password)) {
        $_SESSION['error'] = "Please enter matric number and password.";
        header("Location: index.php");
        exit();
    }

    // Check user exists
    $sql = "SELECT * FROM users WHERE matric_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $matric);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        // Check password
        if (password_verify($password, $user['password'])) {

            $_SESSION['user'] = $user; // store user data

            // ROLE DETECTION BY FIRST LETTER
            $first = $matric[0];

            if ($first == "a") {
                header("Location: student/dashboard.php");
                exit();
            }

            if ($first == "k") {
                header("Location: admin/dashboard.php");
                exit();
            }

            $_SESSION['error'] = "Invalid role.";
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

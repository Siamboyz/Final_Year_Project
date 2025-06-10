<?php
include "connection_database.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; // Do NOT escape password, as it's hashed

    // Define tables and their corresponding ID columns
    $tables = [
        "counter_staff" => "staff_id",
        "doctor" => "doctor_id",
        "admin" => "admin_id"
    ];

    $user = null;
    $id_column = "";
    $role = "";

    foreach ($tables as $table => $column) {
        // Query to check user email in each table
        $query = "SELECT * FROM $table WHERE email = '$email' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            $id_column = $column; // Get the correct ID column
            $role = $table; // Assign the role dynamically
            $userFound = true;

            // 🔒 Doctor-specific block check
            if ($table === 'doctor' && $user['status'] === 'inactive') {
                $_SESSION['temp_doctor_email'] = $user['email'];
                // Redirect to block page or show alert
                echo "<script>alert('Your account is currently inactive. Please contact admin to reactivate.'); window.location.href='blocked_doctor.php';</script>";
                exit();
            }

            // Check if the password is correct
            if ($user && password_verify($password, $user['password'])) {
                // Store session variables
                $_SESSION['user_id'] = $user[$id_column];
                $_SESSION['role'] = $role;
                $_SESSION['name'] = $user['name'];

                // Redirect based on role
                if ($role == "counter_staff") {
                    header("Location: staff_dashboard.php");
                } elseif ($role == "doctor") {
                    header("Location: doctor_dashboard.php");
                } elseif ($role == "admin") {
                    header("Location: admin_dashboard.php");
                }
                exit();
            } else {
                echo "<script>alert('Invalid email or password!'); window.location.href='homepage.php';</script>";
            }
        }
    }
    // ✅ If no user found in any table
    if (!$userFound) {
        echo "<script>alert('Invalid email or password!'); window.location.href='homepage.php';</script>";
        exit();
    }
}
?>

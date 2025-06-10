<?php
session_start();
include "connection_database.php"; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register-button'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role']; // Selected role

    // Hash the password before storing
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Generate a 6-character unique numeric ID
    function generateUUID() {
        return str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT); // Ensures 6-digit ID
    }

    $id = generateUUID();

    // Determine the correct table and ID column
    $table = "";
    $id_column = "";

    if ($role == "counter_staff") {
        $table = "counter_staff";
        $id_column = "staff_id";
    } elseif ($role == "doctor") {
        $table = "doctor";
        $id_column = "doctor_id";
    } elseif ($role == "admin") {
        $table = "admin";
        $id_column = "admin_id";
    } else {
        die("Invalid role selected.");
    }

    // Check if the email or username already exists
    $check_query = "SELECT * FROM $table WHERE email = '$email' OR name = '$name' LIMIT 1";
    $result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($result) > 0) {
        echo "<script>
                alert('Error: Username or Email already exists!');
                window.location.href = 'homepage.php';
              </script>";
    } else {
        // Insert user with dynamically assigned ID column
        $query = "INSERT INTO $table ($id_column, name, email, password) VALUES ('$id', '$name', '$email', '$hashed_password')";

        if (mysqli_query($conn, $query)) {
            echo "<script>
                alert('Registration successful! Click OK to log in.');
                window.location.href = 'homepage.php';
              </script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}
?>

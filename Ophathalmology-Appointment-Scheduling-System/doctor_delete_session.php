<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: homepage.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    echo "<script>alert('Session ID missing.'); window.location.href='doctor_session.php';</script>";
    exit();
}

$session_id = $_GET['id'];

// Check if session belongs to this doctor
$checkQuery = "SELECT * FROM session WHERE session_id = '$session_id' AND doctor_id = '$doctor_id'";
$checkResult = mysqli_query($conn, $checkQuery);

if (mysqli_num_rows($checkResult) == 0) {
    echo "<script>alert('Unauthorized or session not found.'); window.location.href='doctor_session.php';</script>";
    exit();
}

// Proceed to delete
$deleteQuery = "DELETE FROM session WHERE session_id = '$session_id' AND doctor_id = '$doctor_id'";
$deleteResult = mysqli_query($conn, $deleteQuery);

if ($deleteResult) {
    echo "<script>alert('Session deleted successfully.'); window.location.href='doctor_session.php';</script>";
} else {
    echo "<script>alert('Failed to delete session.'); window.location.href='doctor_session.php';</script>";
}
?>

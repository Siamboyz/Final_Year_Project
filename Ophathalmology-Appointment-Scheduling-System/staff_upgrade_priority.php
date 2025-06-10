<?php
session_start();
include 'connection_database.php';

// Ensure only logged-in staff can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counter_staff') {
    header("Location: homepage.php");
    exit();
}

// Validate if apt_id exists
if (!isset($_GET['apt_id'])) {
    echo "<script>alert('Invalid appointment ID.'); window.location.href='staff_queue.php';</script>";
    exit();
}

$apt_id = mysqli_real_escape_string($conn, $_GET['apt_id']);

// Fetch current priority
$checkQuery = "SELECT apt_priority FROM appointment WHERE apt_id = '$apt_id'";
$checkResult = mysqli_query($conn, $checkQuery);

if (!$checkResult || mysqli_num_rows($checkResult) == 0) {
    echo "<script>alert('Appointment not found.'); window.location.href='staff_queue.php';</script>";
    exit();
}

$current = mysqli_fetch_assoc($checkResult);
$currentPriority = (int)$current['apt_priority'];

// If already high, no need to upgrade
if ($currentPriority >= 3) {
    echo "<script>alert('Priority is already High!'); window.location.href='staff_queue.php';</script>";
    exit();
}

// Upgrade priority to High (3)
$updateQuery = "UPDATE appointment SET apt_priority = 3 WHERE apt_id = '$apt_id'";

if (mysqli_query($conn, $updateQuery)) {
    echo "<script>alert('✅ Appointment priority upgraded to HIGH successfully!'); window.location.href='staff_queue.php';</script>";
} else {
    echo "<script>alert('❌ Error upgrading priority: " . mysqli_error($conn) . "'); window.location.href='staff_queue.php';</script>";
}
?>

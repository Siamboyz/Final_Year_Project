<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

// Check if staff ID is valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('❌ Invalid staff ID.'); window.location.href='admin_manage_staff.php';</script>";
    exit();
}

$staff_id = mysqli_real_escape_string($conn, $_GET['id']);

// Check if staff exists
$check = mysqli_query($conn, "SELECT * FROM counter_staff WHERE staff_id = '$staff_id'");
if (mysqli_num_rows($check) === 0) {
    echo "<script>alert('❌ Staff not found.'); window.location.href='admin_manage_staff.php';</script>";
    exit();
}

// Delete staff
$delete = mysqli_query($conn, "DELETE FROM counter_staff WHERE staff_id = '$staff_id'");

if ($delete) {
    echo "<script>alert('✅ Staff deleted successfully.'); window.location.href='admin_manage_staff.php';</script>";
} else {
    echo "<script>alert('❌ Failed to delete staff.'); window.location.href='admin_manage_staff.php';</script>";
}
?>

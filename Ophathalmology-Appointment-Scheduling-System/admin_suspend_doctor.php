<?php
session_start();
include 'connection_database.php';


error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Ensure only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

// ✅ Validate doctor_id from GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('❌ Invalid doctor ID.'); window.location.href='admin_manage_doctor.php';</script>";
    exit();
}

$doctor_id = intval($_GET['id']);

// ✅ Check if doctor exists
$check = mysqli_query($conn, "SELECT * FROM doctor WHERE doctor_id = $doctor_id");
if (!$check || mysqli_num_rows($check) === 0) {
    echo "<script>alert('❌ Doctor not found.'); window.location.href='admin_manage_doctor.php';</script>";
    exit();
}

// ✅ Attempt to delete doctor
$delete = mysqli_query($conn, "UPDATE doctor SET status = 'inactive' WHERE doctor_id = $doctor_id");

// ✅ Handle result
if ($delete) {
    echo "<script>alert('✅ Doctor suspend successfully.'); window.location.href='admin_manage_doctor.php';</script>";
} else {
    $error = mysqli_error($conn); // Capture MySQL error
    echo "<script>alert('❌ Failed to suspend doctor.\\nError: " . addslashes($error) . "'); window.location.href='admin_manage_doctor.php';</script>";
}
?>

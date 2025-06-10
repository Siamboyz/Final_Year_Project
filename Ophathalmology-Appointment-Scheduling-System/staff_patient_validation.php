<?php
session_start();
include 'connection_database.php';

// Step 1: Check if ID is passed and user is authorized
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counter_staff') {
    header("Location: homepage.php");
    exit();
}

if (isset($_GET['apt_id']) && is_numeric($_GET['apt_id'])) {
    $apt_id = $_GET['apt_id'];  // Safe because is_numeric
    date_default_timezone_set('Asia/Kuala_Lumpur');
    $validateTime = date('Y-m-d H:i:s');

    // Step 2: Prepare and execute the query
    $updateQuery = "UPDATE appointment 
                SET apt_status = 'Scheduled', 
                    validated_datetime = '$validateTime' 
                WHERE apt_id = '$apt_id'";

    if (mysqli_query($conn, $updateQuery)) {
        // Step 3: Go back or forward
        header("Location: staff_appointment.php?updated=success");
        exit();
    } else {
        echo "<script>alert('❌ Failed to update appointment status.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('❌ Invalid appointment ID.'); window.history.back();</script>";
}
?>



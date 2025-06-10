<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $room_id = intval($_GET['id']);

    // Step 1: Set doctor.room_id = NULL for doctors using this room
    $clearDoctors = mysqli_query($conn, "UPDATE doctor SET room_id = NULL WHERE room_id = '$room_id'");

    // Step 2: Now delete the room
    $delete = mysqli_query($conn, "DELETE FROM room WHERE room_id = '$room_id'");

    if ($delete) {
        echo "<script>alert('✅ Room deleted successfully. Doctors unassigned from the room.'); window.location.href='admin_manage_room.php';</script>";
    } else {
        echo "<script>alert('❌ Failed to delete room.'); window.location.href='admin_manage_room.php';</script>";
    }
} else {
    echo "<script>alert('❗ Invalid room ID.'); window.location.href='admin_manage_room.php';</script>";
}
?>

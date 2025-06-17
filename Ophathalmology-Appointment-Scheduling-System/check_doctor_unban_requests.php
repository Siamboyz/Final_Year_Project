<?php
// check_doctor_unban_requests.php

require_once 'connection_database.php'; // This should define $conn as a MySQLi connection

header('Content-Type: application/json');

$hasRequests = false;

try {
    $query = "SELECT COUNT(*) AS pending_count FROM doctor_unblock_requests WHERE status = 'pending'";
    $result = mysqli_query($conn, $query);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row['pending_count'] > 0) {
            $hasRequests = true;
        }
    } else {
        error_log("MySQL Error in check_doctor_unban_requests.php: " . mysqli_error($conn));
        $hasRequests = false;
    }
} catch (Exception $e) {
    error_log("Exception in check_doctor_unban_requests.php: " . $e->getMessage());
    $hasRequests = false;
}

echo json_encode(['hasRequests' => $hasRequests]);
?>

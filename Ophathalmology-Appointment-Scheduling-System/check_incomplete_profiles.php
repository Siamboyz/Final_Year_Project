<?php
require_once 'connection_database.php'; // This should define $conn as a mysqli connection

header('Content-Type: application/json');

$response = [
    'hasIncompleteProfiles' => false,
    'count' => 0
];

try {
    // Use MySQLi to check for incomplete profiles
    $query = "SELECT COUNT(*) AS incomplete_count FROM patient WHERE profile_completed = 0";
    $result = mysqli_query($conn, $query);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row && $row['incomplete_count'] > 0) {
            $response['hasIncompleteProfiles'] = true;
            $response['count'] = (int)$row['incomplete_count'];
        }
    } else {
        // Error in SQL query
        $response['error'] = 'Database query failed.';
        error_log("MySQL Error in check_incomplete_profiles.php: " . mysqli_error($conn));
    }
} catch (Exception $e) {
    $response['error'] = 'An unexpected error occurred.';
    error_log("Exception in check_incomplete_profiles.php: " . $e->getMessage());
}

echo json_encode($response);
?>

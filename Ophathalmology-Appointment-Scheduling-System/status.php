<?php
header('Content-Type: application/json');
include 'connection_database.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

// Check DB
$db_status = $conn ? 'Online' : 'Offline';

// Check if AJAX/API endpoint is accessible
$api_status = @file_get_contents('report_ajax.php') !== false ? 'Online' : 'Offline';

// Return JSON
echo json_encode([
    'db' => $db_status,
    'api' => $api_status,
    'lastSync' => date('d M Y, H:i')
]);
?>

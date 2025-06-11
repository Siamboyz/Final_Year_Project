<?php
include 'connection_database.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

$startDate = $_POST['start_date'] ?? date('Y-m-d');
$endDate = $_POST['end_date'] ?? $startDate;
$filterType = $_POST['filter_type'] ?? 'custom';

switch ($filterType) {
    case 'day':
        $startDate = $endDate = date('Y-m-d');
        break;
    case 'week':
        $startDate = date('Y-m-d', strtotime('-6 days'));
        $endDate = date('Y-m-d');
        break;
    case 'month':
        $startDate = date('Y-m-d', strtotime('-1 month'));
        $endDate = date('Y-m-d');
        break;
    // 'custom' uses passed values directly
}

$condition = "apt_date BETWEEN '$startDate' AND '$endDate'";

$data = [
    'total_appt' => 0,
    'completed' => 0,
    'visit_types' => [],
    'doctors' => [],
    'avg_wait_times' => [],
    'doctor_utilization' => [],
    'room_usage' => [],
    'no_show_trends' => []
];

// 1. Total appointments
$res = mysqli_query($conn, "SELECT COUNT(*) FROM appointment WHERE $condition");
if ($res) $data['total_appt'] = mysqli_fetch_array($res)[0];

// 2. Completed appointments
$res = mysqli_query($conn, "SELECT COUNT(*) FROM appointment WHERE $condition AND apt_status = 'Completed'");
if ($res) $data['completed'] = mysqli_fetch_array($res)[0];

// 3. Visit types
$res = mysqli_query($conn, "SELECT visit_type, COUNT(*) AS total FROM appointment WHERE $condition GROUP BY visit_type");
while ($row = mysqli_fetch_assoc($res)) {
    $data['visit_types'][$row['visit_type']] = $row['total'];
}

// 4. Doctor appointments
$res = mysqli_query($conn, "
    SELECT d.name, COUNT(a.apt_id) AS total 
    FROM doctor d
    LEFT JOIN appointment a ON a.doctor_id = d.doctor_id 
    WHERE $condition
    GROUP BY d.doctor_id
    ORDER BY total DESC
    LIMIT 5
");
while ($row = mysqli_fetch_assoc($res)) {
    $data['doctors'][$row['name']] = $row['total'];
}

// 5. Avg Wait Time per Visit Type
$res = mysqli_query($conn, "
    SELECT visit_type, AVG(TIMESTAMPDIFF(MINUTE, validated_datetime, CONCAT(apt_date, ' ', apt_time))) AS avg_wait
    FROM appointment
    WHERE validated_datetime IS NOT NULL AND apt_time IS NOT NULL AND $condition
    GROUP BY visit_type
");
while ($row = mysqli_fetch_assoc($res)) {
    $data['avg_wait_times'][$row['visit_type']] = round($row['avg_wait'], 1);
}

// 6. Doctor Utilization
$res = mysqli_query($conn, "
    SELECT d.name, SUM(a.duration_minutes) AS total_minutes
    FROM doctor d
    LEFT JOIN appointment a ON d.doctor_id = a.doctor_id
    WHERE $condition
    GROUP BY d.doctor_id
");
while ($row = mysqli_fetch_assoc($res)) {
    $data['doctor_utilization'][$row['name']] = round(($row['total_minutes'] / 480) * 100, 1);
}

// 7. Doctor Utilization by Total Minutes Served
$res = mysqli_query($conn, "
    SELECT d.name, SUM(a.duration_minutes) AS total_minutes
    FROM doctor d
    LEFT JOIN appointment a ON d.doctor_id = a.doctor_id
    WHERE $condition
    GROUP BY d.doctor_id
");
while ($row = mysqli_fetch_assoc($res)) {
    $data['doctor_Utilization'][$row['name']] = $row['total_minutes'] ?? 0;
}

header('Content-Type: application/json');
echo json_encode($data);
mysqli_close($conn);
?>

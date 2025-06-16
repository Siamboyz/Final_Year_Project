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
        // Start date is 6 days before end date (inclusive, to get a 7-day week)
        $startDate = date('Y-m-d', strtotime($endDate . ' -6 days'));
        break;
    case 'month':
        // Start date is the first day of the month of the end date
        $startDate = date('Y-m-01', strtotime($endDate));
        break;
    // 'custom' uses passed values directly
}

$condition = "apt_date BETWEEN '$startDate' AND '$endDate'";

$data = [
    'appointmentsToday' => 0,
    'completedAppointments' => 0,
    'missed' => 0,
    'monthly_labels' => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], // For Monthly Appointments
    'monthly_data' => [], // For Monthly Appointments
    'visit_types' => [],
    'top_doctors_by_appointments' => [],
    'avg_wait_times' => [],
    'doctor_utilization_percent' => [],
    'doctor_utilization_minutes' => [],
    'no_show_labels' => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    'no_show_data' => []
];

// --- Summary Card Data (ONLY those that depend on the selected date range) ---

// Appointments Today (within the filtered range)
// Note: CURDATE() makes this strictly "today" based on server time.
// If you want "appointments on the end date of the filter", use DATE(apt_date) = '$endDate'.
// I'll assume "Today's Appointments" means appointments on the *current actual day*, regardless of selected range.
// If it should refer to the 'end date' of the filter, then change CURDATE() to '$endDate'.
$res = mysqli_query($conn, "SELECT COUNT(*) FROM appointment WHERE DATE(apt_date) = CURDATE()");
if ($res) $data['appointmentsToday'] = mysqli_fetch_array($res)[0];

// Completed Appointments (within the filtered range)
$res = mysqli_query($conn, "SELECT COUNT(*) FROM appointment WHERE apt_status = 'Completed' AND $condition");
if ($res) $data['completedAppointments'] = mysqli_fetch_array($res)[0];

// Missed Appointments (within the filtered range)
$res = mysqli_query($conn, "SELECT COUNT(*) FROM appointment WHERE apt_status = 'Missed' AND was_missed = 1 AND $condition");
if ($res) $data['missed'] = mysqli_fetch_array($res)[0];


// 1. Monthly Appointments (Full year, independent of filter for general overview)
// This remains a full-year overview of the CURRENT year.
for ($i = 1; $i <= 12; $i++) {
    $res = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM appointment
        WHERE MONTH(apt_date) = $i
        AND YEAR(apt_date) = YEAR(CURDATE())
    ");
    $data['monthly_data'][] = mysqli_fetch_array($res)['total'];
}


// 2. Visit types (filtered by date range)
$res = mysqli_query($conn, "SELECT visit_type, COUNT(*) AS total FROM appointment WHERE $condition GROUP BY visit_type");
while ($row = mysqli_fetch_assoc($res)) {
    $data['visit_types'][$row['visit_type']] = (int)$row['total'];
}

// 3. Top 5 Doctor Appointments (filtered by date range)
$res = mysqli_query($conn, "
    SELECT d.name, COUNT(a.apt_id) AS total
    FROM doctor d
    LEFT JOIN appointment a ON a.doctor_id = d.doctor_id
    WHERE $condition AND apt_status = 'Completed'
    GROUP BY d.doctor_id
    ORDER BY total DESC
    LIMIT 5
");
while ($row = mysqli_fetch_assoc($res)) {
    $data['top_doctors_by_appointments'][$row['name']] = (int)$row['total'];
}

// 4. Avg Wait Time per Visit Type (filtered by date range)
$res = mysqli_query($conn, "
    SELECT visit_type, AVG(TIMESTAMPDIFF(MINUTE, validated_datetime, CONCAT(apt_date, ' ', apt_time))) AS avg_wait
    FROM appointment
    WHERE validated_datetime IS NOT NULL AND apt_time IS NOT NULL AND $condition
    GROUP BY visit_type
");
while ($row = mysqli_fetch_assoc($res)) {
    $data['avg_wait_times'][$row['visit_type']] = round($row['avg_wait'], 1);
}

// 5. Doctor Utilization (Percentage - filtered by date range)
// Assuming 8 hours workday = 480 minutes for this calculation within the selected range
$res = mysqli_query($conn, "
    SELECT d.name, SUM(a.duration_minutes) AS total_minutes
    FROM doctor d
    LEFT JOIN appointment a ON d.doctor_id = a.doctor_id
    WHERE $condition AND apt_status = 'Completed'
    GROUP BY d.doctor_id
");
while ($row = mysqli_fetch_assoc($res)) {
    $data['doctor_utilization_percent'][$row['name']] = round((($row['total_minutes'] ?? 0) / 480) * 100, 1);
}

// 6. Doctor Utilization by Total Minutes Served (Raw Minutes - filtered by date range)
$res = mysqli_query($conn, "
    SELECT d.name, SUM(a.duration_minutes) AS total_minutes
    FROM doctor d
    LEFT JOIN appointment a ON d.doctor_id = a.doctor_id
    WHERE $condition AND apt_status = 'Completed'
    GROUP BY d.doctor_id
");
while ($row = mysqli_fetch_assoc($res)) {
    $data['doctor_utilization_minutes'][$row['name']] = (int)($row['total_minutes'] ?? 0);
}

// 7. No-Show Trends (Monthly Missed Appointments for the current year - independent of filter)
// This remains a full-year overview of the CURRENT year.
for ($i = 1; $i <= 12; $i++) {
    $res = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM appointment
        WHERE apt_status = 'Missed'
        AND was_missed = 1
        AND MONTH(apt_date) = $i
        AND YEAR(apt_date) = YEAR(CURDATE())
    ");
    $total = mysqli_fetch_assoc($res)['total'];
    $data['no_show_data'][] = (int)$total;
}

header('Content-Type: application/json');
echo json_encode($data);
mysqli_close($conn);
?>
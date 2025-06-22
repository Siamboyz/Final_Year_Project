<?php
include 'connection_database.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

// Only fetch active doctors
$doctors = mysqli_query($conn, "SELECT doctor_id, name FROM doctor WHERE status = 'active'");
$today = date('Y-m-d');
$results = [];

while ($doc = mysqli_fetch_assoc($doctors)) {
    $doctor_id = $doc['doctor_id'];
    $doctor_name = $doc['name'];
    $available_day = 'No free day in next 7 days';
    $minutes_available_on_this_day = 0; // Initialize to 0 or another default

    // ✅ Get total appointment count (all time) for today
    $countRes = mysqli_query($conn, "
        SELECT COUNT(*) AS total_appointments 
        FROM appointment 
        WHERE doctor_id = '$doctor_id' AND apt_date = '$today'
    ");
    $total_appointments = (int)mysqli_fetch_assoc($countRes)['total_appointments'];

    // ✅ Check next 7 days for availability
    for ($i = 0; $i < 7; $i++) {
        $check_date = date('Y-m-d', strtotime("+$i days", strtotime($today)));

        $res = mysqli_query($conn, "
            SELECT SUM(duration_minutes) AS total_minutes
            FROM appointment
            WHERE doctor_id = '$doctor_id' AND apt_date = '$check_date'
        ");
        $total_minutes = (int)mysqli_fetch_assoc($res)['total_minutes'];

        if ($total_minutes < 480) { // Assuming 480 minutes (8 hours) is full capacity
            $minutes_available_on_this_day = 480 - $total_minutes; // Calculate available minutes
            $available_day = $check_date . " (" . $minutes_available_on_this_day . " mins available)";
            break; // Found an available day, exit loop
        }
    }

    $results[] = [
        'doctor_id' => $doctor_id,
        'doctor' => $doctor_name,
        'total_appointments' => $total_appointments,
        'next_available_day' => $available_day,
        'available_minutes' => $minutes_available_on_this_day // ADD THIS LINE
    ];
}

header('Content-Type: application/json');
echo json_encode($results);
?>
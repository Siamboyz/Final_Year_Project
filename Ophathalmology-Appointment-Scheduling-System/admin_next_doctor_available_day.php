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
    $total_appointments = 0; // Initialize total appointments to 0

    // ✅ Check next 7 days for availability (Skip today if already fully booked)
    for ($i = 1; $i < 7; $i++) {  // Start from 1 to skip today's date
        $check_date = date('Y-m-d', strtotime("+$i days", strtotime($today)));

        // Get total minutes for the check_date (appointments already booked)
        $res = mysqli_query($conn, "
            SELECT SUM(duration_minutes) AS total_minutes
            FROM appointment
            WHERE doctor_id = '$doctor_id' AND apt_date = '$check_date'
        ");
        $total_minutes = (int)mysqli_fetch_assoc($res)['total_minutes'];

        if ($total_minutes < 480) { // Assuming 480 minutes (8 hours) is full capacity
            $minutes_available_on_this_day = 480 - $total_minutes; // Calculate available minutes
            $available_day = $check_date . " (" . $minutes_available_on_this_day . " mins available)";

            // Fetch total appointments for the available day
            $countRes = mysqli_query($conn, "
                SELECT COUNT(*) AS total_appointments 
                FROM appointment 
                WHERE doctor_id = '$doctor_id' AND apt_date = '$check_date'
            ");
            $total_appointments = (int)mysqli_fetch_assoc($countRes)['total_appointments'];

            break; // Found an available day, exit loop
        }
    }

    $results[] = [
        'doctor_id' => $doctor_id,
        'doctor' => $doctor_name,
        'total_appointments' => $total_appointments, // Total appointments for the next available day
        'next_available_day' => $available_day,
        'available_minutes' => $minutes_available_on_this_day
    ];
}

header('Content-Type: application/json');
echo json_encode($results);
?>

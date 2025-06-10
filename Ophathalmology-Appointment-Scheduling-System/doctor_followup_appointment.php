<?php
session_start();
include 'connection_database.php';

// Ensure the doctor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: homepage.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['buttonfollowup'])) {
    // Sanitize inputs
    $apt_id = $_POST['a_id'];
    $patient_id = $_POST['p_id'];
    $doctor_id = $_SESSION['user_id'];
    $next_date = $_POST['next_date'];
    $next_time = $_POST['next_time'];
    $priority = $_POST['priority'];
    $status = 'Not Assigned';
    $followup_note = mysqli_real_escape_string($conn, $_POST['followupnote']);

    // Set duration based on priority
    function getDurationByPriority($priorityInt) {
        switch ($priorityInt) {
            case 3: return 45; // High
            case 2: return 20; // Medium
            case 1:
            default: return 20; // Low
        }
    }

    $duration = getDurationByPriority($priority);

    // Step 1: Check if doctor is unavailable or on leave on the selected date
    $leaveCheckSQL = "SELECT * FROM session 
                      WHERE doctor_id = '$doctor_id' 
                      AND s_date = '$next_date' 
                      AND s_status IN ('On Leave', 'Unavailable')";
    $leaveResult = mysqli_query($conn, $leaveCheckSQL);

    if (mysqli_num_rows($leaveResult) > 0) {
        echo "<script>alert('❌ The selected date ($next_date) is unavailable. The doctor is on leave or unavailable.'); window.location.href='doctor_serve_appointment.php';</script>";
        exit();
    }

    // Step 2: Check total scheduled time for that doctor on that date
    $checkSQL = "SELECT SUM(duration_minutes) AS total_scheduled 
                 FROM appointment 
                 WHERE doctor_id = '$doctor_id' AND apt_date = '$next_date'";
    $result = mysqli_query($conn, $checkSQL);
    $row = mysqli_fetch_assoc($result);
    $totalScheduled = isset($row['total_scheduled']) ? $row['total_scheduled'] : 0;

    // Step 3: If over 420 mins, suggest next available date
    if (($totalScheduled + $duration) > 420) {
        $suggestedDate = null;

        for ($i = 1; $i <= 7; $i++) {
            $nextDate = date('Y-m-d', strtotime($next_date . " +$i days"));

            // Skip if doctor is unavailable/leave on that date
            $leaveCheck = "SELECT * FROM session 
                           WHERE doctor_id = '$doctor_id' 
                           AND s_date = '$nextDate' 
                           AND s_status IN ('On Leave', 'Unavailable')";
            $resLeave = mysqli_query($conn, $leaveCheck);
            if (mysqli_num_rows($resLeave) > 0) continue;

            // Check scheduled duration for that day
            $checkNext = "SELECT SUM(duration_minutes) AS total_scheduled 
                          FROM appointment 
                          WHERE doctor_id = '$doctor_id' AND apt_date = '$nextDate'";
            $resNext = mysqli_query($conn, $checkNext);
            $rowNext = mysqli_fetch_assoc($resNext);
            $totalNext = isset($rowNext['total_scheduled']) ? $rowNext['total_scheduled'] : 0;

            if (($totalNext + $duration) <= 420) {
                $suggestedDate = $nextDate;
                $availableSlots = floor((420 - $totalNext) / $duration);
                break;
            }
        }

        if ($suggestedDate) {
            echo "<script>alert('❌ Full on selected day. Next available: $suggestedDate ($availableSlots slot(s) available)'); window.location.href='doctor_serve_appointment.php';</script>";
        } else {
            echo "<script>alert('❌ Fully booked for the next 7 days. Please try again later.'); window.location.href='doctor_serve_appointment.php';</script>";
        }

        exit();
    }

    // Step 4: Insert follow-up appointment
    $insert_sql = "INSERT INTO appointment 
        (patient_id, doctor_id, apt_date, apt_time, apt_status, apt_notes, apt_priority, duration_minutes) 
        VALUES 
        ('$patient_id', '$doctor_id', '$next_date', '$next_time', '$status', '$followup_note', '$priority', '$duration')";

    // Step 5: Update current appointment status to 'Completed'
    $update_sql = "UPDATE appointment SET apt_status = 'Completed' WHERE apt_id = '$apt_id'";

    // Step 6: Execute both queries
    if (mysqli_query($conn, $insert_sql) && mysqli_query($conn, $update_sql)) {
        echo "<script>alert('✅ Follow-up appointment successfully scheduled.'); window.location.href='doctor_serve_appointment.php';</script>";
    } else {
        echo "❌ Error: " . mysqli_error($conn);
    }

} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['completeOnly'])) {
    $apt_id = $_POST['a_id'];

    $update_sql = "UPDATE appointment SET apt_status = 'Completed' WHERE apt_id = '$apt_id'";

    if (mysqli_query($conn, $update_sql)) {
        echo "<script>alert('✅ Appointment marked as completed. No follow-up scheduled.'); window.location.href='doctor_serve_appointment.php';</script>";
    } else {
        echo "❌ Error: " . mysqli_error($conn);
    }
}
?>

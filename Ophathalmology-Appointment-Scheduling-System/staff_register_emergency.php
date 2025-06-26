<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counter_staff') {
    header("Location: homepage.php");
    exit();
}

date_default_timezone_set('Asia/Kuala_Lumpur');
$currentTime = date('H:i');
$today = date('Y-m-d');
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['emergency'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $no_ic = mysqli_real_escape_string($conn, $_POST['no_ic']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    // Step 1: Check if patient already exists by IC
    $checkPatient = mysqli_query($conn, "SELECT * FROM patient WHERE no_ic = '$no_ic' LIMIT 1");
    if (mysqli_num_rows($checkPatient) > 0) {
        $patient = mysqli_fetch_assoc($checkPatient);
        $patient_id = $patient['patient_id'];
    } else {
        // Step 2: If not exist, create new patient
        $insertPatient = "INSERT INTO patient (name, no_ic, phone_number, registered_datetime)
                          VALUES ('$name', '$no_ic', '$phone', NOW())";

        if (mysqli_query($conn, $insertPatient)) {
            $patient_id = mysqli_insert_id($conn);
        } else {
            $error = "❌ Failed to register patient: " . mysqli_error($conn);
            $patient_id = null;
        }
    }

    // Step 3: Prevent duplicate emergency registration for today
    if ($patient_id) {
        $checkEmergency = mysqli_query($conn, "
            SELECT * FROM appointment 
            WHERE patient_id = '$patient_id' 
              AND apt_date = '$today' 
              AND visit_type = 'Emergency' 
              AND apt_status != 'Completed'
        ");

        if (mysqli_num_rows($checkEmergency) > 0) {
            $error = "⚠️ This patient already has an emergency appointment today that is not yet completed.";
        } else {
            // Step 4: Find best doctor with no high priority patients
            $bestDoctorId = null;
            $leastAppointments = PHP_INT_MAX;
            $eligibleDoctors = mysqli_query($conn, "
                SELECT d.doctor_id, d.name 
                FROM doctor d 
                WHERE d.status = 'active' 
                  AND d.room_id IS NOT NULL 
                  AND d.room_id IN (SELECT room_id FROM room) 
                  AND d.doctor_id NOT IN (
                      SELECT doctor_id FROM session 
                      WHERE s_date = '$today' 
                        AND s_status IN ('On Leave', 'Unavailable')
                  )
            ");

            while ($doc = mysqli_fetch_assoc($eligibleDoctors)) {
                $docId = $doc['doctor_id'];
                $hasHighPriority = mysqli_query($conn, "
                    SELECT 1 FROM appointment 
                    WHERE doctor_id = '$docId' 
                      AND apt_date = '$today' 
                      AND apt_priority = '3' 
                      AND apt_status IN ('Scheduled', 'Now Serving', 'Paused') 
                    LIMIT 1
                ");
                if (mysqli_num_rows($hasHighPriority) == 0) {
                    $countQuery = mysqli_query($conn, "
                        SELECT COUNT(*) as total 
                        FROM appointment 
                        WHERE doctor_id = '$docId' 
                          AND apt_date = '$today' 
                          AND apt_status IN ('Scheduled', 'Now Serving', 'Paused')
                    ");
                    $count = mysqli_fetch_assoc($countQuery)['total'];
                    if ($count < $leastAppointments) {
                        $leastAppointments = $count;
                        $bestDoctorId = $docId;
                    }
                }
            }

            // If all have high-priority patients, fallback to least loaded doctor
            if (!$bestDoctorId) {
                $fallbackDoctor = mysqli_query($conn, "
                    SELECT d.doctor_id, d.name, COUNT(a.apt_id) AS total
                    FROM doctor d
                    LEFT JOIN appointment a ON d.doctor_id = a.doctor_id 
                        AND a.apt_date = '$today' 
                        AND a.apt_status IN ('Scheduled', 'Now Serving', 'Paused')
                    WHERE d.status = 'active'
                      AND d.doctor_id NOT IN (
                          SELECT doctor_id FROM session 
                          WHERE s_date = '$today' 
                          AND s_status IN ('On Leave', 'Unavailable')
                      )
                    GROUP BY d.doctor_id
                    ORDER BY total ASC 
                    LIMIT 1
                ");
                $fallbackDoctorRow = mysqli_fetch_assoc($fallbackDoctor);
                $bestDoctorId = $fallbackDoctorRow['doctor_id'];
            }

            // Step 5: Pause current Now Serving (if any)
            $checkNowServing = "SELECT apt_id FROM appointment 
                                WHERE doctor_id = '$bestDoctorId' 
                                  AND apt_date = '$today' 
                                  AND apt_status = 'Now Serving' 
                                LIMIT 1";

            $nowServingResult = mysqli_query($conn, $checkNowServing);
            if ($nowServingResult && mysqli_num_rows($nowServingResult) > 0) {
                $current = mysqli_fetch_assoc($nowServingResult);
                $pausedAptId = $current['apt_id'];
                mysqli_query($conn, "UPDATE appointment SET apt_status = 'Paused' WHERE apt_id = '$pausedAptId'");
                $emergencyStatus = 'Now Serving';
            } else {
                $emergencyStatus = 'Now Serving';
            }

            // Step 6: Insert emergency appointment
            $insertAppointment = "INSERT INTO appointment 
                (patient_id, doctor_id, apt_date, apt_time, apt_status, apt_priority, apt_notes, visit_type, duration_minutes, validated_datetime)
                VALUES 
                ('$patient_id', '$bestDoctorId', '$today', '$currentTime', '$emergencyStatus', '3', 'EMERGENCY CASE', 'Emergency', 45, NOW())";

            if (mysqli_query($conn, $insertAppointment)) {
                $doctorNameResult = mysqli_query($conn, "SELECT name FROM doctor WHERE doctor_id = '$bestDoctorId' LIMIT 1");
                $doctorNameRow = mysqli_fetch_assoc($doctorNameResult);
                $doctorName = $doctorNameRow['name'];

                $_SESSION['emergency_success'] = "🚑 Emergency case for patient $name has been assigned to Dr. $doctorName.";
                header("Location: staff_queue.php");
                exit();
            } else {
                $error = "❌ Failed to assign emergency case: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Emergency Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .main-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .main-container {
            padding: 10px 30px;
            display: flex;
            flex-direction: column;
            background-color: white;
            text-align: justify;
            margin-left: calc(100% - 80%);
            max-width: 100%;
        }

        .breadcrumb {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
            text-align: right;
        }

        h1 {
            color: #dc3545;
            margin-bottom: 25px;
            font-size: 28px;
            text-align: center;
        }

        label {
            font-weight: 600;
            margin-top: 15px;
            display: block;
            color: #333;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
            box-sizing: border-box;
        }

        button {
            margin-top: 30px;
            padding: 14px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #c82333;
        }

        .note {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-left: 5px solid #ffeeba;
            border-radius: 6px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<?php include 'header_staff.php'; ?>
<div class="main-container">
    <h1>🚑 Emergency Patient Registration</h1>
    <?php include 'breadcrumb.php'; ?>
    <br>
    <div class="note">
        Emergency Registration. System will automatically assign the best available doctor today.
    </div>

    <?php if ($success): ?>
        <div class="note" style="
        background-color: #d4edda;
        color: #155724;
        padding: 10px 15px;
        margin-bottom: 20px;
        border-left: 5px solid #c3e6cb;
        border-radius: 6px;
        font-size: 14px;
    ">
            <?= $success ?>
        </div>
    <?php elseif ($error): ?>
        <div class="note" style="
        background-color: #f8d7da;
        color: #721c24;
        padding: 10px 15px;
        margin-bottom: 20px;
        border-left: 5px solid #f5c6cb;
        border-radius: 6px;
        font-size: 14px;
    ">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="staff_register_emergency.php">
        <label>Patient Name</label>
        <input type="text" name="name" required>

        <label>IC Number</label>
        <input type="text" name="no_ic" required>

        <label>Phone Number</label>
        <input type="text" name="phone" required>

        <button type="submit" name="emergency">Emergency Appointment</button>
    </form>
</div>
</body>
</html>

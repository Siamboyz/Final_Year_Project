<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counter_staff') {
    header("Location: homepage.php");
    exit();
}

if (isset($_SESSION['emergency_success'])) {
    echo "<script>alert('" . $_SESSION['emergency_success'] . "');</script>";
    unset($_SESSION['emergency_success']);
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDate = date('Y-m-d');

// ✅ STEP 0: Emergency Interrupt - Pause current Now Serving if emergency appears
$emergencyInterruptQuery = "
SELECT r.room_id, r.room_name, a.apt_id AS emergency_apt_id
FROM appointment a
JOIN doctor d ON a.doctor_id = d.doctor_id
JOIN room r ON d.room_id = r.room_id
WHERE a.apt_status = 'Scheduled'
  AND a.visit_type = 'emergency'
  AND a.apt_date = '$currentDate'
ORDER BY a.validated_datetime ASC
";

$interruptResult = mysqli_query($conn, $emergencyInterruptQuery);
$emergencyRooms = [];

while ($row = mysqli_fetch_assoc($interruptResult)) {
    $roomId = $row['room_id'];
    $roomName = $row['room_name'];
    $emergencyAptId = $row['emergency_apt_id'];
    $emergencyRooms[] = $roomName;

    // Pause any existing Now Serving appointment (non-emergency) in that room
    mysqli_query($conn, "
        UPDATE appointment a
        JOIN doctor d ON a.doctor_id = d.doctor_id
        SET a.apt_status = 'Paused'
        WHERE a.apt_status = 'Now Serving'
          AND a.apt_date = '$currentDate'
          AND d.room_id = '$roomId'
          AND a.visit_type != 'emergency'
    ");

    // Set the emergency case as Now Serving
    mysqli_query($conn, "
        UPDATE appointment 
        SET apt_status = 'Now Serving' 
        WHERE apt_id = '$emergencyAptId'
    ");
}

// ✅ STEP 0.5: Resume Paused appointment if emergency is done
$resumeQuery = "
SELECT a.apt_id, d.room_id
FROM appointment a
JOIN doctor d ON a.doctor_id = d.doctor_id
WHERE a.apt_status = 'Paused'
  AND a.apt_date = '$currentDate'
  AND NOT EXISTS (
    SELECT 1 FROM appointment a2
    JOIN doctor d2 ON a2.doctor_id = d2.doctor_id
    WHERE a2.apt_status = 'Now Serving'
      AND a2.visit_type = 'emergency'
      AND a2.apt_date = '$currentDate'
      AND d2.room_id = d.room_id
  )
";

$resumeResult = mysqli_query($conn, $resumeQuery);
while ($row = mysqli_fetch_assoc($resumeResult)) {
    $aptId = $row['apt_id'];
    mysqli_query($conn, "
        UPDATE appointment 
        SET apt_status = 'Now Serving' 
        WHERE apt_id = '$aptId'
    ");
}

// ✅ STEP 1: Auto-assign Now Serving if no current one
$doctorQuery = mysqli_query($conn, "SELECT doctor_id FROM doctor");
while ($doc = mysqli_fetch_assoc($doctorQuery)) {
    $doctorId = $doc['doctor_id'];

    $checkNowServing = mysqli_query($conn, "
        SELECT apt_id FROM appointment 
        WHERE apt_status = 'Now Serving' 
          AND apt_date = '$currentDate' 
          AND doctor_id = '$doctorId'
        LIMIT 1
    ");

    if (mysqli_num_rows($checkNowServing) == 0) {
        $firstAppt = mysqli_query($conn, "
            SELECT apt_id FROM appointment 
            WHERE apt_status = 'Scheduled' 
              AND apt_date = '$currentDate' 
              AND doctor_id = '$doctorId'
            ORDER BY 
                CASE visit_type WHEN 'emergency' THEN 0 ELSE 1 END ASC,
                CAST(apt_priority AS UNSIGNED) DESC,
                validated_datetime ASC
            LIMIT 1
        ");

        if ($firstAppt && mysqli_num_rows($firstAppt) > 0) {
            $row = mysqli_fetch_assoc($firstAppt);
            $aptId = $row['apt_id'];

            mysqli_query($conn, "
                UPDATE appointment 
                SET apt_status = 'Now Serving' 
                WHERE apt_id = '$aptId'
            ");
        }
    }
}

// ✅ STEP 2: Fetch today’s queue
$query = "
SELECT a.*, p.name AS patient_name, d.name AS doctor_name, r.room_name
FROM appointment a
JOIN patient p ON a.patient_id = p.patient_id
JOIN doctor d ON a.doctor_id = d.doctor_id
JOIN room r ON d.room_id = r.room_id
WHERE (a.apt_status = 'Scheduled' OR a.apt_status = 'Now Serving' OR a.apt_status = 'Paused')
  AND a.apt_date = '$currentDate'
ORDER BY r.room_name ASC,
         CASE a.visit_type WHEN 'emergency' THEN 0 ELSE 1 END ASC,
         CASE a.apt_status WHEN 'Now Serving' THEN 0 WHEN 'Paused' THEN 1 ELSE 2 END ASC,
         CAST(a.apt_priority AS UNSIGNED) DESC,
         a.validated_datetime ASC
";

$result = mysqli_query($conn, $query);
$rooms = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rooms[$row['room_name']][] = $row;
}

// ✅ Emergency alert check
$emergency_exist = false;
foreach ($rooms as $room => $appointments) {
    foreach ($appointments as $appt) {
        if ($appt['visit_type'] === 'emergency') {
            $emergency_exist = true;
            break 2;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Queue Monitoring</title>
    <meta http-equiv="refresh" content="5">
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
            text-align: center;
            color: #007bff;
        }

        .room-section {
            margin-top: 30px;
        }

        .room-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 10px;
            border-left: 6px solid #007bff;
            padding-left: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            font-size: 14px;
            text-align: center;
        }

        th {
            background: #e9f1fb;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            color: white;
        }

        .priority-high {
            background-color: #dc3545;
        }

        .priority-medium {
            background-color: #ffc107;
            color: #212529;
        }

        .priority-low {
            background-color: #28a745;
        }

        .status {
            font-weight: bold;
            color: #007bff;
        }

        .now-serving {
            color: #28a745;
            font-weight: bold;
            font-size: 13px;
        }

        .emergency-highlight {
            background-color: #ffe6e6;
        }

        .btn-upgrade {
            background-color: #ff9800;
            color: white;
            padding: 6px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
        }

        .btn-upgrade:hover {
            background-color: #e65100;
        }

        .no-queue {
            text-align: center;
            padding: 40px;
            color: #888;
            font-style: italic;
        }
    </style>
</head>
<body>
<?php include 'header_staff.php'; ?>

<div class="main-container">
    <h1>Queue Monitoring (Scheduled & Emergency Appointments)</h1>
    <?php include 'breadcrumb.php'; ?>

    <?php if ($emergency_exist): ?>
        <div style="background-color: #ffcccc; padding: 15px; text-align: center; border-radius: 8px; font-weight: bold; color: #b30000;">
            🚑 Emergency Case in Queue! Please prioritize handling.
        </div>
    <?php endif; ?>

    <?php if (empty($rooms)): ?>
        <p class="no-queue">No scheduled appointments in queue.</p>
    <?php else: ?>
        <?php foreach ($rooms as $roomName => $appointments): ?>
            <div class="room-section">
                <div class="room-title"><?= htmlspecialchars($roomName) ?></div>
                <table>
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Doctor</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $index = 1;

                    // ✅ STEP 1: Find Now Serving and determine runningTime
                    $runningTime = time(); // default fallback
                    $foundNowServing = null;

                    foreach ($appointments as $appt) {
                        if ($appt['apt_status'] === 'Now Serving') {
                            $foundNowServing = $appt;
                            break;
                        }
                    }

                    if ($foundNowServing) {
                        if (!empty($foundNowServing['validated_datetime'])) {
                            $startTime = strtotime($foundNowServing['validated_datetime']);
                        } else {
                            $startTime = strtotime($foundNowServing['apt_date'] . ' ' . $foundNowServing['apt_time']);
                        }

                        $baseDuration = ((int)$foundNowServing['apt_priority'] >= 3) ? 45 : 20;
                        $expectedEndTime = $startTime + ($baseDuration * 60);

                        // ✅ If doctor is late, shift estimation forward from NOW
                        if (time() > $expectedEndTime) {
                            $runningTime = time() + 2 * 60; // 2-minute buffer after now
                        } else {
                            $runningTime = $expectedEndTime;
                        }
                    }
                    ?>

                    <?php foreach ($appointments as $appt): ?>
                        <?php
                        $priority = (int)$appt['apt_priority'];
                        $priorityLabel = ($priority >= 3) ? 'High' : (($priority == 2) ? 'Medium' : 'Low');
                        $priorityClass = ($priority >= 3) ? 'priority-high' : (($priority == 2) ? 'priority-medium' : 'priority-low');

                        $nowServing = ($appt['apt_status'] === 'Now Serving') ? "<span class='now-serving'>(Now Serving)</span>" : "";
                        $emergencyBadge = ($appt['visit_type'] === 'emergency') ? "<span style='color: red; font-weight: bold;'> [EMERGENCY]</span>" : "";
                        $emergencyClass = ($appt['visit_type'] === 'emergency') ? 'emergency-highlight' : '';

                        $statusDisplay = htmlspecialchars($appt['apt_status']);
                        if ($appt['apt_status'] === 'Paused') {
                            $statusDisplay = "<span style='color: orange; font-weight: bold;'>⏸ Paused – Waiting after Emergency</span>";
                        }

                        echo "<tr class='{$emergencyClass}'>";
                        echo "<td>{$index}</td>";
                        echo "<td>" . htmlspecialchars($appt['patient_name']) . " {$nowServing} {$emergencyBadge}";

                        if ($appt['apt_status'] !== 'Now Serving') {
                            $estimatedServiceTime = ($priority >= 3) ? 45 : 20;
                            $estimatedTurnTime = $runningTime;

                            echo "<br><small style='color: gray;'>🕒 Estimated Turn: " . date('h:i A', $estimatedTurnTime) . "</small>";

                            $runningTime += $estimatedServiceTime * 60;
                        }

                        echo "</td>";
                        echo "<td>" . date('d/m/Y', strtotime($appt['apt_date'])) . "</td>";
                        echo "<td>" . date('h:i A', strtotime($appt['apt_time'])) . "</td>";
                        echo "<td>" . htmlspecialchars($appt['doctor_name']) . "</td>";
                        echo "<td><span class='badge {$priorityClass}'>{$priorityLabel}</span></td>";
                        echo "<td><span class='status'>{$statusDisplay}</span></td>";
                        echo "<td>";

                        if ($appt['apt_status'] === 'Scheduled') {
                            echo "<a href='staff_upgrade_priority.php?apt_id=" . $appt['apt_id'] . "' class='btn-upgrade'>🔼 Upgrade</a>";
                        } else {
                            echo "-";
                        }

                        echo "</td></tr>";
                        $index++;
                    endforeach;
                    ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>

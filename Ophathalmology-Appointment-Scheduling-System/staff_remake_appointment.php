<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counter_staff') {
    header("Location: homepage.php");
    exit();
}

if (!isset($_GET['apt_id'])) {
    echo "<script>alert('Invalid appointment ID.'); window.location.href='staff_patient.php';</script>";
    exit();
}

$apt_id = mysqli_real_escape_string($conn, $_GET['apt_id']);

// Fetch missed appointment details
$query = "SELECT a.*, p.name AS patient_name, p.no_ic, p.phone_number, p.address, d.name AS doctor_name
          FROM appointment a
          INNER JOIN patient p ON a.patient_id = p.patient_id
          INNER JOIN doctor d ON a.doctor_id = d.doctor_id
          WHERE a.apt_id = '$apt_id'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    echo "<script>alert('Appointment not found.'); window.location.href='staff_patient.php';</script>";
    exit();
}

$apt = mysqli_fetch_assoc($result);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Re-Make Missed Appointment</title>
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
            padding: 10px 30px;
            display: flex;
            flex-direction: column;
            text-align: justify;
            margin-left: calc(100% - 80%);
            max-width: 100%;
        }

        h2 {
            color: #007bff;
            text-align: center;
            font-size: 1.8rem;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
        }

        button {
            margin-top: 20px;
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .breadcrumb {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
            text-align: right;
        }
    </style>
</head>
<body>
<?php include 'header_staff.php'; ?>

<div class="main-container">
    <h2>Re-Make Missed Appointment</h2>
    <?php include 'breadcrumb.php'; ?>

    <form method="POST" action="staff_remake_appointment.php?apt_id=<?= $apt_id ?>">
        <input type="hidden" name="patient_id" value="<?= htmlspecialchars($apt['patient_id']) ?>">
        <input type="hidden" name="old_apt_id" value="<?= $apt_id ?>">
        <input type="hidden" name="doctor_id" value="<?= htmlspecialchars($apt['doctor_id']) ?>">
        <input type="hidden" name="apt_priority" value="<?= $apt['apt_priority'] ?>">

        <label>Patient Name:</label>
        <input type="text" value="<?= htmlspecialchars($apt['patient_name']) ?>" readonly>

        <label>IC Number:</label>
        <input type="text" value="<?= htmlspecialchars($apt['no_ic']) ?>" readonly>

        <label>Phone Number:</label>
        <input type="text" value="<?= htmlspecialchars($apt['phone_number']) ?>" readonly>

        <label>Doctor:</label>
        <input type="text" value="<?= htmlspecialchars($apt['doctor_name']) ?>" readonly>

        <label>Date:</label>
        <input type="date" name="apt_date" min="<?= date('Y-m-d') ?>" required>

        <label>Time:</label>
        <input type="time" name="apt_time" required>

        <label>Priority:</label>
        <input type="text" value="<?php
        echo ($apt['apt_priority'] == 3) ? 'High' : (($apt['apt_priority'] == 2) ? 'Medium' : 'Low');
        ?>" readonly>

        <label>Notes:</label>
        <textarea name="apt_notes" rows="4" readonly><?= htmlspecialchars($apt['apt_notes']) ?></textarea>

        <center><button type="submit" name="submit">Create Re-Schedule Appointment</button></center>
    </form>
</div>

<?php
if (isset($_POST['submit'])) {
    $patient_id = mysqli_real_escape_string($conn, $_POST['patient_id']);
    $doctor_id = mysqli_real_escape_string($conn, $_POST['doctor_id']);
    $apt_date = mysqli_real_escape_string($conn, $_POST['apt_date']);
    $apt_time = mysqli_real_escape_string($conn, $_POST['apt_time']);
    $apt_notes = mysqli_real_escape_string($conn, $_POST['apt_notes']);
    $apt_priority = intval($_POST['apt_priority']);

    function getDurationByPriority($priority) {
        switch ($priority) {
            case 3: return 45;
            case 2: return 20;
            case 1:
            default: return 20;
        }
    }

    $duration = getDurationByPriority($apt_priority);

    // 1. Check doctor availability on the selected date
    $checkSession = "SELECT * FROM session 
                     WHERE doctor_id = '$doctor_id' 
                     AND s_date = '$apt_date'
                     AND s_status IN ('Unavailable', 'On Leave')";
    $sessionResult = mysqli_query($conn, $checkSession);

    if (mysqli_num_rows($sessionResult) > 0) {
        echo "<script>alert('❌ The selected date ($apt_date) is unavailable. The doctor is on leave or unavailable.'); window.location.href='staff_remake_appointment.php?apt_id=$apt_id';</script>";
        exit();
    }

    // Step 2: Check total scheduled time for that doctor on that date
    $checkSQL = "SELECT SUM(duration_minutes) AS total_scheduled 
             FROM appointment 
             WHERE doctor_id = '$doctor_id' AND apt_date = '$apt_date'";
    $result = mysqli_query($conn, $checkSQL);
    $row = mysqli_fetch_assoc($result);
    $totalScheduled = isset($row['total_scheduled']) ? $row['total_scheduled'] : 0;

    // Step 3: If over 420 mins, suggest next available date
    if (($totalScheduled + $duration) > 420) {
        $suggestedDate = null;

        for ($i = 1; $i <= 7; $i++) {
            $nextDate = date('Y-m-d', strtotime($apt_date . " +$i days")); // ✅ Fix

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
            echo "<script>alert('❌ Full on selected day. Next available: $suggestedDate ($availableSlots slot(s) available)'); 
        window.location.href='staff_remake_appointment.php?apt_id=$apt_id';</script>";
        } else {
            echo "<script>alert('❌ Fully booked for the next 7 days. Please try again later.'); 
        window.location.href='staff_remake_appointment.php?apt_id=$apt_id';</script>";
        }

        exit();
    }

    // 4. Update appointment
    $update = "UPDATE appointment 
               SET doctor_id = '$doctor_id',
                   apt_date = '$apt_date',
                   apt_time = '$apt_time',
                   apt_status = 'Not Assigned',
                   apt_notes = '$apt_notes',
                   apt_priority = '$apt_priority',
                   duration_minutes = '$duration'
               WHERE apt_id = '$apt_id'";

    if (mysqli_query($conn, $update)) {
        echo "<script>alert('✅ Appointment updated successfully.'); window.location.href='staff_patient_detail.php?id=$patient_id';</script>";
    } else {
        echo "<script>alert('❌ Error updating appointment: " . mysqli_error($conn) . "');</script>";
    }
}
?>
</body>
</html>

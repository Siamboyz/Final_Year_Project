<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: homepage.php");
    exit();
}

date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDate = date('Y-m-d');
$doctorId = $_SESSION['user_id'];

// ✅ Fetch currently serving patient
$appointmentQuery = mysqli_query($conn, "
    SELECT a.*, p.name, p.no_ic, p.phone_number, p.dob, p.gender, p.marital_status, 
           p.occupation, p.state, p.address, p.registered_datetime
    FROM appointment a
    JOIN patient p ON a.patient_id = p.patient_id
    WHERE a.apt_status = 'Now Serving'
      AND a.doctor_id = '$doctorId'
      AND a.apt_date = '$currentDate'
    LIMIT 1
");

$appointment = mysqli_fetch_assoc($appointmentQuery);

// ✅ Auto-fill served_datetime if not set yet
if (!empty($appointment) && empty($appointment['served_datetime'])) {
    $aptId = $appointment['apt_id'];
    $now = date('Y-m-d H:i:s');
    mysqli_query($conn, "UPDATE appointment SET served_datetime = '$now' WHERE apt_id = '$aptId'");
    $appointment['served_datetime'] = $now; // update local variable to prevent null reference
}


// ✅ Fetch history if current appointment exists
$history = [];
if (!empty($appointment)) {
    $patientId = $appointment['patient_id'];
    $historyQuery = mysqli_query($conn, "
        SELECT * FROM appointment
        WHERE patient_id = '$patientId'
          AND doctor_id = '$doctorId'
          AND apt_status = 'Completed'
        ORDER BY apt_date DESC, apt_time DESC
        LIMIT 5
    ");
    while ($row = mysqli_fetch_assoc($historyQuery)) {
        $history[] = $row;
    }
}

// ✅ Overdue detection logic
$showOverrunAlert = false;
if (!empty($appointment) && $appointment['apt_status'] === 'Now Serving') {
    $priority = (int)$appointment['apt_priority'];
    $baseDuration = ($priority >= 3) ? 45 : 20;

    if (!empty($appointment['validated_datetime'])) {
        $startTime = strtotime($appointment['validated_datetime']);
    } else {
        $startTime = strtotime($appointment['apt_date'] . ' ' . $appointment['apt_time']);
    }

    $expectedEndTime = $startTime + ($baseDuration * 60);
    if (time() > $expectedEndTime) {
        $showOverrunAlert = true;
    }
}

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aptId = $_POST['a_id'];
    $patientId = $_POST['p_id'];
    $today = $currentDate;

    if (isset($_POST['buttonfollowup']) && $_POST['buttonfollowup'] === 'yes') {
        $nextDate = $_POST['next_date'];
        $nextTime = $_POST['next_time'];
        $priority = $_POST['priority'];
        $followupNote = mysqli_real_escape_string($conn, $_POST['followupnote']);

        mysqli_query($conn, "INSERT INTO appointment 
            (patient_id, doctor_id, apt_date, apt_time, apt_status, apt_priority, apt_notes, visit_type, duration_minutes, validated_datetime)
            VALUES ('$patientId', '$doctorId', '$nextDate', '$nextTime', 'Scheduled', '$priority', '$followupNote', 'Follow-up', 15, NOW())");
    }

    // Mark current appointment as completed
    mysqli_query($conn, "UPDATE appointment SET apt_status = 'Completed' WHERE apt_id = '$aptId'");

    // If emergency, resume paused case
    $check = mysqli_query($conn, "SELECT visit_type FROM appointment WHERE apt_id = '$aptId'");
    $row = mysqli_fetch_assoc($check);
    $isEmergency = (strtolower($row['visit_type']) === 'emergency');

    if ($isEmergency) {
        $resume = mysqli_query($conn, "
            SELECT apt_id FROM appointment 
            WHERE apt_status = 'Paused' 
              AND doctor_id = '$doctorId' 
              AND apt_date = '$today'
            ORDER BY validated_datetime ASC
            LIMIT 1
        ");
        if (mysqli_num_rows($resume) > 0) {
            $paused = mysqli_fetch_assoc($resume);
            $pausedId = $paused['apt_id'];
            mysqli_query($conn, "UPDATE appointment SET apt_status = 'Now Serving' WHERE apt_id = '$pausedId'");
        }
    } else {
        $assignNext = mysqli_query($conn, "
            SELECT apt_id FROM appointment
            WHERE apt_status = 'Scheduled'
              AND doctor_id = '$doctorId'
              AND apt_date = '$today'
            ORDER BY 
                CASE visit_type WHEN 'emergency' THEN 0 ELSE 1 END ASC,
                CAST(apt_priority AS UNSIGNED) DESC,
                validated_datetime ASC
            LIMIT 1
        ");
        if (mysqli_num_rows($assignNext) > 0) {
            $next = mysqli_fetch_assoc($assignNext);
            $nextId = $next['apt_id'];
            mysqli_query($conn, "UPDATE appointment SET apt_status = 'Now Serving' WHERE apt_id = '$nextId'");
        }
    }

    header("Location: doctor_serve_appointment.php");
    exit();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="js/jquery.js"></script>
    <title>Current Appointment</title>
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
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            text-align: justify;
            margin-left: calc(100% - 80%);
            max-width: 100%;
        }

        h1 {
            font-size: 1.8rem;
            color: #007bff;
        }

        .breadcrumb {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
            text-align: right;
        }

        label {
            font-weight: bold;
            margin-top: 15px;
            display: block;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
        }

        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
        }

        .card {
            background-color: #fff;
            padding: 25px 30px;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .card h2 {
            color: #007bff;
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px 20px;
        }

        .info-grid .full {
            grid-column: 1 / -1;
        }

        form {
            padding: 30px;
            max-width: 700px;
            margin: 0 auto;
            font-family: 'Segoe UI', sans-serif;
            transition: all 0.3s ease-in-out;
        }

        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
            font-size: 15px;
        }

        form textarea,
        form input[type="date"],
        form input[type="time"],
        form select {
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 22px;
            border: 1px solid #d0d0d0;
            border-radius: 10px;
            font-size: 15px;
            transition: border 0.3s, box-shadow 0.3s;
            background-color: #fff;
            box-sizing: border-box;
        }

        form textarea:focus,
        form input:focus,
        form select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.15);
            outline: none;
        }

        form button {
            background: #007bff;
            color: #fff;
            padding: 14px;
            width: 100%;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.2);
            transition: background 0.3s, transform 0.2s;
        }

        form button:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        form button:active {
            transform: scale(0.98);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th, table td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }

        table th {
            background-color: #f9f9f9;
        }

        .priority-select {
            font-weight: 600;
            background-color: #fff;
            color: #333;
        }

    </style>
</head>
<body>
<?php include 'header_doc.php'; ?>

<div class="main-container">
    <h1>Doctor | Current Patient Appointment</h1>
    <?php include 'breadcrumb.php'; ?>

    <?php if ($showOverrunAlert): ?>
        <div style="background-color: #ffcccc; color: #b30000; font-weight: bold; text-align: center; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
            ⚠️ This appointment is overdue. Please complete it or take action immediately.
        </div>
    <?php endif; ?>

<?php if ($appointment): ?>
    <div class="card">
        <h2>📅 Current Appointment</h2>
        <p><strong>Date:</strong> <?= date('d/m/Y', strtotime($appointment['apt_date'])) ?></p>
        <p><strong>Time:</strong> <?= date('h:i A', strtotime($appointment['apt_time'])) ?></p>
        <p><strong>Note:</strong> <?= htmlspecialchars($appointment['apt_notes'] ?? '-') ?></p>
    </div>

    <div class="card">
        <h2>🩺 Patient Details</h2>
        <br>
        <div class="info-grid">
            <div><strong>Name:</strong> <?= htmlspecialchars($appointment['name']) ?></div>
            <div><strong>IC:</strong> <?= htmlspecialchars($appointment['no_ic'] ?? '-') ?></div>
            <div><strong>Phone:</strong> <?= htmlspecialchars($appointment['phone_number'] ?? '-') ?></div>
            <div><strong>Date of Birth:</strong> <?= htmlspecialchars($appointment['dob'] ?? '-') ?></div>
            <div><strong>Gender:</strong> <?= htmlspecialchars($appointment['gender']) ?></div>
            <div><strong>Marital Status:</strong> <?= htmlspecialchars($appointment['marital_status']) ?></div>
            <div><strong>Occupation</strong> <?= htmlspecialchars($appointment['marital_status']) ?></div>
            <div><strong>State</strong> <?= htmlspecialchars($appointment['state']) ?></div>
            <div class="full"><strong>Address:</strong> <?= htmlspecialchars($appointment['address'] ?? '-') ?></div>
            <div><strong>Registered:</strong> <?= htmlspecialchars($appointment['registered_datetime']) ?></div>
        </div>
    </div>

    <div class="card">
        <h2>📝 Follow-Up Form</h2>
        <form action="doctor_followup_appointment.php" method="POST" id="followupForm">
            <!-- Hidden fields -->
            <input type="hidden" name="a_id" value="<?=htmlspecialchars($appointment['apt_id']) ?>">
            <input type="hidden" name="p_id" value="<?=htmlspecialchars($appointment['patient_id']) ?>">
            <input type="hidden" name="d_id" value="<?=htmlspecialchars($appointment['doctor_id']) ?>">

            <!-- Next appointment details -->
            <label>Next Date:</label>
            <input type="date" name="next_date" >

            <label>Next Time:</label>
            <input type="time" name="next_time" >

            <label>Priority:</label>
            <select name="priority" >
                <option value="1">Low</option>
                <option value="2">Medium</option>
                <option value="3">High</option>
            </select>

            <label>Follow-Up Notes:</label>
            <textarea name="followupnote" ></textarea>

            <button type="submit" name="buttonfollowup" value="yes" class="btn-primary">Submit Follow-Up</button>
            <button type="submit" name="completeOnly" value="yes" class="btn-secondary" style="margin-top: 10px; background-color: gray;">
                Complete Without Follow-Up
            </button>
        </form>
    </div>

    <?php if (!empty($history)): ?>
        <div class="card">
            <h2>📖 Patient Medical History</h2>
            <table>
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Note</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($history as $row): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($row['apt_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($row['apt_time'])) ?></td>
                        <td><?= htmlspecialchars($row['apt_notes'] ?? '-') ?></td>
                        <td><?= $row['apt_status'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

<?php else: ?>
    <p>No patient currently being served.</p>
<?php endif; ?>
</div>
</body>
</html>

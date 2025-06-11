<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counter_staff') {
    header("Location: homepage.php");
    exit();
}

if (!isset($_GET['patient_id'])) {
    echo "<script>alert('Invalid patient ID'); window.location.href='staff_patient.php';</script>";
    exit();
}

$patient_id = mysqli_real_escape_string($conn, $_GET['patient_id']);

// Fetch patient info
$patientQuery = "SELECT * FROM patient WHERE patient_id = '$patient_id'";
$patientResult = mysqli_query($conn, $patientQuery);
$patient = mysqli_fetch_assoc($patientResult);

// Auto-insert date and time
date_default_timezone_set('Asia/Kuala_Lumpur');
$today = date('Y-m-d');
$defaultTime = date('H:i'); // You can change this to fixed time like '09:00'

// Fetch doctors who are available today
$doctorQuery = "SELECT d.doctor_id, d.name 
                FROM doctor d
                WHERE  d.status = 'active'
                AND d.doctor_id NOT IN (
                    SELECT doctor_id FROM session 
                    WHERE s_date = '$today' 
                    AND s_status IN ('On Leave', 'Unavailable')
                )
                ORDER BY d.name";
$doctorResult = mysqli_query($conn, $doctorQuery);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doctor_id = $_POST['doctor_id'];
    $apt_date = $_POST['apt_date'];
    $apt_time = $_POST['apt_time'];
    $priority = $_POST['priority'];
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $status = 'Scheduled';
    $validateTime = date('Y-m-d H:i:s');

    // Determine duration based on priority
    switch ($priority) {
        case '3': // High
            $duration_minutes = 45;
            break;
        case '2': // Medium
        case '1': // Low
        default:
            $duration_minutes = 20;
            break;
    }

    $sql = "INSERT INTO appointment 
        (patient_id, doctor_id, apt_date, apt_time, apt_priority, apt_notes, apt_status, validated_datetime, duration_minutes)
        VALUES 
        ('$patient_id', '$doctor_id', '$apt_date', '$apt_time', '$priority', '$notes', '$status', '$validateTime', '$duration_minutes')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('✅ Returning appointment successfully scheduled.'); window.location.href='staff_patient_detail.php?id=$patient_id';</script>";
    } else {
        echo "<script>alert('❌ Error scheduling appointment: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Returning Patient</title>
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
            font-weight: 600;
            margin-top: 15px;
            display: block;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-top: 5px;
            box-sizing: border-box;
        }

        input[readonly] {
            background-color: #f4f4f4;
        }

        p.auto-info {
            background-color: #e9f5ff;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        button {
            margin-top: 25px;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            width: 30%;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<?php include 'header_staff.php'; ?>
<div class="main-container">
    <h1>➕ Register Returning Appointment</h1>
    <?php include 'breadcrumb.php'; ?>
    <br>

    <form method="POST">
        <!-- Patient Info -->
        <label>Patient Name</label>
        <input type="text" value="<?= htmlspecialchars($patient['name']) ?>" readonly>

        <label>IC Number</label>
        <input type="text" value="<?= htmlspecialchars($patient['no_ic']) ?>" readonly>

        <!-- Doctor -->
        <label>Doctor</label>
        <select name="doctor_id" required>
            <option value="">-- Select Doctor --</option>
            <?php
            if (mysqli_num_rows($doctorResult) > 0) {
                while ($doc = mysqli_fetch_assoc($doctorResult)) {
                    echo "<option value='{$doc['doctor_id']}'>" . htmlspecialchars($doc['name']) . "</option>";
                }
            } else {
                echo "<option value='' disabled>No available doctors today</option>";
            }
            ?>
        </select>

        <!-- Auto Date & Time (Hidden + Visual) -->
        <input type="hidden" name="apt_date" value="<?= $today ?>">
        <input type="hidden" name="apt_time" value="<?= $defaultTime ?>">

        <p class="auto-info"><strong>Auto-Assigned Date:</strong> <?= $today ?> &nbsp;&nbsp;
            <strong>Time:</strong> <?= $defaultTime ?></p>

        <!-- Priority -->
        <label>Priority</label>
        <select name="priority" required>
            <option value="1">Low</option>
            <option value="2">Medium</option>
            <option value="3">High</option>
        </select>

        <!-- Notes -->
        <label>Notes</label>
        <textarea name="notes" placeholder="Optional notes..." rows="4"></textarea>

        <!-- Submit -->
        <center><button type="submit">Schedule Appointment</button></center>
    </form>
</div>
</body>
</html>

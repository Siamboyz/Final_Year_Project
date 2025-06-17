<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counter_staff') {
    header("Location: homepage.php");
    exit();
}

include 'connection_database.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

// ✅ Auto-mark missed appointments
$currentTimestamp = date('Y-m-d H:i:s');
$autoUpdateMissed = "
    UPDATE appointment
    SET apt_status = 'Missed', was_missed = 1
    WHERE apt_status NOT IN ('Not Assigned', 'Scheduled', 'Completed', 'Now Serving')
    AND CONCAT(apt_date, ' ', apt_time) < '$currentTimestamp'
";
mysqli_query($conn, $autoUpdateMissed);

if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid patient ID.'); window.location.href='staff_patient.php';</script>";
    exit();
}

$patient_id = mysqli_real_escape_string($conn, $_GET['id']);

$query = "SELECT * FROM patient WHERE patient_id = '$patient_id'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    echo "<script>alert('Patient not found.'); window.location.href='staff_patient.php';</script>";
    exit();
}

$patient = mysqli_fetch_assoc($result);

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$countQuery = "SELECT COUNT(*) AS total FROM appointment WHERE patient_id = '$patient_id'";
$countResult = mysqli_query($conn, $countQuery);
$totalAppointments = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalAppointments / $limit);

$queryAppointments = "SELECT appointment.apt_id, appointment.apt_date, appointment.apt_time,
    appointment.apt_status, appointment.apt_notes, doctor.name AS doctor_name
    FROM appointment
    INNER JOIN doctor ON appointment.doctor_id = doctor.doctor_id
    WHERE appointment.patient_id = '$patient_id'
    ORDER BY appointment.apt_date DESC
    LIMIT $limit OFFSET $offset";

$appointments = mysqli_query($conn, $queryAppointments);
if (!$appointments) {
    echo "<script>alert('Error fetching appointment data: " . mysqli_error($conn) . "');</script>";
    exit();
}

$currentDate = date('Y-m-d');
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Information</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        table th {
            background-color: #f4f4f4;
        }

        .validate-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
        }

        .validate-button:hover {
            background-color: #0056b3;
        }

        .tabs {
            display: flex;
            background-color: #f4f4f4;
            border-bottom: 2px solid #ccc;
        }

        .tab-link {
            flex: 1;
            padding: 10px 20px;
            text-align: center;
            cursor: pointer;
            border: none;
            background-color: #f4f4f4;
            color: #333;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .tab-link:hover {
            background-color: #ddd;
        }

        .tab-link.active {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }

        .tab-content {
            display: none;
            padding: 20px;
            border: 1px solid #ccc;
        }

        .tab-content.active {
            display: block;
        }

        .pagination {
            margin-top: 20px;
            text-align: center;
        }

        .pagination a {
            text-decoration: none;
            padding: 5px 10px;
            margin: 0 3px;
            border: 1px solid #ccc;
            color: #007bff;
        }

        .pagination a.active-page {
            font-weight: bold;
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
        }
    </style>
</head>
<body>
<?php include 'header_staff.php' ?>
<div class="main-container">
    <h1>Counter Staff | Patient Details</h1>
    <?php include 'breadcrumb.php'; ?>

    <div class="tabs">
        <button class="tab-link active" onclick="openTab(event, 'patient-details')">Patient Details</button>
        <button class="tab-link" onclick="openTab(event, 'appointment-info')">Appointment Info</button>
    </div>
    <br>

    <div id="patient-details" class="tab-content active">
        <h2>Patient Details</h2>
        <table>
            <tbody>
            <tr>
                <th>Patient Name</th>
                <td><?= htmlspecialchars($patient['name'] ?? '') ?></td>
                <th>No. IC</th>
                <td><?= htmlspecialchars($patient['no_ic'] ?? '') ?></td>
            </tr>
            <tr>
                <th>Contact Number</th>
                <td><?= htmlspecialchars($patient['phone_number'] ?? '') ?></td>
                <th>Address</th>
                <td><?= htmlspecialchars($patient['address'] ?? '') ?></td>
            </tr>
            <tr>
                <th>Gender</th>
                <td><?= htmlspecialchars($patient['gender'] ?? '') ?></td>
                <th>Date of Birth</th>
                <td><?= htmlspecialchars($patient['dob'] ?? '') ?></td>
            </tr>
            <tr>
                <th>State</th>
                <td><?= htmlspecialchars($patient['state'] ?? '') ?></td>
                <th>Registered Date</th>
                <td><?= htmlspecialchars($patient['registered_datetime'] ?? '') ?></td>
            </tr>
            <tr>
                <th>Marital Status</th>
                <td><?= htmlspecialchars($patient['marital_status'] ?? '') ?></td>
                <th>Occupation</th>
                <td><?= htmlspecialchars($patient['occupation'] ?? '') ?></td>
            </tr>
            </tbody>
        </table>
    </div>
    <br>

    <div id="appointment-info" class="tab-content">
        <h2>Appointment History</h2>

        <!-- ✅ Dynamic Returning Button Logic -->
        <?php
        // CONDITION A: Active upcoming appointments (Scheduled, Now Serving, Not Assigned)
        $checkActive = "SELECT * FROM appointment 
                WHERE patient_id = '$patient_id' 
                  AND apt_status IN ('Scheduled', 'Now Serving', 'Not Assigned') 
                  AND apt_date >= CURDATE()";
        $activeResult = mysqli_query($conn, $checkActive);

        // CONDITION B: Missed upcoming appointments
        $checkMissed = "SELECT * FROM appointment 
                WHERE patient_id = '$patient_id' 
                  AND apt_status = 'Missed' ";
        $missedResult = mysqli_query($conn, $checkMissed);

        // SHOW RESULTS
        if (mysqli_num_rows($activeResult) > 0) {
            echo "<div style='margin: 20px 0; color: green; font-weight: bold;'>
            ✅ This patient already has an active appointment.
          </div>";
        } elseif (mysqli_num_rows($missedResult) > 0) {
            echo "<div style='margin: 20px 0; color: orange; font-weight: bold;'>
            ⚠ This patient has missed appointment(s) that require rescheduling.
          </div>";
        } else {
            // ✅ Safe to show register returning button
            echo "<div style='margin: 10px 0 20px; text-align: right;'>
            <a href='staff_register_returning.php?patient_id=$patient_id' 
               class='validate-button' 
               style='background-color: #ffc107; color: black; font-weight: bold;'>
               ➕ Register Returning Patient
            </a>
          </div>";
        }
        ?>
        <br>

        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Date & Time</th>
                <th>Doctor Name</th>
                <th>Appointment Notes</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $index = ($page - 1) * $limit + 1;
            while ($appointment = mysqli_fetch_assoc($appointments)) {
                $aptDate = $appointment['apt_date'];
                $aptStatus = strtolower(trim($appointment['apt_status']));
                $isToday = ($aptDate === $currentDate);
                $isMissed = ($aptStatus === 'missed');

                echo "<tr>
                    <td>{$index}</td>
                    <td>{$aptDate} {$appointment['apt_time']}</td>
                    <td>{$appointment['doctor_name']}</td>
                    <td>{$appointment['apt_notes']}</td>
                    <td>";

                if ($isToday && in_array($aptStatus, ['pending', 'not assigned'])) {
                    echo "<button class='validate-button' onclick='validateAppointment({$appointment['apt_id']})'>Validate</button>";
                } elseif (in_array($aptStatus, ['scheduled', 'now serving', 'completed'])) {
                    echo "<span style='color: green; font-weight: bold;'>{$appointment['apt_status']}</span>";
                } elseif ($isMissed) {
                    echo "<button class='validate-button' onclick='remakeAppointment({$appointment['apt_id']})'>Re-Schedule</button>";
                } else {
                    echo "No Action Available";
                }

                echo "</td></tr>";
                $index++;
            }
            ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php
            for ($i = 1; $i <= $totalPages; $i++) {
                $activeClass = ($i == $page) ? 'active-page' : '';
                echo "<a href='staff_patient_detail.php?id={$patient_id}&page={$i}#appointment-info' class='{$activeClass}'>Page $i</a>";
            }
            ?>
        </div>
    </div>
</div>

<script>
    function openTab(evt, tabName) {
        const tabs = document.querySelectorAll('.tab-content');
        const buttons = document.querySelectorAll('.tab-link');

        tabs.forEach(tab => tab.classList.remove('active'));
        buttons.forEach(btn => btn.classList.remove('active'));

        document.getElementById(tabName).classList.add('active');
        if (evt) evt.currentTarget.classList.add('active');

        history.replaceState(null, null, '#' + tabName);
    }

    function validateAppointment(id) {
        window.location.href = 'staff_patient_validation.php?apt_id=' + id;
    }

    function remakeAppointment(id) {
        if (confirm('Do you want to re-schedule this missed appointment?')) {
            window.location.href = 'staff_remake_appointment.php?apt_id=' + id;
        }
    }

    window.onload = function () {
        const hash = window.location.hash.substring(1);
        const defaultTab = hash ? hash : 'patient-details';
        const activeButton = [...document.querySelectorAll('.tab-link')]
            .find(btn => btn.textContent.replace(/\s/g, '').toLowerCase().includes(defaultTab.replace('-', '')));
        openTab({ currentTarget: activeButton }, defaultTab);
    };
</script>
</body>
</html>

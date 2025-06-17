<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counter_staff') {
    header("Location: homepage.php");
    exit();
}

date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDate = date('Y-m-d');
$currentTime = date('h:i A');
$currentDay = date('l');

// Get search term
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : "";
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'appointment'; // Default to 'appointment' tab

// SQL for Appointments (original query)
$appointmentQuery = "SELECT a.*, p.name AS patient_name, d.name AS doctor_name 
          FROM appointment a
          JOIN patient p ON a.patient_id = p.patient_id
          JOIN doctor d ON a.doctor_id = d.doctor_id
          WHERE a.apt_date = '$currentDate'";

if (!empty($search)) {
    $appointmentQuery .= " AND p.name LIKE '%$search%'";
}

$appointmentQuery .= " ORDER BY a.apt_time ASC";

$appointmentResult = mysqli_query($conn, $appointmentQuery);

// SQL for Missed Appointments (new query)
$missedAppointmentQuery = "SELECT a.*, p.name AS patient_name, d.name AS doctor_name
                           FROM appointment a
                           JOIN patient p ON a.patient_id = p.patient_id
                           JOIN doctor d ON a.doctor_id = d.doctor_id
                           WHERE a.apt_date < '$currentDate' AND a.apt_status = 'Missed'";

if (!empty($search)) {
    $missedAppointmentQuery .= " AND p.name LIKE '%$search%'";
}

$missedAppointmentQuery .= " ORDER BY a.apt_date DESC, a.apt_time DESC";

$missedAppointmentResult = mysqli_query($conn, $missedAppointmentQuery);

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment</title>
    <meta http-equiv="refresh" content="30">
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
            max-width: 100%; /* Limits the content width */
        }

        h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #007bff;
        }

        .breadcrumb {
            font-size: 14px;
            color: #666;
            margin-bottom: 25px;
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background-color: white;
        }

        table th, table td {
            border: 1px solid #e0e0e0;
            padding: 12px 14px;
            text-align: left;
            font-size: 14px;
        }

        table th {
            background-color: #f0f4f8;
            color: #333;
        }

        table tbody tr:hover {
            background-color: #f9fcff;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: white;
        }

        .badge-scheduled {
            background-color: #28a745; /* green */
        }

        .badge-not-assigned {
            background-color: #ffc107; /* yellow/orange */
            color: #212529;
        }

        .badge-cancelled {
            background-color: #dc3545; /* red */
        }

        .badge-completed {
            background-color: #007bff; /* blue */
        }

        .badge-missed {
            background-color: #6c757d; /* grey */
        }

        .no-appointments {
            text-align: center;
            color: #888;
            font-style: italic;
            padding: 30px 0;
        }

        .search-form {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-form {
            margin: 20px auto;
            display: flex;
            justify-content: center; /* center horizontally */
            align-items: center;
            gap: 10px;
            flex-wrap: wrap; /* allows wrapping on small screens */
        }

        .search-form input[type="text"] {
            padding: 10px 14px;
            width: 100%;
            max-width: 80%;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        }

        .search-form button {
            padding: 10px 20px;
            font-size: 14px;
            border: none;
            border-radius: 6px;
            background-color: #007bff;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-form button:hover {
            background-color: #0056b3;
        }

        .datetime-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #eef4fa;
            color: #333;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-family: 'Segoe UI', sans-serif;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .datetime-bar span {
            flex: 1;
            text-align: center;
        }

        .datetime-bar strong {
            color: #1a73e8; /* soft blue for keywords */
        }

        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }

        .clickable-row:hover {
            background-color: #e6f2ff;
        }

        /* Tab styles */
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .tab-button {
            padding: 12px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            background-color: #f0f4f8;
            color: #555;
            font-weight: bold;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            transition: background-color 0.3s, color 0.3s;
            text-decoration: none; /* For anchor tags */
        }

        .tab-button:hover {
            background-color: #e2e8f0;
        }

        .tab-button.active {
            background-color: white;
            border: 1px solid #ddd;
            border-bottom: 1px solid white;
            color: #007bff;
        }

        .tab-content {
            display: none;
            padding-top: 10px;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
<?php include 'header_staff.php'; ?>
<div class="main-container">
    <h1>Counter Staff | Appointment</h1>
    <?php include 'breadcrumb.php'; ?>

    <div class="tabs">
        <a href="?tab=appointment&search=<?= htmlspecialchars($search) ?>" class="tab-button <?= ($tab === 'appointment') ? 'active' : '' ?>">Appointment</a>
        <a href="?tab=missed_appointment&search=<?= htmlspecialchars($search) ?>" class="tab-button <?= ($tab === 'missed_appointment') ? 'active' : '' ?>">Missed Appointment</a>
    </div>

    <div id="appointmentTab" class="tab-content <?= ($tab === 'appointment') ? 'active' : '' ?>">
        <p class="datetime-bar"><strong>Date:</strong> <?= date('d/m/Y', strtotime($currentDate)) ?> |
            <strong>Day:</strong> <?= $currentDay ?> |
            <strong>Current Time:</strong> <?= $currentTime ?></p>
        <form class="search-form" method="GET" action="staff_appointment.php">
            <input type="hidden" name="tab" value="appointment">
            <input type="text" name="search" placeholder="Search by patient name..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>
        <br>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Patient Name</th>
                <th>Date</th>
                <th>Time</th>
                <th>Doctor</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $index = 1;
            while ($row = mysqli_fetch_assoc($appointmentResult)) {
                $patient_id = $row['patient_id']; // Get patient_id from query result
                echo "<tr class='clickable-row' onclick=\"window.location.href='staff_patient_detail.php?id=$patient_id'\">";
                echo "<td>{$index}</td>";
                echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
                echo "<td>" . date('d/m/Y', strtotime($row['apt_date'])) . "</td>";
                echo "<td>" . date('h:i A', strtotime($row['apt_time'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
                echo "<td>";

                // Colored badges
                switch (strtolower($row['apt_status'])) {
                    case 'scheduled':
                        echo "<span class='badge badge-scheduled'>Scheduled</span>";
                        break;
                    case 'not assigned':
                        echo "<span class='badge badge-not-assigned'>Not Assigned</span>";
                        break;
                    case 'cancelled':
                        echo "<span class='badge badge-cancelled'>Cancelled</span>";
                        break;
                    case 'completed':
                        echo "<span class='badge badge-completed'>Completed</span>";
                        break;
                    default:
                        echo "<span class='badge badge-not-assigned'>" . htmlspecialchars($row['apt_status']) . "</span>";
                        break;
                }

                echo "</td></tr>";
                $index++;
            }

            if ($index === 1) {
                echo "<tr><td colspan='6' class='no-appointments'>No appointments found for today.</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>

    <div id="missedAppointmentTab" class="tab-content <?= ($tab === 'missed_appointment') ? 'active' : '' ?>">
        <p class="datetime-bar"><strong>Date:</strong> <?= date('d/m/Y', strtotime($currentDate)) ?> |
            <strong>Day:</strong> <?= $currentDay ?> |
            <strong>Current Time:</strong> <?= $currentTime ?></p>
        <form class="search-form" method="GET" action="staff_appointment.php">
            <input type="hidden" name="tab" value="missed_appointment">
            <input type="text" name="search" placeholder="Search by patient name..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>
        <br>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Patient Name</th>
                <th>Date</th>
                <th>Time</th>
                <th>Doctor</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $index = 1;
            while ($row = mysqli_fetch_assoc($missedAppointmentResult)) {
                $patient_id = $row['patient_id']; // Get patient_id from query result
                echo "<tr class='clickable-row' onclick=\"window.location.href='staff_patient_detail.php?id=$patient_id'\">";
                echo "<td>{$index}</td>";
                echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
                echo "<td>" . date('d/m/Y', strtotime($row['apt_date'])) . "</td>";
                echo "<td>" . date('h:i A', strtotime($row['apt_time'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
                echo "<td>";
                echo "<span class='badge badge-missed'>Missed</span>"; // Always 'Missed' for this tab
                echo "</td></tr>";
                $index++;
            }

            if ($index === 1) {
                echo "<tr><td colspan='6' class='no-appointments'>No missed appointments found.</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: homepage.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];
date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDate = date('Y-m-d');

// Fetch all appointments for today for this doctor (no priority sorting)
$query = "SELECT a.*, p.name AS patient_name, d.name AS doctor_name
          FROM appointment a
          JOIN patient p ON a.patient_id = p.patient_id
          JOIN doctor d ON a.doctor_id = d.doctor_id
          WHERE a.apt_date = '$currentDate'
          AND a.doctor_id = '$doctor_id'
          ORDER BY a.apt_time ASC";

$result = mysqli_query($conn, $query);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor's Daily Appointments</title>
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

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }

        .status {
            font-weight: bold;
        }

        .status.Scheduled {
            color: #007bff;
        }

        .status['Now Serving'] {
            color: #28a745;
        }

        .status.Completed {
            color: gray;
        }

        .datetime-banner {
            background-color: #f0f8ff;
            border-left: 5px solid #007bff;
            padding: 10px 20px;
            margin-bottom: 20px;
            font-size: 18px;
            color: #333;
            box-shadow: 0 0 6px rgba(0,0,0,0.05);
            border-radius: 6px;
        }
        .datetime-banner strong {
            color: #007bff;
        }

    </style>
</head>
<body>

<?php include 'header_doc.php'; ?>

<div class="main-container">
    <h1>Doctor | Today's Appointment List</h1>
    <?php include 'breadcrumb.php'; ?>
    <?php
    $currentTime = date('h:i A');
    $currentDay = date('l');
    $formattedDate = date('d/m/Y');
    ?>
    <div class="datetime-banner">
        <p>
            <strong>Date:</strong> <?= $formattedDate ?> |
            <strong>Day:</strong> <?= $currentDay ?> |
            <strong>Current Time:</strong> <?= $currentTime ?>
        </p>
    </div>
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Doctor Name</th>
            <th>Patient Name</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $index = 1;
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>{$index}</td>
                        <td>" . htmlspecialchars($row['doctor_name']) . "</td>
                        <td>" . htmlspecialchars($row['patient_name']) . "</td>
                        <td>" . date('d/m/Y', strtotime($row['apt_date'])) . "</td>
                        <td>" . date('h:i A', strtotime($row['apt_time'])) . "</td>
                        <td class='status " . htmlspecialchars($row['apt_status']) . "'>" . htmlspecialchars($row['apt_status']) . "</td>
                      </tr>";
                $index++;
            }
        } else {
            echo "<tr><td colspan='6'>No appointments found for today.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

date_default_timezone_set('Asia/Kuala_Lumpur');

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Query for 'Appointment' tab
$query_appointments = "
    SELECT a.*, 
           p.name AS patient_name, 
           d.name AS doctor_name,
           r.room_name
    FROM appointment a
    JOIN patient p ON a.patient_id = p.patient_id
    JOIN doctor d ON a.doctor_id = d.doctor_id
    LEFT JOIN room r ON d.room_id = r.room_id
    WHERE a.apt_date = '$date'
    ORDER BY a.apt_time ASC
";
$result_appointments = mysqli_query($conn, $query_appointments);

// Query for 'Missed Appointment' tab
$current_datetime = date('Y-m-d H:i:s');
$current_date = date('Y-m-d');
$current_time = date('H:i:s');


$query_missed_appointments = "
    SELECT a.*,
           p.name AS patient_name,
           d.name AS doctor_name,
           r.room_name
    FROM appointment a
    JOIN patient p ON a.patient_id = p.patient_id
    JOIN doctor d ON a.doctor_id = d.doctor_id
    LEFT JOIN room r ON d.room_id = r.room_id
    WHERE (a.apt_status = 'Missed')
      AND (
            (a.apt_date < '$current_date')
            OR
            (a.apt_date = '$current_date' AND a.apt_time < '$current_time')
          )
    ORDER BY a.apt_date ASC, a.apt_time ASC
";
$result_missed_appointments = mysqli_query($conn, $query_missed_appointments);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Appointments | OASS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
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
            font-size: 1.8rem;
            color: #007bff;
            margin-bottom: 10px;
            border-bottom: 1px solid #ecf0f1;
            padding-bottom: 15px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .filter-form input[type="date"] {
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-form button {
            padding: 10px 18px;
            background-color: #007bff;
            border: none;
            color: white;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
        }

        .filter-form button:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-top: 20px;
        }

        table th, table td {
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            text-align: left;
        }

        table th {
            background-color: #f1f3f5;
            color: #333;
        }

        table tbody tr:hover {
            background-color: #eef5ff;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #777;
        }

        /* Tab styles */
        .tab-container {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .tab-button {
            background-color: #f1f3f5;
            border: none;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 16px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            transition: background-color 0.3s ease;
            margin-right: 5px;
            color: #555;
        }

        .tab-button:hover {
            background-color: #e2e6ea;
        }

        .tab-button.active {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            border-bottom: 3px solid #007bff; /* Underline effect for active tab */
            margin-bottom: -1px; /* To prevent double border with container */
        }

        .tab-content {
            display: none;
            padding: 15px 0;
            border-top: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Modern CSS for Missed Appointments Table (reusing general table styles) */
        .missed-appointment-table {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .missed-appointment-table th {
            background-color: #ffcccc; /* Light red for missed appointments header */
            color: #8b0000; /* Darker red text */
            font-weight: bold;
        }

        .missed-appointment-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .missed-appointment-table tbody tr:hover {
            background-color: #ffe0e0; /* Lighter red on hover */
        }

        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }
            .tab-container {
                flex-direction: column;
            }
            .tab-button {
                width: 100%;
                border-radius: 0;
                margin-bottom: 2px;
            }
            .main-container {
                margin-left: 0;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<div class="main-container">
    <h1><i class="fas fa-calendar-check"></i> Admin | Appointments</h1>
    <?php include 'breadcrumb.php'; ?>
    <br>

    <div class="filter-form">
        <input type="date" name="date" id="appointmentDate" value="<?= $date ?>" required>
        <button type="button" onclick="filterAppointments()"><i class="fas fa-search"></i> View</button>
    </div>

    <div class="tab-container">
        <button class="tab-button active" onclick="openTab(event, 'appointments')">Appointments</button>
        <button class="tab-button" onclick="openTab(event, 'missedAppointments')">Missed Appointments</button>
    </div>

    <div id="appointments" class="tab-content active">
        <h2>Appointments on <?= date('F j, Y', strtotime($date)) ?></h2>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Room</th>
                <th>Time</th>
                <th>Status</th>
                <th>Note</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $index = 1;
            if (mysqli_num_rows($result_appointments) > 0) {
                while ($row = mysqli_fetch_assoc($result_appointments)) {
                    echo "<tr>";
                    echo "<td>{$index}</td>";
                    echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['room_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['apt_time']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['apt_status']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['apt_notes']) . "</td>";
                    echo "</tr>";
                    $index++;
                }
            } else {
                echo "<tr><td colspan='7' class='no-data'>No appointments found for this date.</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>

    <div id="missedAppointments" class="tab-content">
        <h2>Missed Appointments</h2>
        <table class="missed-appointment-table">
            <thead>
            <tr>
                <th>#</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Room</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Note</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $index_missed = 1;
            if (mysqli_num_rows($result_missed_appointments) > 0) {
                while ($row = mysqli_fetch_assoc($result_missed_appointments)) {
                    echo "<tr>";
                    echo "<td>{$index_missed}</td>";
                    echo "<td>" . htmlspecialchars($row['patient_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['room_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['apt_date']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['apt_time']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['apt_status']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['apt_notes']) . "</td>";
                    echo "</tr>";
                    $index_missed++;
                }
            } else {
                echo "<tr><td colspan='8' class='no-data'>No missed appointments found.</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;

        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
            tabcontent[i].classList.remove("active");
        }

        tablinks = document.getElementsByClassName("tab-button");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }

        document.getElementById(tabName).style.display = "block";
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");

        // If the 'Appointments' tab is selected, update the URL with the date
        if (tabName === 'appointments') {
            const date = document.getElementById('appointmentDate').value;
            history.replaceState(null, '', `admin_view_appointment.php?date=${date}`);
        } else {
            // For other tabs (like Missed Appointments), remove the date parameter from the URL
            history.replaceState(null, '', `admin_view_appointment.php`);
        }
    }

    function filterAppointments() {
        const date = document.getElementById('appointmentDate').value;
        window.location.href = `admin_view_appointment.php?date=${date}`;
    }

    // Set initial tab based on URL parameter or default to 'appointments'
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const dateParam = urlParams.get('date');
        if (dateParam) {
            document.getElementById('appointmentDate').value = dateParam;
            openTab(event, 'appointments'); // Open appointments tab if date param exists
        } else {
            // Default to opening the 'appointments' tab if no specific date is selected (or any other tab if desired)
            document.querySelector('.tab-button').click(); // Simulates click on the first tab
        }
    });

</script>
</body>
</html>
<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counter_staff') {
    header("Location: homepage.php");
    exit();
}

date_default_timezone_set('Asia/Kuala_Lumpur');
$today = date('Y-m-d');

// Dashboard stats
$total_patients_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM patient");
$total_patients = mysqli_fetch_assoc($total_patients_query)['total'];

$total_doctors_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM doctor");
$total_doctors = mysqli_fetch_assoc($total_doctors_query)['total'];

$today_appointments_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointment WHERE apt_date = '$today'");
$today_appointments = mysqli_fetch_assoc($today_appointments_query)['total'];

$completed_appointments_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointment WHERE apt_date = '$today' AND apt_status = 'Completed'");
$completed_appointments = mysqli_fetch_assoc($completed_appointments_query)['total'];

// Incomplete profiles
$incomplete_patients_query = mysqli_query($conn, "SELECT patient_id, name, no_ic FROM patient WHERE profile_completed = 0 ORDER BY registered_datetime ASC");
$incomplete_patients = [];
if ($incomplete_patients_query) {
    while ($row = mysqli_fetch_assoc($incomplete_patients_query)) {
        $incomplete_patients[] = $row;
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counter Staff Dashboard</title>
    <link rel="stylesheet" href="css/staff_dashboard_style.css" type="text/css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }

        .main-container {
            background-color: white;
            padding: 30px;
            margin-left: calc(100% - 80%);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .dashboard-cards {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .card {
            flex: 1;
            min-width: 220px;
            background-color: #f0f4ff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .card img {
            width: 50px;
            margin-bottom: 10px;
        }

        .card h3 {
            margin: 10px 0;
            font-size: 18px;
        }

        .card p {
            margin: 5px 0;
            font-size: 24px;
            font-weight: bold;
            color: #1a237e;
        }

        .card span {
            display: block;
            font-size: 14px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: center;
        }

        table th {
            background-color: #ffe6e6;
        }

        .btn-complete {
            background-color: #007bff;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-complete:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<?php include 'header_staff.php'; ?>

<div class="main-container">
    <main class="dashboard">
        <section>
            <img src="img/main.jpg" alt="Dashboard Banner" style="max-width: 100%; height: 360px; width: 100%; margin-bottom: 20px;">
            <br>
            <h1>Welcome to the Counter Staff Dashboard</h1>
            <p>Here you can manage patient appointments, manage patient records, and much more.</p>
        </section>

        <!-- Real-Time Stats Cards -->
        <section class="dashboard-cards">
            <div class="card">
                <img src="icons/patient.png" alt="Total Patients">
                <h3>Total Patients</h3>
                <p><?php echo $total_patients; ?>+</p>
                <span>Till Today</span>
            </div>

            <div class="card">
                <img src="icons/appointment.png" alt="Today Appointments">
                <h3>Today Appointments</h3>
                <p><?php echo $today_appointments; ?></p>
                <span><?php echo date('d M Y'); ?></span>
            </div>

            <div class="card">
                <img src="icons/complete%20appointment.png" alt="Completed Today">
                <h3>Completed Appointments</h3>
                <p><?php echo $completed_appointments; ?></p>
                <span><?php echo date('d M Y'); ?></span>
            </div>

            <div class="card">
                <img src="icons/doctor.png" alt="Total Doctors">
                <h3>Total Doctors</h3>
                <p><?php echo $total_doctors; ?>+</p>
                <span>Till Today</span>
            </div>
        </section>

        <!-- Reminder for Incomplete Profiles -->
        <section style="margin-top: 50px;">
            <h2 style="color: #dc3545;">⚠ Patients with Incomplete Profile</h2>

            <?php if (count($incomplete_patients) > 0): ?>
                <table>
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient Name</th>
                        <th>IC Number</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($incomplete_patients as $index => $patient): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($patient['name']) ?></td>
                            <td><?= htmlspecialchars($patient['no_ic']) ?></td>
                            <td>
                                <a href="staff_complete_profile.php?id=<?= $patient['patient_id'] ?>" class="btn-complete">Complete Profile</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: green; font-weight: bold;">✅ All patient profiles are completed!</p>
            <?php endif; ?>
        </section>

    </main>
</div>

</body>
</html>

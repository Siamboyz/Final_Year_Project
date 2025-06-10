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

// Get doctor info
$doctor_sql = "SELECT d.name AS doctor_name, d.specialization, r.room_name
               FROM doctor d
               JOIN room r ON d.room_id = r.room_id
               WHERE d.doctor_id = '$doctor_id'";
$doctor_result = mysqli_query($conn, $doctor_sql);
$doctor_info = mysqli_fetch_assoc($doctor_result);

// Total patients ever consulted
$total_patient_sql = "SELECT COUNT(DISTINCT patient_id) AS total FROM appointment WHERE doctor_id = '$doctor_id'";
$total_patient_result = mysqli_query($conn, $total_patient_sql);
$total_patients = mysqli_fetch_assoc($total_patient_result)['total'];

// Total appointments for today by this doctor
$today_patient_sql = "SELECT COUNT(*) AS today_appointments 
                      FROM appointment 
                      WHERE doctor_id = '$doctor_id' AND apt_date = '$currentDate'";
$today_patient_result = mysqli_query($conn, $today_patient_sql);
$today_appointments = mysqli_fetch_assoc($today_patient_result)['today_appointments'];

// Today’s completed appointments
$completed_sql = "SELECT COUNT(*) AS completed FROM appointment 
                  WHERE doctor_id = '$doctor_id' AND apt_date = '$currentDate' AND apt_status = 'Completed'";
$completed_result = mysqli_query($conn, $completed_sql);
$completed_appointments = mysqli_fetch_assoc($completed_result)['completed'];
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="css/doc_dashboard_style.css" type="text/css">
    <title>Doctor Dashboard</title>
    <style>

        /* Dashboard Content */
        .dashboard {
            flex-grow: 1;
            padding-bottom: 60px; /* Ensures space for footer */
        }

        .dashboard h1 {
            font-size: 2.5rem;
            color: #005A9C; /* Deep Blue - Professional & Primary */
            margin-bottom: 1rem;
        }

        .dashboard p {
            font-size: 1rem;
            color: #008080; /* Teal - Accent for readability */
            line-height: 1.6;
            margin: 0 auto;
        }

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
    </style>
</head>
<body>
<?php include 'header_doc.php' ?>
<div class="main-container">
    <main class="dashboard">
        <section>
            <img src="img/doctor%20background.jpg" alt="Dashboard Banner" style="max-width: 100%; height: 360px; width: 100%; margin-bottom: 20px;">
            <br>
            <h1>Welcome to the Doctor Dashboard</h1>
            <p>Here you can manage your appointments, view patient records, and much more.</p>
        </section>

        <div style="margin-top: 30px; padding: 20px; background: #fff4e6; border-left: 5px solid #ffa500; border-radius: 10px; font-size: 20px;">
            <h3 style="margin-bottom: 10px;">Doctor Info</h3>
            <p><strong>Name:</strong> <?= $doctor_info['doctor_name'] ?></p>
            <p><strong>Specialization:</strong> <?= $doctor_info['specialization'] ?></p>
            <p><strong>Room:</strong> <?= $doctor_info['room_name'] ?></p>
        </div>

        <div style="display: flex; gap: 20px; flex-wrap: wrap; margin: 20px 0;">

            <!-- Total Patients -->
            <div style="flex: 1; min-width: 250px; background: #eef3f8; padding: 20px; border-radius: 10px; text-align: center;">
                <img src="icons/patient.png" alt="Total Patients Icon" style="width: 60px; height: 60px; margin-bottom: 10px;">
                <h2>Total Patients</h2>
                <p style="font-size: 28px; font-weight: bold; color: #333;"><?= $total_patients ?>+</p>
                <p style="font-size: 14px;">Till Today</p>
            </div>

            <!-- Today's Appointments -->
            <div style="flex: 1; min-width: 250px; background: #eef3f8; padding: 20px; border-radius: 10px; text-align: center;">
                <img src="icons/appointment.png" alt="Today Appointment Icon" style="width: 60px; height: 60px; margin-bottom: 10px;">
                <h2>Today's Total Appointments</h2>
                <p style="font-size: 28px; font-weight: bold; color: #333;"><?= $today_appointments ?>+</p>
                <p style="font-size: 14px;"><?= date('d M Y') ?></p>
            </div>

            <!-- Completed Appointments -->
            <div style="flex: 1; min-width: 250px; background: #eef3f8; padding: 20px; border-radius: 10px; text-align: center;">
                <img src="icons/complete appointment.png" alt="Completed Appointments Icon" style="width: 60px; height: 60px; margin-bottom: 10px;">
                <h2>Completed Today</h2>
                <p style="font-size: 28px; font-weight: bold; color: #333;"><?= $completed_appointments ?>+</p>
                <p style="font-size: 14px;"><?= date('d M Y') ?></p>
            </div>

        </div>
    </main>
</div>
</body>
</html>

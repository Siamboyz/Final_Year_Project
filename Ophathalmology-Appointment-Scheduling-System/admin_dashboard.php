<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

date_default_timezone_set('Asia/Kuala_Lumpur');
$today = date('Y-m-d');

// Queue Monitoring
$q8 = mysqli_query($conn, "
    SELECT p.name AS patient_name, r.room_name
    FROM appointment a
    JOIN patient p ON a.patient_id = p.patient_id
    JOIN doctor d ON a.doctor_id = d.doctor_id
    JOIN room r ON d.room_id = r.room_id
    WHERE a.apt_date = '$today' AND a.apt_status IN ('Scheduled', 'Now Serving')
    ORDER BY r.room_id, a.apt_time
");

$roomQueues = [];
while ($row = mysqli_fetch_assoc($q8)) {
    $roomQueues[$row['room_name']][] = $row['patient_name'];
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/admin_dashboard_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .dashboard h1 {
            font-size: 2.5rem;
            color: #005A9C;
            margin-bottom: 1rem;
        }

        .dashboard p {
            font-size: 1rem;
            color: #008080;
            line-height: 1.6;
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<div class="main-container">
    <main class="dashboard">
        <img src="img/Eye-clinic-.jpg" alt="Eye Dashboard Image" style="width: 100%; margin-top: 20px; height: 300px;">
        <section>
            <h1>Welcome to the Admin Dashboard</h1>
            <p>Here you can manage appointments, view patient records, and monitor real-time clinic operations.</p>
        </section>
    </main>
</div>
</body>
</html>
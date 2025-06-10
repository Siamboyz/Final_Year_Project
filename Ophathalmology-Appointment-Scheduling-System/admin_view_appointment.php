<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

date_default_timezone_set('Asia/Kuala_Lumpur');

$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$query = "
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

$result = mysqli_query($conn, $query);
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

        .breadcrumb {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
            text-align: right;
        }

        h1 {
            font-size: 1.8rem;
            color: #007bff;
            margin-bottom: 10px; /* Adjusted margin */
            border-bottom: 1px solid #ecf0f1; /* Subtle separator */
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

        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<div class="main-container">
    <h1><i class="fas fa-calendar-check"></i> Admin | Appointments on <?= date('F j, Y', strtotime($date)) ?></h1>
    <?php include 'breadcrumb.php'; ?>
    <br>

    <form class="filter-form" method="GET">
        <input type="date" name="date" value="<?= $date ?>" required>
        <button type="submit"><i class="fas fa-search"></i> View</button>
    </form>

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
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
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
</body>
</html>
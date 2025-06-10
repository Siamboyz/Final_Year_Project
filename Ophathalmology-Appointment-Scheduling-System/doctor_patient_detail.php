<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: homepage.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];
$patient_id = isset($_GET['patient_id']) ? mysqli_real_escape_string($conn, $_GET['patient_id']) : null;

if (!$patient_id) {
    echo "<script>alert('Invalid patient ID'); window.location.href='doctor_patient.php';</script>";
    exit();
}

// Fetch patient info
$patientQuery = "SELECT * FROM patient WHERE patient_id = '$patient_id'";
$patientResult = mysqli_query($conn, $patientQuery);
if (!$patientResult || mysqli_num_rows($patientResult) == 0) {
    echo "<script>alert('Patient not found'); window.location.href='doctor_patient.php';</script>";
    exit();
}
$patient = mysqli_fetch_assoc($patientResult);

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Appointments with this doctor only
$appointmentsQuery = "SELECT * FROM appointment 
                      WHERE patient_id = '$patient_id' AND doctor_id = '$doctor_id' 
                      ORDER BY apt_date DESC, apt_time DESC 
                      LIMIT $limit OFFSET $offset";
$appointmentsResult = mysqli_query($conn, $appointmentsQuery);

// Total count
$countQuery = "SELECT COUNT(*) AS total FROM appointment 
               WHERE patient_id = '$patient_id' AND doctor_id = '$doctor_id'";
$countResult = mysqli_query($conn, $countQuery);
$totalAppointments = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalAppointments / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor | Patient Detail</title>
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

        .back-link {
            display: block;
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
            text-decoration: none;
            color: #555;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .info-box {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 30px;
            background-color: #f9fbfc;
            border-radius: 10px;
            padding: 20px 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .info-box p {
            margin: 0;
            font-size: 15px;
        }

        .info-box span.label {
            font-weight: 600;
            color: #0056b3;
            display: inline-block;
            min-width: 130px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f1f1f1;
        }

        .pagination {
            margin-top: 20px;
            text-align: center;
        }

        .pagination a {
            text-decoration: none;
            margin: 0 5px;
            padding: 8px 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            color: #007bff;
        }

        .pagination a.active {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
<?php include 'header_doc.php'; ?>
<div class="main-container">
    <h1>👤 Doctor | Patient Details</h1>
    <?php include 'breadcrumb.php'; ?>
    <br>
    <div class="info-box">
        <p><span class="label">Name:</span> <?= htmlspecialchars($patient['name']) ?></p>
        <p><span class="label">IC Number:</span> <?= htmlspecialchars($patient['no_ic']) ?></p>

        <p><span class="label">Date of Birth:</span> <?= htmlspecialchars($patient['dob']) ?></p>
        <p><span class="label">Phone:</span> <?= htmlspecialchars($patient['phone_number']) ?></p>

        <p><span class="label">Address:</span> <?= htmlspecialchars($patient['address']) ?></p>
        <p><span class="label">State:</span> <?= htmlspecialchars($patient['state']) ?></p>

        <p><span class="label">Gender:</span> <?= htmlspecialchars($patient['gender']) ?></p>
        <p><span class="label">Marital Status:</span> <?= htmlspecialchars($patient['marital_status']) ?></p>

        <p><span class="label">Occupation:</span> <?= htmlspecialchars($patient['occupation']) ?></p>
        <p><span class="label">Registered Date:</span> <?= htmlspecialchars($patient['registered_datetime']) ?></p>
    </div>

    <h2>📖 Appointment History</h2>
    <table>
        <thead>
        <tr>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
            <th>Notes</th>
        </tr>
        </thead>
        <tbody>
        <?php
        if (mysqli_num_rows($appointmentsResult) > 0) {
            while ($apt = mysqli_fetch_assoc($appointmentsResult)) {
                echo "<tr>
                        <td>" . date('d/m/Y', strtotime($apt['apt_date'])) . "</td>
                        <td>" . date('h:i A', strtotime($apt['apt_time'])) . "</td>
                        <td>" . htmlspecialchars($apt['apt_status']) . "</td>
                        <td>" . htmlspecialchars($apt['apt_notes']) . "</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No appointment history available.</td></tr>";
        }
        ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="pagination">
        <?php
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = ($i == $page) ? 'active' : '';
            echo "<a class='$active' href='doctor_patient_detail.php?patient_id=$patient_id&page=$i'>$i</a>";
        }
        ?>
    </div>

    <a href="doctor_patient.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Patient List</a>
</div>
</body>
</html>

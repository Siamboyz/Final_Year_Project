<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Fetch patient info
$patient_query = mysqli_query($conn, "SELECT * FROM patient WHERE patient_id = '$patient_id'");
$patient = mysqli_fetch_assoc($patient_query);

if (!$patient) {
    echo "<script>alert('Patient not found'); window.location.href='admin_view_patient.php';</script>";
    exit();
}

// Fetch past appointments
$appt_query = mysqli_query($conn, "
    SELECT a.*, d.name AS doctor_name
    FROM appointment a
    LEFT JOIN doctor d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = '$patient_id' AND a.apt_status = 'Completed'
    ORDER BY a.apt_date DESC
    LIMIT $limit OFFSET $offset
");

// Total records for pagination
$count_query = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM appointment 
    WHERE patient_id = '$patient_id' AND apt_status = 'Completed'
");
$total = mysqli_fetch_assoc($count_query)['total'];
$total_pages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Details | OASS</title>
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
        }

        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            background-color: #f9fbfd;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .info-section p {
            margin: 0;
            font-size: 14.5px;
            color: #333;
        }

        .info-section p strong {
            display: inline-block;
            min-width: 140px;
            color: #005A9C;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
        }

        table th {
            background-color: #f1f3f5;
        }

        .pagination {
            margin-top: 20px;
            text-align: center;
        }

        .pagination a {
            margin: 0 5px;
            padding: 6px 12px;
            color: #007bff;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .pagination a.active {
            background-color: #007bff;
            color: white;
            pointer-events: none;
        }

        .pagination a:hover {
            background-color: #0056b3;
            color: white;
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
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<div class="main-container">
    <h1><i class="fas fa-user"></i> Admin | Patient Profile</h1>
    <?php include 'breadcrumb.php'; ?>
    <br>

    <div class="info-section">
        <p><strong>Name:</strong> <?= htmlspecialchars($patient['name']) ?></p>
        <p><strong>IC Number:</strong> <?= htmlspecialchars($patient['no_ic']) ?></p>
        <p><strong>Date of Birth:</strong> <?= htmlspecialchars($patient['dob']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($patient['phone_number']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($patient['address']) ?></p>
        <p><strong>State:</strong> <?= htmlspecialchars($patient['state']) ?></p>
        <p><strong>Gender:</strong> <?= htmlspecialchars($patient['gender']) ?></p>
        <p><strong>Marital Status:</strong> <?= htmlspecialchars($patient['marital_status']) ?></p>
        <p><strong>Occupation:</strong> <?= htmlspecialchars($patient['occupation']) ?></p>
        <p><strong>Registered Date:</strong> <?= htmlspecialchars($patient['registered_datetime']) ?></p>
    </div>

    <h1><i class="fas fa-file-medical-alt"></i> Medical History (Past Appointments)</h1>
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Time</th>
            <th>Doctor</th>
            <th>Notes</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $i = $offset + 1;
        if (mysqli_num_rows($appt_query) > 0) {
            while ($row = mysqli_fetch_assoc($appt_query)) {
                echo "<tr>";
                echo "<td>{$i}</td>";
                echo "<td>" . htmlspecialchars($row['apt_date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['apt_time']) . "</td>";
                echo "<td>" . htmlspecialchars($row['doctor_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['apt_notes']) . "</td>";
                echo "</tr>";
                $i++;
            }
        } else {
            echo "<tr><td colspan='5' style='text-align:center; color: #999;'>No past appointments found.</td></tr>";
        }
        ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <a href="?id=<?= $patient_id ?>&page=<?= $p ?>" class="<?= $p == $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <a href="admin_view_patient.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Patient List</a>
</div>
</body>
</html>

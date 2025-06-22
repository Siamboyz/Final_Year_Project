<?php
include 'connection_database.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

// Validate and sanitize inputs from the URL
// Doctor ID and Date are mandatory
if (!isset($_GET['doctor_id']) || !isset($_GET['date'])) {
    // Using a custom message box instead of alert() for better UI/UX
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                const container = document.querySelector('.container');
                if (container) {
                    container.innerHTML = '<div class=\"alert alert-danger text-center\">Doctor ID or Date not specified. Redirecting...</div>';
                }
                setTimeout(() => { window.location.href = 'admin_report.php'; }, 2000);
            });
          </script>";
    exit();
}

$doctorId = mysqli_real_escape_string($conn, $_GET['doctor_id']);
$date = mysqli_real_escape_string($conn, $_GET['date']);
// Get available minutes; it's optional, so provide a default or null
$availableMinutes = isset($_GET['available_minutes']) ? (int)$_GET['available_minutes'] : null;

// Fetch doctor's name based on the sanitized doctor ID
$doctorRes = mysqli_query($conn, "SELECT name FROM doctor WHERE doctor_id = '$doctorId'");
$doctorData = mysqli_fetch_assoc($doctorRes);

if (!$doctorData) {
    // Using a custom message box for doctor not found
    echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                const container = document.querySelector('.container');
                if (container) {
                    container.innerHTML = '<div class=\"alert alert-danger text-center\">Doctor not found. Redirecting...</div>';
                }
                setTimeout(() => { window.location.href = 'admin_report.php'; }, 2000);
            });
          </script>";
    exit();
}

$doctorName = $doctorData['name'];

// Construct the title suffix including available minutes if provided
$titleSuffix = '';
if ($availableMinutes !== null) {
    $titleSuffix = " ($availableMinutes mins available)";
}

// Fetch appointments for the selected doctor and date
$appointments = mysqli_query($conn, "
    SELECT a.*, p.name AS patient_name
    FROM appointment a
    LEFT JOIN patient p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = '$doctorId' AND a.apt_date = '$date'
    ORDER BY a.apt_time ASC
");
// Changed ORDER BY to apt_time ASC for a more logical display order
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($doctorName) ?> - Appointment Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5; /* Light grey background */
            color: #333;
            line-height: 1.6;
        }

        .container {
            margin-top: 40px;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 12px; /* Soft rounded corners */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); /* Subtle shadow */
        }

        h2 {
            color: #2c3e50; /* Darker heading color */
            font-weight: 700;
            margin-bottom: 25px;
            text-align: center;
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            transition: all 0.3s ease;
            border-radius: 8px; /* Rounded buttons */
            padding: 10px 20px;
            font-weight: 500;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
            transform: translateY(-2px); /* Slight lift on hover */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .table {
            margin-top: 20px;
            border-radius: 10px; /* Rounded table corners */
            overflow: hidden; /* Ensures rounded corners are visible */
        }

        .table thead {
            background-color: #007bff; /* Primary color for header */
            color: #ffffff;
        }

        .table th {
            padding: 15px;
            font-weight: 600;
            text-align: left;
        }

        .table tbody tr {
            background-color: #ffffff;
            transition: background-color 0.2s ease;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa; /* Zebra striping for readability */
        }

        .table tbody tr:hover {
            background-color: #e2f0ff; /* Light blue on hover */
        }

        .table td {
            padding: 12px 15px;
            vertical-align: middle;
        }

        .alert-info, .alert-danger {
            background-color: #e0f2f7; /* Lighter info background */
            color: #007bff; /* Info text color */
            border-color: #b3e0ed; /* Info border */
            border-radius: 8px; /* Rounded alerts */
            padding: 15px;
            font-weight: 500;
            text-align: center;
        }

        .alert-danger {
            background-color: #f8d7da; /* Lighter danger background */
            color: #721c24; /* Danger text color */
            border-color: #f5c6cb; /* Danger border */
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                margin-top: 20px;
                padding: 20px;
            }
            .table th, .table td {
                padding: 10px;
                font-size: 0.9em;
            }
            .btn-secondary {
                width: 100%; /* Full width button on small screens */
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body class="p-4">
<div class="container">
    <h2 class="mb-4">
        <?= htmlspecialchars($doctorName) ?>'s Appointment Records for <?= htmlspecialchars($date) ?><?= htmlspecialchars($titleSuffix) ?>
    </h2>

    <a href="admin_report.php" class="btn btn-secondary mb-3">Back to Report</a>

    <?php if (mysqli_num_rows($appointments) > 0): ?>
        <div class="table-responsive"> <!-- Added for better table responsiveness on small screens -->
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>No.</th>
                    <th>Patient Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Visit Type</th>
                </tr>
                </thead>
                <tbody>
                <?php $no = 1; while ($row = mysqli_fetch_assoc($appointments)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['patient_name']) ?></td>
                        <td><?= htmlspecialchars($row['apt_date']) ?></td>
                        <td><?= htmlspecialchars($row['apt_time']) ?></td>
                        <td><?= htmlspecialchars($row['apt_status']) ?></td>
                        <td><?= htmlspecialchars($row['visit_type']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            No appointments found for this doctor on <?= htmlspecialchars($date) ?><?= htmlspecialchars($titleSuffix) ?>.
        </div>
    <?php endif; ?>
</div>
</body>
</html>
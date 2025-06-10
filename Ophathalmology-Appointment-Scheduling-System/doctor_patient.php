<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: homepage.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$query = "SELECT DISTINCT p.*
          FROM patient p
          JOIN appointment a ON p.patient_id = a.patient_id
          WHERE a.doctor_id = '$doctor_id'
            AND (p.name LIKE '%$search%' OR '$search' = '')
          ORDER BY p.name ASC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor | My Patients</title>
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

        form {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        input[type="text"] {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        button {
            padding: 10px 18px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        button:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 14px;
            border: 1px solid #e0e0e0;
            text-align: left;
        }

        th {
            background-color: #f7f9fc;
            color: #333;
        }

        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .clickable-row:hover {
            background-color: #e9f2ff;
        }
    </style>
</head>
<body>
<?php include 'header_doc.php'; ?>

<div class="main-container">
    <h1>👨‍⚕️ Doctor | Patients</h1>
    <?php include 'breadcrumb.php'; ?>
    <br>

    <!-- Search Form -->
    <form method="GET">
        <input type="text" name="search" placeholder="Search by patient name..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Search</button>
    </form>

    <!-- Patients Table -->
    <table>
        <thead>
        <tr>
            <th>No</th>
            <th>Patient Name</th>
            <th>IC Number</th>
            <th>Phone Number</th>
            <th>Address</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $index = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            $patientId = $row['patient_id'];
            echo "<tr class='clickable-row' data-href='doctor_patient_detail.php?patient_id=$patientId'>
                    <td>{$index}</td>
                    <td>" . htmlspecialchars($row['name']) . "</td>
                    <td>" . htmlspecialchars($row['no_ic']) . "</td>
                    <td>" . htmlspecialchars($row['phone_number']) . "</td>
                    <td>" . htmlspecialchars($row['address']) . "</td>
                  </tr>";
            $index++;
        }

        if ($index === 1) {
            echo "<tr><td colspan='5'>No patients found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<!-- JS to handle row clicks -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const rows = document.querySelectorAll(".clickable-row");
        rows.forEach(row => {
            row.addEventListener("click", () => {
                window.location.href = row.getAttribute("data-href");
            });
        });
    });
</script>
</body>
</html>

<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

$searchTerm = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$query = "SELECT * FROM patient";
if (!empty($searchTerm)) {
    $query .= " WHERE name LIKE '%$searchTerm%'";
}
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Patients | OASS</title>
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

        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-container input {
            flex: 1;
            padding: 10px 15px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .search-container button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
        }

        .search-container button:hover {
            background-color: #218838;
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
            cursor: pointer;
        }

        .no-data {
            text-align: center;
            color: #888;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .search-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<div class="main-container">
    <h1><i class="fas fa-users"></i> Admin | Registered Patients</h1>
    <?php include 'breadcrumb.php'; ?>
    <br>

    <form method="GET" class="search-container">
        <input type="text" name="search" placeholder="Search by patient name..." value="<?= htmlspecialchars($searchTerm) ?>">
        <button type="submit"><i class="fas fa-search"></i> Search</button>
    </form>

    <table>
        <thead>
        <tr>
            <th style="width: 50px;">#</th>
            <th>Patient Name</th>
            <th>Date of Birth</th>
            <th>Phone</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $index = 1;
        if (mysqli_num_rows($result) > 0) {
            while ($patient = mysqli_fetch_assoc($result)) {
                echo "<tr onclick=\"window.location.href='admin_view_patientDetail.php?id={$patient['patient_id']}'\">";
                echo "<td>{$index}</td>";
                echo "<td>" . htmlspecialchars($patient['name']) . "</td>";
                echo "<td>" . htmlspecialchars($patient['dob']) . "</td>";
                echo "<td>" . htmlspecialchars($patient['phone_number']) . "</td>";
                echo "</tr>";
                $index++;
            }
        } else {
            echo "<tr><td colspan='4' class='no-data'>No patients found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>
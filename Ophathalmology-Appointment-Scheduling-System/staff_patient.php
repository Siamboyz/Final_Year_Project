<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counter_staff') {
    header("Location: homepage.php");
    exit();
}

include 'connection_database.php';

$patients = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['searchPatient'])) {
    $search = mysqli_real_escape_string($conn, trim($_POST['search']));
    $query = "SELECT * FROM patient WHERE name LIKE '%$search%' OR no_ic LIKE '%$search%'";
} else {
    $query = "SELECT * FROM patient";
}
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $patients[] = $row;
    }
} else {
    echo "<script>alert('Error fetching patient data: " . mysqli_error($conn) . "');</script>";
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Management</title>
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
        }

        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        input[type="text"] {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: left;
        }

        table th {
            background-color: #f4f4f4;
        }

        tr:hover {
            background-color: #f0f8ff;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .status-complete {
            color: green;
            font-weight: bold;
        }

        .status-incomplete {
            color: orange;
            font-weight: bold;
        }
    </style>
</head>
<body>
<?php include 'header_staff.php'; ?>

<div class="main-container">
    <h1>Counter Staff | Patient Management</h1>
    <?php include 'breadcrumb.php'; ?>

    <form method="POST" class="search-container">
        <input type="text" name="search" placeholder="Search by name or IC...">
        <button type="submit" name="searchPatient">Search</button>
        <button type="button" onclick="window.location.href='staff_add_patient.php'">Add Patient</button>
    </form>

    <?php if (!empty($patients)) : ?>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Patient Name</th>
                <th>No. IC</th>
                <th>Date of Birth</th>
                <th>State</th>
                <th>Gender</th>
                <th>Marital Status</th>
                <th>Occupation</th>
                <th>Profile Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($patients as $index => $patient) : ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($patient['name']) ?></td>
                    <td><?= htmlspecialchars($patient['no_ic']) ?></td>
                    <td><?= htmlspecialchars($patient['dob']) ?></td>
                    <td><?= htmlspecialchars($patient['state']) ?></td>
                    <td><?= htmlspecialchars($patient['gender']) ?></td>
                    <td><?= htmlspecialchars($patient['marital_status']) ?></td>
                    <td><?= htmlspecialchars($patient['occupation']) ?></td>
                    <td>
                        <?php if ($patient['profile_completed'] == 1): ?>
                            <span class="status-complete">✅ Completed</span>
                        <?php else: ?>
                            <span class="status-incomplete">⚠ Incomplete</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($patient['profile_completed'] == 1): ?>
                            <a href="staff_patient_detail.php?id=<?= $patient['patient_id'] ?>">View</a>
                        <?php else: ?>
                            <a href="staff_complete_profile.php?id=<?= $patient['patient_id'] ?>" style="color:orange; font-weight:bold;">Complete Profile</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>No patients found.</p>
    <?php endif; ?>
</div>

</body>
</html>

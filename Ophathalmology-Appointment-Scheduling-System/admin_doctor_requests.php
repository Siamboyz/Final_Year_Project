<?php
session_start();
include 'connection_database.php';

// ✅ Redirect if not admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

// ✅ Handle approve or reject actions
if (isset($_GET['action']) && isset($_GET['email'])) {
    $email = $_GET['email'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        mysqli_query($conn, "UPDATE doctor SET status = 'active' WHERE email = '$email'");
        mysqli_query($conn, "UPDATE doctor_unblock_requests SET status = 'approved' WHERE doctor_email = '$email'");
    } elseif ($action === 'reject') {
        mysqli_query($conn, "UPDATE doctor_unblock_requests SET status = 'rejected' WHERE doctor_email = '$email'");
    }

    header("Location: admin_doctor_requests.php");
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM doctor_unblock_requests WHERE status = 'pending'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Reactivation Requests</title>
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
            color: #888;
            text-align: right;
        }

        h1 {
            font-size: 1.8rem;
            color: #0275d8;
            margin-bottom: 10px; /* Adjusted margin */
            border-bottom: 1px solid #ecf0f1; /* Subtle separator */
            padding-bottom: 15px;
        }

        h1 {
            font-size: 1.8rem;
            color: #007bff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 16px 20px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background-color: #0275d8;
            color: #fff;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f0f8ff;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            margin: 0 4px;
        }

        .approve {
            background-color: #28a745;
        }

        .approve:hover {
            background-color: #218838;
        }

        .reject {
            background-color: #dc3545;
        }

        .reject:hover {
            background-color: #c82333;
        }

        .no-requests {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<div class="main-container">
    <h1>🩺 Admin | Doctor Account Reactivation Requests</h1>
    <div class="breadcrumb">
        <?php include 'breadcrumb.php'; ?>
    </div>
    <br>
    <?php if (mysqli_num_rows($result) > 0): ?>
        <table>
            <thead>
            <tr>
                <th>No</th>
                <th>Doctor Email</th>
                <th>Request Date</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $i = 1;
            while ($row = mysqli_fetch_assoc($result)):
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['doctor_email']) ?></td>
                    <td><?= $row['request_date'] ?></td>
                    <td>
                        <a class="btn approve" href="?action=approve&email=<?= urlencode($row['doctor_email']) ?>">Approve</a>
                        <a class="btn reject" href="?action=reject&email=<?= urlencode($row['doctor_email']) ?>">Reject</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-requests">
            <p>There are no pending doctor reactivation requests at the moment.</p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>

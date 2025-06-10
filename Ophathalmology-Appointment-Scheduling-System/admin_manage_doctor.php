<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

// Handle search and filter
$searchTerm = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

$query = "
    SELECT d.doctor_id, d.name, d.email, d.specialization, d.status, r.room_name
    FROM doctor d
    LEFT JOIN room r ON d.room_id = r.room_id 
    WHERE 1=1
";

if (!empty($searchTerm)) {
    $query .= " AND d.name LIKE '%$searchTerm%'";
}

if (!empty($statusFilter) && in_array($statusFilter, ['active', 'inactive'])) {
    $query .= " AND d.status = '$statusFilter'";
}

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Doctors</title>
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
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        .search-container input,
        .search-container select {
            padding: 10px 15px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            min-width: 250px;
        }

        .search-button {
            background-color: #28a745;
            color: white;
            padding: 10px 18px;
            font-size: 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .search-button:hover {
            background-color: #218838;
        }

        .add-doctor-button {
            background-color: #007bff;
            color: white;
            padding: 10px 18px;
            font-size: 14px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .add-doctor-button:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            font-size: 15px;
            background-color: #fff;
        }

        table th, table td {
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            text-align: left;
            vertical-align: middle;
        }

        table td {
            vertical-align: middle;
            box-sizing: border-box;
            min-height: 65px; /* ✅ Ensures height consistency */
        }

        table th {
            background-color: #f1f3f5;
            color: #333;
        }

        table tbody tr:hover {
            background-color: #f9fcff;
        }

        .action-icons {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }

        .edit-icon {
            color: #007bff;
            font-size: 16px;
        }

        .suspend-icon {
            color: #fd7e14;
            font-size: 16px;
        }

        .result-info {
            margin-bottom: 20px;
            font-size: 14px;
            color: #555;
        }

        @media (max-width: 768px) {
            .search-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-container input,
            .search-container select {
                width: 100%;
            }

            .action-icons {
                gap: 10px;
            }
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<div class="main-container">
    <h1><i class="fas fa-user-cog"></i> Admin | Manage Doctors</h1>
    <?php include 'breadcrumb.php'; ?>
    <br>

    <form method="GET" class="search-container">
        <!-- Left side: Filter dropdown -->
        <div style="flex: 1;">
            <select name="status">
                <option value="">All Status</option>
                <option value="active" <?= $statusFilter == 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $statusFilter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>

        <!-- Right side: Search bar and button -->
        <div style="display: flex; gap: 10px; flex: 1; justify-content: flex-end;">
            <input type="text" name="search" placeholder="Search by doctor name..." value="<?= htmlspecialchars($searchTerm) ?>">
            <button type="submit" class="search-button"><i class="fas fa-search"></i> Search</button>
            <a href="admin_add_doctor.php" class="add-doctor-button"><i class="fas fa-plus"></i> Add Doctor</a>
        </div>
    </form>


    <?php if (!empty($searchTerm) || !empty($statusFilter)) : ?>
        <div class="result-info">
            Showing results for:
            <?= !empty($searchTerm) ? "<strong>Name:</strong> " . htmlspecialchars($searchTerm) : '' ?>
            <?= !empty($statusFilter) ? " | <strong>Status:</strong> " . ucfirst($statusFilter) : '' ?>
        </div>
    <?php endif; ?>

    <table id="doctorTable">
        <thead>
        <tr>
            <th>#</th>
            <th>Specialization</th>
            <th>Doctor Info</th>
            <th>Room</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $index = 1;
        if (mysqli_num_rows($result) > 0) {
            while ($doctor = mysqli_fetch_assoc($result)) {
                $specialization = !empty($doctor['specialization']) ? $doctor['specialization'] : '<em style="color:gray;">Not Set</em>';
                $room = !empty($doctor['room_name']) ? $doctor['room_name'] : '<em style="color:gray;">No Room</em>';
                $status = $doctor['status'] === 'active'
                    ? '<span style="color:green;font-weight:500;">Active</span>'
                    : '<span style="color:red;font-weight:500;">Inactive</span>';

                echo "<tr>
                    <td>{$index}.</td>
                    <td>{$specialization}</td>
                    <td><strong>{$doctor['name']}</strong><br><small style='color:#777;'>{$doctor['email']}</small></td>
                    <td>{$room}</td>
                    <td>{$status}</td>
                    <td class='action-icons'>
                        <a href='admin_edit_doctor.php?id={$doctor['doctor_id']}' class='edit-icon' title='Edit'><i class='fas fa-edit'></i></a>
                        <a href='#' class='suspend-icon' onclick='suspendDoctor({$doctor['doctor_id']})' title='Suspend'><i class='fas fa-user-slash'></i></a>
                    </td>
                </tr>";
                $index++;
            }
        } else {
            echo "<tr><td colspan='6' style='text-align:center; color:#888;'>No doctors found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<script>
    function suspendDoctor(doctorId) {
        if (confirm("Are you sure you want to suspend this doctor?")) {
            window.location.href = `admin_suspend_doctor.php?id=${doctorId}`;
        }
    }
</script>
</body>
</html>

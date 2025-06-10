<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

// Handle search
$searchTerm = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$query = "SELECT * FROM counter_staff";
if (!empty($searchTerm)) {
    $query .= " WHERE name LIKE '%$searchTerm%'";
}
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Counter Staff</title>
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
            margin-bottom: 20px;
            gap: 10px;
        }

        .search-container input {
            flex: 1;
            padding: 10px 15px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .search-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
        }

        .search-button:hover {
            background-color: #218838;
        }

        .add-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 14px;
            text-decoration: none;
        }

        .add-button:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-top: 20px;
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

        table td {
            vertical-align: middle;
            box-sizing: border-box;
            min-height: 65px; /* ✅ Ensures height consistency */
        }

        table tbody tr:hover {
            background-color: #f9fcff;
        }

        .action-icons {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            height: 100%;
            box-sizing: border-box;
        }

        table td:last-child {
            vertical-align: middle;
        }

        .edit-icon {
            color: #007bff;
            font-size: 16px;
        }

        .delete-icon {
            color: #dc3545;
            font-size: 16px;
        }

        .action-icons a i {
            vertical-align: middle;
        }

        @media (max-width: 768px) {
            .search-container {
                flex-direction: column;
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
    <h1><i class="fas fa-user-cog"></i> Admin | Manage Counter Staff</h1>
    <?php include 'breadcrumb.php'; ?>
    <br>
    <form method="GET" class="search-container">
        <input type="text" name="search" placeholder="Search by staff name..." value="<?= htmlspecialchars($searchTerm) ?>">
        <button type="submit" class="search-button"><i class="fas fa-search"></i> Search</button>
        <a href="admin_add_staff.php" class="add-button"><i class="fas fa-plus-circle"></i> Add Staff</a>
    </form>

    <table>
        <thead>
        <tr>
            <th style="width: 40px;">#</th>
            <th>Staff Name</th>
            <th>Email</th>
            <th style="width: 100px;">Action</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $index = 1;
        if (mysqli_num_rows($result) > 0) {
            while ($staff = mysqli_fetch_assoc($result)) {
                echo "<tr>
                    <td>{$index}</td>
                    <td>" . htmlspecialchars($staff['name']) . "</td>
                    <td>" . htmlspecialchars($staff['email']) . "</td>
                    <td class='action-icons'>
                        <a href='admin_edit_staff.php?id={$staff['staff_id']}' class='edit-icon' title='Edit'><i class='fas fa-edit'></i></a>
                        <a href='admin_delete_staff.php?id={$staff['staff_id']}' class='delete-icon' title='Delete' onclick=\"return confirm('Are you sure to delete this staff?')\"><i class='fas fa-trash'></i></a>
                    </td>
                </tr>";
                $index++;
            }
        } else {
            echo "<tr><td colspan='4' style='text-align:center; color:#888;'>No staff found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>

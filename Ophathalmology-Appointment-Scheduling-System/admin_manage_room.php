<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

$searchTerm = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$query = "SELECT * FROM room ORDER BY room_name ASC";

if (!empty($searchTerm)) {
    $query .= " WHERE room_name LIKE '%$searchTerm%'";
}

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Rooms | OASS</title>
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

        .top-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .top-actions input[type="text"] {
            flex: 1;
            padding: 10px 15px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .top-actions button, .top-actions a {
            padding: 10px 18px;
            font-size: 14px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            color: white;
            text-decoration: none;
        }

        .search-btn {
            background-color: #28a745;
        }

        .search-btn:hover {
            background-color: #218838;
        }

        .add-btn {
            background-color: #007bff;
        }

        .add-btn:hover {
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
        }

        .action-icons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .edit-icon {
            color: #007bff;
        }

        .delete-icon {
            color: #dc3545;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #777;
        }

        @media (max-width: 768px) {
            .top-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<div class="main-container">
    <h1><i class="fas fa-door-open"></i> Admin | Manage Rooms</h1>
    <?php include 'breadcrumb.php'; ?>
    <br>

    <form class="top-actions" method="GET">
        <input type="text" name="search" placeholder="Search by room name..." value="<?= htmlspecialchars($searchTerm) ?>">
        <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
        <a href="admin_add_room.php" class="add-btn"><i class="fas fa-plus-circle"></i> Add Room</a>
    </form>

    <table>
        <thead>
        <tr>
            <th style="width: 50px;">#</th>
            <th>Room Name</th>
            <th style="width: 120px;">Action</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $index = 1;
        if (mysqli_num_rows($result) > 0) {
            while ($room = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>{$index}</td>";
                echo "<td>" . htmlspecialchars($room['room_name']) . "</td>";
                echo "<td class='action-icons'>
                        <a href='admin_edit_room.php?id={$room['room_id']}' class='edit-icon' title='Edit'><i class='fas fa-edit'></i></a>
                        <a href='admin_delete_room.php?id={$room['room_id']}' class='delete-icon' title='Delete' onclick=\"return confirm('Are you sure you want to delete this room?')\"><i class='fas fa-trash'></i></a>
                      </td>";
                echo "</tr>";
                $index++;
            }
        } else {
            echo "<tr><td colspan='3' class='no-data'>No rooms found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>

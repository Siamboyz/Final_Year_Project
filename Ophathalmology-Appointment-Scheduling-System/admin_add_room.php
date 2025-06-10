<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addRoom'])) {
    $room_name = mysqli_real_escape_string($conn, $_POST['room_name']);

    if (!empty($room_name)) {
        $query = "INSERT INTO room (room_name) VALUES ('$room_name')";
        if (mysqli_query($conn, $query)) {
            echo "<script>alert('✅ Room added successfully!'); window.location.href='admin_manage_room.php';</script>";
        } else {
            echo "<script>alert('❌ Failed to add room.');</script>";
        }
    } else {
        echo "<script>alert('❗ Please enter a room name.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Room | OASS</title>
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

        h2 {
            color: #005A9C;
            margin-bottom: 25px;
            text-align: center;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 15px;
        }

        button {
            display: block;
            margin: 0 auto;
            width: 40%;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
            text-decoration: none;
        }

        .back-link i {
            margin-right: 6px;
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<div class="main-container">
    <h2><i class="fas fa-door-open"></i> Add New Room</h2>
    <?php include 'breadcrumb.php'; ?>
    <br>

    <form method="POST" action="admin_add_room.php">
        <label for="room_name">Room Name</label>
        <input type="text" name="room_name" id="room_name" placeholder="e.g. B101, Consultation Room" required>

        <button type="submit" name="addRoom"><i class="fas fa-plus-circle"></i> Add Room</button>
    </form>

    <a href="admin_manage_room.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Manage Rooms</a>
</div>
</body>
</html>

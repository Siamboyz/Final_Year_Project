<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

// Fetch room options
$rooms = mysqli_query($conn, "
    SELECT room_id, room_name 
    FROM room
    WHERE room_id NOT IN (SELECT room_id FROM doctor)
");

// Generate unique 6-digit doctor_id
function generateUUID() {
    return str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addDoctor'])) {
    $doctor_id = generateUUID();
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    $room_id = mysqli_real_escape_string($conn, $_POST['room_id']);

    $query = "INSERT INTO doctor (doctor_id, name, email, password, specialization, room_id)
              VALUES ('$doctor_id', '$name', '$email', '$password', '$specialization', '$room_id')";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('✅ Doctor added successfully!'); window.location.href='admin_manage_doctor.php';</script>";
    } else {
        echo "<script>alert('❌ Failed to add doctor.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Doctor | OASS</title>
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
            text-align: center;
            color: #005A9C;
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
            font-size: 15px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        input:focus, select:focus {
            border-color: #007bff;
            outline: none;
        }

        button {
            width: 20%;
            background-color: #007bff;
            border: none;
            color: white;
            padding: 14px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
            margin: 10px auto 0;
            display: block;
        }

        button:hover {
            background-color: #0056b3;
        }

        .back-link {
            display: inline-block;
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: #555;
            text-decoration: none;
        }

        .back-link i {
            margin-right: 6px; /* ✅ adds space between icon and text */
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .main-container {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<div class="main-container">
    <h2><i class="fas fa-user-md"></i> Add New Doctor</h2>
    <?php include 'breadcrumb.php'; ?>
    <br>
    <form method="POST" action="admin_add_doctor.php">
        <label for="name">Doctor Name</label>
        <input type="text" name="name" id="name" required>

        <label for="email">Doctor Email</label>
        <input type="email" name="email" id="email" required>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <label for="specialization">Specialization</label>
        <input type="text" name="specialization" id="specialization" required>

        <label for="room_id">Select Room</label>
        <select name="room_id" id="room_id" required>
            <option value="" disabled selected>Select a room</option>
            <?php while ($room = mysqli_fetch_assoc($rooms)) : ?>
                <option value="<?= $room['room_id'] ?>"><?= $room['room_name'] ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit" name="addDoctor"><i class="fas fa-plus-circle"></i> Add Doctor</button>
    </form>
    <a href="admin_manage_doctor.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Manage Doctors</a>
</div>
</body>
</html>

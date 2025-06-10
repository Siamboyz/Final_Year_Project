<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: homepage.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];

// 2️⃣ Fetch current doctor info
function fetchDoctorById($conn, $doctor_id) {
    $query = "SELECT * FROM doctor WHERE doctor_id = '$doctor_id'";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// 3️⃣ Fetch all rooms
function fetchAllRooms($conn) {
    $query = "SELECT * FROM room";
    $result = mysqli_query($conn, $query);
    $rooms = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rooms[] = $row;
    }
    return $rooms;
}

$doctor = fetchDoctorById($conn, $doctor_id);
$rooms = fetchAllRooms($conn);

if (!$doctor) {
    echo "Doctor not found.";
    exit();
}

// 4️⃣ Handle form submission with partial update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update'])) {
    $updates = [];

    if (!empty($_POST['name']) && $_POST['name'] !== $doctor['name']) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $updates[] = "name='$name'";
    }

    if (!empty($_POST['email']) && $_POST['email'] !== $doctor['email']) {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $updates[] = "email='$email'";
    }

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $updates[] = "password='$password'";
    }

    if (!empty($_POST['specialization']) && $_POST['specialization'] !== $doctor['specialization']) {
        $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
        $updates[] = "specialization='$specialization'";
    }

    if (!empty($_POST['room_id']) && $_POST['room_id'] != $doctor['room_id']) {
        $room_id = intval($_POST['room_id']);
        $updates[] = "room_id='$room_id'";
    }

    if (!empty($updates)) {
        $updateQuery = "UPDATE doctor SET " . implode(", ", $updates) . " WHERE doctor_id='$doctor_id'";
        if (mysqli_query($conn, $updateQuery)) {
            echo "<script>alert('✅ Profile updated successfully!'); window.location='doctor_profile.php';</script>";
            exit();
        } else {
            echo "<script>alert('❌ Failed to update. Try again.');</script>";
        }
    } else {
        echo "<script>alert('⚠️ No changes made.');</script>";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Doctor | My Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .profile-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .profile-container {
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

        label {
        display: block;
        margin-top: 15px;
        font-weight: 500;
        }

        input, select {
        width: 100%;
        padding: 10px;
        margin-top: 5px;
        border: 1px solid #ccc;
        border-radius: 6px;
            box-sizing: border-box;
        }

        button {
            background: #007BFF;
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 6px;
            margin-top: 20px;
            width: 20%;
            font-size: 16px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<?php include 'header_doc.php'; ?>
<div class="profile-container">
    <h1>Doctor | My Profile</h1>
    <?php include 'breadcrumb.php'; ?>
    <form method="POST" action="doctor_profile.php">
        <label>Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($doctor['name']) ?>">

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($doctor['email']) ?>">

        <label>Password (Leave blank to keep current):</label>
        <input type="password" name="password" placeholder="New password (optional)">

        <label>Specialization:</label>
        <input type="text" name="specialization" value="<?= htmlspecialchars($doctor['specialization']) ?>">

        <label>Select Room:</label>
        <select name="room_id">
            <option value="">-- Select Room --</option>
            <?php foreach ($rooms as $room): ?>
                <option value="<?= $room['room_id'] ?>" <?= $room['room_id'] == $doctor['room_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($room['room_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <center><button type="submit" name="update">Update Profile</button></center>
    </form>
</div>
</body>
</html>
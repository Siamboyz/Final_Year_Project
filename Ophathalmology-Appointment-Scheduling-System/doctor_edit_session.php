<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: homepage.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];
$message = '';

// Step 1: Get session ID from URL
if (!isset($_GET['id'])) {
    die("Session ID is missing.");
}

$session_id = $_GET['id'];

// Step 2: Fetch session data
$query = "SELECT * FROM session WHERE session_id = '$session_id' AND doctor_id = '$doctor_id'";
$result = mysqli_query($conn, $query);
if (!$result || mysqli_num_rows($result) == 0) {
    die("Session not found or unauthorized access.");
}

$row = mysqli_fetch_assoc($result);

// Step 3: Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s_date = $_POST['s_date'];
    $s_starttime = $_POST['s_starttime'];
    $s_endtime = $_POST['s_endtime'];
    $s_status = $_POST['s_status'];

    if ($s_date && $s_starttime && $s_endtime && $s_status) {
        $update = "UPDATE session 
                   SET s_date = '$s_date', s_starttime = '$s_starttime', s_endtime = '$s_endtime', s_status = '$s_status' 
                   WHERE session_id = '$session_id' AND doctor_id = '$doctor_id'";
        $exec = mysqli_query($conn, $update);

        if ($exec) {
            $message = "Session updated successfully.";
        } else {
            $message = "Failed to update session: " . mysqli_error($conn);
        }
    } else {
        $message = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Availability</title>
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
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            max-width: 100%;
            margin-left: calc(100% - 80%);
        }

        h2 {
            font-size: 1.6rem;
            color: #007bff;
            text-align: center;
            margin-bottom: 10px;
        }

        .breadcrumb {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
            float: right;
            text-align: right;
        }

        form {
            max-width: 600px;
            margin: auto;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 14px;
            box-sizing: border-box;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 10px;
            margin-top: 20px;
            border: none;
            width: 100%;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .message {
            margin-top: 15px;
            text-align: center;
            color: green;
            font-weight: bold;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<?php include 'header_doc.php'; ?>

<div class="main-container">
    <h2>Edit Doctor Availability</h2>
    <?php include 'breadcrumb.php'; ?>

    <?php if ($message): ?>
        <script>
            alert("<?= addslashes($message) ?>");
            window.location.href = "doctor_session.php";
        </script>
    <?php endif; ?>

    <br>
    <br>
    <form method="POST" action="">
        <label for="s_date">Date</label>
        <input type="date" name="s_date" id="s_date" value="<?= $row['s_date'] ?>" required>

        <label for="s_starttime">Start Time</label>
        <input type="time" name="s_starttime" id="s_starttime" value="<?= $row['s_starttime'] ?>" required>

        <label for="s_endtime">End Time</label>
        <input type="time" name="s_endtime" id="s_endtime" value="<?= $row['s_endtime'] ?>" required>

        <label for="s_status">Status</label>
        <select name="s_status" id="s_status" required>
            <option value="">-- Select Status --</option>
            <option value="Available" <?= $row['s_status'] == 'Available' ? 'selected' : '' ?>>Available</option>
            <option value="Unavailable" <?= $row['s_status'] == 'Unavailable' ? 'selected' : '' ?>>Unavailable</option>
            <option value="On Leave" <?= $row['s_status'] == 'On Leave' ? 'selected' : '' ?>>On Leave</option>
        </select>

        <button type="submit">Update Session</button>
    </form>

    <div class="back-link">
        <a href="doctor_session.php">← Back to Session List</a>
    </div>
</div>
</body>
</html>

<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: homepage.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s_date = $_POST['s_date'];
    $s_starttime = $_POST['s_starttime'];
    $s_endtime = $_POST['s_endtime'];
    $s_status = $_POST['s_status'];

    if ($s_date && $s_starttime && $s_endtime && $s_status) {
        // Check if a session already exists for this doctor on that date
        $checkQuery = "SELECT * FROM session WHERE doctor_id = '$doctor_id' AND s_date = '$s_date'";
        $checkResult = mysqli_query($conn, $checkQuery);

        if (mysqli_num_rows($checkResult) > 0) {
            $message = "You already have a session for this date!";
        } else {
            // Insert new session
            $query = "INSERT INTO session (doctor_id, s_date, s_starttime, s_endtime, s_status) 
                  VALUES ('$doctor_id', '$s_date', '$s_starttime', '$s_endtime', '$s_status')";
            $insert = mysqli_query($conn, $query);

            if ($insert) {
                $message = "Session added successfully.";
            } else {
                $message = "Failed to add session: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Availability</title>
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
            box-sizing: border-box; /* ✅ ensures padding doesn't overflow */
        }


        button {
            background-color: #28a745;
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
            background-color: #218838;
        }

        .message {
            margin-top: 15px;
            text-align: center;
            color: red;
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
    <h2>Add Doctor Availability</h2>
    <?php include 'breadcrumb.php'; ?>
    <br>
    <?php if ($message): ?>
        <script>
            alert("<?= addslashes($message) ?>");
            window.location.href = "doctor_session.php";
        </script>
    <?php endif; ?>


    <form method="POST" action="">
        <label for="s_date">Date</label>
        <input type="date" name="s_date" id="s_date" required>

        <label for="s_starttime">Start Time</label>
        <input type="time" name="s_starttime" id="s_starttime" required>

        <label for="s_endtime">End Time</label>
        <input type="time" name="s_endtime" id="s_endtime" required>

        <label for="s_status">Status</label>
        <select name="s_status" id="s_status" required>
            <option value="">-- Select Status --</option>
            <option value="Available">Available</option>
            <option value="Unavailable">Unavailable</option>
            <option value="On Leave">On Leave</option>
        </select>

        <button type="submit">Add Session</button>
    </form>

    <div class="back-link">
        <a href="doctor_session.php">← Back to Session List</a>
    </div>
</div>
</body>
</html>

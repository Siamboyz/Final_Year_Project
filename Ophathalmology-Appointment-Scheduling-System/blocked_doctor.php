<?php
session_start();
$doctor_email = $_SESSION['temp_doctor_email'] ?? ''; // Store this during failed login
$submitted = false;

include "connection_database.php";

// Handle request form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_unblock'])) {
    $check = mysqli_query($conn, "SELECT * FROM doctor_unblock_requests WHERE doctor_email = '$doctor_email' AND status = 'pending'");

    if (mysqli_num_rows($check) == 0) {
        $stmt = $conn->prepare("INSERT INTO doctor_unblock_requests (doctor_email) VALUES (?)");
        $stmt->bind_param("s", $doctor_email);
        $stmt->execute();
        $submitted = true;
    } else {
        $submitted = 'duplicate';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Blocked</title>
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc, #cfdef3);
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .card {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 450px;
            width: 100%;
            animation: fadeIn 0.5s ease;
        }

        h2 {
            color: #d9534f;
        }

        p {
            color: #555;
            font-size: 16px;
        }

        form {
            margin-top: 20px;
        }

        button {
            background-color: #0275d8;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #025aa5;
        }

        a {
            display: block;
            margin-top: 20px;
            text-decoration: none;
            color: #333;
        }

        .success {
            color: green;
            margin-top: 15px;
        }

        .info {
            color: orange;
            margin-top: 15px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="card">
    <h2>Access Denied</h2>
    <p>Your doctor account is currently <strong>inactive</strong>.</p>
    <p>Please contact the system administrator or submit a request below to reactivate your account.</p>

    <?php if ($submitted === true): ?>
        <p class="success">✅ Your request has been sent to the admin.</p>
    <?php elseif ($submitted === 'duplicate'): ?>
        <p class="info">ℹ️ You have already submitted a request. Please wait for admin approval.</p>
    <?php else: ?>
        <form method="POST">
            <button type="submit" name="request_unblock">Request Reactivation</button>
        </form>
    <?php endif; ?>

    <a href="homepage.php">← Back to Login</a>
</div>
</body>
</html>

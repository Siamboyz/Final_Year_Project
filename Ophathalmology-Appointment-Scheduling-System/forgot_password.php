<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Include the database connection
    include 'connection_database.php';

    // Check if the email exists in any of the 3 tables: counter_staff, doctor, admin
    $stmt = $conn->prepare("SELECT * FROM counter_staff WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $userId = $user['user_id'];
        $userRole = 'counter_staff';
    } else {
        $stmt = $conn->prepare("SELECT * FROM doctor WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $userId = $user['doctor_id'];
            $userRole = 'doctor';
        } else {
            $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $userId = $user['admin_id'];
                $userRole = 'admin';
            } else {
                // If email not found, show an alert and redirect to the same page
                echo '<script type="text/javascript">
                        alert("Email not found.");
                        window.location.href = "forgot_password.php"; // Redirect to forgot password page
                      </script>';
                exit(); // Stop further execution
            }
        }
    }

    // Generate a unique reset token
    $token = bin2hex(random_bytes(50));
    $expiry = time() + 3600;  // Token expires in 1 hour

    // Save the token and expiry time in the corresponding table
    $updateStmt = $conn->prepare("UPDATE $userRole SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
    $updateStmt->bind_param("sis", $token, $expiry, $email);
    $updateStmt->execute();

    // Redirect the user to the reset password page with the token
    header("Location: reset_password.php?token=" . $token);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        /* General body styling */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fb;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #0b3d91;  /* OASS theme color */
        }

        .form-group {
            margin-bottom: 20px;
        }

        input[type="email"] {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        input[type="email"]:focus {
            outline: none;
            border-color: #0b3d91;  /* OASS theme color */
            box-shadow: 0 0 8px rgba(11, 61, 145, 0.2);
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #0b3d91; /* OASS theme color */
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0a3169; /* Darker shade of OASS theme color */
        }

        .message {
            text-align: center;
            margin-top: 20px;
            color: #555;
        }

        .message a {
            color: #0b3d91;
            text-decoration: none;
            font-weight: bold;
        }

        .message a:hover {
            text-decoration: underline;
        }

        .error-message {
            color: red;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Forgot Password</h1>
    </div>

    <?php if (isset($errorMessage)): ?>
        <div class="error-message"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <form action="forgot_password.php" method="POST">
        <div class="form-group">
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>
        <button type="submit">Submit</button>
    </form>

    <div class="message">
        <p>Remembered your password? <a href="homepage.php">Login here</a></p>
    </div>
</div>

</body>
</html>

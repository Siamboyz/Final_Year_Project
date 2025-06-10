<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $newPassword = $_POST['new_password'];
    $token = $_GET['token']; // Get the token from the URL

    // Include the database connection
    include 'connection_database.php';

    // Validate the token and ensure it is not expired
    // First, check the token in counter_staff table
    $stmt = $conn->prepare("SELECT * FROM counter_staff WHERE reset_token = ? AND reset_token_expiry > ?");
    $stmt->bind_param("si", $token, $currentTime);
    $currentTime = time(); // Ensure time() is called separately
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $userId = $user['staff_id'];  // Use the correct user ID column name
        $userRole = 'counter_staff';
    } else {
        // If not found in counter_staff, check doctor table
        $stmt = $conn->prepare("SELECT * FROM doctor WHERE reset_token = ? AND reset_token_expiry > ?");
        $stmt->bind_param("si", $token, $currentTime);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $userId = $user['doctor_id'];  // Correct column for doctor ID
            $userRole = 'doctor';
        } else {
            // If not found in doctor, check admin table
            $stmt = $conn->prepare("SELECT * FROM admin WHERE reset_token = ? AND reset_token_expiry > ?");
            $stmt->bind_param("si", $token, $currentTime);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $userId = $user['admin_id'];  // Correct column for admin ID
                $userRole = 'admin';
            } else {
                // If token is invalid or expired
                echo "Invalid or expired token.";
                exit();
            }
        }
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the password and reset the token and expiry time
    $updateStmt = $conn->prepare("UPDATE $userRole SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE {$userId} = ?");
    $updateStmt->bind_param("si", $hashedPassword, $userId);
    $updateStmt->execute();

    // Redirect to homepage with an alert
    echo '<script type="text/javascript">
            alert("Your password has been reset successfully.");
            window.location.href = "homepage.php"; // Redirect to homepage.php after alert
          </script>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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

        input[type="password"] {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        input[type="password"]:focus {
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
        <h1>Reset Your Password</h1>
    </div>

    <?php if (isset($errorMessage)): ?>
        <div class="error-message"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <form action="reset_password.php?token=<?php echo $_GET['token']; ?>" method="POST">
        <div class="form-group">
            <input type="password" name="new_password" placeholder="Enter your new password" required>
        </div>
        <button type="submit">Reset Password</button>
    </form>

    <div class="message">
        <p>Remembered your password? <a href="homepage.php">Login here</a></p>
    </div>
</div>

</body>
</html>

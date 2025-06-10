<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counter_staff') {
    header("Location: index.php");
    exit();
}

$staff_id = $_SESSION['user_id'];
$message = "";

// Fetch current staff data
$sql = "SELECT * FROM counter_staff WHERE staff_id = '$staff_id'";
$result = mysqli_query($conn, $sql);
$staff = mysqli_fetch_assoc($result);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $fields = [];

    // Only update name if it's not empty
    if (!empty($_POST['name'])) {
        $new_name = mysqli_real_escape_string($conn, $_POST['name']);
        $fields[] = "name = '$new_name'";
        $staff['name'] = $new_name; // update display variable
    }

    // Only update email if it's not empty
    if (!empty($_POST['email'])) {
        $new_email = mysqli_real_escape_string($conn, $_POST['email']);
        $fields[] = "email = '$new_email'";
        $staff['email'] = $new_email;
    }

    // Only update password if it's not empty
    if (!empty($_POST['password'])) {
        $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $fields[] = "password = '$new_password'";
    }

    if (!empty($fields)) {
        $update_sql = "UPDATE counter_staff SET " . implode(", ", $fields) . " WHERE staff_id = '$staff_id'";
        if (mysqli_query($conn, $update_sql)) {
            if (!empty($new_name)) {
                $_SESSION['name'] = $new_name; // Or whatever you use in your header
            }

            echo "<script>alert('✅ Profile updated successfully!'); window.location.href='staff_profile.php';</script>";
            exit();
        } else {
            echo "<script>alert('❌ Failed to update profile. Please try again.'); window.location.href='staff_profile.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('⚠️ No changes made.'); window.location.href='staff_profile.php';</script>";
        exit();
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
    <title>My Profile - Staff</title>
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
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
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
        .message {
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
            color: green;
        }

        .breadcrumb {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
            text-align: right;
        }
    </style>
</head>
<body>
<?php include 'header_staff.php'; ?>

<div class="profile-container">
    <h1>Counter Staff | My Profile</h1>
    <?php include 'breadcrumb.php'; ?>
    <br>

    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <label>Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($staff['name']) ?>" >

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($staff['email']) ?>" >

        <label>New Password:</label>
        <input type="password" name="password" placeholder="Enter new password" >

        <center><button type="submit" name="update">Update Profile</button></center>
    </form>
</div>
</body>
</html>
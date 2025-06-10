<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

// Generate UUID (6-digit)
function generateUUID() {
    return str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addstaff'])) {
    $staff_id = generateUUID();
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $query = "INSERT INTO counter_staff (staff_id, name, email, password) 
              VALUES ('$staff_id', '$name', '$email', '$password')";

    if (mysqli_query($conn, $query)) {
        echo "<script>alert('✅ Staff added successfully!'); window.location.href='admin_manage_staff.php';</script>";
    } else {
        echo "<script>alert('❌ Failed to add staff.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Staff | OASS</title>
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

        input {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
            font-size: 15px;
        }

        input:focus {
            border-color: #007bff;
            outline: none;
        }

        button {
            width: 100%;
            background-color: #007bff;
            border: none;
            color: white;
            padding: 14px;
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
            font-size: 14px;
            color: #666;
            text-decoration: none;
        }

        .back-link i {
            margin-right: 5px;
        }

        @media (max-width: 600px) {
            .container {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<div class="main-container">
    <h2><i class="fas fa-user-plus"></i> Add Counter Staff</h2>
    <?php include 'breadcrumb.php'; ?>
    <br>
    <form method="POST" action="admin_add_staff.php">
        <label for="name">Staff Name</label>
        <input type="text" name="name" id="name" required>

        <label for="email">Staff Email</label>
        <input type="email" name="email" id="email" required>

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <button type="submit" name="addstaff"><i class="fas fa-plus-circle"></i> Add Staff</button>
    </form>
    <a href="admin_manage_staff.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Manage Staff</a>
</div>
</body>
</html>

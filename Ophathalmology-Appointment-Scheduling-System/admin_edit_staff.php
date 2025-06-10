<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

$staff_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

// Fetch staff info
$staff_query = mysqli_query($conn, "SELECT * FROM counter_staff WHERE staff_id = '$staff_id'");
$staff = mysqli_fetch_assoc($staff_query);

if (!$staff) {
    echo "<script>alert('❌ Staff not found.'); window.location.href='admin_manage_staff.php';</script>";
    exit();
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateStaff'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    $update_query = "UPDATE counter_staff SET name = '$name', email = '$email'";
    if ($password) {
        $update_query .= ", password = '$password'";
    }
    $update_query .= " WHERE staff_id = '$staff_id'";

    if (mysqli_query($conn, $update_query)) {
        echo "<script>alert('✅ Staff updated successfully.'); window.location.href='admin_manage_staff.php';</script>";
    } else {
        echo "<script>alert('❌ Failed to update staff.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Staff | OASS</title>
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
            box-sizing: border-box;
        }

        input:focus {
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
            .main-container {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<div class="main-container">
    <h2><i class="fas fa-user-edit"></i> Edit Counter Staff</h2>
    <?php include 'breadcrumb.php'; ?>
    <br>
    <form method="POST" action="admin_edit_staff.php?id=<?= $staff_id ?>">
    <label for="name">Staff Name</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($staff['name']) ?>" required>

        <label for="email">Staff Email</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($staff['email']) ?>" required>

        <label for="password">New Password <small>(leave blank to keep current)</small></label>
        <input type="password" name="password" id="password">

        <button type="submit" name="updateStaff"><i class="fas fa-save"></i> Save Changes</button>
    </form>
    <a href="admin_manage_staff.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Manage Staff</a>
</div>
</body>
</html>

<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counter_staff') {
    header("Location: homepage.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "<script>alert('Invalid patient ID.'); window.location.href='staff_patient.php';</script>";
    exit();
}

$patient_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch patient info
$query = "SELECT * FROM patient WHERE patient_id = '$patient_id'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<script>alert('Patient not found.'); window.location.href='staff_patient.php';</script>";
    exit();
}

$patient = mysqli_fetch_assoc($result);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['completeProfile'])) {
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);
    $marital_status = mysqli_real_escape_string($conn, $_POST['marital_status']);
    $occupation = mysqli_real_escape_string($conn, $_POST['occupation']);

    $updateQuery = "UPDATE patient 
                    SET address = '$address', 
                        dob = '$dob', 
                        gender = '$gender', 
                        state = '$state', 
                        marital_status = '$marital_status', 
                        occupation = '$occupation',
                        profile_completed = 1
                    WHERE patient_id = '$patient_id'";

    if (mysqli_query($conn, $updateQuery)) {
        echo "<script>alert('✅ Profile completed successfully.'); window.location.href='staff_patient.php';</script>";
    } else {
        echo "<script>alert('❌ Error updating profile: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete Patient Profile</title>
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
            max-width: 100%;
        }

        .breadcrumb {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
            text-align: right;
        }

        h1 {
            color: #007bff;
            margin-bottom: 25px;
            font-size: 28px;
            text-align: center;
        }

        h3 {
            color: #333;
            margin-top: 30px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        label {
            font-weight: 600;
            margin-top: 15px;
            display: block;
            color: #333;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
            box-sizing: border-box;
        }

        input[readonly] {
            background-color: #f7f7f7;
            cursor: not-allowed;
        }

        button {
            margin-top: 30px;
            padding: 14px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        .note {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-left: 5px solid #ffeeba;
            border-radius: 6px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<?php include 'header_staff.php'; ?>
<div class="main-container">
    <h1>📝 Complete Patient Profile</h1>
    <?php include 'breadcrumb.php'; ?>
    <br>

    <!-- 🔵 Patient Information (Read-only) -->
    <h3>Patient Information</h3>
    <label>Patient Name</label>
    <input type="hidden" name="patient_id" value="<?= $patient_id ?>">

    <input type="text" value="<?= htmlspecialchars($patient['name'] ?? '') ?>" readonly>

    <label>IC Number</label>
    <input type="text" value="<?= htmlspecialchars($patient['no_ic'] ?? '') ?>" readonly>

    <label>Phone Number</label>
    <input type="text" value="<?= htmlspecialchars($patient['phone_number'] ?? '') ?>" readonly>

    <label>Registered Date</label>
    <input type="text" value="<?= htmlspecialchars($patient['registered_datetime'] ?? '') ?>" readonly>

    <!-- 🟡 Complete Profile Section -->
    <h3>Complete Missing Information</h3>
    <form method="POST" action="staff_complete_profile.php?id=<?= $patient_id ?>">
        <label>Address</label>
        <textarea name="address" rows="3"><?= htmlspecialchars($patient['address'] ?? '') ?></textarea>

        <label>Date of Birth</label>
        <input type="date" name="dob" value="<?= htmlspecialchars($patient['dob'] ?? '') ?>">

        <label>Gender</label>
        <select name="gender" required>
            <option value="">-- Select Gender --</option>
            <option value="Male" <?= ($patient['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($patient['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
        </select>

        <label>State</label>
        <input type="text" name="state" value="<?= htmlspecialchars($patient['state'] ?? '') ?>">

        <label>Marital Status</label>
        <select name="marital_status" required>
            <option value="">-- Select Marital Status --</option>
            <option value="Single" <?= ($patient['marital_status'] == 'Single') ? 'selected' : '' ?>>Single</option>
            <option value="Married" <?= ($patient['marital_status'] == 'Married') ? 'selected' : '' ?>>Married</option>
            <option value="Widowed" <?= ($patient['marital_status'] == 'Widowed') ? 'selected' : '' ?>>Widowed</option>
            <option value="Divorced" <?= ($patient['marital_status'] == 'Divorced') ? 'selected' : '' ?>>Divorced</option>
        </select>

        <label>Occupation</label>
        <input type="text" name="occupation" value="<?= htmlspecialchars($patient['occupation'] ?? '') ?>">

        <button type="submit" name="completeProfile">✅ Complete Profile</button>
    </form>
</div>
</body>
</html>

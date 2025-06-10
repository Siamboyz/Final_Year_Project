<?php
session_start();

// Ensure only logged-in users can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counter_staff') {
    header("Location: homepage.php");
    exit();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Registration</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: auto;
            flex-direction: column;
        }

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
            width: 1000px;
        }

        h2 { text-align: center; }

        label { display: block; margin-top: 10px; }

        input, select, textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        button {
            background-color: #007bff;
            color: white;
            margin-top: 20px;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
<?php include 'header_staff.php'; ?>

<div class="main-container">
        <h2>Add Patient</h2>
        <form action="staff_process_add_patient.php" method="POST">
            <label for="ic">Identity Number (IC)</label>
            <input type="text" id="ic" name="ic" required>

            <label for="patient_name">Patient Name</label>
            <input type="text" id="patient_name" name="patient_name" required>

            <label for="contact_no">Contact Number</label>
            <input type="text" id="contact_no" name="contact_no" required>

            <label for="address">Address</label>
            <textarea id="address" name="address" required></textarea>

            <label for="dob">Date of Birth</label>
            <input type="date" id="dob" name="dob" required>

            <label for="state">State</label>
            <select id="state" name="state" required>
                <option value="" disabled selected>Select State</option>
                <option value="Johor">Johor</option>
                <option value="Kedah">Kedah</option>
                <option value="Kelantan">Kelantan</option>
                <option value="Melaka">Melaka</option>
                <option value="Negeri Sembilan">Negeri Sembilan</option>
                <option value="Pahang">Pahang</option>
                <option value="Penang">Penang</option>
                <option value="Perak">Perak</option>
                <option value="Perlis">Perlis</option>
                <option value="Sabah">Sabah</option>
                <option value="Sarawak">Sarawak</option>
                <option value="Selangor">Selangor</option>
                <option value="Terengganu">Terengganu</option>
                <option value="Kuala Lumpur">Kuala Lumpur</option>
                <option value="Labuan">Labuan</option>
                <option value="Putrajaya">Putrajaya</option>
            </select>

            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="" disabled selected>Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>

            <label for="marital_status">Marital Status</label>
            <select id="marital_status" name="marital_status" required>
                <option value="Single">Single</option>
                <option value="Married">Married</option>
                <option value="Divorced">Divorced</option>
                <option value="Widowed">Widowed</option>
            </select>

            <label for="occupation">Occupation</label>
            <input type="text" id="occupation" name="occupation" required>

            <button type="submit" name="addPatient">Add Patient</button>
        </form>
</div>
</body>
</html>

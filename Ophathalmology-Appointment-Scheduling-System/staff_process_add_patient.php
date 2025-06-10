<?php
include 'connection_database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addPatient'])) {
    $ic = $_POST['ic'];
    $patient_name = $_POST['patient_name'];
    $phone_number = $_POST['contact_no'];
    $address = $_POST['address'];
    $dob = $_POST['dob'];
    $state = $_POST['state'];
    $gender = $_POST['gender'];
    $marital_status = $_POST['marital_status'];
    $occupation = $_POST['occupation'];
    $registered_datetime = date('Y-m-d H:i:s');


    // SQL query to insert patient data into the database
    $sql = "INSERT INTO `patient` (`name`, `no_ic`, `dob`, `phone_number`, `address`, `state`, `gender`, `marital_status`, `occupation`, `registered_datetime`, `profile_completed`)
        VALUES ('$patient_name', '$ic', '$dob', '$phone_number', '$address', '$state', '$gender', '$marital_status', '$occupation', '$registered_datetime', '1')";


    // Execute the query and check if it is successful
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Patient added successfully!'); window.location.href='staff_patient.php';</script>";
    } else {
        echo "<script>alert('Error adding patient: " . mysqli_error($conn) . "');</script>";
    }

    // Close the database connection
    mysqli_close($conn);
}
?>

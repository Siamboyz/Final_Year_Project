<?php
$server = "localhost";
$username = "root";
$password = "root";
$db = "db_oass";

// Establish connection
$conn = mysqli_connect($server, $username, $password, $db);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Select database
$select_db = mysqli_select_db($conn, $db);

// Check database selection
if (!$select_db) {
    die("Error selecting database: " . mysqli_error($conn));
}

?>

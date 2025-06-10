<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>OASS | Doctor Panel</title>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Header Styling */
    .header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background-color: #B3E5FC; /* Light Blue */
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 30px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        border-bottom: 1px solid #e0e0e0;
        z-index: 1000;
    }

    .header .logo {
        font-size: 22px;
        font-weight: bold;
        color:rgb(1, 46, 72); /* Deep Blue */
    }

    .header .user-info {
        font-size: 16px;
        color:rgb(1, 46, 72);
        font-weight: bold;
    }

    /* Sidebar Navigation */
    .container {
        display: flex;
        width: 100%;
        margin-top: 50px; /* Creates space below header */
        flex-grow: 1; /* Ensures sidebar grows properly */
    }

    .sidebar {
        width: 20%;
        background-color: #ffffff;
        border-right: 2px solid #ddd;
        display: flex;
        flex-direction: column;
        height: 100%;
        position: fixed;
        left: 0;
    }

    .sidebar ul {
        display: flex;
        flex-direction: column;
        height: 100vh; /* full height of the sidebar */
        padding: 0;
        margin: 0;
        list-style-type: none;
    }

    .sidebar ul li {
        border-bottom: 1px solid #eee;
    }

    .sidebar ul li a {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #333;
        padding: 15px 20px;
        transition: all 0.3s ease;
        font-size: 17px;
    }

    .sidebar ul li a:hover {
        background-color: #4FC3F7; /* Moderate Blue */
        color: white;
        border-left: 4px solid #0288D1; /* Deep Blue */
        padding-left: 16px;
    }

    .sidebar ul li.active a {
        background-color: #81D4FA; /* Soft Blue */
        color: #000;
        font-weight: bold;
        border-left: 4px solid #0288D1; /* Deep Blue */
        padding-left: 16px;
    }

    .sidebar ul li a .icon {
        margin-right: 10px;
        font-size: 18px;
    }

    ul li.logout {
        margin-top: auto; /* pushes it to the bottom */
        position: fixed;
        bottom: 0;
        width: 20%;
    }

    .logout .icon {
        margin-right: 10px;
    }

</style>
</head>
<body>

<div class="header">
    <div class="logo">OASS | OPHTHALMOLOGY APPOINTMENT SCHEDULING SYSTEM</div>
    <div class="user-info">Welcome, <?php echo $_SESSION['name']; ?></div>
</div>

<div class="container">
    <div class="sidebar">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        ?>

        <ul>
            <li class="<?= ($current_page == 'doctor_dashboard.php') ? 'active' : '' ?>">
                <a href="doctor_dashboard.php"><span class="icon">🏠</span> Dashboard</a>
            </li>
            <li class="<?= ($current_page == 'doctor_serve_appointment.php') ? 'active' : '' ?>">
                <a href="doctor_serve_appointment.php"><span class="icon">🩺</span> Current Appointments</a>
            </li>
            <li class="<?= ($current_page == 'doctor_view_appointment.php') ? 'active' : '' ?>">
                <a href="doctor_view_appointment.php"><span class="icon">📅</span> Appointments</a>
            </li>
            <li class="<?= ($current_page == 'doctor_patient.php') ? 'active' : '' ?>">
                <a href="doctor_patient.php"><span class="icon">👨‍⚕️</span> Patients</a>
            </li>
            <li class="<?= ($current_page == 'doctor_session.php') ? 'active' : '' ?>">
                <a href="doctor_session.php"><span class="icon">📖</span> Sessions</a>
            </li>
            <li class="<?= ($current_page == 'doctor_profile.php') ? 'active' : '' ?>">
                <a href="doctor_profile.php"><span class="icon">👤</span> My Profile</a>
            </li>
            <li class="logout <?= ($current_page == 'staff_logout.php') ? 'active' : '' ?>">
                <a href="staff_logout.php"><span class="icon">🚪</span> Logout</a>
            </li>
        </ul>


    </div>
</div>

</body>
</html>

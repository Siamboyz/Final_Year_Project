<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>SlideBar</title>
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

        .header .user-info-wrapper { /* New wrapper for user-info and notification */
            display: flex;
            align-items: center;
            position: relative; /* For dropdown positioning */
        }

        .header .user-info {
            font-size: 16px;
            color:rgb(1, 46, 72);
            font-weight: bold;
            margin-right: 15px; /* Space between text and icon */
        }

        /* Notification Icon Styling */
        .notification-icon {
            cursor: pointer;
            font-size: 20px;
            color: rgb(1, 46, 72);
            position: relative;
        }

        .notification-icon:hover {
            color: #0288D1; /* Hover effect */
        }

        /* Notification Indicator */
        .notification-indicator {
            position: absolute;
            top: -5px; /* Adjust as needed */
            right: -5px; /* Adjust as needed */
            background-color: #FF5252; /* Red color for indicator */
            color: white;
            border-radius: 50%;
            width: 15px;
            height: 15px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            /* display: none; */ /* Initially hidden, show with JavaScript */
        }

        /* Notification Dropdown Styling */
        .notification-dropdown {
            display: none; /* Hidden by default */
            position: absolute;
            top: 40px; /* Position below the icon */
            right: 0;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 5px;
            min-width: 220px;
            z-index: 1001;
            overflow: hidden;
        }

        .notification-dropdown.show {
            display: block; /* Show when 'show' class is added */
        }

        .notification-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .notification-dropdown ul li a {
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            color: #333;
            font-size: 15px;
            white-space: nowrap; /* Prevent text wrapping */
        }

        .notification-dropdown ul li a:hover {
            background-color: #f0f0f0;
            color: #0288D1;
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
    <div class="logo">
        <span>OASS | OPHTHALMOLOGY APPOINTMENT SCHEDULING SYSTEM</span>
    </div>
    <div class="user-info-wrapper">
        <div class="user-info">
            <span>Welcome, <?php echo $_SESSION['name']; ?> </span>
        </div>
        <div class="notification-icon" id="notificationIcon">
            🔔
            <div class="notification-indicator" id="notificationIndicator">!</div>
        </div>
        <div class="notification-dropdown" id="notificationDropdown">
            <ul>
                <li><a href="patient_incomplete_profile.php">Patient Incompleted Profile > </a></li>
            </ul>
        </div>
    </div>
</div>

<div class="container">
    <div class="sidebar">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        ?>

        <ul>
            <li class="<?= ($current_page == 'staff_dashboard.php') ? 'active' : '' ?>">
                <a href="staff_dashboard.php"><span class="icon">📊</span> Dashboard</a>
            </li>
            <li class="<?= ($current_page == 'staff_queue.php') ? 'active' : '' ?>">
                <a href="staff_queue.php"><span class="icon">⏳</span> Queue</a>
            </li>
            <li class="<?= ($current_page == 'staff_appointment.php') ? 'active' : '' ?>">
                <a href="staff_appointment.php"><span class="icon">📅</span> Appointment</a>
            </li>
            <li class="<?= ($current_page == 'staff_patient.php') ? 'active' : '' ?>">
                <a href="staff_patient.php"><span class="icon">🧑‍⚕️</span> Patients</a>
            </li>
            <li class="logout <?= ($current_page == 'staff_logout.php') ? 'active' : '' ?>">
                <a href="staff_logout.php"><span class="icon">🚪</span> Logout</a>
            </li>
            <li class="<?= ($current_page == 'staff_register_emergency.php') ? 'active' : '' ?>">
                <a href="staff_register_emergency.php"><span class="icon">🚑</span> Emergency</a>
            </li>
            <li class="<?= ($current_page == 'staff_profile.php') ? 'active' : '' ?>">
                <a href="staff_profile.php"><span class="icon">👤</span> My Profile</a>
            </li>
        </ul>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationIndicator = document.getElementById('notificationIndicator');

        // Function to check for incomplete profiles (replace with actual AJAX/PHP call)
        function checkForIncompleteProfiles() {
            // This is a placeholder. In a real application, you would make an AJAX
            // request to your server (e.g., to a PHP script) to check if there
            // are any incomplete patient profiles.
            // For demonstration, we'll randomly show the indicator.
            const hasIncompleteProfiles = Math.random() > 0.5; // Simulate a check

            if (hasIncompleteProfiles) {
                notificationIndicator.style.display = 'flex'; // Show the indicator
            } else {
                notificationIndicator.style.display = 'none'; // Hide the indicator
            }
        }

        // Call the function when the page loads
        checkForIncompleteProfiles();

        notificationIcon.addEventListener('click', function(event) {
            event.stopPropagation(); // Prevent document click from closing immediately
            notificationDropdown.classList.toggle('show');
            // Optionally, hide the indicator once the dropdown is opened
            notificationIndicator.style.display = 'none';
        });

        // Close the dropdown if the user clicks outside of it
        document.addEventListener('click', function(event) {
            if (!notificationIcon.contains(event.target) && !notificationDropdown.contains(event.target)) {
                notificationDropdown.classList.remove('show');
            }
        });
    });
</script>

</body>
</html>
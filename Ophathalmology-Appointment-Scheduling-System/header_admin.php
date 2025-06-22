<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>SlideBar</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; /* Add this to remove default body margin */
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
            display: flex; /* Use flexbox to align text and indicator */
            justify-content: space-between; /* Space out content */
            align-items: center;
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

        /* Subpage Notification Indicator */
        .subpage-notification-indicator {
            background-color: red; /* Green color for subpage indicator */
            color: white;
            border-radius: 50%;
            width: 10px;
            height: 10px;
            font-size: 8px; /* Smaller font for tiny dot */
            display: none; /* Hidden by default */
            align-items: center;
            justify-content: center;
            margin-left: 5px; /* Space between text and indicator */
            flex-shrink: 0; /* Prevent it from shrinking */
        }

        /* --- NEW: Hamburger Menu Icon Styling --- */
        .hamburger-menu {
            display: none; /* Hidden by default, shown on smaller screens */
            font-size: 28px;
            color: rgb(1, 46, 72);
            cursor: pointer;
            margin-right: 20px;
        }

        /* Sidebar Navigation */
        .container {
            display: flex;
            width: 100%;
            margin-top: 50px; /* Creates space below header */
            flex-grow: 1; /* Ensures sidebar grows properly */
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
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

        .sidebar {
            width: 20%;
            background-color: #ffffff;
            border-right: 2px solid #ddd;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 50px); /* Adjust height to fill remaining viewport, considering header */
            position: fixed;
            left: 0;
            top: 70px; /* Position below header */
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1); /* Add shadow for better visual */
            transition: transform 0.3s ease-in-out; /* Smooth transition for sliding */
            z-index: 990; /* Ensure sidebar is behind header but above main content when open on mobile */
        }

        .sidebar ul {
            display: flex;
            flex-direction: column;
            height: 100%; /* full height of the sidebar */
            padding: 0;
            margin: 0;
            list-style-type: none;
            overflow-y: auto; /* Enable scrolling for long menus on small screens */
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
            position: fixed; /* Keep fixed for desktop, adjust for mobile */
            bottom: 0;
            width: 20%; /* Keep 20% for desktop */
            border-top: 1px solid #eee; /* Add a top border for logout */
        }

        .logout .icon {
            margin-right: 10px;
        }

        /* Media Queries for Responsiveness */

        /* Tablets and smaller (e.g., max-width: 768px) */
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
                justify-content: flex-start; /* Align items to start for hamburger-logo-userinfo */
            }

            .hamburger-menu {
                display: block; /* Show hamburger menu */
                order: -1; /* Place it first in the flex container */
                margin-right: 15px; /* Space between hamburger and logo */
            }

            .header .logo {
                font-size: 18px;
                white-space: nowrap; /* Prevent logo wrapping */
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: calc(100% - 180px); /* Adjust max-width considering hamburger and user info/notif */
            }

            .header .user-info-wrapper {
                margin-left: auto; /* Push user info to the right */
            }

            .header .user-info {
                display: none; /* Hide welcome text on smaller screens */
            }

            .sidebar {
                width: 250px; /* Fixed width for collapsed sidebar */
                transform: translateX(-100%); /* Hide sidebar off-screen to the left */
                top: 0; /* Align to top of viewport */
                height: 100vh; /* Full viewport height */
                box-shadow: 5px 0 10px rgba(0, 0, 0, 0.2); /* Stronger shadow when slid out */
                z-index: 1000; /* Bring sidebar above everything else when open */
            }

            .sidebar.show {
                transform: translateX(0); /* Slide in sidebar */
            }

            .container {
                margin-top: 0; /* Remove top margin on container, header handles space */
            }

            .main-content {
                margin-left: 0; /* Main content takes full width */
                width: 100%; /* Ensure full width */
                padding-top: 70px; /* Keep padding for header */
            }

            /* Adjust fixed logout button for mobile sidebar */
            ul li.logout {
                position: absolute; /* Change to absolute within sidebar for scrolling */
                bottom: 0;
                width: 100%; /* Take full width of sidebar */
                padding-bottom: 10px; /* Add some padding at the bottom */
                background-color: #ffffff; /* Match sidebar background */
            }
        }

        /* Small mobile screens (e.g., max-width: 480px) */
        @media (max-width: 480px) {
            .header {
                padding: 12px 15px;
            }
            .hamburger-menu {
                font-size: 24px;
                margin-right: 10px;
            }
            .header .logo {
                font-size: 15px;
                max-width: calc(100% - 140px); /* Adjust for smaller hamburger/notif icon */
            }
            .notification-icon {
                font-size: 18px;
            }
            .notification-indicator {
                width: 12px;
                height: 12px;
                font-size: 8px;
            }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="hamburger-menu" id="hamburgerMenu">
        &#9776; </div>
    <div class="logo">
        <span>OASS | OPHTHALMOLOGY APPOINTMENT SCHEDULING SYSTEM</span>
    </div>
    <div class="user-info-wrapper">
        <div class="user-info">
            <span>Welcome,  <?php echo $_SESSION['name']; ?></span>
        </div>
        <div class="notification-icon" id="notificationIcon">
            🔔
            <div class="notification-indicator" id="notificationIndicator">!</div>
        </div>
        <div class="notification-dropdown" id="notificationDropdown">
            <ul>
                <li><a href="admin_doctor_requests.php">Doctor Reactive Account Requests <span class="subpage-notification-indicator" id="doctorUnbanRequestsIndicator"></span></a></li>
            </ul>
        </div>
    </div>
</div>
<div class="container">
    <div class="sidebar" id="sidebar">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        ?>

        <ul>
            <li class="<?= ($current_page == 'admin_dashboard.php') ? 'active' : '' ?>">
                <a href="admin_dashboard.php"><span class="icon">🏠</span> Dashboard</a>
            </li>
            <li class="<?= ($current_page == 'admin_monitoring.php') ? 'active' : '' ?>">
                <a href="admin_monitoring.php"><span class="icon">🕒</span> Queue</a>
            </li>
            <li class="<?= ($current_page == 'admin_manage_doctor.php') ? 'active' : '' ?>">
                <a href="admin_manage_doctor.php"><span class="icon">🩺</span> Doctor</a>
            </li>
            <li class="<?= ($current_page == 'admin_manage_staff.php') ? 'active' : '' ?>">
                <a href="admin_manage_staff.php"><span class="icon">👥</span> Counter Staff</a>
            </li>
            <li class="<?= ($current_page == 'admin_view_patient.php') ? 'active' : '' ?>">
                <a href="admin_view_patient.php"><span class="icon">🧑‍⚕️</span> Patients</a>
            </li>
            <li class="<?= ($current_page == 'admin_view_appointment.php') ? 'active' : '' ?>">
                <a href="admin_view_appointment.php"><span class="icon">📅</span> Appointment</a>
            </li>
            <li class="<?= ($current_page == 'admin_manage_room.php') ? 'active' : '' ?>">
                <a href="admin_manage_room.php"><span class="icon">🚪</span> Room</a>
            </li>
            <li class="<?= ($current_page == 'admin_doctor_requests.php') ? 'active' : '' ?>">
                <a href="admin_doctor_requests.php"><span class="icon">📩</span> Requests Account Doctor</a>
            </li>
            <li class="<?= ($current_page == 'admin_report.php') ? 'active' : '' ?>">
                <a href="admin_report.php"><span class="icon">📊</span> Report</a>
            </li>
            <li class="logout <?= ($current_page == 'staff_logout.php') ? 'active' : '' ?>">
                <a href="staff_logout.php"><span class="icon">🚪</span> Logout</a>
            </li>
        </ul>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationIndicator = document.getElementById('notificationIndicator');
        const doctorUnbanRequestsIndicator = document.getElementById('doctorUnbanRequestsIndicator');

        // --- NEW: Elements for hamburger menu ---
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const sidebar = document.getElementById('sidebar');
        const body = document.body; // Reference to the body for preventing scroll

        // Function to check for doctor unban requests
        function checkForDoctorUnbanRequests() {
            fetch('check_doctor_unban_requests.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.hasRequests) {
                        notificationIndicator.style.display = 'flex';
                        doctorUnbanRequestsIndicator.style.display = 'flex';
                    } else {
                        // Only hide if there are no requests
                        notificationIndicator.style.display = 'none';
                        doctorUnbanRequestsIndicator.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error fetching doctor unban requests:', error));
        }

        // Call the function when the page loads
        checkForDoctorUnbanRequests();

        // Optional: Periodically check for new requests (uncomment if needed)
        // setInterval(checkForDoctorUnbanRequests, 60000); // Check every 60 seconds

        notificationIcon.addEventListener('click', function(event) {
            event.stopPropagation();
            notificationDropdown.classList.toggle('show');
            // Close sidebar if open when notification is clicked (on small screens)
            if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
                body.style.overflow = ''; // Restore body scroll
            }
        });

        // Close dropdown and sidebar if the user clicks outside of them
        document.addEventListener('click', function(event) {
            // Close notification dropdown
            if (!notificationIcon.contains(event.target) && !notificationDropdown.contains(event.target)) {
                notificationDropdown.classList.remove('show');
            }

            // Close sidebar if clicked outside (only on small screens)
            // Ensure click is not on sidebar itself, nor on the hamburger menu
            if (window.innerWidth <= 768 && sidebar.classList.contains('show') &&
                !sidebar.contains(event.target) && !hamburgerMenu.contains(event.target)) {
                sidebar.classList.remove('show');
                body.style.overflow = ''; // Restore body scroll
            }
        });

        // Optionally, hide the subpage indicator when its link is clicked
        doctorUnbanRequestsIndicator.closest('a').addEventListener('click', function() {
            // In a real application, you'd likely mark these notifications as "read" in the database
            // after the admin views the page.
            doctorUnbanRequestsIndicator.style.display = 'none';
            // Re-check for other notifications to update the main indicator
            checkForDoctorUnbanRequests();
            // Close dropdown after clicking a link
            notificationDropdown.classList.remove('show');
            // Close sidebar if on mobile after clicking a link
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('show');
                body.style.overflow = '';
            }
        });

        // --- NEW: Hamburger Menu Toggle Logic ---
        hamburgerMenu.addEventListener('click', function(event) {
            event.stopPropagation(); // Prevent document click listener from immediately closing it
            sidebar.classList.toggle('show');
            // Prevent body scrolling when sidebar is open on small screens
            if (sidebar.classList.contains('show')) {
                body.style.overflow = 'hidden';
            } else {
                body.style.overflow = '';
            }
            // Close notification dropdown if sidebar is opened
            notificationDropdown.classList.remove('show');
        });

        // Close sidebar if a link inside it is clicked (on small screens)
        sidebar.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('show');
                    body.style.overflow = ''; // Restore body scroll
                }
            });
        });

        // Handle window resize to reset sidebar state for larger screens
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
                body.style.overflow = ''; // Ensure scroll is restored on larger screens
            }
        });
    });
</script>
</body>
</html>
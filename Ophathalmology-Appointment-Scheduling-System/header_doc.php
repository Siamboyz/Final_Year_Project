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
            /* Original height: 100% -> changed to calc for fixed header */
            height: calc(100vh - 50px); /* Adjust height to fill remaining viewport, considering header */
            position: fixed;
            left: 0;
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
                max-width: calc(100% - 150px); /* Adjust max-width considering hamburger and user info */
            }

            .header .user-info {
                margin-left: auto; /* Push user info to the right */
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
                max-width: calc(100% - 120px); /* Adjust for smaller hamburger/user info */
            }
            .header .user-info {
                font-size: 14px; /* Smaller font for user info on small screens */
            }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="hamburger-menu" id="hamburgerMenu">
        &#9776; </div>
    <div class="logo">OASS | OPHTHALMOLOGY APPOINTMENT SCHEDULING SYSTEM</div>
    <div class="user-info">Welcome, <?php echo $_SESSION['name']; ?></div>
</div>

<div class="container">
    <div class="sidebar" id="sidebar">
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- NEW: Elements for hamburger menu ---
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const sidebar = document.getElementById('sidebar');
        const body = document.body; // Reference to the body for preventing scroll

        // Hamburger Menu Toggle Logic
        hamburgerMenu.addEventListener('click', function(event) {
            event.stopPropagation(); // Prevent document click listener from immediately closing it
            sidebar.classList.toggle('show');
            // Prevent body scrolling when sidebar is open on small screens
            if (sidebar.classList.contains('show')) {
                body.style.overflow = 'hidden';
            } else {
                body.style.overflow = '';
            }
        });

        // Close sidebar if clicked outside (only on small screens)
        document.addEventListener('click', function(event) {
            // Ensure click is not on sidebar itself, nor on the hamburger menu
            if (window.innerWidth <= 768 && sidebar.classList.contains('show') &&
                !sidebar.contains(event.target) && !hamburgerMenu.contains(event.target)) {
                sidebar.classList.remove('show');
                body.style.overflow = ''; // Restore body scroll
            }
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
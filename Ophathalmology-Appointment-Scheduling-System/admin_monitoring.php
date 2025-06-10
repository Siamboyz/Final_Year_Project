<?php
// admin_monitoring.php - Real-Time Queue Monitoring for Admin

// Include the database connection file.
// Ensure 'connection_database.php' exists and properly establishes $conn.
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

// Get the current date and time in 'YYYY-MM-DD HH:MM:SS' format.
$currentTime = date('Y-m-d H:i:s');

// SQL query to fetch appointments with specific statuses and join necessary tables.
// It joins:
// - 'appointment' (aliased as 'a') for core appointment data.
// - 'patient' (aliased as 'p') using 'patient_id' to get the patient's name.
// - 'doctor' (aliased as 'd') using 'doctor_id' to get the doctor's name and room_id.
// - 'room' (aliased as 'r') using 'room_id' from the 'doctor' table to get the room_name.
// It filters for appointments with status 'Now Serving', 'Paused', or 'Emergency'
// and orders them by appointment time.
$sql = "SELECT
            a.apt_id,             -- Appointment ID
            a.apt_time,           -- Appointment Time
            a.apt_status,         -- Appointment Status (e.g., 'Now Serving', 'Paused', 'Emergency')
            p.name AS patient_name,  -- Patient's name from the 'patient' table
            d.name AS doctor_name,   -- Doctor's name from the 'doctor' table
            r.room_name           -- Room name from the 'room' table
        FROM
            appointment AS a
        JOIN
            patient AS p ON a.patient_id = p.patient_id
        JOIN
            doctor AS d ON a.doctor_id = d.doctor_id
        JOIN
            room AS r ON d.room_id = r.room_id
        WHERE
            a.apt_status IN ('Now Serving', 'Paused', 'Emergency')
        ORDER BY
            a.apt_time";

// Execute the SQL query.
$result = mysqli_query($conn, $sql);

// Initialize an empty array to store the fetched queue data.
$queueData = [];

// Check if the query execution failed.
if (!$result) {
    // If there's an error, print it and exit.
    echo "Error fetching data: " . mysqli_error($conn);
    exit;
}

// Loop through each row fetched from the database.
while ($row = mysqli_fetch_assoc($result)) {
    // The status is directly taken from the 'apt_status' column in the database.
    $status = $row['apt_status'];

    // Add the fetched data to the queueData array.
    $queueData[] = [
        'room' => $row['room_name'],      // Room name
        'patient' => $row['patient_name'], // Patient's name
        'doctor' => $row['doctor_name'],  // Doctor's name
        'status' => $status,              // Current status
        'start_time' => $row['apt_time'], // Appointment scheduled time
        'queue_id' => $row['apt_id']      // Unique appointment ID
    ];
}

// Close the database connection.
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Real-Time Queue Monitoring</title>
    <!-- Link to Google Fonts for 'Inter' - a modern, professional font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Link to Font Awesome for professional icons (reintroduced) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* General body styling for a clean, modern look */
        body {
            font-family: 'Inter', sans-serif; /* Using Inter font */
            background-color: #f0f2f5; /* Light grey background */
            color: #34495e; /* Darker text for professionalism */
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }

        /* Main container for the page content, centered and with refined shadow */
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

        /* Breadcrumb styling */
        .breadcrumb {
            font-size: 13px; /* Slightly smaller */
            color: #7f8c8d; /* Muted color */
            margin-bottom: 15px;
            text-align: right;
        }

        /* Page title styling */
        h1 {
            font-size: 1.8rem; /* Slightly larger */
            color: #007bff; /* Darker, more serious blue/grey */
            margin-bottom: 10px; /* Adjusted margin */
            border-bottom: 1px solid #ecf0f1; /* Subtle separator */
            padding-bottom: 15px;
        }

        /* Container for queue cards */
        .queue-container {
            display: flex;
            flex-direction: column;
            gap: 18px; /* Slightly reduced gap */
        }

        /* Individual queue card styling with a more professional look */
        .queue-card {
            background: #ffffff;
            border: 1px solid #e0e6ed; /* Subtle border */
            border-radius: 8px;
            padding: 20px 25px; /* Adjusted padding */
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; /* Smooth hover effect */
        }

        .queue-card:hover {
            transform: translateY(-3px); /* Lift effect on hover */
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1); /* Enhanced shadow on hover */
        }

        /* Styling for headings within queue cards */
        .queue-card h4 {
            margin: 0;
            font-size: 1.15rem; /* Slightly larger room name */
            color: #3498db; /* A professional blue */
            margin-bottom: 4px;
            font-weight: 600;
        }

        /* Styling for paragraphs within queue cards */
        .queue-card p {
            font-size: 1rem;
            margin-bottom: 3px;
            color: #5d6d7e; /* Slightly lighter text */
        }

        .queue-card p strong {
            color: #34495e; /* Stronger bold text */
        }

        /* Patient information section within a queue card */
        .patient-info {
            flex-grow: 1;
            margin-right: 30px; /* More space */
        }

        /* Status-specific left border colors for queue cards (more muted/professional tones) */
        .queue-card.paused {
            border-left: 6px solid #f39c12; /* Professional orange */
        }

        .queue-card.emergency {
            border-left: 6px solid #e74c3c; /* Professional red */
        }

        .queue-card.now-serving {
            border-left: 6px solid #27ae60; /* Professional green */
        }

        /* Container for the redesigned status visualizer (text + progress bar) */
        .status-visualizer {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            min-width: 220px; /* Slightly wider */
            flex-shrink: 0; /* Prevent shrinking on smaller screens */
        }

        /* Styling for the status text (e.g., "Now Serving") */
        .status-text {
            font-size: 1rem;
            font-weight: 600; /* Medium-bold */
            margin-bottom: 50px; /* More space */
            color: #34495e;
            text-transform: uppercase; /* Uppercase for a crisp look */
            letter-spacing: 0.5px;
        }

        /* The background track of the progress bar */
        .status-progress-bar {
            width: 100%;
            height: 10px; /* Slightly thicker */
            background-color: #ecf0f1; /* Lighter grey track */
            border-radius: 5px;
            position: relative;

        }

        /* The colored fill of the progress bar */
        .progress-fill {
            height: 100%;
            border-radius: 5px;
            position: relative;
            transition: width 0.6s ease-out, background-color 0.4s ease; /* Slower, smoother transition */
            display: flex;
            align-items: center;
            justify-content: flex-end;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); /* Inner shadow for depth */
        }

        /* The circular "thumb" indicator on the progress bar */
        .progress-thumb {
            position: absolute;
            top: 50%;
            right: 0;
            transform: translate(50%, -50%);
            width: 28px; /* Slightly larger thumb */
            height: 28px;
            border-radius: 50%;
            background-color: #ffffff; /* White background */
            border: 4px solid; /* Thicker border */
            display: flex;
            align-items: center;
            justify-content: center;
            /* No direct font-size or color for the thumb itself, only for its content */
            box-shadow: 0 4px 10px rgba(0,0,0,0.2); /* More prominent shadow */
            z-index: 2; /* Ensure thumb is always on top */
        }

        /* NEW STYLES FOR THE ICON BACKGROUND */
        .progress-thumb .icon-bg-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 18px;   /* Size of the blue square background */
            height: 18px;
            background-color: #3498db; /* Blue background for the icon */
            border-radius: 3px; /* Slightly rounded corners for the blue square */
            font-size: 10px; /* Smaller icon size to fit */
            color: white; /* White color for the Font Awesome icon */
            overflow: hidden; /* Ensure nothing overflows */
        }

        /* Status-specific styles for the progress fill and thumb border (refined colors) */
        .progress-fill.now-serving {
            background-color: #2ecc71; /* Professional green */
            width: 80%; /* Simulate active progress */
            background-image: linear-gradient(to right, #2ecc71, #27ae60); /* Subtle gradient */
        }
        .progress-fill.now-serving .progress-thumb {
            border-color: #27ae60;
        }

        .progress-fill.paused {
            background-color: #f1c40f; /* Professional yellow/orange */
            width: 50%; /* Simulate paused state */
            background-image: linear-gradient(to right, #f1c40f, #f39c12); /* Subtle gradient */
        }
        .progress-fill.paused .progress-thumb {
            border-color: #f39c12;
        }

        .progress-fill.emergency {
            background-color: #e74c3c; /* Professional red */
            width: 100%; /* Simulate urgent, full attention */
            background-image: linear-gradient(to right, #e74c3c, #c0392b); /* Subtle gradient */
        }
        .progress-fill.emergency .progress-thumb {
            border-color: #c0392b;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
                margin: 10px auto;
            }

            h1 {
                font-size: 1.5rem;
                padding-bottom: 10px;
            }

            h2 {
                font-size: 1.3rem;
                margin-top: 15px;
                margin-bottom: 15px;
            }

            .queue-card {
                flex-direction: column; /* Stack items vertically on smaller screens */
                align-items: flex-start; /* Align to the start when stacked */
                padding: 15px;
            }

            .patient-info {
                margin-right: 0;
                margin-bottom: 15px; /* Add space below info when stacked */
                width: 100%; /* Take full width */
            }

            .status-visualizer {
                width: 100%; /* Take full width */
                align-items: flex-start; /* Align to the start when stacked */
                min-width: unset; /* Remove min-width restriction */
            }

            .status-text {
                align-self: flex-start; /* Ensure text aligns left above bar */
            }
        }
    </style>
</head>
<body>
<!-- Include the header for the admin section -->
<?php include 'header_admin.php'; ?>

<div class="main-container">
    <h1>📍 Admin | Real-Time Queue Monitoring</h1>
    <!-- Include the breadcrumb navigation -->
    <?php include 'breadcrumb.php'; ?>
    <br>
    <section class="queue-section">
        <div class="queue-container">
            <?php if (empty($queueData)) { ?>
                <!-- Message if no patients are in the queue, styled professionally -->
                <p style="text-align: center; color: #7f8c8d; padding: 20px; border: 1px dashed #dbe3eb; border-radius: 8px;">No patients in the queue at the moment.</p>
            <?php } else { ?>
                <?php foreach ($queueData as $data) { ?>
                    <!-- Loop through each patient in the queue -->
                    <div class="queue-card <?= strtolower(str_replace(' ', '-', $data['status'])) ?>">
                        <div class="patient-info">
                            <h4>Room: <?= htmlspecialchars($data['room']) ?></h4>
                            <p><strong>Now Serving:</strong> <?= htmlspecialchars($data['patient']) ?></p>
                            <p><strong>Doctor:</strong> <?= htmlspecialchars($data['doctor']) ?></p>
                            <p><strong>Appointment Time:</strong> <?= date('h:i A', strtotime($data['start_time'])) ?></p>
                        </div>

                        <!-- Redesigned Progress / Status Indicator for professional look -->
                        <div class="status-visualizer">
                            <span class="status-text"><?= htmlspecialchars($data['status']) ?></span>
                            <div class="status-progress-bar">
                                <!-- The colored fill of the progress bar, dynamic width and color based on status -->
                                <div class="progress-fill <?= strtolower(str_replace(' ', '-', $data['status'])) ?>">
                                    <!-- The circular thumb with an icon -->
                                    <div class="progress-thumb">
                                        <?php
                                        // Conditional rendering of icons based on status
                                        if ($data['status'] == 'Now Serving') {
                                            // Specific styling for the play icon
                                            echo '<span class="icon-bg-wrapper"><i class="fas fa-play"></i></span>';
                                        } elseif ($data['status'] == 'Paused') {
                                            echo '⏸️'; // Emoji for Paused
                                        } elseif ($data['status'] == 'Emergency') {
                                            echo '⚠️'; // Emoji for Emergency
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    </section>
</div>

</body>
</html>

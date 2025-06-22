<?php
session_start();
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
$currentDate = date('Y-m-d');

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
            a.visit_type,         -- Added visit_type to determine emergency
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
            AND a.apt_date = '$currentDate'
        ORDER BY
            r.room_name";

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
    $status = $row['apt_status'];
    $isEmergency = ($row['visit_type'] == 'Emergency'); // Check if it's an emergency visit

    // If it's an emergency, display "Now Serving" but keep the internal flag for styling
    if ($isEmergency) {
        $status = 'Now Serving'; // Display "Now Serving" for emergency cases
    }

    // Add the fetched data to the queueData array.
    $queueData[] = [
        'room' => $row['room_name'],      // Room name
        'patient' => $row['patient_name'], // Patient's name
        'doctor' => $row['doctor_name'],  // Doctor's name
        'status' => $status,              // Display status (Now Serving, Paused, or Emergency)
        'start_time' => $row['apt_time'], // Appointment scheduled time
        'queue_id' => $row['apt_id'],      // Unique appointment ID
        'is_emergency' => $isEmergency    // Flag to indicate if it's an emergency appointment
    ];
}

// Close the database connection.
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="5">
    <title>Admin Real-Time Queue Monitoring</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            color: #34495e;
            margin: 0;
            padding: 25px;
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
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 15px;
            text-align: right;
        }

        h1 {
            font-size: 1.8rem;
            color: #007bff;
            margin-bottom: 10px;
            border-bottom: 1px solid #ecf0f1;
            padding-bottom: 15px;
        }

        .queue-container {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .queue-card {
            background: #ffffff;
            border: 1px solid #e0e6ed;
            border-radius: 8px;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .queue-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .queue-card h4 {
            margin: 0;
            font-size: 1.15rem;
            color: #3498db;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .queue-card p {
            font-size: 1rem;
            margin-bottom: 3px;
            color: #5d6d7e;
        }

        .queue-card p strong {
            color: #34495e;
        }

        .patient-info {
            flex-grow: 1;
            margin-right: 30px;
        }

        .queue-card.paused {
            border-left: 6px solid #f39c12;
        }

        /* This border will be used for both "Emergency" appointments (which display as "Now Serving") */
        .queue-card.emergency {
            border-left: 6px solid #e74c3c;
        }

        .queue-card.now-serving {
            border-left: 6px solid #27ae60;
        }

        .status-visualizer {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            min-width: 220px;
            flex-shrink: 0;
            position: relative;
        }

        .status-text {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 50px; /* Adjusted to make space for the label above */
            color: #34495e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Ensure the status text for emergency cards is red */
        .queue-card.emergency .status-text {
            color: #e74c3c; /* Red text for emergency status */
        }

        .status-progress-bar {
            width: 100%;
            height: 10px;
            background-color: #ecf0f1;
            border-radius: 5px;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            border-radius: 5px;
            position: relative;
            transition: width 0.6s ease-out, background-color 0.4s ease;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }

        .progress-thumb {
            position: absolute;
            top: 50%;
            right: 0;
            transform: translate(50%, -50%);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-color: #ffffff;
            border: 4px solid;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            z-index: 2;
            font-size: 14px; /* Adjust emoji size */
        }

        .progress-thumb .icon-bg-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            background-color: #3498db;
            border-radius: 3px;
            font-size: 10px;
            color: white;
            overflow: hidden;
        }

        .progress-fill.now-serving {
            background-color: #2ecc71;
            width: 80%;
            background-image: linear-gradient(to right, #2ecc71, #27ae60);
        }
        .progress-fill.now-serving .progress-thumb {
            border-color: #27ae60;
        }

        .progress-fill.paused {
            background-color: #f1c40f;
            width: 50%;
            background-image: linear-gradient(to right, #f1c40f, #f39c12);
        }
        .progress-fill.paused .progress-thumb {
            border-color: #f39c12;
        }

        /* Specific styles for emergency now serving */
        .progress-fill.emergency-now-serving {
            background-color: #e74c3c; /* Professional red */
            width: 80%; /* Simulate urgent, full attention */
            background-image: linear-gradient(to right, #e74c3c, #c0392b);
        }
        .progress-fill.emergency-now-serving .progress-thumb {
            border-color: #c0392b;
        }

        .emergency-label {
            position: absolute;
            top: 0;
            right: 0;
            background-color: #e74c3c;
            color: white;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 3;
            /* Position directly above the status text, aligning with its right edge */
            transform: translateY(-130%); /* Adjusted for more space above status text */
            margin-right: -10px; /* To align with the right edge of the visualizer */
        }


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
                flex-direction: column;
                align-items: flex-start;
                padding: 15px;
            }

            .patient-info {
                margin-right: 0;
                margin-bottom: 15px;
                width: 100%;
            }

            .status-visualizer {
                width: 100%;
                align-items: flex-start;
                min-width: unset;
            }

            .status-text {
                align-self: flex-start;
            }
            .emergency-label {
                right: unset;
                left: 0;
                transform: translateY(-130%); /* Adjusted for more space above status text */
                margin-right: 0;
                margin-left: -10px;
            }
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>

<div class="main-container">
    <h1>📍 Admin | Real-Time Queue Monitoring</h1>
    <?php include 'breadcrumb.php'; ?>
    <br>
    <section class="queue-section">
        <div class="queue-container">
            <?php if (empty($queueData)) { ?>
                <p style="text-align: center; color: #7f8c8d; padding: 20px; border: 1px dashed #dbe3eb; border-radius: 8px;">No patients in the queue at the moment.</p>
            <?php } else { ?>
                <?php foreach ($queueData as $data) { ?>
                    <div class="queue-card <?= $data['is_emergency'] ? 'emergency' : strtolower(str_replace(' ', '-', $data['status'])) ?>">
                        <div class="patient-info">
                            <h4>Room: <?= htmlspecialchars($data['room']) ?></h4>
                            <p><strong>Now Serving:</strong> <?= htmlspecialchars($data['patient']) ?></p>
                            <p><strong>Doctor:</strong> <?= htmlspecialchars($data['doctor']) ?></p>
                            <p><strong>Appointment Time:</strong> <?= date('h:i A', strtotime($data['start_time'])) ?></p>
                        </div>

                        <div class="status-visualizer">
                            <?php if ($data['is_emergency']) { ?>
                                <span class="emergency-label">Emergency</span>
                            <?php } ?>
                            <span class="status-text"><?= htmlspecialchars($data['status']) ?></span>
                            <div class="status-progress-bar">
                                <div class="progress-fill <?= $data['is_emergency'] ? 'emergency-now-serving' : strtolower(str_replace(' ', '-', $data['status'])) ?>">
                                    <div class="progress-thumb">
                                        <?php
                                        // Conditional rendering of icons based on 'is_emergency' flag or status
                                        if ($data['is_emergency']) {
                                            echo '⚠️'; // Emergency icon
                                        } elseif ($data['status'] == 'Now Serving') {
                                            echo '<span class="icon-bg-wrapper"><i class="fas fa-play"></i></span>'; // Play icon for standard 'Now Serving'
                                        } elseif ($data['status'] == 'Paused') {
                                            echo '⏸️'; // Paused icon
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
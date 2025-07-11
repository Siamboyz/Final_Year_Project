<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

date_default_timezone_set('Asia/Kuala_Lumpur');

$today = date("Y-m-d");

// These counts are typically static overall counts, not date-range specific.
// They are fetched once on page load and remain constant.
$patients = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM patient"))[0];
$doctors = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM doctor"))[0];
$staff = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) AS total_staff FROM counter_staff"))[0];
$room = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) AS total_rooms FROM room"))[0];


// Data for monthly and no-show charts (full year overview, not date range filtered)
$monthlyData = [];
for ($i = 1; $i <= 12; $i++) {
    $res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointment WHERE MONTH(apt_date) = $i AND YEAR(apt_date) = YEAR(CURDATE())");
    $monthlyData[] = mysqli_fetch_array($res)['total'];
}

$monthlyDataLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

$noShowLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$noShowData = [];
for ($i = 1; $i <= 12; $i++) {
    $res = mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM appointment
        WHERE apt_status = 'Missed'
        AND was_missed = 1
        AND MONTH(apt_date) = $i
        AND YEAR(apt_date) = YEAR(CURDATE())
    ");
    $total = mysqli_fetch_assoc($res)['total'];
    $noShowData[] = (int)$total;
}

// Initial values for dynamic summary cards (will be updated by AJAX)
// Fetch these initially for "today" as default view if no range is selected.
$appointmentsToday = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM appointment WHERE DATE(apt_date) = CURDATE()"))[0];
$completedAppointments = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM appointment WHERE apt_status = 'Completed' AND DATE(apt_date) = CURDATE()"))[0]; // Filter for today
$missed = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) AS missed FROM appointment WHERE apt_date = CURDATE() AND apt_status = 'Missed'"))[0]; // Filter for today

// Initial data for charts that are filtered by date range
$initialVisitTypes = [];
$res = mysqli_query($conn, "SELECT visit_type, COUNT(*) as total FROM appointment WHERE DATE(apt_date) = CURDATE() GROUP BY visit_type");
while ($row = mysqli_fetch_assoc($res)) {
    $initialVisitTypes[$row['visit_type']] = $row['total'];
}
$initialVisitTypesLabels = array_keys($initialVisitTypes);
$initialVisitTypesData = array_values($initialVisitTypes);


$initialDoctorNames = [];
$initialDoctorCounts = [];
$res = mysqli_query($conn, "
    SELECT d.name, COUNT(a.apt_id) AS total
    FROM doctor d
    LEFT JOIN appointment a ON a.doctor_id = d.doctor_id
    WHERE DATE(a.apt_date) = CURDATE()
    GROUP BY d.doctor_id
    ORDER BY total DESC
    LIMIT 5
");
while ($row = mysqli_fetch_assoc($res)) {
    $initialDoctorNames[] = $row['name'];
    $initialDoctorCounts[] = $row['total'];
}

$initialWaitLabels = $initialWaitData = [];
$res = mysqli_query($conn, "
    SELECT visit_type, AVG(TIMESTAMPDIFF(MINUTE, validated_datetime, CONCAT(apt_date, ' ', apt_time))) AS avg_wait
    FROM appointment
    WHERE validated_datetime IS NOT NULL AND apt_time IS NOT NULL AND DATE(apt_date) = CURDATE()
    GROUP BY visit_type
");
while ($row = mysqli_fetch_assoc($res)) {
    $initialWaitLabels[] = $row['visit_type'];
    $initialWaitData[] = round($row['avg_wait'], 1);
}

$initialUtilLabels = $initialUtilData = [];
$res = mysqli_query($conn, "
    SELECT d.name, SUM(a.duration_minutes) AS total_minutes
    FROM doctor d
    LEFT JOIN appointment a ON d.doctor_id = a.doctor_id
    WHERE DATE(a.apt_date) = CURDATE()
    GROUP BY d.doctor_id
");
while ($row = mysqli_fetch_assoc($res)) {
    $initialUtilLabels[] = $row['name'];
    // Assuming 8 hours workday = 480 minutes
    $initialUtilData[] = round((($row['total_minutes'] ?? 0) / 480) * 100, 1);
}

$initialDoctorLabels = $initialDoctorData = [];
$res = mysqli_query($conn, "
    SELECT d.name, SUM(a.duration_minutes) AS total_minutes
    FROM doctor d
    LEFT JOIN appointment a ON d.doctor_id = a.doctor_id
    WHERE DATE(a.apt_date) = CURDATE()
    GROUP BY d.doctor_id
");
while ($row = mysqli_fetch_assoc($res)) {
    $initialDoctorLabels[] = $row['name'];
    $initialDoctorData[] = $row['total_minutes'] ?? 0;
}

// Get current date for the input field default value
$currentDate = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Report | OASS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            padding: 10px 30px;
            display: flex;
            flex-direction: column;
            text-align: justify;
            margin-left: calc(100% - 80%);
            max-width: 100%; /* Limits the content width */
        }

        h1 {
            font-size: 1.8rem;
            color: #0275d8;
            margin-bottom: 10px; /* Adjusted margin */
            border-bottom: 1px solid #ecf0f1; /* Subtle separator */
            padding-bottom: 15px;
            padding-top: 15px;
            font-weight: bold;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease-in-out;
            background-color: #fff;
        }

        .card:hover {
            transform: scale(1.01);
        }

        .card-body {
            padding: 20px;
        }

        .summary-card h5 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .summary-card h3 {
            font-size: 1.5rem;
            color: #2a2f45;
            font-weight: 700;
        }

        .chart-container {
            height: 280px;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        /* Breadcrumb styling for right alignment */
        .breadcrumb-container {
            display: flex;
            justify-content: flex-end;
            width: 100%;
            margin-bottom: 20px;
        }

        .breadcrumb {
            font-size: 14px;
            color: #666;
        }

        /* Date Selection Card - New layout with filters and date input on one line */
        .date-selection-card {
            margin-bottom: 25px;
        }


        .date-selection-card .card-body.filter-date-line {
            display: flex;
            flex-wrap: wrap;
            justify-content: center !important; /* force override */
            align-items: center;
            text-align: center;
            padding: 15px 20px;
            gap: 15px;
        }

        .full-inline-group {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: nowrap; /* forces all to stay in one line */
            gap: 12px;
        }

        .full-inline-group .form-control,
        .full-inline-group .btn,
        .full-inline-group .btn-group {
            white-space: nowrap;
            flex-shrink: 0;
        }

        @media (max-width: 991.98px) {
            .full-inline-group {
                flex-wrap: wrap; /* allow stacking on mobile */
                justify-content: flex-start;
            }

            .full-inline-group .btn-group {
                width: 100%;
                justify-content: start;
            }
        }


        .date-selection-card .section-title {
            margin-bottom: 0;
            margin-right: auto;
            text-align: left;
        }

        /* Date input styling */
        .date-input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .date-selection-card input[type="date"] {
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            color: #333;
            width: 100%;
            max-width: 150px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
            transition: border-color 0.2s ease-in-out;
        }

        .date-selection-card input[type="date"]:focus {
            border-color: #0d6efd;
            outline: none;
            box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.25);
        }

        .input-icon {
            position: absolute;
            right: 10px;
            pointer-events: none;
            color: #666;
            top: 50%;
            transform: translateY(-50%);
        }

        /* Button group & buttons styling */
        .btn-group {
            display: flex;
        }

        .btn-group .btn {
            min-width: 80px;       /* consistent button width */
            padding-left: 1rem;
            padding-right: 1rem;
            font-weight: 500;
            font-size: 1rem;
            height: 38px;          /* consistent button height */
            border-radius: 5px;    /* optional: subtle rounding */
        }

        #nextAvailableContainer .list-group-item {
            /* Box Container Styling: Elevated & Clean */
            border: none; /* No explicit border, relying on shadow for definition */
            border-radius: 10px; /* Even softer, more prominent rounded corners */
            margin-bottom: 20px; /* Slightly increased space between items for an 'airy' feel */
            padding: 22px 30px; /* More generous padding inside for luxury feel */
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); /* Smoother, more distinct transitions */
            position: relative;
            overflow: hidden; /* Ensures anything outside the border-radius is hidden */

            /* Visuals: Subtle Gradient & Refined Shadow */
            background: linear-gradient(145deg, #ffffff, #f0f2f5); /* Very subtle, cool gradient */
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08); /* More diffuse, premium shadow */

            /* Font Baseline */
            font-family: 'Inter', 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; /* Modern, clean font preference */
            font-size: 0.98rem;
            color: #4a5568; /* A sophisticated dark grey */
        }

        #nextAvailableContainer .list-group-item:hover {
            transform: translateY(-7px); /* More pronounced lift on hover */
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15); /* Stronger shadow on hover */
            cursor: pointer;
        }

        /* Accent Top Border (Alternative to left bar, or combined) */
        /* This will create a thin accent line at the top of the box */
        #nextAvailableContainer .list-group-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px; /* Thin top border */
            background: linear-gradient(90deg, #007bff, #6f42c1); /* A vibrant, modern gradient accent */
            border-top-left-radius: 1px;
            border-top-right-radius: 1px;
            transition: all 0.3s ease-in-out;
            transform: translateY(-100%); /* Start off-screen */
            opacity: 0;
        }

        #nextAvailableContainer .list-group-item:hover::before {
            transform: translateY(0); /* Slide in on hover */
            opacity: 1;
        }

        /* Individual Content Lines within the Box */
        #nextAvailableContainer .list-group-item span {
            display: block;
            margin-bottom: 7px; /* Fine-tuned spacing between lines */
            line-height: 1.5; /* Slightly relaxed line height for readability */
        }

        #nextAvailableContainer .list-group-item span:last-child {
            margin-bottom: 0;
        }

        /* Doctor's Name Styling */
        #nextAvailableContainer .list-group-item span strong {
            color: #2c3e50; /* Darker, more formal grey for the name */
            font-size: 1.3rem; /* Prominent font size for the doctor's name */
            font-weight: 800; /* Extra bold for strong presence */
            letter-spacing: -0.02em; /* Slightly tighter letter spacing for modern look */
            margin-bottom: 8px; /* More space below the main name */
        }

        /* Total Appointments Styling */
        #nextAvailableContainer .list-group-item .text-success {
            color: #14713c !important; /* Brighter, more friendly green */
            font-weight: 600;
            font-size: 1rem; /* Slightly larger for clarity */
            display: flex;
            align-items: center;
            gap: 9px; /* More space between icon and text */
            margin-top: 5px; /* Add a little space if needed after doctor's name */
        }

        /* Next Available Day Styling */
        #nextAvailableContainer .list-group-item .text-primary {
            color: #e67e22 !important; /* A bolder, more commanding orange */
            font-weight: 600;
            font-size: 1rem; /* Slightly larger for clarity */
            display: flex;
            align-items: center;
            gap: 9px;
            margin-top: 5px; /* Add a little space if needed after previous line */
        }

        /* Styling for messages (No data, Error) */
        #nextAvailableContainer .list-group-item.text-muted {
            background-color: #fcfcfc;
            color: #90a4ae; /* Softer muted color */
            text-align: center;
            border: 1px dashed #cfd8dc; /* Dashed border for informational state */
            box-shadow: none;
            padding: 20px;
            margin-top: 15px;
            font-style: italic;
            border-radius: 12px;
        }

        #nextAvailableContainer .list-group-item.text-danger {
            background-color: #ffe0e0; /* Softer red background */
            color: #c0392b; /* Darker, professional red */
            text-align: center;
            border: 1px solid #e74c3c;
            box-shadow: none;
            padding: 20px;
            margin-top: 15px;
            font-weight: 600;
            border-radius: 12px;
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .main-container {
                padding: 15px;
            }
            .date-selection-card {
                margin-bottom: 20px;
            }
            .row.g-3 {
                --bs-gutter-x: 1rem;
                --bs-gutter-y: 1rem;
            }
            .summary-card {
                margin-bottom: 15px;
            }
        }

        @media (max-width: 767.98px) {
            h1 {
                font-size: 1.5rem;
            }
            .section-title {
                font-size: 1rem;
            }
            .card-body {
                padding: 15px;
            }
            .chart-container {
                height: 250px;
            }
            .date-selection-card .card-body.filter-date-line {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .date-selection-card .section-title {
                width: 100%;
                text-align: left;
                margin-right: 0;
                margin-bottom: 5px;
            }
            .btn-group {
                width: 100%;
                justify-content: flex-start;
            }
            .date-input-group {
                width: 100%;
            }
            .date-selection-card input[type="date"] {
                max-width: unset;
                width: 100%;
                font-size: 0.9rem;
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>
<?php include 'header_admin.php'; ?>
<div class="main-container">
    <h1>📊 Admin | Report Dashboard</h1>

    <div class="breadcrumb-container">
        <nav class="ms-auto" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><?php include 'breadcrumb.php'; ?></li>
            </ol>
        </nav>
    </div>

    <div class="card date-selection-card">
        <div class="card-body filter-date-line full-inline-group">
            <h5 class="section-title mb-0 me-3">📆 Select Date Range</h5>

            <label class="me-2 mb-0">Start Date</label>
            <input type="date" id="startDate" class="form-control me-3" style="max-width: 170px;" />

            <label class="me-2 mb-0">End Date</label>
            <input type="date" id="endDate" class="form-control me-3" style="max-width: 170px;" />

            <button class="btn btn-primary me-3" onclick="applyDateRange()">Apply</button>

            <div class="btn-group filter-toggle" role="group">
                <button type="button" class="btn btn-outline-primary filter-btn" data-filter="day">Day</button>
                <button type="button" class="btn btn-outline-primary filter-btn" data-filter="week">Week</button>
                <button type="button" class="btn btn-outline-primary filter-btn" data-filter="month">Month</button>
            </div>
        </div>
    </div>

    <!-- System Monitoring Section (Admin Only) -->
    <div class="card bg-light mb-4">
        <div class="card-header fw-bold">🔧 System Status</div>
        <div class="card-body">
            <?php
            // ✅ Check database connection
            if ($conn) {
                echo '<p>📡 <strong>Database Status:</strong> <span id="dbStatus" class="text-muted">Checking...</span></p>';
            } else {
                echo '<p>📡 <strong>Database Status:</strong> <span id="dbStatus" class="text-muted">Offline</span></p>';
            }

            // ✅ Simulate API status check (in real cases, you could ping an internal endpoint)
            $apiStatus = @file_get_contents('report_ajax.php') !== false ? 'Online' : 'Offline';

            echo '<p>🌐 <strong>API Response:</strong> ';
            echo $apiStatus === 'Online'
                ? '<span id="apiStatus" class="text-muted">Checking...</span>'
                : '<span id="apiStatus" class="text-muted">API not responding</span>';
            echo '</p>';
            ?>

            <p>🕒 <strong>Last Data Sync:</strong> <span id="lastSyncTime">Loading...</span></p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card summary-card">
                <div class="card-body">
                    <h5>Patients</h5>
                    <h3 id="patientsCount"><?= $patients ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card summary-card">
                <div class="card-body">
                    <h5>Doctors</h5>
                    <h3 id="doctorsCount"><?= $doctors ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card summary-card">
                <div class="card-body">
                    <h5>Today's Appointments</h5>
                    <h3 id="appointmentsTodayCount"><?= $appointmentsToday ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card summary-card">
                <div class="card-body">
                    <h5>Completed</h5>
                    <h3 id="completedAppointmentsCount"><?= $completedAppointments ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card summary-card">
                <div class="card-body">
                    <h5>Staff</h5>
                    <h3 id="staffCount"><?= $staff ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card summary-card">
                <div class="card-body">
                    <h5>Room</h5>
                    <h3 id="roomCount"><?= $room ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card summary-card">
                <div class="card-body">
                    <h5>Missed Appointment</h5>
                    <h3 id="missedAppointmentsCount"><?= $missed ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 align-items-start mb-5">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="section-title">📈 Monthly Appointments Overview</h5>
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="section-title">📊 Type of Visits Distribution</h5>
                    <div class="chart-container">
                        <canvas id="visitTypeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="section-title">🩺 Doctors by Total Appointments</h5>
                    <div class="chart-container">
                        <canvas id="doctorChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="section-title">⏱️ Average Wait Time (mins)</h5>
                    <div class="chart-container">
                        <canvas id="waitTimeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="section-title">🩺 Doctor Utilization (%)</h5>
                    <div class="chart-container">
                        <canvas id="utilizationChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="section-title">🩺 Doctor Utilization by Total Minutes Served</h5>
                    <div class="chart-container">
                        <canvas id="doctorUtilizationChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="section-title">📉 No-Show Trends (Monthly Missed Appointments)</h5>
                    <div class="chart-container">
                        <canvas id="noShowChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
    <!-- After doctorUtilizationChart and noShowChart -->
    <div class="row g-3">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="section-title">🔮 Next Available Appointment Day (Per Doctor)</h5>
                    <div id="nextAvailableContainer" class="list-group">
                        <!-- JS will insert doctor availability here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        const filterButtons = document.querySelectorAll('.filter-btn');

        // Set default values for date inputs to current date
        const today = new Date();
        const formatDate = d => d.toISOString().split('T')[0];
        startDateInput.value = formatDate(today);
        endDateInput.value = formatDate(today);

        // Function to set the active filter button
        function setActiveFilterButton(filterType) {
            filterButtons.forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
                if (btn.dataset.filter === filterType) {
                    btn.classList.remove('btn-outline-primary');
                    btn.classList.add('btn-primary');
                }
            });
        }

        // Initially set 'Day' as active
        setActiveFilterButton('day');

        // Initial data fetch on page load using default dates (today)
        fetchData(startDateInput.value, endDateInput.value);

        // Event listeners for date input changes
        startDateInput.addEventListener('change', function() {
            setActiveFilterButton('custom'); // Mark as custom when dates are changed manually
            applyDateRange();
        });

        endDateInput.addEventListener('change', function() {
            setActiveFilterButton('custom'); // Mark as custom when dates are changed manually
            applyDateRange();
        });

        // Event listeners for filter buttons
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                setActiveFilterButton(this.dataset.filter);

                const today = new Date();
                let startDate = new Date(today);
                let endDate = new Date(today);

                if (this.dataset.filter === 'day') {
                    // Already set to today
                } else if (this.dataset.filter === 'week') {
                    startDate.setDate(today.getDate() - 6);
                } else if (this.dataset.filter === 'month') {
                    startDate.setDate(1); // First day of current month
                }

                startDateInput.value = formatDate(startDate);
                endDateInput.value = formatDate(endDate);

                fetchData(startDateInput.value, endDateInput.value, this.dataset.filter);
            });
        });

        // Function to apply manual date range from input fields
        window.applyDateRange = function() {
            const start = startDateInput.value;
            const end = endDateInput.value;
            setActiveFilterButton('custom'); // Ensure custom button is not active if manual date is selected
            if (start && end) {
                fetchData(start, end, 'custom');
            }
        }


        // Function to fetch data via AJAX and update dashboard elements
        function fetchData(startDate, endDate, filterType = 'custom') {
            $.ajax({
                url: 'report_ajax.php',
                method: 'POST',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    filter_type: filterType
                },
                dataType: 'json',
                success: function (data) {
                    // Update summary cards that depend on date range
                    $('#appointmentsTodayCount').text(data.appointmentsToday);
                    $('#completedAppointmentsCount').text(data.completedAppointments);
                    $('#missedAppointmentsCount').text(data.missed);

                    // Update charts that depend on date range
                    updateChart(window.visitTypeChart, Object.keys(data.visit_types), Object.values(data.visit_types));
                    updateChart(window.doctorChart, Object.keys(data.top_doctors_by_appointments), Object.values(data.top_doctors_by_appointments));
                    updateChart(window.waitTimeChart, Object.keys(data.avg_wait_times), Object.values(data.avg_wait_times));
                    updateChart(window.utilizationChart, Object.keys(data.doctor_utilization_percent), Object.values(data.doctor_utilization_percent));
                    updateChart(window.doctorUtilizationChart, Object.keys(data.doctor_utilization_minutes), Object.values(data.doctor_utilization_minutes));

                    // Monthly Appointments Overview and No-Show Trends remain full year, so their data is NOT updated by this AJAX call
                    // Instead, they use the initial PHP data which covers the full current year.
                    // If monthlyChart should also filter, you'd uncomment and adjust this:
                    updateChart(window.monthlyChart, data.monthly_labels, data.monthly_data); // This line is correct to update it based on report_ajax.php's current year data
                },
                error: function (xhr) {
                    console.error("AJAX error:", xhr.responseText);
                }
            });
        }

        // Helper function to update Chart.js instances
        function updateChart(chart, labels, data) {
            chart.data.labels = labels;
            chart.data.datasets[0].data = data;
            chart.update();
        }

        // --- Chart.js Initializations ---
        // Charts that depend on the date range should be initialized with data for 'today' (default view)
        // Charts that show full year data (monthly, no-show) are initialized with PHP data for the current year.

        window.monthlyChart = new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($monthlyDataLabels) ?>, // Labels are fixed for monthly
                datasets: [{
                    label: 'Appointments',
                    data: <?= json_encode($monthlyData) ?>, // Initial data from PHP (full current year)
                    borderColor: '#42a5f5',
                    backgroundColor: 'rgba(66, 165, 245, 0.2)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#42a5f5',
                    pointBorderColor: '#fff',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#42a5f5',
                    pointHoverBorderColor: '#fff',
                    pointHitRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 10,
                            padding: 20
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: "rgba(0, 0, 0, 0.05)" },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });

        window.visitTypeChart = new Chart(document.getElementById('visitTypeChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($initialVisitTypesLabels) ?>,
                datasets: [{
                    data: <?= json_encode($initialVisitTypesData) ?>,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                    hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#f0b72a', '#e02d1d'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 10,
                            padding: 15,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) label += ': ';
                                if (context.parsed !== null) label += context.parsed;
                                return label;
                            }
                        }
                    }
                },
                cutout: '70%',
            }
        });

        window.doctorChart = new Chart(document.getElementById('doctorChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($initialDoctorNames) ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?= json_encode($initialDoctorCounts) ?>,
                    backgroundColor: '#82b1ff',
                    hoverBackgroundColor: '#42a5f5',
                    borderColor: '#64b5f6',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) label += context.parsed.y;
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: "rgba(0, 0, 0, 0.05)" },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });

        window.waitTimeChart = new Chart(document.getElementById('waitTimeChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($initialWaitLabels) ?>,
                datasets: [{ label: 'Avg Wait Time', data: <?= json_encode($initialWaitData) ?>, backgroundColor: '#ffcc80' }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        window.utilizationChart = new Chart(document.getElementById('utilizationChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($initialUtilLabels) ?>,
                datasets: [{ label: 'Utilization (%)', data: <?= json_encode($initialUtilData) ?>, backgroundColor: '#81c784' }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true, max: 100 } } }
        });

        window.doctorUtilizationChart = new Chart(document.getElementById('doctorUtilizationChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($initialDoctorLabels) ?>,
                datasets: [{
                    label: 'Doctor Utilization (Minutes Served)',
                    data: <?= json_encode($initialDoctorData) ?>,
                    backgroundColor: '#4caf50'
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Doctor Utilization Summary'
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });

        window.noShowChart = new Chart(document.getElementById('noShowChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($noShowLabels) ?>,
                datasets: [{
                    label: 'Missed Appointments',
                    data: <?= json_encode($noShowData) ?>,
                    borderColor: '#ef5350',
                    backgroundColor: 'rgba(239,83,80,0.2)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#ef5350',
                    pointBorderColor: '#fff',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#ef5350',
                    pointHoverBorderColor: '#fff',
                    pointHitRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 10,
                            padding: 20
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: "rgba(0, 0, 0, 0.05)" },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });

    }); // End of DOMContentLoaded

    function fetchNextAvailableDays() {
        fetch('admin_next_doctor_available_day.php')
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('nextAvailableContainer');
                container.innerHTML = '';  // Clear previous content

                if (data.length === 0) {
                    container.innerHTML = '<div class="list-group-item text-muted">No data available</div>';
                    return;
                }

                // Loop through each doctor and create clickable elements
                data.forEach(item => {
                    // Log doctor_id to verify it's being passed
                    console.log("Doctor ID:", item.doctor_id);
                    // Log the full next_available_day string from PHP response
                    console.log("Next Available Day (from PHP):", item.next_available_day);
                    // Log available minutes (should be present from PHP modification)
                    console.log("Available Minutes (from PHP):", item.available_minutes);

                    // Create the div element for the doctor
                    const div = document.createElement('div');
                    div.className = 'list-group-item';
                    div.setAttribute('data-doctor-id', item.doctor_id);

                    // The next_available_day string might contain "YYYY-MM-DD (X mins available)"
                    // We need to extract just the YYYY-MM-DD part for the URL 'date' parameter
                    const dateMatch = item.next_available_day.match(/^(\d{4}-\d{2}-\d{2})/);
                    let selectedDateForUrl = '';
                    if (dateMatch && dateMatch[1]) {
                        selectedDateForUrl = dateMatch[1];
                    } else {
                        // Fallback or error handling if date format is not as expected
                        console.error('Could not extract date from next_available_day:', item.next_available_day);
                        // If no valid date can be extracted, you might want to skip this item or show an error
                        return;
                    }

                    // Store the extracted date and available minutes in data attributes
                    // so they are easily accessible in the click event listener
                    div.setAttribute('data-selected-date', selectedDateForUrl);
                    div.setAttribute('data-available-minutes', item.available_minutes); // Ensure PHP sends this

                    // Set the inner HTML of the div
                    div.innerHTML = `
                    <span>👨‍⚕️ <strong>${item.doctor}</strong></span>
                    <span class="text-success">🗂️ Total Appointments: ${item.total_appointments}</span>
                    <span class="text-primary">📅 ${item.next_available_day}</span>
                `;

                    // Event listener for redirecting to the doctor’s appointment page
                    div.addEventListener('click', () => {
                        const doctorId = div.getAttribute('data-doctor-id');
                        const dateToPass = div.getAttribute('data-selected-date'); // Use the extracted date
                        const availableMinutesToPass = div.getAttribute('data-available-minutes'); // Use available minutes

                        // Construct the URL with doctorId, date, and available_minutes
                        const redirectUrl = 'admin_view_doctor_appointments.php?' +
                            'doctor_id=' + encodeURIComponent(doctorId) +
                            '&date=' + encodeURIComponent(dateToPass) +
                            '&available_minutes=' + encodeURIComponent(availableMinutesToPass);

                        console.log("Redirecting to:", redirectUrl); // Log the final URL
                        window.location.href = redirectUrl;
                    });

                    container.appendChild(div);  // Append the doctor div to the container
                });
            })
            .catch(err => {
                console.error('❌ Failed to fetch next available days:', err);
                const container = document.getElementById('nextAvailableContainer');
                container.innerHTML = '<div class="list-group-item text-danger">Error loading data</div>';
            });
    }

    fetchNextAvailableDays();  // Call this function on page load

    function updateSystemStatus() {
        fetch('status.php')
            .then(res => res.json())
            .then(data => {
                const dbEl = document.getElementById('dbStatus');
                const apiEl = document.getElementById('apiStatus');
                const syncEl = document.getElementById('lastSyncTime');

                dbEl.innerText = data.db;
                dbEl.className = data.db === 'Online' ? 'text-success' : 'text-danger';

                apiEl.innerText = data.api === 'Online' ? 'All APIs working' : 'API not responding';
                apiEl.className = data.api === 'Online' ? 'text-success' : 'text-danger';

                syncEl.innerText = data.lastSync;
            })
            .catch(err => {
                document.getElementById('dbStatus').innerText = 'Error';
                document.getElementById('apiStatus').innerText = 'Error';
                document.getElementById('lastSyncTime').innerText = 'Unavailable';
            });
    }

    // Initial load
    updateSystemStatus();

    // Refresh every 2 minutes (120,000ms)
    setInterval(updateSystemStatus, 120000);
</script>
</body>
</html>
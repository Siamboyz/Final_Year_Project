<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: homepage.php");
    exit();
}

date_default_timezone_set('Asia/Kuala_Lumpur');

$today = date("Y-m-d");

$patients = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM patient"))[0];
$doctors = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM doctor"))[0];
$appointmentsToday = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM appointment WHERE DATE(apt_date) = CURDATE()"))[0];
$completedAppointments = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM appointment WHERE apt_status = 'Completed'"))[0];
$staff = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) AS total_staff FROM counter_staff"))[0];
$room = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) AS total_rooms FROM room"))[0];
$missed = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) AS missed FROM appointment WHERE apt_date = '$today' AND apt_status = 'Missed'"))[0];

$monthlyData = [];
for ($i = 1; $i <= 12; $i++) {
    $res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointment WHERE MONTH(apt_date) = $i AND YEAR(apt_date) = YEAR(CURDATE())");
    $monthlyData[] = mysqli_fetch_array($res)['total'];
}

$visitTypes = [];
$res = mysqli_query($conn, "SELECT visit_type, COUNT(*) as total FROM appointment GROUP BY visit_type");
while ($row = mysqli_fetch_assoc($res)) {
    $visitTypes[$row['visit_type']] = $row['total'];
}

// Prepare labels and data for visit types for Chart.js
$visitTypesLabels = array_keys($visitTypes);
$visitTypesData = array_values($visitTypes);

$doctorNames = [];
$doctorCounts = [];
$res = mysqli_query($conn, "
    SELECT d.name, COUNT(a.apt_id) AS total
    FROM doctor d
    LEFT JOIN appointment a ON a.doctor_id = d.doctor_id
    GROUP BY d.doctor_id
    ORDER BY total DESC
    LIMIT 5
");
while ($row = mysqli_fetch_assoc($res)) {
    $doctorNames[] = $row['name'];
    $doctorCounts[] = $row['total'];
}

// New Enhancement Data
$waitLabels = $waitData = [];
$res = mysqli_query($conn, "
    SELECT visit_type, AVG(TIMESTAMPDIFF(MINUTE, validated_datetime, CONCAT(apt_date, ' ', apt_time))) AS avg_wait
    FROM appointment
    WHERE validated_datetime IS NOT NULL AND apt_time IS NOT NULL
    GROUP BY visit_type
");
while ($row = mysqli_fetch_assoc($res)) {
    $waitLabels[] = $row['visit_type'];
    $waitData[] = round($row['avg_wait'], 1);
}

$utilLabels = $utilData = [];
$res = mysqli_query($conn, "
    SELECT d.name, SUM(a.duration_minutes) AS total_minutes
    FROM doctor d
    LEFT JOIN appointment a ON d.doctor_id = a.doctor_id
    GROUP BY d.doctor_id
");
while ($row = mysqli_fetch_assoc($res)) {
    $utilLabels[] = $row['name'];
    $utilData[] = round(($row['total_minutes'] / 480) * 100, 1);
}

$doctorLabels = $doctorData = [];
$res = mysqli_query($conn, "
    SELECT d.name, SUM(a.duration_minutes) AS total_minutes
    FROM doctor d
    LEFT JOIN appointment a ON d.doctor_id = a.doctor_id
    GROUP BY d.doctor_id
");
while ($row = mysqli_fetch_assoc($res)) {
    $doctorLabels[] = $row['name'];
    $doctorData[] = $row['total_minutes'] ?? 0;
}

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
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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


    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card summary-card">
                <div class="card-body">
                    <h5>Patients</h5>
                    <h3><?= $patients ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card summary-card">
                <div class="card-body">
                    <h5>Doctors</h5>
                    <h3><?= $doctors ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card summary-card">
                <div class="card-body">
                    <h5>Today's Appointments</h5>
                    <h3><?= $appointmentsToday ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card summary-card">
                <div class="card-body">
                    <h5>Completed</h5>
                    <h3><?= $completedAppointments ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card summary-card">
                <div class="card-body">
                    <h5>Staff</h5>
                    <h3><?= $staff ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card summary-card">
                <div class="card-body">
                    <h5>Room</h5>
                    <h3><?= $room ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card summary-card">
                <div class="card-body">
                    <h5>Missed Appointment</h5>
                    <h3><?= $missed ?></h3>
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
                    <h5 class="section-title">⭐ Top 5 Doctors by Appointments</h5>
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
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const startDateInput = document.getElementById('startDate'); // Corrected ID
        const endDateInput = document.getElementById('endDate');     // Corrected ID
        const filterButtons = document.querySelectorAll('.filter-btn');

        // Set default values for date inputs to current date
        const today = new Date();
        const formatDate = d => d.toISOString().split('T')[0];
        startDateInput.value = formatDate(today);
        endDateInput.value = formatDate(today);

        let currentFilterType = 'custom'; // Default filter type

        // Initial data fetch on page load using default dates
        fetchData(startDateInput.value, endDateInput.value, currentFilterType);

        // Event listeners for date input changes
        startDateInput.addEventListener('change', function() {
            // When a date input changes, reset filter type to custom
            currentFilterType = 'custom';
            // Remove active class from filter buttons
            filterButtons.forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
            });
            applyDateRange(); // Re-apply date range
        });

        endDateInput.addEventListener('change', function() {
            // When a date input changes, reset filter type to custom
            currentFilterType = 'custom';
            // Remove active class from filter buttons
            filterButtons.forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
            });
            applyDateRange(); // Re-apply date range
        });

        // Event listeners for filter buttons
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Update active class
                filterButtons.forEach(btn => {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-primary');
                });
                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-primary');

                currentFilterType = this.dataset.filter; // Update current filter (day, week, month)

                // Adjust date inputs based on selected filter
                const today = new Date();
                let startDate = new Date(today);
                let endDate = new Date(today);

                if (currentFilterType === 'day') {
                    // Already set to today
                } else if (currentFilterType === 'week') {
                    startDate.setDate(today.getDate() - 6);
                } else if (currentFilterType === 'month') {
                    startDate.setDate(1); // Set to the first day of the current month
                }
                // For a "month" filter, it's often more intuitive to show data for the *current* month
                // If the user meant "last 30 days" for month, the initial logic was fine,
                // but "month" usually implies calendar month. I'll stick to calendar month.

                startDateInput.value = formatDate(startDate);
                endDateInput.value = formatDate(endDate);

                fetchData(startDateInput.value, endDateInput.value, currentFilterType); // Fetch data with new filter
            });
        });

        // Function to apply manual date range from input fields
        window.applyDateRange = function() {
            const start = startDateInput.value;
            const end = endDateInput.value;
            currentFilterType = 'custom'; // Ensure filter type is custom
            // Remove active class from filter buttons when applying custom date range
            filterButtons.forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
            });
            if (start && end) {
                fetchData(start, end, currentFilterType);
            }
        }


        // Function to fetch data via AJAX and update dashboard elements
        function fetchData(startDate, endDate, filterType) {
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
                    // Update summary cards
                    $('#patientsCount').text(data.patients); // Assuming you add IDs to summary card h3s
                    $('#doctorsCount').text(data.doctors);
                    $('#appointmentsTodayCount').text(data.appointmentsToday);
                    $('#completedAppointmentsCount').text(data.completedAppointments);
                    $('#staffCount').text(data.staff);
                    $('#roomCount').text(data.room);
                    $('#missedAppointmentsCount').text(data.missed);


                    updateChart(window.visitTypeChart, Object.keys(data.visit_types), Object.values(data.visit_types));
                    updateChart(window.doctorChart, Object.keys(data.doctors), Object.values(data.doctors));
                    updateChart(window.waitTimeChart, Object.keys(data.avg_wait_times), Object.values(data.avg_wait_times));
                    updateChart(window.utilizationChart, Object.keys(data.doctor_utilization), Object.values(data.doctor_utilization));
                    updateChart(window.doctorUtilizationChart, Object.keys(data.doctor_Utilization_minutes), Object.values(data.doctor_Utilization_minutes)); // Corrected variable name as per PHP for minutes
                    updateChart(window.monthlyChart, data.monthly_labels, data.monthly_data); // Assuming monthly data also comes from AJAX
                    updateChart(window.noShowChart, data.no_show_labels, data.no_show_data); // Assuming no-show data also comes from AJAX

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
        window.monthlyChart = new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($monthlyData) ?>, // Initial data from PHP
                datasets: [{
                    label: 'Booked',
                    data: <?= json_encode($monthlyData) ?>, // Initial data from PHP
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
                labels: <?= json_encode($visitTypesLabels) ?>,
                datasets: [{
                    data: <?= json_encode($visitTypesData) ?>,
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
                labels: <?= json_encode($doctorNames) ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?= json_encode($doctorCounts) ?>,
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

        // The monthly chart initialization is already above, moved here to ensure consistency
        // No need to re-declare it, just ensure it's defined once.
        // If you intend for this chart to also be updated by AJAX, then its initial labels/data
        // should also come from the PHP and then be updated via updateChart.

        window.waitTimeChart = new Chart(document.getElementById('waitTimeChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($waitLabels) ?>, // Initial data from PHP
                datasets: [{ label: 'Avg Wait Time', data: <?= json_encode($waitData) ?>, backgroundColor: '#ffcc80' }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        window.utilizationChart = new Chart(document.getElementById('utilizationChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($utilLabels) ?>, // Initial data from PHP
                datasets: [{ label: 'Utilization (%)', data: <?= json_encode($utilData) ?>, backgroundColor: '#81c784' }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true, max: 100 } } }
        });

        window.doctorUtilizationChart = new Chart(document.getElementById('doctorUtilizationChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($doctorLabels) ?>, // Initial data from PHP
                datasets: [{
                    label: 'Doctor Utilization (Minutes Served)',
                    data: <?= json_encode($doctorData) ?>,
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
                labels: <?= json_encode($noShowLabels) ?>, // Initial data from PHP
                datasets: [{
                    label: 'Missed Appointments',
                    data: <?= json_encode($noShowData) ?>, // Initial data from PHP
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
</script>
</body>
</html>
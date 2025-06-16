<?php
// Start the session at the very beginning of the PHP script
session_start();

// Include your database connection file.
// Make sure 'connection_database.php' is correctly configured and accessible.
include 'connection_database.php';

// Check if the user is logged in and has the correct role.
// If not, redirect to the homepage and exit.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counter_staff') {
    header("Location: homepage.php");
    exit();
}

// Fetch incomplete patient profiles from the database.
// The query selects patient_id, name, and no_ic for patients where 'profile_completed' is 0,
// ordered by their registration datetime in ascending order.
$incomplete_patients_query = mysqli_query($conn, "SELECT patient_id, name, no_ic FROM patient WHERE profile_completed = 0 ORDER BY registered_datetime ASC");
$incomplete_patients = []; // Initialize an empty array to store the fetched patient data.

// Check if the query was successful.
if ($incomplete_patients_query) {
    // If successful, loop through the results and add each row to the $incomplete_patients array.
    while ($row = mysqli_fetch_assoc($incomplete_patients_query)) {
        $incomplete_patients[] = $row;
    }
} else {
    // If the query failed, log the error to the server's error log and display a generic error message to the user.
    error_log("Error fetching incomplete patient profiles: " . mysqli_error($conn));
    echo "<p class='error-message'>An error occurred while fetching data. Please try again later.</p>";
}

// Optionally, close the database connection here if you don't need it further on this page.
// mysqli_close($conn);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Patient Incomplete Profiles</title>
    <style>
        /* Base styles for the body, updated with user's font, background, and color */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        /* Container for the sidebar and the main content area */
        .content-flex-wrapper {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .content-flex-wrapper {
            padding: 10px 30px;
            display: flex;
            flex-direction: column;
            background-color: white;
            text-align: justify;
            margin-left: calc(100% - 80%);
            max-width: 100%; /* Limits the content width */
        }

        /* Styles for the main content area (formerly .main-content-area),
           adapted from user's .main-container and previous .main-content-area */
        .main-panel { /* Renamed for clarity to avoid conflict with 'main-container' idea */
            flex-grow: 1; /* Allows the main content to fill remaining horizontal space */
            padding: 10px 30px; /* User's requested padding */
            display: flex; /* User's requested display */
            flex-direction: column; /* User's requested flex direction */
            background-color: #f9f9f9; /* Keep body background for main panel area */
            text-align: justify; /* User's requested text alignment */
            margin-left: 20%; /* Pushes content right to clear the 20% width sidebar */
            max-width: 100%; /* User's requested max-width */
            min-height: calc(100vh - 66px); /* Ensure it takes full height minus header */
            box-sizing: border-box; /* Include padding in width/height calculations */
        }

        /* Styles for individual content sections (the white card containing the table),
           adapted from user's first .main-container definition and previous .content-section */
        .content-section {
            padding: 20px; /* User's requested padding */
            width: 100%; /* Ensure it doesn't overflow its container */
            margin: 0 auto; /* Center the content section within its container */
        }

        /* Styles for breadcrumbs */
        .breadcrumb {
            font-size: 14px;
            color: #666;
            margin-bottom: 1.5rem; /* Spacing below the breadcrumb */
            text-align: right;
        }

        /* Custom styles for tables (borders, shadows, etc.) not fully covered by Tailwind */
        .table-container {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 0.75rem; /* Equivalent to rounded-xl */
            overflow: hidden; /* Ensures rounded corners apply to content within */
            border: 1px solid #e2e8f0; /* Light border for the container */
            background-color: white; /* Equivalent to bg-white */
        }
        table {
            min-width: 100%; /* Equivalent to min-w-full */
            border-collapse: collapse; /* For clean table borders */
            width: 100%;
        }
        table thead {
            background-color: #f9fafb; /* Equivalent to bg-gray-50 */
        }
        th, td {
            border-bottom: 1px solid #e2e8f0; /* Light border for rows, equivalent to divide-y */
            padding: 0.75rem 1.5rem; /* Equivalent to px-6 py-3 for th */
            text-align: left; /* Equivalent to text-left */
            font-size: 0.75rem; /* Equivalent to text-xs for th */
            font-weight: 500; /* Equivalent to font-medium for th */
            color: #6b7280; /* Equivalent to text-gray-500 for th */
            text-transform: uppercase; /* Equivalent to uppercase */
            letter-spacing: 0.05em; /* Equivalent to tracking-wider */
        }
        td {
            padding: 1rem 1.5rem; /* Equivalent to px-6 py-4 for td */
            font-size: 0.875rem; /* Equivalent to text-sm for td */
            white-space: nowrap; /* Equivalent to whitespace-nowrap */
            color: #111827; /* Default text color for td, equivalent to text-gray-900 */
        }
        td.font-medium-text { /* Specific class for font-medium text in td (Patient Name) */
            font-weight: 500;
        }
        td.text-gray-500-text { /* Specific class for gray-500 text in td (IC Number) */
            color: #6b7280;
        }
        tr:last-child td {
            border-bottom: none; /* No bottom border for the last row */
        }

        /* Button styling */
        .btn-complete {
            display: inline-flex; /* Equivalent to inline-flex */
            align-items: center; /* Equivalent to items-center */
            padding: 0.5rem 1rem; /* Equivalent to px-4 py-2 */
            border: 1px solid transparent; /* Equivalent to border border-transparent */
            font-size: 0.875rem; /* Equivalent to text-sm */
            font-weight: 500; /* Equivalent to font-medium */
            border-radius: 0.375rem; /* Equivalent to rounded-md */
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* Equivalent to shadow-sm */
            color: #fff; /* Equivalent to text-white */
            background-color: #2563eb; /* Equivalent to bg-blue-600 */
            transition: background-color 150ms ease-in-out; /* Equivalent to transition ease-in-out duration-150 */
            text-decoration: none; /* Ensure it looks like a button, not a default link */
        }
        .btn-complete:hover {
            background-color: #1d4ed8; /* Equivalent to hover:bg-blue-700 */
        }
        .btn-complete:focus {
            outline: none; /* Equivalent to focus:outline-none */
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #3b82f6; /* Equivalent to focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 */
        }

        /* All patient profiles completed message */
        .all-profiles-completed {
            color: #16a34a; /* Equivalent to text-green-600 */
            font-weight: 600; /* Equivalent to font-semibold */
            font-size: 1.125rem; /* Equivalent to text-lg */
            display: flex; /* Equivalent to flex */
            align-items: center; /* Equivalent to items-center */
            justify-content: center; /* Equivalent to justify-center */
            padding-top: 1rem; /* Equivalent to py-4 */
            padding-bottom: 1rem;
        }
        .all-profiles-completed .icon {
            margin-right: 0.5rem; /* Equivalent to mr-2 */
            color: #22c55e; /* Equivalent to text-green-500 */
            font-size: 1.25rem; /* Equivalent to text-xl */
        }

        /* Error message styling */
        .error-message {
            color: #dc2626; /* Equivalent to text-red-600 */
            font-weight: bold; /* Equivalent to font-bold */
            padding: 1rem; /* Equivalent to p-4 */
        }
        .page-heading {
            font-size: 1.875rem; /* Equivalent to text-3xl */
            font-weight: bold; /* Equivalent to font-bold */
            color: #dc2626; /* Equivalent to text-red-600 */
            margin-bottom: 1.5rem; /* Equivalent to mb-6 */
            display: flex; /* Equivalent to flex */
            align-items: center; /* Equivalent to items-center */
            justify-content: center; /* Equivalent to justify-center */
        }
        .page-heading .icon {
            margin-right: 0.75rem; /* Equivalent to mr-3 */
            color: #ef4444; /* Equivalent to text-red-500 */
            font-size: 2.25rem; /* Equivalent to text-4xl */
        }

    </style>
</head>
<body>
<!-- Include the header (which also contains the fixed sidebar structure) -->
<?php include 'header_staff.php'; ?>

<!-- Wrapper for the main content area to position it correctly next to the fixed sidebar -->
<div class="content-flex-wrapper">
        <h1 class="page-heading">
            <span class="icon">⚠</span> Patients with Incomplete Profile
        </h1>
        <!-- Breadcrumb (assuming breadcrumb.php generates actual content) -->
        <?php include 'breadcrumb.php'; ?>

        <section class="content-section">
            <?php if (count($incomplete_patients) > 0) : ?>
                <div class="table-container">
                    <table>
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Patient Name</th>
                            <th>IC Number</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($incomplete_patients as $index => $patient) : ?>
                            <tr>
                                <td>
                                    <?= $index + 1 ?>
                                </td>
                                <td class="font-medium-text">
                                    <?= htmlspecialchars($patient['name']) ?>
                                </td>
                                <td class="text-gray-500-text">
                                    <?= htmlspecialchars($patient['no_ic']) ?>
                                </td>
                                <td>
                                    <a href="staff_complete_profile.php?id=<?= $patient['patient_id'] ?>"
                                       class="btn-complete">
                                        Complete Profile
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p class="all-profiles-completed">
                    <span class="icon">✅</span> All patient profiles are completed!
                </p>
            <?php endif; ?>
        </section>
</div>
</body>
</html>

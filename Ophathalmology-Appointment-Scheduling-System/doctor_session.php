<?php
session_start();
include 'connection_database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: homepage.php");
    exit();
}

$doctor_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

if (!empty($search)) {
    $query = "SELECT * FROM session 
              WHERE doctor_id = $doctor_id 
              AND (s_date LIKE '%$search%' OR s_status LIKE '%$search%') 
              ORDER BY s_date DESC, s_starttime ASC";
} else {
    $query = "SELECT * FROM session 
              WHERE doctor_id = $doctor_id 
              ORDER BY s_date DESC, s_starttime ASC";
}

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Schedule</title>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
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

        h1 {
            font-size: 1.8rem;
            color: #007bff;
        }

        .breadcrumb {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
            float: right;
            text-align: right;
        }

        .search-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .search-container input[type="text"] {
            width: 300px;
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .search-container button {
            padding: 8px 12px;
            margin-left: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .search-container button:hover {
            background-color: #218838;
        }

        .add-session-button {
            background-color: #007bff;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .add-session-button:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #f4f4f4;
        }

        tr:hover {
            background-color: #f0f8ff;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 8px;
            color: white;
            font-size: 12px;
        }

        .actionicon{
            color: #007bff;
            font-size: 20px;
        }

        .available { background-color: green; }
        .unavailable { background-color: red; }
        .onleave { background-color: orange; }
    </style>
</head>
<body>
<?php include 'header_doc.php'; ?>

<div class="main-container">
    <h1>Doctor | Manage Availability</h1>
    <?php include 'breadcrumb.php'; ?>

    <br>
    <div class="search-container">
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search by date or status..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
        </form>
        <a href="doctor_set_availability.php"><button class="add-session-button">Add Availability</button></a>
    </div>

    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $counter = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            $statusClass = '';
            switch (strtolower($row['s_status'])) {
                case 'available': $statusClass = 'available'; break;
                case 'unavailable': $statusClass = 'unavailable'; break;
                case 'on leave': $statusClass = 'onleave'; break;
                default: $statusClass = ''; break;
            }

            echo "<tr>";
            echo "<td>" . $counter++ . "</td>";
            echo "<td>" . $row['s_date'] . "</td>";
            echo "<td>" . $row['s_starttime'] . "</td>";
            echo "<td>" . $row['s_endtime'] . "</td>";
            echo "<td><span class='badge $statusClass'>" . $row['s_status'] . "</span></td>";
            echo "<td class='actionicon'>
                    <a href='doctor_edit_session.php?id=" . $row['session_id'] . "'><i class='fas fa-edit'></i></a> |
                    <a href='doctor_delete_session.php?id=" . $row['session_id'] . "' onclick=\"return confirm('Are you sure you want to delete this session?')\"><i class='fas fa-trash'></i></a>
                  </td>";
            echo "</tr>";
        }

        if (mysqli_num_rows($result) === 0) {
            echo "<tr><td colspan='6'>No sessions found.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>

<?php

$pageName = basename($_SERVER['PHP_SELF']); // e.g. "staff_patient_detail.php"
$breadcrumbTitle = "";

switch ($pageName) {
    case "staff_dashboard.php":
        $breadcrumbTitle = "Counter staff / Dashboard";
        break;
    case "staff_patient.php":
        $breadcrumbTitle = "Counter staff / Search Patient";
        break;
    case "staff_add_patient.php":
        $breadcrumbTitle = "Counter staff / Add Patient";
        break;
    case "staff_patient_detail.php":
        $breadcrumbTitle = "Counter staff / Patient Details";
        break;
    case "staff_register_returning.php":
        $breadcrumbTitle = "Counter staff / Register Returning";
        break;
    case "staff_appointment.php":
        $breadcrumbTitle = "Counter staff / Appointment";
        break;
    case "staff_queue.php":
        $breadcrumbTitle = "Counter staff / Queue Monitoring";
        break;
    case "staff_remake_appointment.php":
        $breadcrumbTitle = "Counter staff / Re-Schedule Appointment";
        break;
    case "staff_register_emergency.php":
        $breadcrumbTitle = "Counter staff / Emergency Registration";
        break;
    case "staff_complete_profile.php":
        $breadcrumbTitle = "Counter staff / Complete Profile";
        break;
    case "staff_profile.php":
        $breadcrumbTitle = "Counter staff / My Profile";
        break;
    case "patient_incomplete_profile.php":
        $breadcrumbTitle = "Counter staff / Incompleted Profile";
        break;
    case "doctor_session.php":
        $breadcrumbTitle = "Doctor / Manage Session";
        break;
    case "doctor_set_availability.php":
        $breadcrumbTitle = "Doctor / Set Session";
        break;
    case "doctor_edit_session.php":
        $breadcrumbTitle = "Doctor / Edit Session";
        break;
    case "doctor_view_appointment.php":
        $breadcrumbTitle = "Doctor / Appointment View";
        break;
    case "doctor_serve_appointment.php":
        $breadcrumbTitle = "Doctor / Curent Appointment";
        break;
    case "doctor_profile.php":
        $breadcrumbTitle = "Doctor / My Profile";
        break;
    case "doctor_patient.php":
        $breadcrumbTitle = "Doctor / Patients";
        break;
    case "doctor_patient_detail.php":
        $breadcrumbTitle = "Doctor / Patient Details";
        break;
    case "admin_manage_doctor.php":
        $breadcrumbTitle = "Admin / Manage Doctor";
        break;
    case "admin_add_doctor.php":
        $breadcrumbTitle = "Admin / Add Doctor";
        break;
    case "admin_edit_doctor.php":
        $breadcrumbTitle = "Admin / Edit Doctor";
        break;
    case "admin_manage_staff.php":
        $breadcrumbTitle = "Admin / Manage Staff";
        break;
    case "admin_edit_staff.php":
        $breadcrumbTitle = "Admin / Edit Staff";
        break;
    case "admin_view_patient.php":
        $breadcrumbTitle = "Admin / Patient";
        break;
    case "admin_view_patientDetail.php":
        $breadcrumbTitle = "Admin / Patient Profile";
        break;
    case "admin_view_appointment.php":
        $breadcrumbTitle = "Admin / Appointment Patient";
        break;
    case "admin_manage_room.php":
        $breadcrumbTitle = "Admin / Manage Room";
        break;
    case "admin_add_room.php":
        $breadcrumbTitle = "Admin / Add Room";
        break;
    case "admin_edit_room.php":
        $breadcrumbTitle = "Admin / Edit Room";
        break;
    case "admin_doctor_requests.php":
        $breadcrumbTitle = "Admin / Requests Account Doctor";
        break;
    case "admin_report.php":
        $breadcrumbTitle = "Admin / Report";
        break;
    case "admin_monitoring.php":
        $breadcrumbTitle = "Admin / Queue Monitoring";
        break;
    default:
        $breadcrumbTitle = "Counter staff / Dashboard";
}
?>

<div class="breadcrumb"><?= $breadcrumbTitle ?></div>


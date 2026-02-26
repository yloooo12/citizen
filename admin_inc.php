<?php
session_start();

// Allow admin and teachers
if (!isset($_SESSION["is_admin"]) && (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'teacher')) {
    header("Location: login.php");
    exit;
}

$is_teacher = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher';
$teacher_name = $is_teacher ? $_SESSION['last_name'] . ', ' . $_SESSION['first_name'] : '';

// Logout handler
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// DB connection
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "student_services";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// AJAX handlers first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_student_approve'])) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $subject = 'Final Exam Details';
    $success = false;
    if ($email && $message && $id > 0) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'yloludovice709@gmail.com';
            $mail->Password = 'byvumtkpzqysysvy';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $mail->setFrom('yloludovice709@gmail.com', 'LSPU-CCS Admin');
            $mail->addAddress($email);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->send();
            $success = true;
            $update_stmt = $conn->prepare("UPDATE inc_requests SET approved=1 WHERE id=?");
            $update_stmt->bind_param("i", $id);
            $update_stmt->execute();
            $update_stmt->close();
            $notif_idnum = '';
            $get_id_stmt = $conn->prepare("SELECT student_id FROM inc_requests WHERE id=?");
            $get_id_stmt->bind_param("i", $id);
            $get_id_stmt->execute();
            $get_id_stmt->bind_result($notif_idnum);
            $get_id_stmt->fetch();
            $get_id_stmt->close();
            if (!empty($notif_idnum)) {
                $notif_msg = "Your exam schedule has been sent. Please check your email for details.";
                $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, is_read) VALUES (?, ?, 'academic_alert', 0)");
                $notif_stmt->bind_param("ss", $notif_idnum, $notif_msg);
                $notif_stmt->execute();
                $notif_stmt->close();
            }
        } catch (Exception $e) {
            $success = false;
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_permission'])) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    $teacher = $_POST['teacher'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';
    $success = false;
    if ($email && $message) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'yloludovice709@gmail.com';
            $mail->Password = 'byvumtkpzqysysvy';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $mail->setFrom('yloludovice709@gmail.com', 'LSPU-CCS Admin');
            $mail->addAddress($email);
            $mail->isHTML(false);
            $mail->Subject = 'INC/4.0 Permission Request';
            $mail->Body = $message;
            $mail->send();
            $success = true;
        } catch (Exception $e) {
            $success = false;
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

// --- SEARCH AND FILTER LOGIC ---
$search = "";
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where = [];
$params = [];
$types = '';

if (isset($_GET['search']) && trim($_GET['search']) !== "") {
    $search = trim($_GET['search']);
    $where[] = "(student_name LIKE ? OR student_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if ($filter == 'pending') {
    $where[] = "(dean_approved = 0 OR dean_approved IS NULL)";
} elseif ($filter == 'approved') {
    $where[] = "dean_approved = 1";
}
// 'all' filter - no WHERE condition added

// Add teacher filter
if ($is_teacher) {
    $where[] = "(professor LIKE ? OR professor = ?)";
    $params[] = "%$teacher_name%";
    $params[] = $teacher_name;
    $types .= 'ss';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';



// --- SEND TO DEAN ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_to_dean"])) {
    $ids = json_decode($_POST['ids'], true);
    $message = $_POST['message'] ?? '';
    $teacher_signature = $_POST['teacher_signature'] ?? '';

    if (!empty($ids)) {
        foreach ($ids as $id) {
            // Get request details
            $stmt = $conn->prepare("SELECT student_name, student_id, student_email, professor, subject, inc_reason, inc_semester FROM inc_requests WHERE id=?");
            if (!$stmt) continue;
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($student_name, $student_id, $student_email, $professor, $subject, $inc_reason, $inc_semester);
            $stmt->fetch();
            $stmt->close();

            // Insert into dean_inc_requests
            $stmt = $conn->prepare("INSERT INTO dean_inc_requests (inc_request_id, student_name, student_id, student_email, professor, subject, inc_reason, inc_semester, signature, date_submitted, dean_approved, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0, 'pending', NOW())");
            if (!$stmt) continue;
            $stmt->bind_param("issssssss", $id, $student_name, $student_id, $student_email, $professor, $subject, $inc_reason, $inc_semester, $teacher_signature);
            $stmt->execute();
            $stmt->close();

            // Create dean notification
            $dean_message = "New INC request from $student_name ($student_id) for $subject requires your approval.";
            $conn->query("INSERT INTO dean_notifications (message, is_read) VALUES ('$dean_message', 0)");
        }
    }
    exit;
}

// --- SEND TO STUDENT ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_to_student"])) {
    $ids = json_decode($_POST['ids'], true);
    $message = $_POST['message'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $room = $_POST['room'] ?? '';
    
    if (!empty($ids) && $message && $date && $time && $room) {
        require_once 'PHPMailer/src/Exception.php';
        require_once 'PHPMailer/src/PHPMailer.php';
        require_once 'PHPMailer/src/SMTP.php';
        
        foreach ($ids as $id) {
            // Get request details
            $stmt = $conn->prepare("SELECT student_id, student_name, student_email, subject, professor FROM inc_requests WHERE id=?");
            if (!$stmt) continue;
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($student_id, $student_name, $student_email, $subject, $professor);
            $stmt->fetch();
            $stmt->close();
            
            if (!empty($student_id)) {
                // Send email
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'yloludovice709@gmail.com';
                    $mail->Password = 'byvumtkpzqysysvy';
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
                    $mail->setFrom('yloludovice709@gmail.com', 'LSPU-CCS');
                    $mail->addAddress($student_email);
                    $mail->Subject = 'Exam Schedule - ' . $subject;
                    $mail->Body = $message;
                    $mail->send();
                } catch (Exception $e) {}
                
                // Create notification
                $notif_msg = "📅 Exam Schedule: $subject on $date at $time in $room. Check your email for details.";
                $conn->query("INSERT INTO notifications (user_id, message, type, is_read) VALUES ('$student_id', '$notif_msg', 'exam_schedule', 0)");
                
                // Update existing INC alert with exam details and mark as resolved
                $alert_msg = "📅 Exam Schedule: $subject on $date at $time in $room. Check your email for details.";
                $conn->query("UPDATE academic_alerts SET 
                             grade = 'EXAM SCHEDULED', 
                             reason = 'Exam Schedule', 
                             intervention = '$alert_msg', 
                             alert_type = 'EXAM',
                             is_resolved = 1
                             WHERE student_id = '$student_id' AND course LIKE '%$subject%' AND alert_type = 'INC' AND is_resolved = 0");
                             
                // Create new exam alert to show in dashboard
                $conn->query("INSERT INTO academic_alerts (student_id, course, grade, program_section, reason, intervention, instructor, semester, school_year, alert_type, is_resolved) 
                             VALUES ('$student_id', '$subject', 'EXAM SCHEDULED', 'B. S. Information Technology', 'Exam Schedule', '$alert_msg', '$professor', '2nd', '2025 - 2026', 'EXAM', 0)");
                
                // Update request status and mark grades as resolved to prevent re-detection
                $conn->query("UPDATE inc_requests SET student_approved=1, approved=1, status='exam_scheduled' WHERE id=$id");
                $conn->query("UPDATE grades SET grade='EXAM_SCHEDULED' WHERE student_id='$student_id' AND subject_code LIKE '%$subject%' AND column_name='finals_Exam' AND grade='0'");
            }
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

// --- APPROVAL WITH SIGNATURE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["approve_id"]) && isset($_POST["signature_data"])) {
    $approve_id = intval($_POST["approve_id"]);
    $signature_data = $_POST["signature_data"];

    $stmt = $conn->prepare("SELECT student_email, student_name, student_id FROM inc_requests WHERE id=?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $approve_id);
    $stmt->execute();
    $stmt->bind_result($student_email, $student_name, $id_number);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE inc_requests SET approved=1, signature=? WHERE id=?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("si", $signature_data, $approve_id);
    $stmt->execute();
    $stmt->close();

    // Send email
    if (!empty($student_email)) {
        $subject = "INC/4.0 Request Approved";
        $message = "Dear $student_name,\n\nYour INC/4.0 request has been approved. Please wait for your grade to be updated.\n\nThank you.\nLaguna State Polytechnic University";
        $headers = "From: no-reply@lspu.edu.ph\r\n";
        @mail($student_email, $subject, $message, $headers);
    }

    // Save notification
    if (!empty($id_number)) {
        $notif_msg = "Your INC/4.0 request has been approved. Please wait for your grade.";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (id_number, message) VALUES (?, ?)");
        if ($notif_stmt) {
            $notif_stmt->bind_param("ss", $id_number, $notif_msg);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
    }

    echo "<script>alert('Request approved!'); window.location.href='admin_inc.php';</script>";
    exit;
}

// --- GET REQUESTS ---
$sql = "SELECT * FROM inc_requests $whereClause ORDER BY id DESC";

// Debug output
if ($is_teacher) {
    echo "<!-- DEBUG: Teacher: $teacher_name, SQL: $sql, Params: " . print_r($params, true) . " -->";
}

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $inc_result = $stmt->get_result();
} else {
    $inc_result = $conn->query($sql);
}

// Debug result count
if ($inc_result) {
    echo "<!-- DEBUG: Found " . $inc_result->num_rows . " rows -->";
}




?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INC Requests - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #333;
            overflow-x: hidden;
            width: 100%;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .admin-header {
            background: #667eea;
            padding: 1rem 2rem;
            z-index: 100;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            box-shadow: 0 2px 15px rgba(102, 126, 234, 0.25);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-img {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            object-fit: cover;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 2px solid rgba(255,255,255,0.2);
        }

        .logo-text h1 {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            line-height: 1.2;
        }

        .logo-text p {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.9);
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .welcome-text {
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
        }
        .logout-btn {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1.5px solid rgba(255,255,255,0.3);
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-block;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-1px);
        }

        .toggle-btn {
            background: rgba(255,255,255,0.15);
            border: none;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.6rem;
            margin-right: 1rem;
            border-radius: 8px;
        }

        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 65px;
            width: 260px;
            height: calc(100vh - 65px);
            background: white;
            box-shadow: 4px 0 25px rgba(0,0,0,0.06);
            z-index: 99;
            overflow-y: auto;
            transition: all 0.3s ease;
            border-right: 1px solid #e8ecf4;
        }

        .admin-sidebar.collapsed {
            width: 70px;
        }

        .admin-sidebar.collapsed .sidebar-menu a span {
            display: none;
        }

        .admin-sidebar.collapsed .sidebar-menu a {
            justify-content: center;
            padding: 1rem;
        }

        .admin-sidebar.collapsed:hover {
            width: 260px;
        }

        .admin-sidebar.collapsed:hover .sidebar-menu a span {
            display: inline;
        }

        .admin-sidebar.collapsed:hover .sidebar-menu a {
            justify-content: flex-start;
            padding: 1rem 1.5rem;
        }

        .admin-sidebar .sidebar-menu {
            padding: 1.5rem 0;
        }

        .admin-sidebar .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            margin: 0.25rem 0.75rem;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 12px;
        }

        .admin-sidebar .sidebar-menu a:hover {
            background: #f0f4ff;
            color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .admin-sidebar .sidebar-menu a.active {
            background: #667eea;
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            font-weight: 600;
        }

        .admin-sidebar .sidebar-menu a i {
            width: 22px;
            text-align: center;
            font-size: 1.1rem;
        }
        .admin-content {
            margin-left: 260px;
            margin-top: 85px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }

        .admin-content.collapsed {
            margin-left: 70px;
        }

        .admin-content.teacher-mode {
            margin-left: 260px;
            margin-top: 85px;
        }

        @media (max-width: 768px) {
            .admin-content.teacher-mode {
                margin-left: 0;
            }
        }

        .content-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            width: 100%;
            box-sizing: border-box;
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }

        .page-header {
            margin-bottom: 2rem;
            animation: slideInDown 0.6s ease;
        }

        .page-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #718096;
            font-size: 0.95rem;
        }
        /* Table Styles */
        .table-scroll {
            width: 100%;
            overflow-x: auto;
            margin-top: 1.5rem;
            animation: scaleIn 0.7s ease;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.9rem;
        }

        th {
            background: #f9fafb;
            font-weight: 600;
            color: #4a5568;
            white-space: nowrap;
        }

        td {
            color: #2d3748;
        }

        tr:hover td {
            background: #f7fafc;
        }

        tbody tr {
            animation: fadeIn 0.4s ease backwards;
        }

        tbody tr:nth-child(1) { animation-delay: 0.1s; }
        tbody tr:nth-child(2) { animation-delay: 0.15s; }
        tbody tr:nth-child(3) { animation-delay: 0.2s; }
        tbody tr:nth-child(4) { animation-delay: 0.25s; }
        tbody tr:nth-child(5) { animation-delay: 0.3s; }
        tbody tr:nth-child(n+6) { animation-delay: 0.35s; }
        .action-btn {
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .action-btn:hover {
            background: #5568d3;
            transform: translateY(-1px);
        }

        .action-btn.green {
            background: #10b981;
        }

        .action-btn.green:hover {
            background: #059669;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-badge.approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        /* Signature Modal */
        #signatureModal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0;
            width: 100vw; height: 100vh;
            background: rgba(30,18,54,0.35);
            align-items: center;
            justify-content: center;
        }
        #signatureModal .modal-content {
            background: #fff;
            border-radius: 16px;
            padding: 28px 24px 24px 24px;
            box-shadow: 0 2px 24px #6a11cb44;
            min-width: 330px;
            max-width: 98vw;
            width: 420px;
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        #signatureModal .close-x {
            position: absolute;
            top: 12px;
            right: 18px;
            font-size: 2.3rem;
            color: #888;
            cursor: pointer;
            transition: color 0.2s;
        }
        #signatureModal .close-x:hover {
            color: #6A11CB;
        }
        #signature-pad {
            border: 1.5px solid #6A11CB;
            border-radius: 7px;
            background: #fff;
            width: 100%;
            height: 120px;
            cursor: crosshair;
        }
        .sig-btns {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .sig-btns button {
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            padding: 7px 18px;
            border: none;
            cursor: pointer;
        }
        .sig-btns .clear-btn {
            background: #dc2626;
            color: #fff;
        }
        .sig-btns .save-btn {
            background: #16a34a;
            color: #fff;
        }
        .sig-btns .clear-btn:hover {
            background: #b91c1c;
        }
        .sig-btns .save-btn:hover {
            background: #15803d;
        }
        /* Signature View Modal */
        #viewSignatureModal {
            display: none;
            position: fixed;
            z-index: 99999;
            left: 0; top: 0;
            width: 100vw; height: 100vh;
            background: rgba(30,18,54,0.35);
            align-items: center;
            justify-content: center;
        }
        #viewSignatureModal .modal-content {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 24px #6a11cb44;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        #viewSignatureModal .close-x {
            position: absolute;
            top: 8px;
            right: 18px;
            font-size: 2.3rem;
            color: #888;
            cursor: pointer;
            transition: color 0.2s;
        }
        #viewSignatureModal .close-x:hover {
            color: #6A11CB;
        }
        #viewSignatureImg {
            max-width: 400px;
            max-height: 250px;
            border: 1.5px solid #6A11CB;
            background: #fff;
        }
        @media (max-width: 768px) {
            .header {
                padding: 0.75rem 1rem;
            }

            .logo-img {
                width: 30px;
                height: 30px;
            }

            .logo-text h1 {
                font-size: 0.75rem;
            }

            .logo-text p {
                display: none;
            }

            .welcome-text {
                display: none;
            }

            .logout-btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
            }

            .logout-btn span {
                display: none;
            }

            .toggle-btn {
                display: block;
                padding: 0.4rem;
                font-size: 1rem;
            }

            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                top: 55px;
                height: calc(100vh - 55px);
            }

            .admin-sidebar.show {
                transform: translateX(0);
            }

            .admin-content {
                margin-left: 0;
                margin-top: 55px;
                padding: 1rem;
            }

            .content-card {
                padding: 1rem;
                border-radius: 12px;
            }

            .page-header h2 {
                font-size: 1.25rem;
            }

            .page-header p {
                font-size: 0.85rem;
            }

            .filter-container {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .filter-btn {
                font-size: 0.75rem;
                padding: 0.4rem 0.75rem;
            }

            .table-scroll {
                font-size: 0.8rem;
            }

            th, td {
                padding: 0.5rem;
                font-size: 0.75rem;
            }

            .action-btn {
                padding: 0.4rem 0.75rem;
                font-size: 0.75rem;
            }

            .status-badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.5rem;
            }

            #signatureModal .modal-content,
            #teacherModal .modal-content,
            #studentModal .modal-content,
            #studentApproveModal .modal-content {
                width: 95%;
                max-height: 95vh;
                padding: 1rem 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 0.4rem 0.5rem;
            }

            .logo-img {
                width: 28px;
                height: 28px;
            }

            .logo-text h1 {
                font-size: 0.65rem;
            }

            .toggle-btn {
                padding: 0.3rem;
                font-size: 0.9rem;
                margin-right: 0.2rem;
            }

            .logout-btn {
                padding: 0.35rem 0.5rem;
            }

            .admin-content {
                padding: 0.75rem;
                margin-top: 60px;
            }

            .content-card {
                padding: 0.75rem;
            }

            .page-header h2 {
                font-size: 1.1rem;
            }

            .filter-btn {
                font-size: 0.7rem;
                padding: 0.35rem 0.6rem;
            }

            .filter-btn i {
                display: none;
            }

            .table-scroll {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            th, td {
                padding: 0.4rem;
                font-size: 0.7rem;
            }

            .action-btn {
                padding: 0.35rem 0.6rem;
                font-size: 0.7rem;
            }

            #signatureModal .modal-content,
            #teacherModal .modal-content,
            #studentModal .modal-content,
            #studentApproveModal .modal-content {
                padding: 1rem;
            }

            #signatureModal h3,
            #teacherModal h3,
            #studentModal h3,
            #studentApproveModal h3 {
                font-size: 1rem;
            }
        }
        .search-bar {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: #5568d3;
        }

        .clear-btn {
            background: #f3f4f6;
            color: #4a5568;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .clear-btn:hover {
            background: #e5e7eb;
        }

        .filter-container {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
            animation: fadeIn 0.5s ease backwards;
        }

        .filter-btn:nth-child(1) { animation-delay: 0.1s; }
        .filter-btn:nth-child(2) { animation-delay: 0.15s; }
        .filter-btn:nth-child(3) { animation-delay: 0.2s; }
        .filter-btn:nth-child(4) { animation-delay: 0.25s; }
        .filter-btn:nth-child(5) { animation-delay: 0.3s; }

        .filter-btn:hover {
            background: #f7fafc;
        }

        .filter-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <?php if ($is_teacher): ?>
        <?php include 'teacher_navbar.php'; ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuItems = document.querySelectorAll('.sidebar .menu-item');
            menuItems.forEach(item => {
                if (item.getAttribute('href') === 'admin_inc.php') {
                    item.classList.add('active');
                }
            });
        });
        </script>
    <?php else: ?>
    <header class="admin-header">
        <div class="nav-container">
            <div class="logo-section">
                <button class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <img src="logo-ccs.webp" alt="LSPU Logo" class="logo-img">
                <div class="logo-text">
                    <h1>LSPU Computer Studies</h1>
                    <p>INC Requests Management</p>
                </div>
            </div>
            <div class="user-section">
                <div class="welcome-text">Admin Panel</div>
                <a href="?logout=1" class="logout-btn"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
    </header>

    <aside class="admin-sidebar" id="sidebar">
        <nav class="sidebar-menu">
            <a href="admin_dashboard.php">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin_inc.php" class="active">
                <i class="fas fa-file-alt"></i>
                <span>INC Requests</span>
            </a>
            <a href="admin_crediting.php">
                <i class="fas fa-graduation-cap"></i>
                <span>Crediting Requests</span>
            </a>
            <a href="admin_interviews.php"><i class="fas fa-calendar-check"></i><span>Interview Requests</span></a>
            <a href="admin_students.php">
                <i class="fas fa-users"></i>
                <span>Students</span>
            </a>
            <a href="admin_notification.php">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
            <a href="post_announcement.php">
                <i class="fas fa-bullhorn"></i>
                <span>Announcements</span>
            </a>
            <a href="admin_upload_grades.php">
                <i class="fas fa-upload"></i>
                <span>Upload Grades</span>
            </a>
        </nav>
    </aside>
    <?php endif; ?>

    <div class="admin-content <?php echo $is_teacher ? 'teacher-mode' : ''; ?>" id="mainContainer">
        <div class="content-card">
            <div class="page-header">
                <h2><i class="fas fa-file-alt"></i> INC/4.0 Completion Requests</h2>
                <p>Manage student requests for INC or 4.0 grade completion/removal</p>
            </div>

            <form method="get" action="admin_inc.php" style="margin-bottom: 1.5rem;">
                <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap;">
                    <input type="text" name="search" placeholder="Search by name or student ID" value="<?php echo htmlspecialchars($search); ?>" class="search-input" style="flex: 1; min-width: 200px; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem;">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                    <?php if ($search): ?>
                        <a href="admin_inc.php?filter=<?php echo htmlspecialchars($filter); ?>" class="clear-btn">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="filter-container">
                <button class="filter-btn <?php echo $filter == 'all' ? 'active' : ''; ?>" onclick="location.href='?filter=all<?php echo $search ? '&search=' . urlencode($search) : ''; ?>'">
                    <i class="fas fa-list"></i> All
                </button>
                <button class="filter-btn <?php echo $filter == 'pending' ? 'active' : ''; ?>" onclick="location.href='?filter=pending<?php echo $search ? '&search=' . urlencode($search) : ''; ?>'">
                    <i class="fas fa-clock"></i> Pending
                </button>
                <button class="filter-btn <?php echo $filter == 'approved' ? 'active' : ''; ?>" onclick="location.href='?filter=approved<?php echo $search ? '&search=' . urlencode($search) : ''; ?>'">
                    <i class="fas fa-check-circle"></i> Dean Approved
                </button>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;">
                <div style="display: flex; gap: 0.75rem;">
                    <a href="export_inc_requests.php?filter=<?php echo $filter; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="action-btn" style="background: #10b981; text-decoration: none;"><i class="fas fa-file-pdf"></i> Export PDF</a>
                </div>
                <div style="display: flex; gap: 0.75rem; margin-left: auto;">
                    <button class="action-btn" onclick="sendToDean()" id="sendDeanBtn" disabled style="background: #8b5cf6;">
                        <i class="fas fa-user-tie"></i> Send to Dean
                    </button>
                    <button class="action-btn green" onclick="sendToStudent()" id="sendStudentBtn" disabled>
                        <i class="fas fa-file-alt"></i> Send Exam Schedule to Student
                    </button>
                </div>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <label><input type="checkbox" id="selectAll" name="selectAll" onclick="toggleSelectAll()"></label>
                            </th>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Email</th>
                            <th>Professor</th>
                            <th>Subject</th>
                            <th>Reason</th>
                            <th>Semester</th>
                            <th>Date</th>
                            <th>Dean Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($inc_result && $inc_result->num_rows > 0): ?>
                        <?php while($row = $inc_result->fetch_assoc()): ?>
                        <!-- DEBUG ROW: <?php echo print_r($row, true); ?> -->
                        <tr>
                            <td>
                                <label><input type="checkbox" class="request-checkbox" name="request_<?php echo $row['id']; ?>" value="<?php echo $row['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($row['student_name']); ?>" 
                                    data-id="<?php echo htmlspecialchars($row['student_id']); ?>" 
                                    data-email="<?php echo htmlspecialchars($row['student_email']); ?>" 
                                    data-subject="<?php echo htmlspecialchars($row['subject']); ?>" 
                                    data-semester="<?php echo htmlspecialchars($row['inc_semester']); ?>" 
                                    data-professor="<?php echo htmlspecialchars($row['professor']); ?>" 
                                    data-dean-approved="<?php echo $row['dean_approved'] ?? 0; ?>"
                                    onchange="updateBulkButton()"></label>
                            </td>
                            <td><?php echo htmlspecialchars($row["student_name"] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row["student_id"] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row["student_email"] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row["professor"] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row["subject"] ?? 'N/A'); ?></td>
                            <td style="max-width: 200px; white-space: normal;"><?php echo htmlspecialchars($row["inc_reason"] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row["inc_semester"] ?? 'N/A'); ?></td>
                            <td><?php echo $row["date_submitted"] ? date('M d, Y', strtotime($row["date_submitted"])) : 'N/A'; ?></td>
                            <td>
                                <?php if (isset($row["dean_approved"]) && $row["dean_approved"] == 1): ?>
                                    <span class="status-badge approved">Approved</span>
                                <?php else: ?>
                                    <span class="status-badge pending">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
    <?php endwhile; ?>
<?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align:center;color:#888;padding:2rem;">No requests found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>



<!-- Dean Modal -->
<div id="deanModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:600px; width:90%; box-shadow:0 4px 20px rgba(0,0,0,0.15);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="font-size:1.25rem; font-weight:700; color:#2d3748;">Send to Dean</h3>
            <button onclick="closeDeanModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#718096;">&times;</button>
        </div>
        <div style="margin-bottom:1rem; padding:1rem; background:#f9fafb; border-radius:8px;">
            <div style="margin-bottom:0.5rem;"><strong>Action:</strong> Forward INC requests to Dean for approval</div>
            <div><strong>Recipient:</strong> Dean's Office</div>
        </div>
        <textarea id="deanModalMessage" rows="10" style="width:100%; padding:0.75rem; border:1px solid #e5e7eb; border-radius:8px; font-size:0.95rem; font-family:inherit; resize:vertical;"></textarea>
        <div style="margin-top:1rem; padding:1rem; background:#e8f5e8; border-radius:8px; border-left:4px solid #10b981;">
            <div style="margin-bottom:0.5rem;"><strong>Teacher E-Signature Required:</strong></div>
            <div style="display:flex; gap:1rem; align-items:center;">
                <canvas id="deanTeacherSignature" width="200" height="80" style="border:1px solid #ccc; background:white; border-radius:4px;"></canvas>
                <div>
                    <button onclick="clearDeanSignature()" style="background:#ef4444; color:white; border:none; padding:0.5rem 1rem; border-radius:4px; margin-bottom:0.5rem; display:block;">Clear</button>
                    <small style="color:#666;">Teacher signature for approval</small>
                </div>
            </div>
        </div>
        <div style="display:flex; gap:0.75rem; margin-top:1.5rem; justify-content:flex-end;">
            <button onclick="closeDeanModal()" class="clear-btn">Cancel</button>
            <button onclick="confirmSendToDean()" class="action-btn" style="background:#8b5cf6;">Send to Dean</button>
        </div>
    </div>
</div>

<!-- Student Modal -->
<div id="studentModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:2rem; max-width:600px; width:90%; box-shadow:0 4px 20px rgba(0,0,0,0.15);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="font-size:1.25rem; font-weight:700; color:#2d3748;">Send Exam Schedule to Student</h3>
            <button onclick="closeStudentModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#718096;">&times;</button>
        </div>
        <div style="margin-bottom:1rem; padding:1rem; background:#f9fafb; border-radius:8px;">
            <div style="margin-bottom:0.5rem;"><strong>Student:</strong> <span id="studentModalName"></span></div>
            <div style="margin-bottom:0.5rem;"><strong>Subject:</strong> <span id="studentModalSubject"></span></div>
            <div><strong>Email:</strong> <span id="studentModalEmailDisplay" style="color:#667eea;"></span></div>
        </div>
        <input type="hidden" id="studentModalEmail">
        <div style="display:flex; gap:0.75rem; margin-bottom:1rem;">
            <div style="flex:1;">
                <label for="studentModalDate" style="display:block; margin-bottom:0.5rem; font-weight:600; color:#4a5568;">Date:</label>
                <input type="date" id="studentModalDate" onchange="updateStudentMessage()" style="width:100%; padding:0.75rem; border:1px solid #e5e7eb; border-radius:8px; font-size:0.95rem;">
            </div>
            <div style="flex:1;">
                <label for="studentModalTime" style="display:block; margin-bottom:0.5rem; font-weight:600; color:#4a5568;">Time:</label>
                <input type="time" id="studentModalTime" onchange="updateStudentMessage()" style="width:100%; padding:0.75rem; border:1px solid #e5e7eb; border-radius:8px; font-size:0.95rem;">
            </div>
        </div>
        <div style="margin-bottom:1rem;">
            <label for="studentModalRoom" style="display:block; margin-bottom:0.5rem; font-weight:600; color:#4a5568;">Room:</label>
            <select id="studentModalRoom" onchange="updateStudentMessage()" style="width:100%; padding:0.75rem; border:1px solid #e5e7eb; border-radius:8px; font-size:0.95rem;">
                <option value="">-- Select Room --</option>
                <option value="MacLab 1">MacLab 1</option>
                <option value="MacLab 2">MacLab 2</option>
                <option value="ComLab 1">ComLab 1</option>
                <option value="ComLab 2">ComLab 2</option>
                <option value="Room 101">Room 101</option>
                <option value="Room 102">Room 102</option>
                <option value="Room 103">Room 103</option>
            </select>
        </div>
        <label for="studentModalMessage" style="display:block; margin-bottom:0.5rem; font-weight:600; color:#4a5568;">Message:</label>
        <textarea id="studentModalMessage" rows="6" style="width:100%; padding:0.75rem; border:1px solid #e5e7eb; border-radius:8px; font-size:0.95rem; font-family:inherit; resize:vertical;"></textarea>
        <div style="display:flex; gap:0.75rem; margin-top:1.5rem; justify-content:flex-end;">
            <button onclick="closeStudentModal()" class="clear-btn">Cancel</button>
            <button onclick="confirmSendToStudent()" class="action-btn green">Send to Student</button>
        </div>
    </div>
</div>

<!-- Approve for Student Modal (completely outside .admin-content and table) -->
<div id="studentApproveModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(30,18,54,0.35); align-items:center; justify-content:center;">
    <div class="modal-content" style="background:#fff; border-radius:16px; padding:28px 24px 24px 24px; box-shadow:0 2px 24px #6a11cb44; min-width:330px; max-width:98vw; width:420px; position:relative; display:flex; flex-direction:column; gap:16px;">
        <span class="close-x" onclick="closeStudentApproveModal()" style="position:absolute;top:12px;right:18px;font-size:2.3rem;color:#888;cursor:pointer;transition:color 0.2s;">&times;</span>
        <h3 style="margin-bottom:8px; font-size:1.25rem; font-weight:700; color:#222; text-align:center;">Send Exam Details to Student</h3>
        <form id="studentApproveForm" onsubmit="sendStudentApprove(event)" style="width:100%;display:flex;flex-direction:column;gap:10px;">
            <input type="hidden" id="studentApproveId" name="studentApproveId">
            <input type="hidden" id="studentApproveEmail" name="studentApproveEmail">
            <label for="studentApproveMsg" style="font-weight:600;">Message to Student:</label>
            <textarea id="studentApproveMsg" name="studentApproveMsg" rows="5" style="width:100%;border-radius:8px;border:1.5px solid #6A11CB;padding:10px;font-size:1rem;resize:vertical;min-height:90px;max-height:180px;"></textarea>
            <div style="display:flex;gap:8px;width:100%;">
                <div style="flex:1;display:flex;flex-direction:column;">
                    <label for="studentApproveDate" style="font-weight:600;">Date:</label>
                    <input type="date" id="studentApproveDate" name="studentApproveDate" style="width:100%;border-radius:8px;border:1.5px solid #6A11CB;padding:7px 10px;font-size:1rem;">
                </div>
                <div style="flex:1;display:flex;flex-direction:column;">
                    <label for="studentApproveTime" style="font-weight:600;">Time:</label>
                    <input type="time" id="studentApproveTime" name="studentApproveTime" style="width:100%;border-radius:8px;border:1.5px solid #6A11CB;padding:7px 10px;font-size:1rem;">
                </div>
            </div>
            <div style="width:100%;display:flex;flex-direction:column;">
                <label for="studentApproveRoom" style="font-weight:600;">Room:</label>
                <input type="text" id="studentApproveRoom" name="studentApproveRoom" style="width:100%;border-radius:8px;border:1.5px solid #6A11CB;padding:7px 10px;font-size:1rem;">
            </div>
            <button class="action-btn" style="background:#16a34a;color:#fff;width:100%;margin-top:10px;font-size:1.08rem;font-weight:700;" type="submit">Send to Student</button>
        </form>
    </div>
</div>

<?php
// Handle AJAX request to send exam details to student (also moved outside main content)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_student_approve'])) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $message = isset($_POST['message']) ? $_POST['message'] : '';
    $subject = 'Final Exam Details';
    $success = false;
    if ($email && $message) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'yloludovice709@gmail.com'; // <-- SET YOUR GMAIL
            $mail->Password = 'byvumtkpzqysysvy';    // <-- SET YOUR APP PASSWORD
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $mail->setFrom('yloludovice709@gmail.com', 'LSPU-CCS Admin');
            $mail->addAddress($email);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->send();
            $success = true;
        } catch (Exception $e) {
            $success = false;
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}
?>
    <!-- Signature Modal -->
    <div id="signatureModal">
        <div class="modal-content">
            <span class="close-x" onclick="closeSignatureModal()">&times;</span>
            <h3>Draw Your Signature</h3>
            <canvas id="signature-pad" width="380" height="120"></canvas>
            <img id="signature-preview" src="" alt="Signature Preview" style="display:none; margin-top:10px; border:1px solid #ccc; width:180px; height:60px;">
            <div class="sig-btns">
                <button class="clear-btn" onclick="clearSignaturePad()" type="button">Clear</button>
                <button class="save-btn" onclick="saveSignature()" type="button">Save Signature</button>
            </div>
            <form id="signatureForm" method="post" style="display:none;">
                <input type="hidden" name="approve_id" id="approve_id">
                <input type="hidden" name="signature_data" id="signature_data">
            </form>
        </div>
    </div>
    <div id="teacherModal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(30,18,54,0.35); align-items:center; justify-content:center;">
        <div class="modal-content" style="background:#fff; border-radius:16px; padding:28px 24px 24px 24px; box-shadow:0 2px 24px #6a11cb44; min-width:330px; max-width:98vw; width:420px; position:relative; display:flex; flex-direction:column; gap:16px;">
            <span class="close-x" onclick="closeTeacherModal()" style="position:absolute;top:12px;right:18px;font-size:2.3rem;color:#888;cursor:pointer;transition:color 0.2s;">&times;</span>
            <h3>Send Permission to Teacher</h3>
            <label for="teacherSelect" style="font-weight:600;">Select Teacher:</label>
            <select id="teacherSelect" style="padding:8px 12px;border-radius:8px;border:1.5px solid #6A11CB;font-size:1rem;">
                <option value="">-- Select --</option>
                <option value="Prof. Santos">Prof. Santos</option>
                <option value="Prof. Dela Cruz">Prof. Dela Cruz</option>
                <option value="Prof. Reyes">Prof. Reyes</option>
                <option value="Prof. Garcia">Prof. Garcia</option>
                <option value="Prof. Mendoza">Prof. Mendoza</option>
            </select>
        <label for="permissionText" style="font-weight:600;">Permission Text:</label>
        <textarea id="permissionText" rows="5" style="width:100%;border-radius:8px;border:1.5px solid #6A11CB;padding:10px;font-size:1rem;"></textarea>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button class="action-btn" style="background:#16a34a;color:#fff;" onclick="sendPermission()">Send</button>
            </div>
        </div>
    </div>
    <!-- Signature View Modal -->
    <div id="viewSignatureModal" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100vw; height:100vh; background:rgba(30,18,54,0.35); align-items:center; justify-content:center;">
        <div class="modal-content">
            <span class="close-x" onclick="closeViewSignature()">&times;</span>
            <img id="viewSignatureImg" src="" alt="Signature">
        </div>
    </div>

    <!-- Loading Modal -->
    <div id="loadingModal" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:16px; padding:2rem; text-align:center; box-shadow:0 4px 20px rgba(0,0,0,0.3);">
            <div style="margin-bottom:1rem;">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem; color:#667eea;"></i>
            </div>
            <div id="loadingMessage" style="font-size:1.1rem; font-weight:600; color:#2d3748;">Processing...</div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContainer = document.getElementById('mainContainer');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                if (mainContainer && !mainContainer.classList.contains('teacher-mode')) {
                    mainContainer.classList.toggle('collapsed');
                }
            }
        }

        const sidebar = document.getElementById('sidebar');
        const mainContainer = document.getElementById('mainContainer');

        if (sidebar && mainContainer) {
            sidebar.addEventListener('mouseenter', function() {
                if (window.innerWidth > 768 && sidebar.classList.contains('collapsed')) {
                    mainContainer.style.marginLeft = '260px';
                }
            });

            sidebar.addEventListener('mouseleave', function() {
                if (window.innerWidth > 768 && sidebar.classList.contains('collapsed')) {
                    mainContainer.style.marginLeft = '70px';
                }
            });
        }

        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.toggle-btn');
            
            if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });

        // Signature pad logic
        window.onload = function() {
            signaturePad = document.getElementById('signature-pad');
            ctx = signaturePad.getContext('2d');
            ctx.fillStyle = "#fff";
            ctx.fillRect(0, 0, signaturePad.width, signaturePad.height);

            signaturePad.addEventListener('mousedown', function(e) {
                drawing = true;
                [lastX, lastY] = [e.offsetX, e.offsetY];
            });
            signaturePad.addEventListener('mousemove', function(e) {
                if (!drawing) return;
                ctx.strokeStyle = "#222";
                ctx.lineWidth = 2;
                ctx.lineCap = "round";
                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(e.offsetX, e.offsetY);
                ctx.stroke();
                [lastX, lastY] = [e.offsetX, e.offsetY];
            });
            signaturePad.addEventListener('mouseup', function() { drawing = false; });
            signaturePad.addEventListener('mouseout', function() { drawing = false; });

        };

        let signaturePad, ctx, drawing = false, lastX = 0, lastY = 0, approveId = null;


<?php
// Handle AJAX request to send permission email to professor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_permission'])) {
    // Use PHPMailer for Gmail SMTP
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    $teacher = isset($_POST['teacher']) ? $_POST['teacher'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $message = isset($_POST['message']) ? $_POST['message'] : '';
    $subject = 'Permission Request for INC/4.0 Completion/Removal';
    $success = false;   
    if ($email && $message) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'yloludovice709@gmail.com'; // <-- SET YOUR GMAIL
            $mail->Password = 'byvumtkpzqysysvy';    // <-- SET YOUR APP PASSWORD
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            $mail->setFrom('yloludovice709@gmail.com', 'LSPU-CCS Admin');
            $mail->addAddress($email, $teacher);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->send();
            $success = true;
        } catch (Exception $e) {
            $success = false;
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}
?>
        function clearSignaturePad() {
            ctx = document.getElementById('signature-pad').getContext('2d');
            ctx.clearRect(0, 0, signaturePad.width, signaturePad.height);
            ctx.fillStyle = "#fff";
            ctx.fillRect(0, 0, signaturePad.width, signaturePad.height);
            document.getElementById('signature-preview').style.display = 'none';
        }
        function saveSignature() {
            let dataURL = signaturePad.toDataURL();
            if (isCanvasBlank(signaturePad)) {
                alert("Please draw your signature first.");
                return;
            }
            document.getElementById('signature-preview').src = dataURL;
            document.getElementById('signature-preview').style.display = 'block';
            setTimeout(function() {
                document.getElementById('signature_data').value = dataURL;
                document.getElementById('signatureForm').submit();
            }, 1000);
        }
        function isCanvasBlank(canvas) {
            const blank = document.createElement('canvas');
            blank.width = canvas.width;
            blank.height = canvas.height;
            return canvas.toDataURL() === blank.toDataURL();
        }
        function viewSignature(src) {
            document.getElementById('viewSignatureImg').src = src;
            document.getElementById('viewSignatureModal').style.display = 'flex';
        }
        function closeViewSignature() {
            document.getElementById('viewSignatureModal').style.display = 'none';
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.request-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkButton();
        }

        function updateBulkButton() {
            const checkboxes = document.querySelectorAll('.request-checkbox:checked');
            const deanBtn = document.getElementById('sendDeanBtn');
            const studentBtn = document.getElementById('sendStudentBtn');
            const count = checkboxes.length;
            
            // Check if any selected request is dean approved
            let hasDeanApproved = false;
            checkboxes.forEach(cb => {
                const deanApproved = cb.getAttribute('data-dean-approved');
                console.log('Dean approved status:', deanApproved);
                if (deanApproved == '1') {
                    hasDeanApproved = true;
                }
            });
            console.log('Has dean approved:', hasDeanApproved);
            
            // Check if any selected request is already dean approved (can't send to dean again)
            let hasAlreadyApproved = false;
            checkboxes.forEach(cb => {
                const deanApproved = cb.getAttribute('data-dean-approved');
                if (deanApproved == '1') {
                    hasAlreadyApproved = true;
                }
            });
            
            deanBtn.disabled = count === 0 || hasAlreadyApproved;
            studentBtn.disabled = count === 0 || !hasDeanApproved;
            
            deanBtn.innerHTML = count > 0 ? `<i class="fas fa-user-tie"></i> Send to Dean (${count})` : '<i class="fas fa-user-tie"></i> Send to Dean';
            studentBtn.innerHTML = count > 0 ? `<i class="fas fa-file-alt"></i> Send Exam Schedule to Student (${count})` : '<i class="fas fa-file-alt"></i> Send Exam Schedule to Student';
        }

        function sendToDean() {
            const checkboxes = document.querySelectorAll('.request-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one request.');
                return;
            }
            
            // Check if any selected request is already dean approved
            let hasAlreadyApproved = false;
            checkboxes.forEach(cb => {
                const deanApproved = cb.getAttribute('data-dean-approved');
                if (deanApproved == '1') {
                    hasAlreadyApproved = true;
                }
            });
            
            if (hasAlreadyApproved) {
                alert('Cannot send already approved requests to Dean again.');
                return;
            }
            
            // Get first selected request data for modal
            const firstCheckbox = checkboxes[0];
            const studentName = firstCheckbox.dataset.name;
            const subject = firstCheckbox.dataset.subject;
            const semester = firstCheckbox.dataset.semester;
            
            // Pre-fill modal
            const professor = firstCheckbox.dataset.professor;
            document.getElementById('deanModalMessage').value = `Dear Dean,\n\nI am forwarding the following INC/4.0 completion request for your approval:\n\nStudent: ${studentName}\nSubject: ${subject}\nSemester: ${semester}\nProfessor: ${professor}\n\nTEACHER APPROVAL:\n"I approve this student to take the INC completion exam/requirements for the above subject. The student has valid reasons for the incomplete grade and I recommend approval for INC removal."\n\nPlease review and approve at your earliest convenience.\n\nThank you.\n\n- Admin`;
            document.getElementById('deanModal').style.display = 'flex';
        }
        
        function closeDeanModal() {
            document.getElementById('deanModal').style.display = 'none';
        }
        
        function confirmSendToDean() {
            const checkboxes = document.querySelectorAll('.request-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            const message = document.getElementById('deanModalMessage').value;
            const signatureCanvas = document.getElementById('deanTeacherSignature');
            const signatureData = signatureCanvas.toDataURL();
            
            // Check if signature is provided
            if (isCanvasBlank(signatureCanvas)) {
                alert('Please provide teacher signature before sending to Dean.');
                return;
            }
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'admin_inc.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    alert('Requests sent to Dean successfully!');
                    closeDeanModal();
                    location.reload();
                }
            };
            xhr.send('send_to_dean=1&ids=' + encodeURIComponent(JSON.stringify(ids)) + '&message=' + encodeURIComponent(message) + '&teacher_signature=' + encodeURIComponent(signatureData));
        }
        
        function clearDeanSignature() {
            const canvas = document.getElementById('deanTeacherSignature');
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        }
        
        // Initialize dean signature canvas
        let deanSignatureCanvas, deanCtx, deanDrawing = false, deanLastX = 0, deanLastY = 0;
        
        function initDeanSignature() {
            deanSignatureCanvas = document.getElementById('deanTeacherSignature');
            if (!deanSignatureCanvas) return;
            
            deanCtx = deanSignatureCanvas.getContext('2d');
            deanCtx.fillStyle = '#fff';
            deanCtx.fillRect(0, 0, deanSignatureCanvas.width, deanSignatureCanvas.height);
            
            deanSignatureCanvas.addEventListener('mousedown', function(e) {
                deanDrawing = true;
                const rect = deanSignatureCanvas.getBoundingClientRect();
                deanLastX = e.clientX - rect.left;
                deanLastY = e.clientY - rect.top;
            });
            
            deanSignatureCanvas.addEventListener('mousemove', function(e) {
                if (!deanDrawing) return;
                const rect = deanSignatureCanvas.getBoundingClientRect();
                const currentX = e.clientX - rect.left;
                const currentY = e.clientY - rect.top;
                
                deanCtx.strokeStyle = '#222';
                deanCtx.lineWidth = 2;
                deanCtx.lineCap = 'round';
                deanCtx.beginPath();
                deanCtx.moveTo(deanLastX, deanLastY);
                deanCtx.lineTo(currentX, currentY);
                deanCtx.stroke();
                
                deanLastX = currentX;
                deanLastY = currentY;
            });
            
            deanSignatureCanvas.addEventListener('mouseup', function() { deanDrawing = false; });
            deanSignatureCanvas.addEventListener('mouseout', function() { deanDrawing = false; });
        }
        
        // Initialize when modal is shown
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initDeanSignature, 100);
        });



        let currentStudentName = '';
        let currentSubject = '';

        function sendToStudent() {
            const checkboxes = document.querySelectorAll('.request-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one request.');
                return;
            }
            
            // Check if selected requests are dean approved
            let hasUnapproved = false;
            checkboxes.forEach(cb => {
                const deanApproved = cb.getAttribute('data-dean-approved');
                console.log('Send check - Dean approved:', deanApproved);
                if (deanApproved != '1' && deanApproved != 1) {
                    hasUnapproved = true;
                }
            });
            console.log('Has unapproved:', hasUnapproved);
            
            if (hasUnapproved) {
                alert('Can only send exam schedule to students with Dean approved requests.');
                return;
            }

            // Get first selected request data
            const firstCheckbox = checkboxes[0];
            currentStudentName = firstCheckbox.dataset.name;
            currentSubject = firstCheckbox.dataset.subject;
            const studentEmail = firstCheckbox.dataset.email;

            // Pre-fill modal
            document.getElementById('studentModalName').textContent = currentStudentName;
            document.getElementById('studentModalSubject').textContent = currentSubject;
            document.getElementById('studentModalEmailDisplay').textContent = studentEmail;
            document.getElementById('studentModalEmail').value = studentEmail;
            document.getElementById('studentModalDate').value = '';
            document.getElementById('studentModalTime').value = '';
            document.getElementById('studentModalRoom').value = '';
            updateStudentMessage();
            document.getElementById('studentModal').style.display = 'flex';
        }

        function updateStudentMessage() {
            const date = document.getElementById('studentModalDate').value || '[Date]';
            const time = document.getElementById('studentModalTime').value || '[Time]';
            const room = document.getElementById('studentModalRoom').value || '[Room]';
            const professor = document.querySelector('.request-checkbox:checked')?.getAttribute('data-professor') || 'Teacher';
            document.getElementById('studentModalMessage').value = `Dear ${currentStudentName},\n\nYour exam schedule for ${currentSubject} has been finalized:\n\nDate: ${date}\nTime: ${time}\nRoom: ${room}\n\nPlease be on time.\n\n- ${professor}`;
        }

        function closeStudentModal() {
            document.getElementById('studentModal').style.display = 'none';
        }

        function confirmSendToStudent() {
            const checkboxes = document.querySelectorAll('.request-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);
            const message = document.getElementById('studentModalMessage').value;
            const date = document.getElementById('studentModalDate').value;
            const time = document.getElementById('studentModalTime').value;
            const room = document.getElementById('studentModalRoom').value;

            if (!date || !time || !room) {
                alert('Please fill in date, time, and room.');
                return;
            }
            
            // Show loading modal
            showLoadingModal('Sending exam schedules to students...');
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'admin_inc.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    hideLoadingModal();
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                alert('Exam schedules sent to students successfully!');
                                closeStudentModal();
                                location.reload();
                            } else {
                                alert('Failed to send exam schedules. Please try again.');
                            }
                        } catch (e) {
                            alert('Error processing response. Please try again.');
                        }
                    } else {
                        alert('Network error. Please check your connection and try again.');
                    }
                }
            };
            xhr.send('send_to_student=1&ids=' + encodeURIComponent(JSON.stringify(ids)) + '&message=' + encodeURIComponent(message) + '&date=' + encodeURIComponent(date) + '&time=' + encodeURIComponent(time) + '&room=' + encodeURIComponent(room));
        }

        function closeStudentApproveModal() {
            document.getElementById('studentApproveModal').style.display = 'none';
        }

        function showLoadingModal(message) {
            document.getElementById('loadingMessage').textContent = message;
            document.getElementById('loadingModal').style.display = 'flex';
        }
        
        function hideLoadingModal() {
            document.getElementById('loadingModal').style.display = 'none';
        }
        
        function sendStudentApprove(e) {
            e.preventDefault();
            var email = document.getElementById('studentApproveEmail').value;
            var msg = document.getElementById('studentApproveMsg').value;
            var date = document.getElementById('studentApproveDate').value;
            var time = document.getElementById('studentApproveTime').value;
            var room = document.getElementById('studentApproveRoom').value;
            var id = document.getElementById('studentApproveId').value;
            if (!email || !msg) {
                alert('Missing student email or message.');
                return;
            }
            if (!date || !time || !room) {
                alert('Please fill in date, time, and room.');
                return;
            }
            // Replace placeholders in message
            msg = msg.replace('[Date]', date).replace('[Time]', time).replace('[Room]', room);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'admin_inc.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        alert('Exam details sent to st  udent!');
                        closeStudentApproveModal();
                        // Optionally, reload to update status
                        window.location.reload();
                    } else {
                        alert('Failed to send email.');
                    }
                }
            };
            xhr.send('send_student_approve=1&id=' + encodeURIComponent(id) + '&email=' + encodeURIComponent(email) + '&message=' + encodeURIComponent(msg));
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>



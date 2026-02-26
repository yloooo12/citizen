<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "citizenproj");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle Secretary Document Preparation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["prepare_document"])) {
    $id = $_POST['id'];
    $secretary_notes = trim($_POST['secretary_notes']);
    $document_filename = '';

    // Handle file upload
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
        $upload_dir = 'uploads/crediting_documents/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
        $document_filename = 'crediting_' . $id . '_' . time() . '.' . $file_ext;
        move_uploaded_file($_FILES['document_file']['tmp_name'], $upload_dir . $document_filename);
    }

    $stmt = $conn->prepare("UPDATE crediting_requests SET document_file=?, secretary_notes=?, secretary_approved=1, status='preparing_document' WHERE id=?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    $stmt->bind_param("ssi", $document_filename, $secretary_notes, $id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update']);
        exit;
    }
    error_log("Updated " . $stmt->affected_rows . " rows for document preparation");
    $stmt->close();
    $conn->commit();
    
    if (true) {

        // Get student details
        $stmt = $conn->prepare("SELECT student_id, student_name FROM crediting_requests WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($student_id, $student_name);
        $stmt->fetch();
        $stmt->close();

        // Send notification
        $notif_msg = "Your crediting document is being prepared by the College Secretary.";
        $notif_stmt = $conn->prepare("INSERT INTO student_notifications (id_number, message) VALUES (?, ?)");
        $notif_stmt->bind_param("ss", $student_id, $notif_msg);
        $notif_stmt->execute();
        $notif_stmt->close();

        echo json_encode(['success' => true, 'message' => 'Document prepared']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare document']);
    }
    exit;
}

// Handle Program Head Evaluation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["evaluate_request"])) {
    $id = $_POST['id'];
    $credited_subjects = trim($_POST['credited_subjects']);
    $remarks = trim($_POST['remarks']);

    $stmt = $conn->prepare("UPDATE crediting_requests SET credited_subjects=?, evaluation_remarks=?, program_head_approved=1, status='evaluating' WHERE id=?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    $stmt->bind_param("ssi", $credited_subjects, $remarks, $id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update']);
        exit;
    }
    error_log("Updated " . $stmt->affected_rows . " rows for evaluation");
    $stmt->close();
    $conn->commit();
    
    if (true) {

        // Get student details
        $stmt = $conn->prepare("SELECT student_id, student_name FROM crediting_requests WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($student_id, $student_name);
        $stmt->fetch();
        $stmt->close();

        // Send notification
        $notif_msg = "Your crediting request is being evaluated by the Program Head.";
        $notif_stmt = $conn->prepare("INSERT INTO student_notifications (id_number, message) VALUES (?, ?)");
        $notif_stmt->bind_param("ss", $student_id, $notif_msg);
        $notif_stmt->execute();
        $notif_stmt->close();

        echo json_encode(['success' => true, 'message' => 'Evaluation saved']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save evaluation']);
    }
    exit;
}

// Handle Final Approval/Decline
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["final_action"])) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';

    $id = $_POST['id'];
    $action = $_POST['action'];

    if ($action == 'approve') {
        $stmt = $conn->prepare("UPDATE crediting_requests SET approved=1, status='approved', updated_at=NOW() WHERE id=?");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit;
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'Failed to approve']);
            exit;
        }
        error_log("Updated " . $stmt->affected_rows . " rows for approval");
        $stmt->close();
        $conn->commit();

        // Get student details
        $stmt = $conn->prepare("SELECT student_id, student_name, student_email, subjects_to_credit FROM crediting_requests WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($student_id, $student_name, $student_email, $subjects);
        $stmt->fetch();
        $stmt->close();

        // Send portal notification
        $notif_msg = "Your crediting request has been approved.";
        $notif_stmt = $conn->prepare("INSERT INTO student_notifications (id_number, message) VALUES (?, ?)");
        $notif_stmt->bind_param("ss", $student_id, $notif_msg);
        $notif_stmt->execute();
        $notif_stmt->close();

        // Send email
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ludoviceylo26@gmail.com';
            $mail->Password = 'xdnt znus npyg bxuq';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('ludoviceylo26@gmail.com', 'LSPU-CCS Admin');
            $mail->addAddress($student_email, $student_name);
            $mail->isHTML(false);
            $mail->Subject = 'Crediting Request Approved';
            $mail->Body = "Dear $student_name,\n\nYour crediting request has been approved.\n\nSubjects: $subjects\n\nThank you.";
            $mail->send();
        } catch (Exception $e) {}

        echo json_encode(['success' => true, 'message' => 'Request approved']);
    } else {
        $stmt = $conn->prepare("UPDATE crediting_requests SET status='declined', updated_at=NOW() WHERE id=?");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit;
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'Failed to decline']);
            exit;
        }
        error_log("Updated " . $stmt->affected_rows . " rows for decline");
        $stmt->close();
        $conn->commit();

        // Get student details
        $stmt = $conn->prepare("SELECT student_id, student_name, student_email FROM crediting_requests WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($student_id, $student_name, $student_email);
        $stmt->fetch();
        $stmt->close();

        // Send portal notification
        $notif_msg = "Your crediting request has been declined.";
        $notif_stmt = $conn->prepare("INSERT INTO student_notifications (id_number, message) VALUES (?, ?)");
        $notif_stmt->bind_param("ss", $student_id, $notif_msg);
        $notif_stmt->execute();
        $notif_stmt->close();

        // Send email
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ludoviceylo26@gmail.com';
            $mail->Password = 'xdnt znus npyg bxuq';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('ludoviceylo26@gmail.com', 'LSPU-CCS Admin');
            $mail->addAddress($student_email, $student_name);
            $mail->isHTML(false);
            $mail->Subject = 'Crediting Request Declined';
            $mail->Body = "Dear $student_name,\n\nYour crediting request has been declined.\n\nPlease contact the office for more information.\n\nThank you.";
            $mail->send();
        } catch (Exception $e) {}

        echo json_encode(['success' => true, 'message' => 'Request declined']);
    }
    exit;
}

// Handle Send to Registrar
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_to_registrar"])) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    require_once 'crediting_emails.php';

    $ids = json_decode($_POST['ids'], true);

    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("UPDATE crediting_requests SET status='sent_to_registrar' WHERE id IN ($placeholders)");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            exit;
        }
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            exit;
        }
        error_log("Updated " . $stmt->affected_rows . " rows for send to registrar");
        $stmt->close();
        $conn->commit();

        // Send email to Registrar
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ludoviceylo26@gmail.com';
            $mail->Password = 'xdnt znus npyg bxuq';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('ludoviceylo26@gmail.com', 'LSPU-CCS Admin');
            $mail->addAddress($REGISTRAR_EMAIL, 'Registrar');
            $mail->isHTML(false);
            $mail->Subject = 'Subject Crediting Requests for Final Processing';
            $mail->Body = "Dear Registrar,\n\n" . count($ids) . " crediting request(s) have been approved and are ready for final processing.\n\nPlease review in the system.\n\nThank you.";
            $mail->send();
        } catch (Exception $e) {}

        // Send notifications and emails to students
        foreach ($ids as $id) {
            $stmt = $conn->prepare("SELECT student_id, student_name, student_email FROM crediting_requests WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($student_id, $student_name, $student_email);
            $stmt->fetch();
            $stmt->close();

            if (!empty($student_id)) {
                // Portal notification
                $notif_msg = "Your crediting request has been sent to the Registrar. Waiting for Dean/Admin approval.";
                $notif_stmt = $conn->prepare("INSERT INTO student_notifications (id_number, message) VALUES (?, ?)");
                $notif_stmt->bind_param("ss", $student_id, $notif_msg);
                $notif_stmt->execute();
                $notif_stmt->close();

                // Email notification
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'ludoviceylo26@gmail.com';
                    $mail->Password = 'xdnt znus npyg bxuq';
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->setFrom('ludoviceylo26@gmail.com', 'LSPU-CCS Admin');
                    $mail->addAddress($student_email, $student_name);
                    $mail->isHTML(false);
                    $mail->Subject = 'Crediting Request - Waiting for Approval';
                    $mail->Body = "Dear $student_name,\n\nYour crediting request has been sent to the Registrar and is now waiting for Dean/Admin approval.\n\nYou will be notified once the approval process is complete.\n\nThank you.";
                    $mail->send();
                } catch (Exception $e) {}
            }
        }
    }
    exit;
}

// Search and filter
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
    $where[] = "status='pending'";
} elseif ($filter == 'evaluating') {
    $where[] = "status='evaluating'";
} elseif ($filter == 'preparing_document') {
    $where[] = "status='preparing_document'";
} elseif ($filter == 'sent_to_registrar') {
    $where[] = "status='sent_to_registrar'";
} elseif ($filter == 'approved') {
    $where[] = "status='approved'";
} elseif ($filter == 'declined') {
    $where[] = "status='declined'";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$query = "SELECT * FROM crediting_requests $whereClause ORDER BY date_submitted DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    $result = $conn->query($query);
    $requests = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crediting Requests - Admin</title>
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

        .header {
            background: #667eea;
            padding: 1rem 2rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
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

        .sidebar {
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

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar.collapsed .sidebar-menu a span {
            display: none;
        }

        .sidebar.collapsed .sidebar-menu a {
            justify-content: center;
            padding: 1rem;
        }

        .sidebar.collapsed:hover {
            width: 260px;
        }

        .sidebar.collapsed:hover .sidebar-menu a span {
            display: inline;
        }

        .sidebar.collapsed:hover .sidebar-menu a {
            justify-content: flex-start;
            padding: 1rem 1.5rem;
        }

        .sidebar-menu {
            padding: 1.5rem 0;
        }

        .sidebar-menu a {
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

        .sidebar-menu a:hover {
            background: #f0f4ff;
            color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .sidebar-menu a.active {
            background: #667eea;
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            font-weight: 600;
        }

        .sidebar-menu a i {
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

        .content-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            animation: fadeIn 0.5s ease;
        }

        .page-header {
            margin-bottom: 2rem;
            animation: slideInDown 0.6s ease;
        }

        .page-header h2 {
            font-size: 1.75rem;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #718096;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            animation: scaleIn 0.7s ease;
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

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        tr:hover td {
            background: #f7fafc;
        }

        th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }

        .view-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .view-btn:hover {
            background: #5568d3;
            transform: translateY(-1px);
        }

        .approve-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            margin-right: 0.5rem;
        }

        .approve-btn:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .reject-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
        }

        .reject-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .filter-container {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
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

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-dean-approved {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-sent {
            background: #e0e7ff;
            color: #4338ca;
        }

        #viewModal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        #viewModal .modal-content {
            background: white;
            border-radius: 20px;
            padding: 0;
            max-width: 750px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0,0,0,0.4);
        }

        #viewModal .modal-header {
            background: #667eea;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: none;
            margin-bottom: 0;
        }

        #viewModal .modal-header h3 {
            color: white;
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
        }

        #viewModal .modal-header h3 i {
            margin-right: 0.5rem;
        }

        #viewModal .close-x {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        #viewModal .close-x:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }

        #viewModal .modal-body {
            padding: 2rem;
            max-height: calc(90vh - 100px);
            overflow-y: auto;
        }

        #viewModal .detail-row {
            margin-bottom: 1.25rem;
        }

        #viewModal .detail-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        #viewModal .detail-label i {
            color: #667eea;
        }

        #viewModal .detail-value {
            color: #2d3748;
            padding: 0.875rem;
            background: #f7fafc;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            line-height: 1.6;
        }

        .eval-modal, .doc-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.6);
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .eval-modal .modal-content, .doc-modal .modal-content {
            background: white;
            border-radius: 20px;
            padding: 0;
            max-width: 750px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0,0,0,0.4);
            animation: scaleIn 0.3s ease;
        }

        .modal-header {
            background: #667eea;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: none;
        }

        .modal-header h3 {
            color: white;
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
        }

        .modal-header h3 i {
            margin-right: 0.5rem;
        }

        .modal-header .close-x {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .modal-header .close-x:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 2rem;
            max-height: calc(90vh - 150px);
            overflow-y: auto;
        }

        .modal-body textarea {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.95rem;
            resize: vertical;
            transition: all 0.3s;
        }

        .modal-body textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .modal-body label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
            margin-top: 1.25rem;
            font-size: 0.95rem;
        }

        .modal-body label i {
            margin-right: 0.5rem;
            color: #667eea;
        }

        .info-box {
            background: #f0f4ff;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
        }

        .info-box strong {
            color: #667eea;
        }

        .info-box div {
            margin-bottom: 0.75rem;
            line-height: 1.6;
        }

        .info-box div:last-child {
            margin-bottom: 0;
        }

        .file-upload-area {
            border: 2px dashed #cbd5e0;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            background: #f7fafc;
            transition: all 0.3s;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }

        .file-upload-area i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .file-upload-area p {
            color: #4a5568;
            margin: 0.5rem 0;
        }

        .file-upload-area input[type="file"] {
            display: none;
        }

        .file-name {
            margin-top: 1rem;
            padding: 0.75rem;
            background: #e0e7ff;
            border-radius: 8px;
            color: #4338ca;
            font-weight: 600;
            display: none;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f1f5f9;
        }

        .cancel-btn {
            flex: 1;
            background: #f1f5f9;
            color: #475569;
            border: none;
            padding: 0.875rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .cancel-btn:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .save-btn {
            flex: 1;
            background: #667eea;
            color: white;
            border: none;
            padding: 0.875rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .save-btn:hover {
            background: #5568d3;
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        .save-btn i {
            margin-right: 0.5rem;
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

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                top: 55px;
                height: calc(100vh - 55px);
            }

            .sidebar.show {
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

            table {
                font-size: 0.8rem;
            }

            th, td {
                padding: 0.5rem;
                font-size: 0.75rem;
            }

            .view-btn, .approve-btn, .reject-btn {
                padding: 0.4rem 0.75rem;
                font-size: 0.75rem;
            }

            .status-badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.5rem;
            }

            .eval-modal .modal-content, .doc-modal .modal-content {
                width: 95%;
                max-height: 95vh;
            }

            .modal-header {
                padding: 1rem 1.25rem;
            }

            .modal-header h3 {
                font-size: 1.1rem;
            }

            .modal-body {
                padding: 1.25rem;
            }

            .info-box {
                padding: 1rem;
                font-size: 0.85rem;
            }

            .file-upload-area {
                padding: 1.5rem;
            }

            .file-upload-area i {
                font-size: 2rem;
            }

            .btn-group {
                flex-direction: column;
            }

            #viewModal .modal-content {
                width: 95%;
                max-height: 95vh;
            }

            #viewModal .modal-header {
                padding: 1rem 1.25rem;
            }

            #viewModal .modal-header h3 {
                font-size: 1.1rem;
            }

            #viewModal .modal-body {
                padding: 1.25rem;
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

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            th, td {
                padding: 0.4rem;
                font-size: 0.7rem;
            }

            .view-btn, .approve-btn, .reject-btn {
                padding: 0.35rem 0.6rem;
                font-size: 0.7rem;
            }

            .modal-header h3 {
                font-size: 1rem;
            }

            .modal-body {
                padding: 1rem;
            }

            .modal-body label {
                font-size: 0.85rem;
            }

            .modal-body textarea {
                font-size: 0.85rem;
                padding: 0.65rem;
            }
        }
    </style>
</head>
<body>
    <?php $page_title = 'Crediting Requests'; include 'admin_header.php'; ?>

    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-menu">
            <a href="admin_dashboard.php">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin_inc.php">
                <i class="fas fa-file-alt"></i>
                <span>INC Requests</span>
            </a>
            <a href="admin_crediting.php" class="active">
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

    <div class="admin-content" id="mainContainer">
        <div class="content-card">
            <div class="page-header">
                <h2><i class="fas fa-graduation-cap"></i> Subject Crediting Requests</h2>
                <p>Manage student requests for subject crediting</p>
            </div>

            <form method="get" action="admin_crediting.php" style="margin-bottom: 1.5rem;">
                <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap;">
                    <input type="text" name="search" placeholder="Search by name or student ID" value="<?php echo htmlspecialchars($search); ?>" class="search-input" style="flex: 1; min-width: 200px; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem;">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <button type="submit" class="view-btn" style="padding: 0.75rem 1.5rem;"><i class="fas fa-search"></i> Search</button>
                    <?php if ($search): ?>
                        <a href="admin_crediting.php?filter=<?php echo htmlspecialchars($filter); ?>" class="view-btn" style="background: #f3f4f6; color: #4a5568; text-decoration: none; display: inline-flex; align-items: center; padding: 0.75rem 1.5rem;">Clear</a>
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
                <button class="filter-btn <?php echo $filter == 'evaluating' ? 'active' : ''; ?>" onclick="location.href='?filter=evaluating<?php echo $search ? '&search=' . urlencode($search) : ''; ?>'">
                    <i class="fas fa-clipboard-check"></i> Evaluating
                </button>
                <button class="filter-btn <?php echo $filter == 'preparing_document' ? 'active' : ''; ?>" onclick="location.href='?filter=preparing_document<?php echo $search ? '&search=' . urlencode($search) : ''; ?>'">
                    <i class="fas fa-file-signature"></i> Preparing Document
                </button>
                <button class="filter-btn <?php echo $filter == 'sent_to_registrar' ? 'active' : ''; ?>" onclick="location.href='?filter=sent_to_registrar<?php echo $search ? '&search=' . urlencode($search) : ''; ?>'">
                    <i class="fas fa-hourglass-half"></i> Waiting Approval
                </button>
                <button class="filter-btn <?php echo $filter == 'approved' ? 'active' : ''; ?>" onclick="location.href='?filter=approved<?php echo $search ? '&search=' . urlencode($search) : ''; ?>'">
                    <i class="fas fa-check"></i> Approved
                </button>
                <button class="filter-btn <?php echo $filter == 'declined' ? 'active' : ''; ?>" onclick="location.href='?filter=declined<?php echo $search ? '&search=' . urlencode($search) : ''; ?>'">
                    <i class="fas fa-times"></i> Declined
                </button>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;">
                <div style="display: flex; gap: 0.75rem;">
                    <a href="export_crediting_requests.php?filter=<?php echo $filter; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="view-btn" style="background: #10b981; text-decoration: none; display: inline-flex; align-items: center;"><i class="fas fa-file-pdf"></i> Export PDF</a>
                </div>
                <div style="display: flex; gap: 0.75rem; margin-left: auto;">
                    <button class="view-btn" onclick="sendToRegistrar()" id="sendRegistrarBtn" disabled style="background: #8b5cf6;">
                        <i class="fas fa-paper-plane"></i> Send to Registrar
                    </button>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                        </th>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Subjects</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($requests)): ?>
                        <?php foreach($requests as $row): ?>
                        <tr>
                            <td>
                                <?php if ($row['approved'] == 0): ?>
                                    <input type="checkbox" class="request-checkbox" value="<?php echo $row['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($row['student_name']); ?>" 
                                        data-email="<?php echo htmlspecialchars($row['student_email']); ?>" 
                                        data-subjects="<?php echo htmlspecialchars($row['subjects_to_credit']); ?>" 
                                        onchange="updateButton()">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars(substr($row['subjects_to_credit'], 0, 50)) . '...'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['date_submitted'])); ?></td>
                            <td>
                                <span class="status-badge <?php 
                                    if ($row['status'] == 'declined') echo 'status-rejected';
                                    elseif ($row['status'] == 'evaluating') echo 'status-dean-approved';
                                    elseif ($row['status'] == 'preparing_document') echo 'status-sent';
                                    elseif ($row['status'] == 'sent_to_registrar') echo 'status-pending';
                                    elseif ($row['status'] == 'approved') echo 'status-approved';
                                    else echo 'status-pending';
                                ?>">
                                    <?php 
                                    if ($row['status'] == 'evaluating') echo 'Evaluating';
                                    elseif ($row['status'] == 'preparing_document') echo 'Preparing Document';
                                    elseif ($row['status'] == 'sent_to_registrar') echo 'Waiting Approval';
                                    else echo ucwords(str_replace('_', ' ', $row['status']));
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['status'] == 'pending'): ?>
                                    <button class="view-btn" onclick="evaluateRequest(<?php echo $row['id']; ?>)" style="background: #f59e0b;">
                                        <i class="fas fa-clipboard-check"></i> Evaluate
                                    </button>
                                <?php elseif ($row['status'] == 'evaluating'): ?>
                                    <button class="view-btn" onclick="prepareDocument(<?php echo $row['id']; ?>)" style="background: #8b5cf6;">
                                        <i class="fas fa-file-signature"></i> Prepare Document
                                    </button>
                                <?php elseif ($row['status'] == 'preparing_document'): ?>
                                    <span style="color: #10b981; font-weight: 600;"><i class="fas fa-check-circle"></i> Ready for Registrar</span>
                                <?php elseif ($row['status'] == 'sent_to_registrar'): ?>
                                    <button class="approve-btn" onclick="finalAction(<?php echo $row['id']; ?>, 'approve')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="reject-btn" onclick="finalAction(<?php echo $row['id']; ?>, 'decline')">
                                        <i class="fas fa-times"></i> Decline
                                    </button>
                                <?php else: ?>
                                    <button class="view-btn" onclick="viewRequest(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #718096;">
                                No crediting requests found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.request-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateButton();
        }

        function updateButton() {
            const checkboxes = document.querySelectorAll('.request-checkbox:checked');
            const btn = document.getElementById('sendRegistrarBtn');
            const count = checkboxes.length;
            
            btn.disabled = count === 0;
            btn.innerHTML = count > 0 ? `<i class="fas fa-paper-plane"></i> Send to Registrar (${count})` : '<i class="fas fa-paper-plane"></i> Send to Registrar';
        }

        function sendToRegistrar() {
            const checkboxes = document.querySelectorAll('.request-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one request.');
                return;
            }

            const ids = Array.from(checkboxes).map(cb => cb.value);
            const firstCheckbox = checkboxes[0];
            const studentName = firstCheckbox.dataset.name;
            const subjects = firstCheckbox.dataset.subjects;

            if (confirm(`Send ${checkboxes.length} request(s) to Registrar for final processing?`)) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'admin_crediting.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        alert('Requests sent to Registrar successfully!');
                        window.location.reload();
                    }
                };
                xhr.send('send_to_registrar=1&ids=' + encodeURIComponent(JSON.stringify(ids)));
            }
        }

        function finalAction(id, action) {
            const msg = action === 'approve' ? 'Approve this crediting request?' : 'Decline this crediting request?';
            if (!confirm(msg)) return;

            fetch('admin_crediting.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `final_action=1&id=${id}&action=${action}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'Error occurred');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('An error occurred');
            });
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContainer = document.getElementById('mainContainer');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                if (mainContainer) mainContainer.classList.toggle('collapsed');
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

        function viewRequest(id) {
            fetch(`get_crediting_details.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('modalStudentName').textContent = data.student_name;
                    document.getElementById('modalStudentId').textContent = data.student_id;
                    document.getElementById('modalEmail').textContent = data.student_email;
                    document.getElementById('modalSubjects').textContent = data.subjects_to_credit;
                    document.getElementById('modalTranscript').textContent = data.transcript_info;
                    
                    // Show credited subjects if evaluated
                    if (data.credited_subjects) {
                        const creditedRow = `
                            <div class="detail-row">
                                <div class="detail-label">Credited Subjects (Program Head Evaluation)</div>
                                <div class="detail-value" style="white-space: pre-wrap; background: #f0f4ff; border-left: 3px solid #667eea;">${data.credited_subjects}</div>
                            </div>
                        `;
                        document.querySelector('#viewModal .modal-body').insertAdjacentHTML('beforeend', creditedRow);
                    }
                    if (data.evaluation_remarks) {
                        const remarksRow = `
                            <div class="detail-row">
                                <div class="detail-label">Evaluation Remarks</div>
                                <div class="detail-value" style="white-space: pre-wrap;">${data.evaluation_remarks}</div>
                            </div>
                        `;
                        document.querySelector('#viewModal .modal-body').insertAdjacentHTML('beforeend', remarksRow);
                    }
                    if (data.document_file) {
                        const docRow = `
                            <div class="detail-row">
                                <div class="detail-label">Official Document (Secretary)</div>
                                <div class="detail-value" style="background: #f0fdf4; border-left: 3px solid #10b981;">
                                    <a href="uploads/crediting_documents/${data.document_file}" target="_blank" style="color: #10b981; text-decoration: none; font-weight: 600;">
                                        <i class="fas fa-file-pdf"></i> ${data.document_file}
                                    </button>
                                </div>
                            </div>
                        `;
                        document.querySelector('#viewModal .modal-body').insertAdjacentHTML('beforeend', docRow);
                    }
                    if (data.secretary_notes) {
                        const notesRow = `
                            <div class="detail-row">
                                <div class="detail-label">Secretary Notes</div>
                                <div class="detail-value" style="white-space: pre-wrap;">${data.secretary_notes}</div>
                            </div>
                        `;
                        document.querySelector('#viewModal .modal-body').insertAdjacentHTML('beforeend', notesRow);
                    }
                    document.getElementById('modalDate').textContent = new Date(data.date_submitted).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'});
                    document.getElementById('modalStatus').innerHTML = `<span class="status-badge ${getStatusClass(data.status)}">${data.status.replace(/_/g, ' ').toUpperCase()}</span>`;
                    document.getElementById('viewModal').style.display = 'flex';
                })
                .catch(err => alert('Error loading request details'));
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        function getStatusClass(status) {
            if (status === 'rejected') return 'status-rejected';
            if (status === 'dean_approved') return 'status-dean-approved';
            if (status === 'sent_to_registrar') return 'status-sent';
            if (status === 'pending') return 'status-pending';
            return 'status-approved';
        }

        function evaluateRequest(id) {
            fetch(`get_crediting_details.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('evalRequestId').value = id;
                    document.getElementById('evalStudentName').textContent = data.student_name;
                    document.getElementById('evalStudentId').textContent = data.student_id;
                    document.getElementById('evalSubjects').textContent = data.subjects_to_credit;
                    document.getElementById('evalTranscript').textContent = data.transcript_info;
                    document.getElementById('creditedSubjects').value = '';
                    document.getElementById('evalRemarks').value = '';
                    document.getElementById('evaluateModal').style.display = 'flex';
                })
                .catch(err => alert('Error loading request details'));
        }

        function closeEvaluateModal() {
            document.getElementById('evaluateModal').style.display = 'none';
        }

        function saveEvaluation() {
            const id = document.getElementById('evalRequestId').value;
            const credited = document.getElementById('creditedSubjects').value.trim();
            const remarks = document.getElementById('evalRemarks').value.trim();

            if (!credited) {
                alert('Please specify subjects to be credited');
                return;
            }

            fetch('admin_crediting.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `evaluate_request=1&id=${id}&credited_subjects=${encodeURIComponent(credited)}&remarks=${encodeURIComponent(remarks)}`
            })
            .then(res => res.json())
            .then(data => {
                console.log('Evaluate response:', data);
                if (data.success) {
                    alert(data.message);
                    closeEvaluateModal();
                    // Update button to next step
                    const rows = document.querySelectorAll('tbody tr');
                    console.log('Found rows:', rows.length);
                    rows.forEach(row => {
                        const checkbox = row.querySelector('.request-checkbox');
                        console.log('Checkbox:', checkbox, 'ID:', id);
                        if (checkbox && checkbox.value == id) {
                            console.log('Match found! Updating row');
                            const actionCell = row.querySelector('td:last-child');
                            actionCell.innerHTML = '<button class="view-btn" onclick="prepareDocument(' + id + ')" style="background: #8b5cf6;"><i class="fas fa-file-signature"></i> Prepare Document</button>';
                        }
                    });
                } else {
                    alert(data.message || 'Error occurred');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('An error occurred');
            });
        }

        function prepareDocument(id) {
            fetch(`get_crediting_details.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('docRequestId').value = id;
                    document.getElementById('docStudentName').textContent = data.student_name;
                    document.getElementById('docStudentId').textContent = data.student_id;
                    document.getElementById('docCreditedSubjects').textContent = data.credited_subjects || 'N/A';
                    document.getElementById('documentFile').value = '';
                    document.getElementById('fileName').style.display = 'none';
                    document.getElementById('secretaryNotes').value = '';
                    document.getElementById('documentModal').style.display = 'flex';
                })
                .catch(err => alert('Error loading request details'));
        }

        function closeDocumentModal() {
            document.getElementById('documentModal').style.display = 'none';
        }

        function handleFileSelect(input) {
            const file = input.files[0];
            if (file) {
                const fileNameDiv = document.getElementById('fileName');
                fileNameDiv.textContent = `📄 ${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
                fileNameDiv.style.display = 'block';
            }
        }

        function saveDocument() {
            const id = document.getElementById('docRequestId').value;
            const fileInput = document.getElementById('documentFile');
            const notes = document.getElementById('secretaryNotes').value.trim();

            if (!fileInput.files[0]) {
                alert('Please upload a document');
                return;
            }

            const formData = new FormData();
            formData.append('prepare_document', '1');
            formData.append('id', id);
            formData.append('document_file', fileInput.files[0]);
            formData.append('secretary_notes', notes);

            fetch('admin_crediting.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                console.log('Document response:', data);
                if (data.success) {
                    alert(data.message);
                    closeDocumentModal();
                    // Update button to approve/reject
                    const rows = document.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        const checkbox = row.querySelector('.request-checkbox');
                        if (checkbox && checkbox.value == id) {
                            const actionCell = row.querySelector('td:last-child');
                            actionCell.innerHTML = '<span style="color: #8b5cf6; font-weight: 600;"><i class="fas fa-check-circle"></i> Ready for Registrar</span>';
                        }
                    });
                } else {
                    alert(data.message || 'Error occurred');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error uploading document');
            });
        }
    </script>

    <div id="viewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-alt"></i> Request Details</h3>
                <button class="close-x" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-row">
                    <div class="detail-label">Student Name</div>
                    <div class="detail-value" id="modalStudentName"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Student ID</div>
                    <div class="detail-value" id="modalStudentId"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email</div>
                    <div class="detail-value" id="modalEmail"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Subjects to Credit</div>
                    <div class="detail-value" id="modalSubjects" style="white-space: pre-wrap;"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Transcript Info / Previous School</div>
                    <div class="detail-value" id="modalTranscript" style="white-space: pre-wrap;"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Date Submitted</div>
                    <div class="detail-value" id="modalDate"></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status</div>
                    <div class="detail-value" id="modalStatus"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="evaluateModal" class="eval-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-clipboard-check"></i> Evaluate Crediting Request</h3>
                <button class="close-x" onclick="closeEvaluateModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="evalRequestId">
                <div class="info-box">
                    <div style="margin-bottom: 0.5rem;"><strong>Student:</strong> <span id="evalStudentName"></span> (<span id="evalStudentId"></span>)</div>
                    <div style="margin-bottom: 0.5rem;"><strong>Requested Subjects:</strong></div>
                    <div id="evalSubjects" style="white-space: pre-wrap; font-size: 0.9rem;"></div>
                    <div style="margin-top: 0.5rem;"><strong>Previous School:</strong></div>
                    <div id="evalTranscript" style="white-space: pre-wrap; font-size: 0.9rem;"></div>
                </div>

                <label><i class="fas fa-list-check"></i> Subjects to be Credited *</label>
                <textarea id="creditedSubjects" rows="5" placeholder="List the subjects that will be credited (e.g., IT 101 - Programming 1, IT 102 - Data Structures)" required></textarea>

                <label><i class="fas fa-comment-dots"></i> Evaluation Remarks (Optional)</label>
                <textarea id="evalRemarks" rows="3" placeholder="Additional notes or comments about the evaluation"></textarea>

                <div class="btn-group">
                    <button class="cancel-btn" onclick="closeEvaluateModal()">Cancel</button>
                    <button class="save-btn" onclick="saveEvaluation()"><i class="fas fa-save"></i> Save Evaluation</button>
                </div>
            </div>
        </div>
    </div>

    <div id="documentModal" class="doc-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-signature"></i> Prepare Crediting Document</h3>
                <button class="close-x" onclick="closeDocumentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="docRequestId">
                <div class="info-box">
                    <div style="margin-bottom: 0.5rem;"><strong>Student:</strong> <span id="docStudentName"></span> (<span id="docStudentId"></span>)</div>
                    <div style="margin-bottom: 0.5rem;"><strong>Credited Subjects (Program Head):</strong></div>
                    <div id="docCreditedSubjects" style="white-space: pre-wrap; font-size: 0.9rem;"></div>
                </div>

                <label><i class="fas fa-file-upload"></i> Upload Official Document *</label>
                <div class="file-upload-area" onclick="document.getElementById('documentFile').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p style="font-weight: 600; font-size: 1.1rem;">Click to upload document</p>
                    <p style="font-size: 0.9rem; color: #718096;">PDF, DOC, DOCX (Max 5MB)</p>
                    <input type="file" id="documentFile" accept=".pdf,.doc,.docx" onchange="handleFileSelect(this)">
                </div>
                <div id="fileName" class="file-name"></div>

                <label><i class="fas fa-sticky-note"></i> Secretary Notes (Optional)</label>
                <textarea id="secretaryNotes" rows="3" placeholder="Additional notes or processing information"></textarea>

                <div class="btn-group">
                    <button class="cancel-btn" onclick="closeDocumentModal()">Cancel</button>
                    <button class="save-btn" onclick="saveDocument()"><i class="fas fa-save"></i> Save Document</button>
                </div>
            </div>
        </div>
    </div>
<?php include 'admin_logout_modal.php'; ?>`r`n`r`n</body>
</html>
<?php
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

$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "citizenproj";
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Bulk approve/disapprove
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["bulk_action"])) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';

    $ids = json_decode($_POST['ids'], true);
    $action = $_POST['action'];

    if (!empty($ids) && in_array($action, ['approve', 'decline'])) {
        $status = $action === 'approve' ? 'approved' : 'declined';
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("UPDATE users SET status=? WHERE id IN ($placeholders)");
        $types = 's' . str_repeat('i', count($ids));
        $stmt->bind_param($types, $status, ...$ids);
        $stmt->execute();
        $stmt->close();

        // Send emails
        foreach ($ids as $id) {
            $stmt = $conn->prepare("SELECT email, first_name, last_name FROM users WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($email, $fname, $lname);
            $stmt->fetch();
            $stmt->close();

            if ($email) {
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
                    $mail->addAddress($email);
                    $mail->isHTML(false);
                    
                    if ($action === 'approve') {
                        $mail->Subject = "Account Approved";
                        $mail->Body = "Hello $fname $lname,\n\nYour student account has been approved. You may now use the portal.\n\nThank you.";
                    } else {
                        $mail->Subject = "Account Declined";
                        $mail->Body = "Hello $fname $lname,\n\nSorry, your student account registration was declined. Please contact the registrar for more information.\n\nThank you.";
                    }
                    $mail->send();
                } catch (Exception $e) {}
            }
        }
    }
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Search and filter
$search = '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$admin_ids = ['246', '999'];
$where = ["id_number NOT IN ('" . implode("','", $admin_ids) . "')"];
$params = [];
$types = '';

if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search = trim($_GET['search']);
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR id_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

if ($filter == 'pending') {
    $where[] = "status='pending'";
} elseif ($filter == 'approved') {
    $where[] = "status='approved'";
} elseif ($filter == 'declined') {
    $where[] = "status='declined'";
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM users $whereClause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['total'];
}
$total_pages = ceil($total_records / $per_page);

// Get paginated results
$sql = "SELECT * FROM users $whereClause ORDER BY last_name, first_name LIMIT $per_page OFFSET $offset";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Accounts - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #333; overflow-x: hidden; width: 100%; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .header { background: #667eea; padding: 1rem 2rem; z-index: 100; position: fixed; top: 0; left: 0; right: 0; box-shadow: 0 2px 15px rgba(102, 126, 234, 0.25); }
        .nav-container { display: flex; justify-content: space-between; align-items: center; }
        .logo-section { display: flex; align-items: center; gap: 1rem; }
        .logo-img { width: 45px; height: 45px; border-radius: 10px; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: 2px solid rgba(255,255,255,0.2); }
        .logo-text h1 { font-size: 1.1rem; font-weight: 700; color: white; line-height: 1.2; }
        .logo-text p { font-size: 0.75rem; color: rgba(255,255,255,0.9); }
        .user-section { display: flex; align-items: center; gap: 1rem; }
        .welcome-text { font-weight: 600; color: white; font-size: 0.9rem; }
        .logout-btn { background: rgba(255,255,255,0.15); color: white; border: 1.5px solid rgba(255,255,255,0.3); padding: 0.5rem 1.2rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 0.85rem; text-decoration: none; display: inline-block; }
        .logout-btn:hover { background: rgba(255,255,255,0.25); transform: translateY(-1px); }
        .toggle-btn { background: rgba(255,255,255,0.15); border: none; color: white; font-size: 1.25rem; cursor: pointer; padding: 0.6rem; margin-right: 1rem; border-radius: 8px; }
        .sidebar { position: fixed; left: 0; top: 65px; width: 260px; height: calc(100vh - 65px); background: white; box-shadow: 4px 0 25px rgba(0,0,0,0.06); z-index: 99; overflow-y: auto; transition: all 0.3s ease; border-right: 1px solid #e8ecf4; }
        .sidebar.collapsed { width: 70px; }
        .sidebar.collapsed .sidebar-menu a span { display: none; }
        .sidebar.collapsed .sidebar-menu a { justify-content: center; padding: 1rem; }
        .sidebar.collapsed:hover { width: 260px; }
        .sidebar.collapsed:hover .sidebar-menu a span { display: inline; }
        .sidebar.collapsed:hover .sidebar-menu a { justify-content: flex-start; padding: 1rem 1.5rem; }
        .sidebar-menu { padding: 1.5rem 0; }
        .sidebar-menu a { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; margin: 0.25rem 0.75rem; color: #4a5568; text-decoration: none; transition: all 0.3s ease; font-size: 0.95rem; font-weight: 500; border-radius: 12px; }
        .sidebar-menu a:hover { background: #f0f4ff; color: #667eea; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15); }
        .sidebar-menu a.active { background: #667eea; color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); font-weight: 600; }
        .sidebar-menu a i { width: 22px; text-align: center; font-size: 1.1rem; }
        .admin-content { margin-left: 260px; margin-top: 85px; padding: 2rem; transition: margin-left 0.3s ease; }
        .admin-content.collapsed { margin-left: 70px; }
        .content-card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); width: 100%; box-sizing: border-box; overflow: hidden; animation: fadeIn 0.5s ease; }
        .page-header { margin-bottom: 2rem; animation: slideInDown 0.6s ease; }
        .page-header h2 { font-size: 1.5rem; font-weight: 700; color: #2d3748; margin-bottom: 0.5rem; }
        .page-header p { color: #718096; font-size: 0.95rem; }
        .table-scroll { width: 100%; overflow-x: auto; margin-top: 1.5rem; animation: scaleIn 0.7s ease; }
        table { width: 100%; border-collapse: collapse; min-width: 1200px; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 0.9rem; }
        th { background: #f9fafb; font-weight: 600; color: #4a5568; white-space: nowrap; }
        td { color: #2d3748; }
        tr:hover td { background: #f7fafc; }
        tbody tr { animation: fadeIn 0.4s ease backwards; }
        tbody tr:nth-child(1) { animation-delay: 0.1s; }
        tbody tr:nth-child(2) { animation-delay: 0.15s; }
        tbody tr:nth-child(3) { animation-delay: 0.2s; }
        tbody tr:nth-child(4) { animation-delay: 0.25s; }
        tbody tr:nth-child(5) { animation-delay: 0.3s; }
        tbody tr:nth-child(n+6) { animation-delay: 0.35s; }
        .action-btn { background: #667eea; color: white; border: none; border-radius: 6px; padding: 0.5rem 1rem; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.3s ease; white-space: nowrap; }
        .action-btn:hover { background: #5568d3; transform: translateY(-1px); }
        .action-btn.green { background: #10b981; }
        .action-btn.green:hover { background: #059669; }
        .action-btn.red { background: #ef4444; }
        .action-btn.red:hover { background: #dc2626; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600; }
        .status-badge.approved { background: #d1fae5; color: #065f46; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.declined { background: #fee2e2; color: #991b1b; }
        .search-input { flex: 1; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; outline: none; transition: all 0.3s ease; }
        .search-input:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .search-btn { background: #667eea; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .search-btn:hover { background: #5568d3; }
        .clear-btn { background: #f3f4f6; color: #4a5568; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.3s ease; }
        .clear-btn:hover { background: #e5e7eb; }
        .filter-container { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .filter-btn { padding: 0.5rem 1rem; border: 1px solid #e2e8f0; background: white; border-radius: 8px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s; animation: fadeIn 0.5s ease backwards; font-weight: 500; }
        .filter-btn:nth-child(1) { animation-delay: 0.1s; }
        .filter-btn:nth-child(2) { animation-delay: 0.15s; }
        .filter-btn:nth-child(3) { animation-delay: 0.2s; }
        .filter-btn:nth-child(4) { animation-delay: 0.25s; }
        .filter-btn:hover { background: #f7fafc; }
        .filter-btn.active { background: #667eea; color: white; border-color: #667eea; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 2rem; flex-wrap: wrap; }
        .page-btn { padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; background: white; border-radius: 6px; cursor: pointer; font-size: 0.9rem; transition: all 0.3s; color: #4a5568; text-decoration: none; display: inline-block; }
        .page-btn:hover { background: #f7fafc; border-color: #667eea; }
        .page-btn.active { background: #667eea; color: white; border-color: #667eea; font-weight: 600; }
        .page-btn.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
        .page-info { color: #718096; font-size: 0.9rem; }

        @media (max-width: 768px) {
            * { max-width: 100%; }
            body { overflow-x: hidden; width: 100vw; }
            .header { padding: 0.5rem 0.75rem; }
            .logo-img { width: 30px; height: 30px; }
            .logo-text h1 { font-size: 0.75rem; }
            .logo-text p { display: none; }
            .toggle-btn { margin-right: 0.25rem; padding: 0.4rem; font-size: 1rem; }
            .welcome-text { display: none; }
            .logout-btn { padding: 0.4rem 0.6rem; font-size: 0.75rem; }
            .logout-btn span { display: none; }
            .sidebar { transform: translateX(-100%); width: 250px; top: 55px; height: calc(100vh - 55px); }
            .sidebar.show { transform: translateX(0); }
            .admin-content { padding: 0 0.75rem; margin-left: 0; margin-top: 55px; max-width: 100vw; width: 100vw; }
            .content-card { padding: 1rem; }
            .page-header h2 { font-size: 1.25rem; }
            .filter-btn { font-size: 0.75rem; padding: 0.4rem 0.75rem; }
            .page-btn { font-size: 0.8rem; padding: 0.4rem 0.6rem; }
            .page-info { font-size: 0.8rem; }
            th, td { padding: 0.5rem; font-size: 0.8rem; }
        }
        @media (max-width: 480px) {
            .header { padding: 0.4rem 0.5rem; }
            .logo-img { width: 28px; height: 28px; }
            .logo-text h1 { font-size: 0.65rem; }
            .admin-content { padding: 0 0.5rem; margin-top: 55px; }
            .content-card { padding: 0.75rem; }
            .page-header h2 { font-size: 1.1rem; }
            .filter-btn { font-size: 0.7rem; padding: 0.35rem 0.6rem; }
            .filter-btn i { display: none; }
            .page-btn { font-size: 0.75rem; padding: 0.35rem 0.5rem; }
            .page-info { font-size: 0.75rem; width: 100%; text-align: center; margin-top: 0.5rem; }
            th, td { padding: 0.4rem; font-size: 0.75rem; }
            .action-btn { padding: 0.4rem 0.75rem; font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="nav-container">
            <div class="logo-section">
                <button class="toggle-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <img src="logo-ccs.webp" alt="LSPU Logo" class="logo-img">
                <div class="logo-text">
                    <h1>LSPU Computer Studies</h1>
                    <p>Student Management</p>
                </div>
            </div>
            <div class="user-section">
                <div class="welcome-text">Admin Panel</div>
                <a href="?logout=1" class="logout-btn"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>
    </header>

    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-menu">
            <a href="admin_dashboard.php"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <a href="admin_inc.php"><i class="fas fa-file-alt"></i><span>INC Requests</span></a>
            <a href="admin_crediting.php">
                <i class="fas fa-graduation-cap"></i>
                <span>Crediting Requests</span>
            </a>
            <a href="admin_interviews.php"><i class="fas fa-calendar-check"></i><span>Interview Requests</span></a>
            <a href="admin_students.php" class="active"><i class="fas fa-users"></i><span>Students</span></a>
            <a href="admin_notification.php"><i class="fas fa-bell"></i><span>Notifications</span></a>
            <a href="post_announcement.php"><i class="fas fa-bullhorn"></i><span>Announcements</span></a>
            <a href="admin_upload_grades.php">
                <i class="fas fa-upload"></i>
                <span>Upload Grades</span>
            </a>
        </nav>
    </aside>

    <div class="admin-content" id="mainContainer">
        <div class="content-card">
            <div class="page-header">
                <h2><i class="fas fa-users"></i> Student Accounts</h2>
                <p>Manage student registrations and account approvals</p>
            </div>

            <form method="get" action="admin_students.php" style="margin-bottom: 1.5rem;">
                <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap;">
                    <input type="text" name="search" placeholder="Search by name or student ID" value="<?php echo htmlspecialchars($search); ?>" class="search-input" style="flex: 1; min-width: 200px; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem;">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                    <?php if ($search): ?>
                        <a href="admin_students.php?filter=<?php echo htmlspecialchars($filter); ?>" class="clear-btn">Clear</a>
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
                    <i class="fas fa-check-circle"></i> Approved
                </button>
                <button class="filter-btn <?php echo $filter == 'declined' ? 'active' : ''; ?>" onclick="location.href='?filter=declined<?php echo $search ? '&search=' . urlencode($search) : ''; ?>'">
                    <i class="fas fa-times-circle"></i> Declined
                </button>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination" style="margin-bottom: 1.5rem;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?>&filter=<?php echo $filter; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                if ($start > 1) {
                    echo '<a href="?page=1&filter='.$filter.($search ? '&search='.urlencode($search) : '').'" class="page-btn">1</a>';
                    if ($start > 2) echo '<span class="page-btn disabled">...</span>';
                }
                
                for ($i = $start; $i <= $end; $i++) {
                    $active = $i == $page ? 'active' : '';
                    echo '<a href="?page='.$i.'&filter='.$filter.($search ? '&search='.urlencode($search) : '').'" class="page-btn '.$active.'">'.$i.'</a>';
                }
                
                if ($end < $total_pages) {
                    if ($end < $total_pages - 1) echo '<span class="page-btn disabled">...</span>';
                    echo '<a href="?page='.$total_pages.'&filter='.$filter.($search ? '&search='.urlencode($search) : '').'" class="page-btn">'.$total_pages.'</a>';
                }
                ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>&filter=<?php echo $filter; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>

                <span class="page-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
            </div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;">
                <div style="display: flex; gap: 0.75rem;">
                    <a href="export_admin_students.php?filter=<?php echo $filter; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="action-btn" style="background: #10b981; text-decoration: none;"><i class="fas fa-file-pdf"></i> Export PDF</a>
                </div>
                <div style="display: flex; gap: 0.75rem; margin-left: auto;">
                    <button class="action-btn green" onclick="bulkAction('approve')" id="approveBtn" disabled><i class="fas fa-check"></i> Approve</button>
                    <button class="action-btn red" onclick="bulkAction('decline')" id="declineBtn" disabled><i class="fas fa-times"></i> Decline</button>
                </div>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Sex</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>ID Image</th>
                            <th>COR</th>
                            <th>Date Registered</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if ($row["status"] == 'pending'): ?>
                                    <input type="checkbox" class="student-checkbox" value="<?php echo $row['id']; ?>" onchange="updateBulkButton()">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['id_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['last_name'] . ', ' . $row['first_name'] . ($row['middle_name'] ? ' ' . $row['middle_name'] : '')); ?></td>
                            <td><?php echo htmlspecialchars($row['sex']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                            <td>
                                <?php if (!empty($row['student_id_img'])): ?>
                                    <a href="<?php echo htmlspecialchars($row['student_id_img']); ?>" target="_blank" class="action-btn" style="padding:0.4rem 0.75rem; font-size:0.8rem;"></i>View</a>
                                <?php else: ?>N/A<?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['cor_file'])): ?>
                                    <a href="<?php echo htmlspecialchars($row['cor_file']); ?>" target="_blank" class="action-btn" style="padding:0.4rem 0.75rem; font-size:0.8rem;"></i>View</a>
                                <?php else: ?>N/A<?php endif; ?>
                            </td>
                            <td><?php echo isset($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : ''; ?></td>
                            <td>
                                <?php if ($row['status'] == 'approved'): ?>
                                    <span class="status-badge approved">Approved</span>
                                <?php elseif ($row['status'] == 'declined'): ?>
                                    <span class="status-badge declined">Declined</span>
                                <?php else: ?>
                                    <span class="status-badge pending">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="10" style="text-align:center;color:#888;padding:2rem;">No students found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
                if (mainContainer) mainContainer.classList.toggle('collapsed');
            }
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkButton();
        }

        function updateBulkButton() {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            const approveBtn = document.getElementById('approveBtn');
            const declineBtn = document.getElementById('declineBtn');
            const count = checkboxes.length;
            
            approveBtn.disabled = count === 0;
            declineBtn.disabled = count === 0;
            
            approveBtn.innerHTML = count > 0 ? `<i class="fas fa-check"></i> Bulk Approve (${count})` : '<i class="fas fa-check"></i> Bulk Approve';
            declineBtn.innerHTML = count > 0 ? `<i class="fas fa-times"></i> Bulk Decline (${count})` : '<i class="fas fa-times"></i> Bulk Decline';
        }

        function bulkAction(action) {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one student.');
                return;
            }

            const actionText = action === 'approve' ? 'approve' : 'decline';
            if (!confirm(`Are you sure you want to ${actionText} ${checkboxes.length} student(s)?`)) {
                return;
            }

            const ids = Array.from(checkboxes).map(cb => cb.value);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'admin_students.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    alert(`Successfully ${actionText}d ${checkboxes.length} student(s)!`);
                    window.location.reload();
                }
            };
            xhr.send('bulk_action=1&action=' + action + '&ids=' + encodeURIComponent(JSON.stringify(ids)));
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
    </script>
</body>
</html>
<?php $conn->close(); ?>

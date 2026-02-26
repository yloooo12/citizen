<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "citizenproj");
if ($conn->connect_error) die("Connection failed");

// Handle schedule submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["schedule_interview"])) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';

    $id = $_POST['id'];
    $date = $_POST['interview_date'];
    $time = $_POST['interview_time'];
    $platform = $_POST['interview_platform'];
    $link = $_POST['interview_link'] ?? '';
    $room = $_POST['interview_room'] ?? '';

    $stmt = $conn->prepare("UPDATE interview_requests SET interview_date=?, interview_time=?, interview_platform=?, interview_link=?, interview_room=?, status='scheduled' WHERE id=?");
    $stmt->bind_param("sssssi", $date, $time, $platform, $link, $room, $id);
    $stmt->execute();
    $stmt->close();

    // Get student details
    $result = $conn->query("SELECT student_name, student_email, student_id FROM interview_requests WHERE id=$id");
    $row = $result->fetch_assoc();

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
        $mail->addAddress($row['student_email'], $row['student_name']);
        $mail->isHTML(false);
        $mail->Subject = 'Interview Schedule - LSPU CCS';
        $mail->Body = "Dear {$row['student_name']},\n\nYour interview has been scheduled:\n\nDate: $date\nTime: $time\nPlatform: $platform" . ($link ? "\nLink: $link" : "") . ($room ? "\nRoom: $room" : "") . "\n\nPlease be on time.\n\nThank you.";
        $mail->send();
    } catch (Exception $e) {}

    // Send notification
    $msg = "Your interview has been scheduled for $date at $time via $platform" . ($room ? " at $room" : "");
    $conn->query("INSERT INTO student_notifications (id_number, message, title) VALUES ('{$row['student_id']}', '$msg', 'Interview Schedule')");

    header("Location: admin_interviews.php");
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
} elseif ($filter == 'scheduled') {
    $where[] = "status='scheduled'";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$query = "SELECT * FROM interview_requests $whereClause ORDER BY date_submitted DESC";

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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Interview Requests - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #333; overflow-x: hidden; width: 100%; }
        
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
        table { width: 100%; border-collapse: collapse; min-width: 1000px; }
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
        .action-btn { background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.3s ease; white-space: nowrap; }
        .action-btn:hover { background: #5568d3; transform: translateY(-1px); }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-scheduled { background: #d1fae5; color: #065f46; }
        .filter-container { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .filter-btn { padding: 0.5rem 1rem; border: 1px solid #e2e8f0; background: white; border-radius: 8px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s; animation: fadeIn 0.5s ease backwards; }
        .filter-btn:nth-child(1) { animation-delay: 0.1s; }
        .filter-btn:nth-child(2) { animation-delay: 0.15s; }
        .filter-btn:nth-child(3) { animation-delay: 0.2s; }
        .filter-btn:hover { background: #f7fafc; }
        .filter-btn.active { background: #667eea; color: white; border-color: #667eea; }
        #scheduleModal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; animation: fadeIn 0.3s ease; }
        #scheduleModal .modal-content { background: white; border-radius: 20px; padding: 0; max-width: 500px; width: 90%; max-height: 90vh; overflow: hidden; box-shadow: 0 25px 80px rgba(0,0,0,0.4); animation: scaleIn 0.3s ease; }
        .modal-header { background: #667eea; padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; border-bottom: none; }
        .modal-header h3 { color: white; font-size: 1.4rem; font-weight: 700; margin: 0; }
        .modal-header h3 i { margin-right: 0.5rem; }
        .modal-header .close-x { background: rgba(255,255,255,0.2); color: white; border: none; width: 35px; height: 35px; border-radius: 50%; font-size: 1.5rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s; }
        .modal-header .close-x:hover { background: rgba(255,255,255,0.3); transform: rotate(90deg); }
        .modal-body { padding: 2rem; max-height: calc(90vh - 150px); overflow-y: auto; }
        #scheduleModal label { font-weight: 600; margin-top: 0.75rem; display: block; color: #2d3748; font-size: 0.95rem; }
        #scheduleModal label i { margin-right: 0.5rem; color: #667eea; }
        #scheduleModal input, #scheduleModal select { width: 100%; padding: 0.875rem; border: 2px solid #e2e8f0; border-radius: 10px; margin-top: 0.25rem; font-family: inherit; font-size: 0.95rem; transition: all 0.3s; }
        #scheduleModal input:focus, #scheduleModal select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .modal-buttons { display: flex; gap: 1rem; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid #f1f5f9; }
        .cancel-btn { flex: 1; background: #f1f5f9; color: #475569; border: none; padding: 0.875rem; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 1rem; transition: all 0.3s; }
        .cancel-btn:hover { background: #e2e8f0; transform: translateY(-2px); }
        .save-btn { flex: 1; background: #667eea; color: white; border: none; padding: 0.875rem; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 1rem; transition: all 0.3s; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
        .save-btn:hover { background: #5568d3; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4); }
        .save-btn i { margin-right: 0.5rem; }
        .info-box { background: #f0f4ff; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; border-left: 4px solid #667eea; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1); }
        .info-box strong { color: #667eea; }
        .info-box div { margin-bottom: 0.75rem; line-height: 1.6; }
        .info-box div:last-child { margin-bottom: 0; }

        @media (max-width: 768px) {
            .header { padding: 0.75rem 1rem; }
            .logo-img { width: 30px; height: 30px; }
            .logo-text h1 { font-size: 0.75rem; }
            .logo-text p { display: none; }
            .welcome-text { display: none; }
            .logout-btn { padding: 0.4rem 0.6rem; font-size: 0.75rem; }
            .logout-btn span { display: none; }
            .toggle-btn { display: block; padding: 0.4rem; font-size: 1rem; }
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; top: 55px; height: calc(100vh - 55px); }
            .sidebar.show { transform: translateX(0); }
            .admin-content { margin-left: 0; margin-top: 55px; padding: 1rem; }
            .content-card { padding: 1rem; border-radius: 12px; }
            .page-header h2 { font-size: 1.25rem; }
            .page-header p { font-size: 0.85rem; }
            .filter-container { flex-wrap: wrap; gap: 0.5rem; }
            .filter-btn { font-size: 0.75rem; padding: 0.4rem 0.75rem; }
            .table-scroll { font-size: 0.8rem; }
            th, td { padding: 0.5rem; font-size: 0.75rem; }
            .action-btn { padding: 0.4rem 0.75rem; font-size: 0.75rem; }
            .status-badge { font-size: 0.7rem; padding: 0.2rem 0.5rem; }
            #scheduleModal .modal-content { width: 95%; max-height: 95vh; }
            .modal-header { padding: 1rem 1.25rem; }
            .modal-header h3 { font-size: 1.1rem; }
            .modal-body { padding: 1.25rem; }
            .info-box { padding: 1rem; font-size: 0.85rem; }
            .modal-buttons { flex-direction: column; }
        }

        @media (max-width: 480px) {
            .header { padding: 0.4rem 0.5rem; }
            .logo-img { width: 28px; height: 28px; }
            .logo-text h1 { font-size: 0.65rem; }
            .toggle-btn { padding: 0.3rem; font-size: 0.9rem; margin-right: 0.2rem; }
            .logout-btn { padding: 0.35rem 0.5rem; }
            .admin-content { padding: 0.75rem; margin-top: 60px; }
            .content-card { padding: 0.75rem; }
            .page-header h2 { font-size: 1.1rem; }
            .filter-btn { font-size: 0.7rem; padding: 0.35rem 0.6rem; }
            .filter-btn i { display: none; }
            .table-scroll { display: block; overflow-x: auto; white-space: nowrap; }
            th, td { padding: 0.4rem; font-size: 0.7rem; }
            .action-btn { padding: 0.35rem 0.6rem; font-size: 0.7rem; }
            .modal-header h3 { font-size: 1rem; }
            .modal-body { padding: 1rem; }
            #scheduleModal label { font-size: 0.85rem; }
            #scheduleModal input, #scheduleModal select { font-size: 0.85rem; padding: 0.65rem; }
        }
    </style>
</head>
<body>
    <?php 
    $page_title = 'Admission Interview Requests';
    include 'admin_header.php'; 
    ?>

    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-menu">
            <a href="admin_dashboard.php"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <a href="admin_inc.php"><i class="fas fa-file-alt"></i><span>INC Requests</span></a>
            <a href="admin_crediting.php"><i class="fas fa-graduation-cap"></i><span>Crediting Requests</span></a>
            <a href="admin_interviews.php" class="active"><i class="fas fa-calendar-check"></i><span>Interview Requests</span></a>
            <a href="admin_students.php"><i class="fas fa-users"></i><span>Students</span></a>
            <a href="admin_notification.php"><i class="fas fa-bell"></i><span>Notifications</span></a>
            <a href="post_announcement.php"><i class="fas fa-bullhorn"></i><span>Announcements</span></a>
        </nav>
    </aside>

    <div class="admin-content" id="mainContainer">
        <div class="content-card">
            <div class="page-header">
                <h2><i class="fas fa-calendar-check"></i> Admission Interview Requests</h2>
                <p>Manage freshmen admission interview schedules</p>
            </div>

            <form method="get" action="admin_interviews.php" style="margin-bottom: 1.5rem;">
                <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap;">
                    <input type="text" name="search" placeholder="Search by name or student ID" value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 200px; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem;">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <button type="submit" class="action-btn" style="padding: 0.75rem 1.5rem;"><i class="fas fa-search"></i> Search</button>
                    <?php if ($search): ?>
                        <a href="admin_interviews.php?filter=<?php echo htmlspecialchars($filter); ?>" class="action-btn" style="background: #f3f4f6; color: #4a5568; text-decoration: none; display: inline-flex; align-items: center; padding: 0.75rem 1.5rem;">Clear</a>
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
                <button class="filter-btn <?php echo $filter == 'scheduled' ? 'active' : ''; ?>" onclick="location.href='?filter=scheduled<?php echo $search ? '&search=' . urlencode($search) : ''; ?>'">
                    <i class="fas fa-check"></i> Scheduled
                </button>
            </div>

            <div class="table-scroll">
                <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Contact</th>
                        <th>Date Submitted</th>
                        <th>Interview Schedule</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($requests as $req): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($req['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($req['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($req['contact_number']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($req['date_submitted'])); ?></td>
                        <td><?php echo $req['interview_date'] ? date('M d, Y', strtotime($req['interview_date'])) . ' ' . date('h:i A', strtotime($req['interview_time'])) : 'Not set'; ?></td>
                        <td><span class="status-badge status-<?php echo $req['status']; ?>"><?php echo ucfirst($req['status']); ?></span></td>
                        <td>
                            <?php if ($req['status'] == 'pending'): ?>
                            <button class="action-btn" onclick="showScheduleModal(<?php echo $req['id']; ?>, '<?php echo htmlspecialchars($req['student_name']); ?>')">Set Schedule</button>
                            <?php else: ?>
                            <button class="action-btn" onclick="showScheduleModal(<?php echo $req['id']; ?>, '<?php echo htmlspecialchars($req['student_name']); ?>')">Update</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: #718096;">
                            No interview requests found
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="scheduleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-alt"></i> Set Interview Schedule</h3>
                <button class="close-x" onclick="closeModal()" type="button">&times;</button>
            </div>
            <div class="modal-body">
                <div class="info-box">
                    <div><strong>Student:</strong> <span id="studentName"></span></div>
                </div>
                <form method="post">
                <input type="hidden" name="id" id="requestId">
                <label><i class="fas fa-calendar"></i> Interview Date *</label>
                <input type="date" name="interview_date" required>
                <label><i class="fas fa-clock"></i> Interview Time *</label>
                <input type="time" name="interview_time" required>
                <label><i class="fas fa-video"></i> Platform *</label>
                <select name="interview_platform" id="platform" onchange="toggleFields()" required>
                    <option value="">-- Select --</option>
                    <option value="Face-to-Face">Face-to-Face</option>
                    <option value="Google Meet">Google Meet</option>
                    <option value="Zoom">Zoom</option>
                    <option value="Phone Call">Phone Call</option>
                </select>
                <div id="linkField" style="display:none;">
                    <label><i class="fas fa-link"></i> Meeting Link *</label>
                    <input type="url" name="interview_link" id="meetingLink" placeholder="https://meet.google.com/...">
                </div>
                <div id="roomField" style="display:none;">
                    <label><i class="fas fa-door-open"></i> Room *</label>
                    <input type="text" name="interview_room" id="roomInput" placeholder="e.g. Room 101, MacLab 1">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="schedule_interview" class="save-btn"><i class="fas fa-paper-plane"></i> Send Schedule</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'admin_logout_modal.php'; ?>
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
        function showScheduleModal(id, name) {
            document.getElementById('requestId').value = id;
            document.getElementById('studentName').textContent = name;
            document.getElementById('scheduleModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('scheduleModal').style.display = 'none';
        }
        function toggleFields() {
            const platform = document.getElementById('platform').value;
            const linkField = document.getElementById('linkField');
            const roomField = document.getElementById('roomField');
            const meetingLink = document.getElementById('meetingLink');
            const roomInput = document.getElementById('roomInput');
            
            if (platform === 'Google Meet' || platform === 'Zoom') {
                linkField.style.display = 'block';
                roomField.style.display = 'none';
                meetingLink.required = true;
                roomInput.required = false;
            } else if (platform === 'Face-to-Face') {
                linkField.style.display = 'none';
                roomField.style.display = 'block';
                meetingLink.required = false;
                roomInput.required = true;
            } else {
                linkField.style.display = 'none';
                roomField.style.display = 'none';
                meetingLink.required = false;
                roomInput.required = false;
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>

<?php
session_start();
if (!isset($_SESSION["is_teacher"])) {
    header("Location: login.php");
    exit;
}

require_once 'configure_email.php';
$conn = new mysqli("localhost", "root", "", "student_services");
$teacher_id = $_SESSION['id_number'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_schedule'])) {
    $alert_id = $_POST['alert_id'];
    $exam_date = $_POST['exam_date'];
    $exam_time = $_POST['exam_time'];
    $exam_venue = $_POST['exam_venue'];
    
    $conn->query("UPDATE academic_alerts SET exam_date='$exam_date', exam_time='$exam_time', exam_venue='$exam_venue', exam_details_sent=1, alert_type='EXAM' WHERE id='$alert_id'");
    
    $alert = $conn->query("SELECT student_id, course, instructor FROM academic_alerts WHERE id='$alert_id'")->fetch_assoc();
    $student = $conn->query("SELECT email, first_name FROM users WHERE id_number='{$alert['student_id']}'")->fetch_assoc();
    
    require_once 'configure_email.php';
    $subject = 'Exam Schedule: ' . $alert['course'];
    $message = "Dear {$student['first_name']},\n\nYour exam schedule for {$alert['course']} has been set:\n\nDate: $exam_date\nTime: $exam_time\nVenue: $exam_venue\n\nInstructor: {$alert['instructor']}\n\nPlease be on time.\n\nLSPU-CCS";
    sendEmail($student['email'], $subject, $message, $student['first_name']);
    
    $conn->query("INSERT INTO notifications (user_id, message, type, is_read) VALUES ('{$alert['student_id']}', 'Exam scheduled for {$alert['course']} on $exam_date at $exam_time in $exam_venue', 'academic_alert', 0)");
    
    echo "<script>alert('Exam schedule sent to student via email!'); window.location='teacher_inc_students.php';</script>";
}

$inc_alerts = [];
$result = $conn->query("SELECT aa.id, aa.student_id, aa.course, aa.instructor, aa.exam_date, aa.exam_time, aa.exam_venue, u.first_name, u.last_name, u.email 
                        FROM academic_alerts aa
                        INNER JOIN users u ON aa.student_id = u.id_number
                        WHERE aa.instructor LIKE '%{$_SESSION['last_name']}%' 
                        AND aa.alert_type IN ('INC', 'EXAM')
                        AND aa.is_resolved = 0
                        ORDER BY aa.created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $inc_alerts[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Set Exam Schedule</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; }
        .main-container { margin-left: 260px; margin-top: 85px; padding: 2rem; }
        .header { background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .header h1 { color: #2d3748; font-size: 2rem; }
        .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #667eea; color: white; padding: 1rem; text-align: left; font-weight: 600; }
        td { padding: 1rem; border-bottom: 1px solid #e2e8f0; }
        .btn { padding: 0.5rem 1rem; border-radius: 6px; border: none; cursor: pointer; font-weight: 600; }
        .btn-set { background: #10b981; color: white; }
        .btn-back { padding: 0.75rem 1.5rem; background: #667eea; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 2rem; border-radius: 12px; width: 90%; max-width: 500px; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; }
        .badge-inc { background: #f59e0b; color: white; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; }
        .badge-exam { background: #10b981; color: white; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; }
    </style>
</head>
<body>
    <?php include 'teacher_navbar.php'; ?>
    
    <div class="main-container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1><i class="fas fa-calendar-alt"></i> Set Exam Schedule</h1>
                <a href="teacher_inc_students.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Exam Details</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inc_alerts as $a): ?>
                    <tr>
                        <td><?php echo $a['last_name'] . ', ' . $a['first_name']; ?></td>
                        <td><?php echo $a['email']; ?></td>
                        <td><?php echo $a['course']; ?></td>
                        <td>
                            <?php if ($a['exam_date']): ?>
                                <span class="badge-exam">EXAM SET</span>
                            <?php else: ?>
                                <span class="badge-inc">INC</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($a['exam_date']): ?>
                                <?php echo $a['exam_date'] . ' ' . $a['exam_time'] . '<br>' . $a['exam_venue']; ?>
                            <?php else: ?>
                                Not set
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-set" onclick="openModal(<?php echo $a['id']; ?>, '<?php echo $a['course']; ?>')">
                                <i class="fas fa-calendar-plus"></i> Set Schedule
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 1.5rem;"><i class="fas fa-calendar-alt"></i> Set Exam Schedule</h2>
            <form method="POST">
                <input type="hidden" name="alert_id" id="alert_id">
                <div class="form-group">
                    <label>Course</label>
                    <input type="text" id="course_name" readonly style="background: #f8fafc;">
                </div>
                <div class="form-group">
                    <label>Exam Date</label>
                    <input type="date" name="exam_date" required>
                </div>
                <div class="form-group">
                    <label>Exam Time</label>
                    <input type="time" name="exam_time" required>
                </div>
                <div class="form-group">
                    <label>Exam Venue</label>
                    <input type="text" name="exam_venue" placeholder="e.g. Room 301" required>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" name="set_schedule" class="btn btn-set" style="flex: 1;">
                        <i class="fas fa-paper-plane"></i> Send to Student
                    </button>
                    <button type="button" onclick="closeModal()" class="btn" style="background: #e2e8f0; flex: 1;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(alertId, course) {
            document.getElementById('alert_id').value = alertId;
            document.getElementById('course_name').value = course;
            document.getElementById('scheduleModal').style.display = 'block';
        }
        function closeModal() {
            document.getElementById('scheduleModal').style.display = 'none';
        }
        window.onclick = function(event) {
            if (event.target == document.getElementById('scheduleModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>

<?php
session_start();
if (!isset($_SESSION["user_type"]) || $_SESSION["user_type"] !== "secretary") {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Get all interview requests
$query = "SELECT * FROM admission_interviews ORDER BY created_at DESC";
$result = $conn->query($query);
$requests = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Handle schedule setting
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["set_schedule"])) {
    $id = $_POST['id'];
    $interview_date = $_POST['interview_date'];
    $interview_time = $_POST['interview_time'];
    $platform = $_POST['platform'];
    $room = $_POST['room'] ?? '';
    $meeting_link = $_POST['meeting_link'] ?? '';
    
    // Check if columns exist, if not add them
    $check_room = $conn->query("SHOW COLUMNS FROM admission_interviews LIKE 'room'");
    if ($check_room->num_rows == 0) {
        $conn->query("ALTER TABLE admission_interviews ADD COLUMN room VARCHAR(100) DEFAULT NULL AFTER scheduled_date");
    }
    $check_link = $conn->query("SHOW COLUMNS FROM admission_interviews LIKE 'meeting_link'");
    if ($check_link->num_rows == 0) {
        $conn->query("ALTER TABLE admission_interviews ADD COLUMN meeting_link VARCHAR(255) DEFAULT NULL AFTER room");
    }
    
    $stmt = $conn->prepare("UPDATE admission_interviews SET scheduled_date=?, room=?, meeting_link=?, status='scheduled' WHERE id=?");
    if ($stmt) {
        $scheduled_datetime = $interview_date . ' ' . $interview_time;
        $stmt->bind_param("sssi", $scheduled_datetime, $room, $meeting_link, $id);
        $stmt->execute();
        $stmt->close();
        
        // Get student info and send notification
        $result = $conn->query("SELECT student_id, student_name, email FROM admission_interviews WHERE id=$id");
        if ($result && $row = $result->fetch_assoc()) {
            $student_id = $row['student_id'];
            $student_name = $row['student_name'];
            $student_email = $row['email'];
            $room_info = !empty($room) ? " at $room" : '';
            $link_info = !empty($meeting_link) ? " Link: $meeting_link" : '';
            $message = "✅ Your admission interview has been scheduled for $interview_date at $interview_time via $platform$room_info$link_info";
            $conn->query("INSERT INTO notifications (user_id, message, type, is_read) VALUES ('$student_id', '$message', 'interview_scheduled', 0)");
            
            // Update academic alert
            $conn->query("UPDATE academic_alerts SET grade='SCHEDULED', reason='Interview scheduled', intervention='Attend interview on $interview_date at $interview_time via $platform$room_info$link_info' WHERE student_id='$student_id' AND alert_type='INTERVIEW' AND is_resolved=0");
            
            // Send email
            require_once 'send_email_interview.php';
            sendInterviewScheduledEmail($student_email, $student_name, $interview_date, $interview_time, $platform, $room, $meeting_link);
        }
        
        echo json_encode(['success' => true, 'message' => 'Interview scheduled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}



$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Requests - Secretary</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; color: #333; }
        .main-container { margin-left: 260px; margin-top: 65px; padding: 2rem 2.5rem; height: calc(100vh - 65px); overflow-y: auto; transition: margin-left 0.3s ease; }
        .main-container.collapsed { margin-left: 70px; }
        .card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .card h2 { font-size: 1.5rem; color: #2d3748; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; color: #4a5568; }
        tr:hover td { background: #f7fafc; }
        .btn { background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
        .btn:hover { background: #5568d3; }
        .status-pending { background: #fef3c7; color: #92400e; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        .status-scheduled { background: #d1fae5; color: #065f46; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 16px; padding: 2rem; max-width: 500px; width: 90%; }
        .modal-content h3 { margin-bottom: 1.5rem; color: #2d3748; }
        .modal-content label { display: block; font-weight: 600; margin-top: 1rem; margin-bottom: 0.5rem; }
        .modal-content input, .modal-content select { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; }
        .modal-buttons { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .cancel-btn { flex: 1; background: #f3f4f6; color: #374151; border: none; padding: 0.75rem; border-radius: 8px; font-weight: 600; cursor: pointer; }
        @media (max-width: 768px) { .main-container { margin-left: 0; padding: 1rem; } }
    </style>
</head>
<body>
    <?php include 'secretary_navbar.php'; ?>

    <main class="main-container" id="mainContainer">
        <div class="card">
            <h2><i class="fas fa-microphone"></i> Admission Interview Requests</h2>
            <p style="color: #718096; margin-bottom: 1.5rem; font-size: 0.95rem;">Manage freshmen admission interview requests</p>

        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Student ID</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Interview Details</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($requests)): ?>
                    <?php foreach($requests as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td>
                            <span class="status-<?php echo $row['status']; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'scheduled' && !empty($row['scheduled_date'])): ?>
                                <strong>Date/Time:</strong> <?php echo date('M d, Y h:i A', strtotime($row['scheduled_date'])); ?><br>
                                <?php if (!empty($row['room'])): ?>
                                    <strong>Room:</strong> <?php echo htmlspecialchars($row['room']); ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <em style="color: #718096;">Not scheduled yet</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'pending'): ?>
                                <button class="btn" onclick="showScheduleModal(<?php echo $row['id']; ?>)">
                                    <i class="fas fa-calendar-plus"></i> Set Schedule
                                </button>
                            <?php else: ?>
                                <span style="color: #10b981; font-weight: 600;"><i class="fas fa-check-circle"></i> Scheduled</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: #718096;">
                            No interview requests
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </main>

    <!-- Schedule Modal -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-calendar-check"></i> Set Interview Schedule</h3>
            <input type="hidden" id="requestId">
            
            <label>Interview Date *</label>
            <input type="date" id="interviewDate" required>
            
            <label>Interview Time *</label>
            <input type="time" id="interviewTime" required>
            
            <label>Platform *</label>
            <select id="platform" required onchange="toggleFields()">
                <option value="">Select platform</option>
                <option value="Google Meet">Google Meet</option>
                <option value="Zoom">Zoom</option>
                <option value="Microsoft Teams">Microsoft Teams</option>
                <option value="Face-to-Face">Face-to-Face</option>
            </select>
            
            <div id="linkField" style="display: none;">
                <label>Meeting Link *</label>
                <input type="url" id="meetingLink" placeholder="https://meet.google.com/xxx or https://zoom.us/j/xxx">
            </div>
            
            <div id="roomField" style="display: none;">
                <label>Room *</label>
                <input type="text" id="room" placeholder="e.g. Room 301, CCS Building">
            </div>
            
            <div class="modal-buttons">
                <button class="cancel-btn" onclick="closeModal()">Cancel</button>
                <button class="btn" onclick="submitSchedule()" style="flex: 1;">Set Schedule</button>
            </div>
        </div>
    </div>

    <script>
        function showScheduleModal(id) {
            document.getElementById('requestId').value = id;
            document.getElementById('scheduleModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('scheduleModal').style.display = 'none';
        }
        
        function toggleFields() {
            const platform = document.getElementById('platform').value;
            const roomField = document.getElementById('roomField');
            const roomInput = document.getElementById('room');
            const linkField = document.getElementById('linkField');
            const linkInput = document.getElementById('meetingLink');
            
            if (platform === 'Face-to-Face') {
                roomField.style.display = 'block';
                roomInput.required = true;
                linkField.style.display = 'none';
                linkInput.required = false;
                linkInput.value = '';
            } else if (platform === 'Google Meet' || platform === 'Zoom' || platform === 'Microsoft Teams') {
                linkField.style.display = 'block';
                linkInput.required = true;
                roomField.style.display = 'none';
                roomInput.required = false;
                roomInput.value = '';
            } else {
                roomField.style.display = 'none';
                roomInput.required = false;
                roomInput.value = '';
                linkField.style.display = 'none';
                linkInput.required = false;
                linkInput.value = '';
            }
        }
        
        function submitSchedule() {
            const id = document.getElementById('requestId').value;
            const date = document.getElementById('interviewDate').value;
            const time = document.getElementById('interviewTime').value;
            const platform = document.getElementById('platform').value;
            const room = document.getElementById('room').value;
            const meetingLink = document.getElementById('meetingLink').value;
            
            if (!date || !time || !platform) {
                alert('Please fill all fields');
                return;
            }
            
            if (platform === 'Face-to-Face' && !room) {
                alert('Please specify the room for face-to-face interview');
                return;
            }
            
            if ((platform === 'Google Meet' || platform === 'Zoom' || platform === 'Microsoft Teams') && !meetingLink) {
                alert('Please provide the meeting link');
                return;
            }
            
            const formData = new FormData();
            formData.append('set_schedule', '1');
            formData.append('id', id);
            formData.append('interview_date', date);
            formData.append('interview_time', time);
            formData.append('platform', platform);
            formData.append('room', room);
            formData.append('meeting_link', meetingLink);
            
            fetch('secretary_interviews.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    closeModal();
                    window.location.reload();
                }
            });
        }
    </script>
</body>
</html>

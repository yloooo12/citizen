<?php
session_start();
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Handle marking student for crediting
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["mark_crediting"])) {
    $user_id = $_POST['user_id'];
    $student_id = $_POST['student_id'];
    $subjects = $_POST['subjects'];
    $reason = $_POST['reason'];
    
    $conn2 = new mysqli("localhost", "root", "", "citizenproj");
    $subjects_arr = explode("\n", trim($subjects));
    foreach ($subjects_arr as $subj) {
        $subj = trim($subj);
        if (!empty($subj)) {
            $stmt = $conn2->prepare("INSERT INTO crediting_eligibility (user_id, student_id, course, reason, is_submitted) VALUES (?, ?, ?, ?, 0)");
            $stmt->bind_param("isss", $user_id, $student_id, $subj, $reason);
            $stmt->execute();
        }
    }
    
    // Create alert
    $stmt = $conn2->prepare("INSERT INTO academic_alerts (user_id, student_id, alert_type, course, reason, intervention) VALUES (?, ?, 'CREDITING', 'Subject Crediting', ?, 'Submit crediting request in the portal')");
    $stmt->bind_param("iss", $user_id, $student_id, $reason);
    $stmt->execute();
    $conn2->close();
    
    echo "<script>alert('Student marked for crediting'); window.location.reload();</script>";
    exit;
}

// Get all students
$students = [];
$result = $conn->query("SELECT id, id_number, first_name, last_name, email FROM users WHERE user_type IS NULL OR user_type='student' ORDER BY last_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Identify Crediting Students</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; }
        .header { background: #667eea; padding: 1rem 2rem; color: white; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card h2 { font-size: 1.5rem; color: #2d3748; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; color: #4a5568; }
        .btn { background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; }
        .btn:hover { background: #5568d3; }
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 20px; max-width: 600px; width: 90%; padding: 2rem; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h3 { font-size: 1.3rem; color: #2d3748; }
        .close-x { background: #f3f4f6; border: none; width: 35px; height: 35px; border-radius: 50%; font-size: 1.5rem; cursor: pointer; }
        label { display: block; font-weight: 600; color: #2d3748; margin-top: 1rem; margin-bottom: 0.5rem; }
        input, textarea { width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; font-family: inherit; }
        textarea { resize: vertical; }
        .btn-group { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .cancel-btn { flex: 1; background: #f1f5f9; color: #475569; border: none; padding: 0.75rem; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .search-box { margin-bottom: 1.5rem; }
        .search-box input { width: 100%; max-width: 400px; }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-user-graduate"></i> Identify Crediting Students</h1>
        <a href="dean_student_upload.php" style="color: white; text-decoration: none;"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="container">
        <div class="card">
            <h2><i class="fas fa-users"></i> All Students</h2>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search by name or ID..." onkeyup="searchTable()">
            </div>
            <table id="studentsTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['id_number']); ?></td>
                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td>
                            <button class="btn" onclick="openModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['id_number']); ?>', '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>')">
                                <i class="fas fa-graduation-cap"></i> Mark for Crediting
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="creditingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-graduation-cap"></i> Mark Student for Crediting</h3>
                <button class="close-x" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="userId">
                <input type="hidden" name="student_id" id="studentId">
                
                <label>Student Name</label>
                <input type="text" id="studentName" readonly>
                
                <label>Subjects for Crediting (one per line) *</label>
                <textarea name="subjects" rows="5" placeholder="IT 101 - Introduction to Computing&#10;IT 102 - Programming 1" required></textarea>
                
                <label>Reason *</label>
                <textarea name="reason" rows="2" placeholder="Transferee from PUP Manila" required></textarea>
                
                <div class="btn-group">
                    <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="mark_crediting" class="btn" style="flex: 1; margin: 0;">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(userId, studentId, studentName) {
            document.getElementById('userId').value = userId;
            document.getElementById('studentId').value = studentId;
            document.getElementById('studentName').value = studentName;
            document.getElementById('creditingModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('creditingModal').style.display = 'none';
        }

        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('studentsTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const tdId = tr[i].getElementsByTagName('td')[0];
                const tdName = tr[i].getElementsByTagName('td')[1];
                if (tdId || tdName) {
                    const txtId = tdId.textContent || tdId.innerText;
                    const txtName = tdName.textContent || tdName.innerText;
                    if (txtId.toUpperCase().indexOf(filter) > -1 || txtName.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
    </script>
</body>
</html>

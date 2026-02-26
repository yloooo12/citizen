<?php
session_start();
if (!isset($_SESSION["is_teacher"]) || $_SESSION["is_teacher"] !== true) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "student_services");
$teacher_id = $_SESSION['id_number'];

$students = [];
$result = $conn->query("SELECT DISTINCT u.id_number, u.first_name, u.last_name, u.email, ss.subject_code, ss.subject_title, ss.section, ss.year_level, ss.semester, ss.program, ss.school_year 
                        FROM users u 
                        INNER JOIN student_subjects ss ON u.id_number = ss.student_id 
                        LEFT JOIN grades g ON u.id_number = g.student_id AND ss.subject_code = g.subject_code AND g.teacher_id = '$teacher_id' AND g.column_name = 'finals_Exam'
                        WHERE ss.teacher_id = '$teacher_id' 
                        AND (ss.archived IS NULL OR ss.archived = 0)
                        AND (g.grade IS NULL OR g.grade != 0)
                        ORDER BY ss.school_year DESC, ss.section, ss.subject_code, u.last_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $key = $row['section'] . '|' . $row['semester'] . '|' . $row['program'];
        $students[$key][] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Students</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; }
        .header { background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .header h1 { color: #2d3748; font-size: 2rem; }
        .table-container { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #667eea; color: white; padding: 1rem; text-align: left; font-weight: 600; }
        td { padding: 1rem; border-bottom: 1px solid #e2e8f0; }
        tr:hover { background: #f7fafc; }
        .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 1.5rem; }
        .pagination button { padding: 0.5rem 1rem; border: 1px solid #e2e8f0; background: white; border-radius: 6px; cursor: pointer; }
        .pagination button.active { background: #667eea; color: white; border-color: #667eea; }
    </style>
</head>
<body>
    <?php include 'teacher_navbar.php'; ?>
    <div class="main-container" id="mainContainer">
        <div class="header">
            <h1><i class="fas fa-users"></i> My Students (<span id="studentCount">0</span>)</h1>
            <div style="background: #fef3c7; padding: 1rem; border-radius: 8px; margin: 1rem 0; display: flex; gap: 1rem; align-items: center;">
                <i class="fas fa-archive" style="color: #f59e0b; font-size: 1.5rem;"></i>
                <div style="flex: 1;"><strong>Archive Class:</strong> Select subject/section to archive (INC students excluded)</div>
                <select id="archiveSelect" style="padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; min-width: 300px;">
                    <option value="">Select Class to Archive</option>
                    <?php
                    $conn2 = new mysqli("localhost", "root", "", "student_services");
                    $arch = $conn2->query("SELECT DISTINCT subject_code, subject_title, section, semester, school_year
                                          FROM student_subjects 
                                          WHERE teacher_id = '$teacher_id' 
                                          AND (archived IS NULL OR archived = 0)
                                          ORDER BY school_year DESC, subject_code");
                    while ($a = $arch->fetch_assoc()) {
                        echo "<option value='{$a['subject_code']}|{$a['section']}|{$a['semester']}|{$a['school_year']}'>{$a['subject_code']} - {$a['subject_title']} (Sec {$a['section']}, {$a['semester']} Sem, {$a['school_year']})</option>";
                    }
                    $conn2->close();
                    ?>
                </select>
                <button onclick="archiveClass()" style="padding: 0.75rem 1.5rem; background: #f59e0b; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;"><i class="fas fa-archive"></i> Archive</button>
            </div>
            <div style="display: flex; gap: 1rem;">
                <input type="text" id="searchInput" placeholder="Search by name or ID..." oninput="applyFilters()" style="flex: 1; max-width: 300px; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px;">
                <select id="yearFilter" onchange="applyFilters()" style="flex: 1; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <option value="">All Year Levels</option>
                    <?php
                    $years = [];
                    foreach ($students as $group) {
                        foreach ($group as $s) {
                            if (!in_array($s['year_level'], $years)) {
                                echo "<option value='{$s['year_level']}'>{$s['year_level']}</option>";
                                $years[] = $s['year_level'];
                            }
                        }
                    }
                    ?>
                </select>
                <select id="sectionFilter" onchange="applyFilters()" style="flex: 1; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <option value="">All Sections</option>
                    <?php
                    $sections = [];
                    foreach ($students as $key => $group) {
                        list($section, $semester, $program) = explode('|', $key);
                        if (!in_array($section, $sections)) {
                            echo "<option value='$section'>Section $section</option>";
                            $sections[] = $section;
                        }
                    }
                    ?>
                </select>
                <select id="semesterFilter" onchange="applyFilters()" style="flex: 1; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <option value="">All Semesters</option>
                    <option value="1st">1st Semester</option>
                    <option value="2nd">2nd Semester</option>
                </select>
                <select id="schoolYearFilter" onchange="applyFilters()" style="flex: 1; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <option value="">All School Years</option>
                    <?php
                    $years = [];
                    $conn2 = new mysqli("localhost", "root", "", "student_services");
                    $sy_result = $conn2->query("SELECT DISTINCT school_year FROM student_subjects WHERE teacher_id = '$teacher_id' ORDER BY school_year DESC");
                    while ($sy = $sy_result->fetch_assoc()) {
                        echo "<option value='{$sy['school_year']}'>{$sy['school_year']}</option>";
                    }
                    $conn2->close();
                    ?>
                </select>
                <a href="teacher_inc_students.php" style="padding: 0.75rem 1.5rem; background: #f59e0b; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; white-space: nowrap;"><i class="fas fa-exclamation-triangle"></i> INC Students</a>
                <a href="teacher_archived.php" style="padding: 0.75rem 1.5rem; background: #718096; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; white-space: nowrap;"><i class="fas fa-archive"></i> View Archive</a>
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Section</th>
                        <th>Year Level</th>
                        <th>Semester</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $key => $group): 
                        foreach ($group as $s): 
                    ?>
                    <tr data-section="<?php echo $s['section']; ?>" data-semester="<?php echo $s['semester']; ?>" data-year="<?php echo $s['year_level']; ?>" data-schoolyear="<?php echo $s['school_year'] ?? ''; ?>">
                        <td><?php echo $s['id_number']; ?></td>
                        <td><?php echo $s['last_name'] . ', ' . $s['first_name']; ?></td>
                        <td><?php echo $s['email']; ?></td>
                        <td><?php echo $s['subject_code'] . ' - ' . $s['subject_title']; ?></td>
                        <td><?php echo $s['section']; ?></td>
                        <td><?php echo $s['year_level']; ?></td>
                        <td><?php echo $s['semester']; ?></td>
                    </tr>
                    <?php endforeach; endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="pagination" id="pagination"></div>
    </div>
    
    <script>
        const rowsPerPage = 10;
        let currentPage = 1;
        let filteredRows = [];
        
        function applyFilters() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const yearFilter = document.getElementById('yearFilter').value;
            const sectionFilter = document.getElementById('sectionFilter').value;
            const semesterFilter = document.getElementById('semesterFilter').value;
            const schoolYearFilter = document.getElementById('schoolYearFilter').value;
            
            const rows = document.querySelectorAll('tbody tr[data-section]');
            filteredRows = [];
            
            rows.forEach(row => {
                const section = row.dataset.section;
                const semester = row.dataset.semester;
                const year = row.dataset.year;
                const schoolYear = row.dataset.schoolyear;
                const idNumber = row.cells[0].textContent.toLowerCase();
                const name = row.cells[1].textContent.toLowerCase();
                
                const searchMatch = !searchInput || idNumber.includes(searchInput) || name.includes(searchInput);
                const yearMatch = !yearFilter || year === yearFilter;
                const sectionMatch = !sectionFilter || section === sectionFilter;
                const semesterMatch = !semesterFilter || semester === semesterFilter;
                const schoolYearMatch = !schoolYearFilter || schoolYear === schoolYearFilter;
                
                if (searchMatch && yearMatch && sectionMatch && semesterMatch && schoolYearMatch) {
                    filteredRows.push(row);
                }
            });
            
            document.getElementById('studentCount').textContent = filteredRows.length;
            currentPage = 1;
            renderPagination();
            showPage(1);
        }
        
        function showPage(page) {
            currentPage = page;
            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            
            const allRows = document.querySelectorAll('tbody tr[data-section]');
            allRows.forEach(row => row.style.display = 'none');
            
            filteredRows.forEach((row, index) => {
                if (index >= start && index < end) {
                    row.style.display = 'table-row';
                }
            });
            
            document.querySelectorAll('.pagination button').forEach(btn => btn.classList.remove('active'));
            const activeBtn = document.querySelector(`.pagination button[data-page="${page}"]`);
            if (activeBtn) activeBtn.classList.add('active');
        }
        
        function renderPagination() {
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';
            
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.dataset.page = i;
                btn.onclick = () => showPage(i);
                pagination.appendChild(btn);
            }
        }
        
        function archiveClass() {
            const select = document.getElementById('archiveSelect');
            if (!select.value) {
                alert('Please select a class to archive');
                return;
            }
            
            if (!confirm('Archive this class? Students with INC will remain active.')) return;
            
            const [subject_code, section, semester, school_year] = select.value.split('|');
            
            fetch('archive_class.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `subject_code=${subject_code}&section=${section}&semester=${semester}&school_year=${school_year}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(`Class archived! ${data.inc_count} student(s) with INC status remain active.`);
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }
        
        applyFilters();
    </script>
</body>
</html>

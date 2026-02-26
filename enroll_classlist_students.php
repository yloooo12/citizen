<?php
require_once 'config.php';

// Student IDs from Class List.doc
$student_ids = [
    '0122-0353', '0122-0417', '0122-0348', '0122-0415', '0122-0414',
    '0122-1975', '0122-0457', '0122-3402', '0121-1775', '0122-1628',
    '0122-2441', '0122-3268', '0122-0584', '0122-1132', '0122-3632',
    '0122-0783', '0122-0784', '0122-0647', '0121-3886', '0122-0702',
    '0122-0643', '0122-3625', '0122-0876', '0122-3597', '0122-1154',
    '0122-2087', '0122-1390', '0122-1064', '0122-1135'
];

// Random sections to assign
$sections = ['4A', '4B', '4C', '4D'];

// Subject details (you can modify these)
$subject_code = 'ITINFO 306';
$subject_title = 'Information Technology Course';
$program = 'B.S. Information Technology';
$year_level = '4';
$teacher_id = 1; // Change to actual teacher ID
$school_year = '2025 - 2026';
$semester = 'Second Semester';

$enrolled = 0;
$not_found = [];

foreach ($student_ids as $student_id) {
    // Check if student exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id_number = ? AND role = 'student'");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        
        // Assign random section
        $random_section = $sections[array_rand($sections)];
        
        // Insert into student_subjects
        $insert_stmt = $conn->prepare("INSERT INTO student_subjects 
            (student_id, subject_code, subject_title, program, year_level, section, teacher_id, school_year, semester) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            subject_title = VALUES(subject_title),
            program = VALUES(program),
            year_level = VALUES(year_level),
            school_year = VALUES(school_year)");
        
        $insert_stmt->bind_param("issssssss", 
            $student['id'], 
            $subject_code, 
            $subject_title, 
            $program, 
            $year_level, 
            $random_section, 
            $teacher_id, 
            $school_year, 
            $semester
        );
        
        if ($insert_stmt->execute()) {
            echo "✓ Enrolled: $student_id in Section $random_section<br>";
            $enrolled++;
        }
        $insert_stmt->close();
    } else {
        $not_found[] = $student_id;
    }
    $stmt->close();
}

echo "<br><strong>Summary:</strong><br>";
echo "Total enrolled: $enrolled<br>";
echo "Not found in database: " . count($not_found) . "<br>";

if (count($not_found) > 0) {
    echo "<br><strong>Students not found:</strong><br>";
    foreach ($not_found as $id) {
        echo "- $id<br>";
    }
}

$conn->close();
?>

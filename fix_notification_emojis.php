<?php
// Fix notification emojis - remove question marks and emoji characters
$conn = new mysqli("localhost", "root", "", "student_services");
if ($conn->connect_error) die("Connection failed");

// Update notifications to remove emoji characters
$updates = [
    "UPDATE notifications SET message = REPLACE(message, '⚠️ ', '') WHERE message LIKE '%⚠️%'",
    "UPDATE notifications SET message = REPLACE(message, '📋 ', '') WHERE message LIKE '%📋%'",
    "UPDATE notifications SET message = REPLACE(message, '✅ ', '') WHERE message LIKE '%✅%'",
    "UPDATE notifications SET message = REPLACE(message, '? ', '') WHERE message LIKE '%? %'",
    "UPDATE notifications SET message = REPLACE(message, '?? ', '') WHERE message LIKE '%?? %'"
];

foreach ($updates as $sql) {
    if ($conn->query($sql)) {
        echo "Updated: " . $conn->affected_rows . " rows<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

echo "<br><h3>Sample notifications after cleanup:</h3>";
$result = $conn->query("SELECT message, created_at FROM notifications ORDER BY created_at DESC LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "<p><strong>" . $row['created_at'] . ":</strong> " . htmlspecialchars($row['message']) . "</p>";
    }
}

$conn->close();
echo "<br><strong>Done! All emoji characters removed from notifications.</strong>";
?>

<?php
// Simple notification trigger for dean requests
// This can be included in existing request handlers

require_once 'dean_notification_system.php';

function triggerDeanNotification($type, $student_id, $student_name, $request_id = null, $additional_info = '') {
    switch ($type) {
        case 'crediting':
            return notifyDeanCreditingRequest($student_id, $student_name, $request_id);
            
        case 'inc':
            return notifyDeanIncRequest($student_id, $student_name, $request_id, $additional_info);
            
        case 'unscheduled':
            $parts = explode(' - ', $additional_info);
            $subject_code = $parts[0] ?? $additional_info;
            $subject_name = $parts[1] ?? '';
            return notifyDeanUnscheduledRequest($student_id, $student_name, $request_id, $subject_code, $subject_name);
            
        default:
            $message = "📋 New request from $student_name ($student_id)" . ($additional_info ? " - $additional_info" : "");
            return notifyDean($message, $type, $request_id, $student_id, $student_name);
    }
}

// Usage examples:
// triggerDeanNotification('crediting', '0122-1234', 'Juan Dela Cruz', 123);
// triggerDeanNotification('inc', '0122-1234', 'Juan Dela Cruz', 456, 'Math 101');
// triggerDeanNotification('unscheduled', '0122-1234', 'Juan Dela Cruz', 789, 'CS101 - Programming');
?>
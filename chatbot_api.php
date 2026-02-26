<?php
/**
 * AI Chatbot API - CCS Portal Assistant
 * Powered by GitHub Models API (GPT-4o)
 */
header('Content-Type: application/json');

// GitHub Models Configuration
define('GITHUB_TOKEN', 'github_pat_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx'); // Get from https://github.com/settings/tokens
define('GITHUB_API_URL', 'https://models.inference.ai.azure.com/chat/completions');
define('AI_MODEL', 'gpt-4o');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userMessage = trim($input['message'] ?? '');
    
    if (empty($userMessage)) {
        echo json_encode(['reply' => 'Please type a message! 💬']);
        exit;
    }
    
    // Try AI first, fallback to smart response
    $response = getGPTResponse($userMessage);
    if (!$response) {
        $response = getSmartResponse($userMessage);
    }
    
    echo json_encode(['reply' => $response]);
}

function getGPTResponse($message) {
    // Check if token is set
    if (GITHUB_TOKEN === 'github_pat_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx') {
        return false; // Use fallback
    }
    
    $systemPrompt = "You are a helpful AI assistant for Laguna State Polytechnic University (LSPU) Computer Studies Department portal. Be friendly, concise, and helpful. Key info: Registration requires Student ID and COR upload. Password reset via admin at support@lspu.edu.ph. Approval takes 1-2 days. Student ID format: XXXX-XXXX. Answer in a conversational, friendly tone with emojis when appropriate.";
    
    try {
        $data = json_encode([
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message]
            ],
            'model' => AI_MODEL,
            'temperature' => 0.7,
            'max_tokens' => 200,
            'top_p' => 0.9
        ]);
        
        $ch = curl_init(GITHUB_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . GITHUB_TOKEN,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                $aiText = trim($result['choices'][0]['message']['content']);
                if (!empty($aiText)) {
                    return $aiText;
                }
            }
        }
    } catch (Exception $e) {
        // Fallback to smart response
    }
    
    return false;
}

function getSmartResponse($message) {
    $msg = strtolower($message);
    
    // Greetings
    if (preg_match('/\b(hi|hello|hey|good morning|good afternoon|good evening|kumusta|kamusta)\b/i', $msg)) {
        return "Hello! 👋 I'm your AI assistant for LSPU CCS Portal. How can I help you today?";
    }
    
    // Registration
    if (preg_match('/\b(register|sign up|create account|new account|enroll)\b/i', $msg)) {
        return "📝 **Registration Steps:**\n\n1. Click 'Register here' below the login form\n2. Upload your Student ID image\n3. Upload your COR (Certificate of Registration)\n4. Your ID number will auto-fill\n5. Complete the form and submit\n\nApproval takes 1-2 business days! ⏱️";
    }
    
    // Documents
    if (preg_match('/\b(document|requirement|need|upload|file|cor|student id)\b/i', $msg)) {
        return "📄 **Required Documents:**\n\n✅ Student ID (image format: JPG, PNG)\n✅ Certificate of Registration - COR (PDF or image)\n\nMake sure files are clear and readable!";
    }
    
    // Password
    if (preg_match('/\b(password|forgot|reset|login|cant login|cannot login)\b/i', $msg)) {
        return "🔑 **Password Issues:**\n\nIf you forgot your password or can't login:\n• Contact the admin or IT support\n• Email: support@lspu.edu.ph\n• Visit CCS office for immediate assistance\n\nThey'll help you reset it quickly!";
    }
    
    // Grades
    if (preg_match('/\b(grade|grades|view grade|check grade|gwa|gpa|score)\b/i', $msg)) {
        return "📊 **Viewing Your Grades:**\n\n1. Go to 'My Grades' in the sidebar\n2. Select semester and school year\n3. View all your subject grades\n4. Check your GWA/GPA\n\nGrades are updated by your teachers regularly!";
    }
    
    // Approval Time
    if (preg_match('/\b(approval|approve|how long|waiting|pending|when)\b/i', $msg)) {
        return "⏱️ **Account Approval:**\n\nTypically takes **1-2 business days** after registration.\n\nYou'll receive an email notification once approved. Check your spam folder too!\n\nFor urgent concerns, contact the CCS office. 📞";
    }
    
    // Student ID Format
    if (preg_match('/\b(id format|id number|student number|format)\b/i', $msg)) {
        return "🆔 **Student ID Format:**\n\nYour student ID should be:\n**XXXX-XXXX** (8 digits with dash)\n\nExample: 2021-1234\n\nThis is found on your Student ID card and COR.";
    }
    
    // Contact/Support
    if (preg_match('/\b(contact|support|help|email|phone|office|reach)\b/i', $msg)) {
        return "📞 **Contact Information:**\n\n📧 Email: support@lspu.edu.ph\n🏢 Office: Computer Studies Department\n🕐 Hours: Monday-Friday, 8AM-5PM\n\nFor urgent concerns, visit the CCS office directly!";
    }
    
    // Browser Support
    if (preg_match('/\b(browser|chrome|firefox|safari|edge|compatible)\b/i', $msg)) {
        return "🌐 **Supported Browsers:**\n\n✅ Google Chrome (recommended)\n✅ Mozilla Firefox\n✅ Safari\n✅ Microsoft Edge\n\nPlease use the latest version for best experience!";
    }
    
    // Unscheduled Subjects
    if (preg_match('/\b(unscheduled|irregular|subject request|offering|not offered)\b/i', $msg)) {
        return "📚 **Unscheduled Subjects:**\n\nFor irregular students only:\n1. Submit request letter to Registrar\n2. Attach copy of grades\n3. Processing: 3 minutes\n4. Contact Dean/Associate Dean or Program Coordinator\n\nRegular students follow standard schedule.";
    }
    
    // Profile
    if (preg_match('/\b(profile|update profile|change info|edit profile)\b/i', $msg)) {
        return "👤 **Profile Management:**\n\n1. Click 'Profile' in sidebar\n2. View your information\n3. Upload profile picture\n4. Check enrolled subjects\n\nFor info changes, contact the Registrar's office.";
    }
    
    // AI Tools
    if (preg_match('/\b(ai tool|calculator|gpa|predictor|study|career)\b/i', $msg)) {
        return "🤖 **AI-Powered Tools:**\n\nCheck out our AI Tools page:\n• Grade Predictor\n• Schedule Optimizer\n• GPA Calculator\n• Career Analyzer\n• Study Matcher\n• Virtual Library\n\nClick 'AI Tools' in the sidebar!";
    }
    
    // Announcements
    if (preg_match('/\b(announcement|news|update|newsfeed)\b/i', $msg)) {
        return "📰 **Announcements:**\n\nView latest updates in the Newsfeed page!\n\n• Department announcements\n• Important notices\n• Events and activities\n\nStay updated with LSPU-CCS news!";
    }
    
    // Thank you
    if (preg_match('/\b(thank|thanks|salamat)\b/i', $msg)) {
        return "You're welcome! 😊 Feel free to ask if you need anything else. Happy to help!";
    }
    
    // Goodbye
    if (preg_match('/\b(bye|goodbye|see you|exit)\b/i', $msg)) {
        return "Goodbye! 👋 Have a great day! Feel free to come back anytime you need help.";
    }
    
    // Default intelligent response
    return "I understand you're asking about: \"$message\"\n\n🤔 I can help you with:\n• Registration & Documents\n• Grades & GPA\n• Password Reset\n• Contact Information\n• AI Tools\n• Unscheduled Subjects\n\nTry asking about any of these topics, or contact support@lspu.edu.ph for specific concerns!";
}

// Keep existing getSmartResponse function for fallback

<style>
.chatbot-toggle {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 60px;
    height: 60px;
    background: #667eea;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    z-index: 1001;
    transition: all 0.3s;
}
.chatbot-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
}
.chatbot-toggle i {
    color: white;
    font-size: 1.5rem;
}
.chatbot-container {
    position: fixed;
    bottom: 90px;
    right: 20px;
    width: 350px;
    height: 500px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    display: none;
    flex-direction: column;
    z-index: 1001;
    overflow: hidden;
    transition: background 0.3s ease;
}

body.dark-mode .chatbot-container {
    background: #2d3748;
}

body.dark-mode .chatbot-messages {
    background: #1a202c;
}

body.dark-mode .chat-message.bot .message-bubble {
    background: #374151;
    color: #e2e8f0;
}

body.dark-mode .chatbot-input {
    background: #2d3748;
    border-top-color: #4a5568;
}

body.dark-mode .chatbot-input input {
    background: #374151;
    border-color: #4a5568;
    color: #e2e8f0;
}

body.dark-mode .chatbot-input input::placeholder {
    color: #9ca3af;
}

body.dark-mode .quick-btn {
    background: #374151;
    border-color: #4a5568;
    color: #e2e8f0;
}

body.dark-mode .quick-btn:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}
.chatbot-container.active {
    display: flex;
}
.chatbot-header {
    background: #667eea;
    color: white;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.chatbot-header h3 {
    margin: 0;
    font-size: 1.125rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.chatbot-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.25rem;
}
.chatbot-messages {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    background: #f7fafc;
}
.chat-message {
    margin-bottom: 1rem;
    display: flex;
    gap: 0.5rem;
}
.chat-message.bot {
    justify-content: flex-start;
}
.chat-message.user {
    justify-content: flex-end;
}
.message-bubble {
    max-width: 70%;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    font-size: 0.875rem;
    line-height: 1.4;
}
.chat-message.bot .message-bubble {
    background: #e2e8f0;
    color: #2d3748;
}
.chat-message.user .message-bubble {
    background: #667eea;
    color: white;
}
.chatbot-input {
    padding: 1rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 0.5rem;
    background: white;
}
.chatbot-input input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.875rem;
}
.chatbot-input input:focus {
    outline: none;
    border-color: #667eea;
}
.chatbot-send {
    background: #667eea;
    color: white;
    border: none;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}
.chatbot-send:hover {
    background: #5568d3;
}
.quick-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 0.5rem;
}
.quick-btn {
    background: #f7fafc;
    border: 1px solid #e2e8f0;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.3s;
    color: #2d3748;
}
.quick-btn:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}
@media (max-width: 768px) {
    .chatbot-container {
        width: calc(100vw - 40px);
        height: 450px;
        bottom: 80px;
    }
}
</style>

<div class="chatbot-toggle" onclick="toggleChatbot()">
    <i class="fas fa-comments"></i>
</div>
<div class="chatbot-container" id="chatbotContainer">
    <div class="chatbot-header">
        <h3><i class="fas fa-robot"></i> AI Assistant <span style="font-size: 0.7rem; opacity: 0.9;">⚡ GPT-4o</span></h3>
        <button class="chatbot-close" onclick="toggleChatbot()">&times;</button>
    </div>
    <div class="chatbot-messages" id="chatbotMessages">
        <div class="chat-message bot">
            <div class="message-bubble">
                👋 Hi! I'm your AI-powered CCS Portal assistant. Ask me anything about registration, grades, documents, or portal features!
            </div>
        </div>
        <div class="quick-actions" id="quickActions">
            <button class="quick-btn" onclick="sendQuickMessage('How do I register?')">📝 Register</button>
            <button class="quick-btn" onclick="sendQuickMessage('What documents do I need?')">📄 Documents</button>
            <button class="quick-btn" onclick="sendQuickMessage('I forgot my password')">🔑 Password</button>
            <button class="quick-btn" onclick="sendQuickMessage('How long does approval take?')">⏱️ Approval</button>
            <button class="quick-btn" onclick="sendQuickMessage('What is my student ID format?')">🆔 ID Format</button>
            <button class="quick-btn" onclick="sendQuickMessage('Who can I contact for help?')">📞 Contact</button>
            <button class="quick-btn" onclick="sendQuickMessage('What browsers are supported?')">🌐 Browser</button>
            <button class="quick-btn" onclick="sendQuickMessage('How to request unscheduled subjects?')">📚 Unscheduled</button>
        </div>
    </div>
    <div class="chatbot-input">
        <input type="text" id="chatbotInput" placeholder="Type your question..." onkeypress="if(event.key==='Enter') sendMessage()">
        <button class="chatbot-send" onclick="sendMessage()">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
function toggleChatbot() {
    const container = document.getElementById('chatbotContainer');
    container.classList.toggle('active');
    if (container.classList.contains('active')) {
        document.getElementById('chatbotInput').focus();
    }
}

async function sendMessage() {
    const input = document.getElementById('chatbotInput');
    const message = input.value.trim();
    if (!message) return;
    addMessage(message, 'user');
    input.value = '';
    
    // Show typing indicator
    const typingId = 'typing-' + Date.now();
    addTypingIndicator(typingId);
    
    try {
        const response = await fetch('chatbot_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        });
        const data = await response.json();
        removeTypingIndicator(typingId);
        addMessage(data.reply, 'bot');
    } catch (error) {
        removeTypingIndicator(typingId);
        addMessage('Sorry, I encountered an error. Please try again. 😔', 'bot');
    }
}

function addMessage(text, sender) {
    const messagesDiv = document.getElementById('chatbotMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chat-message ' + sender;
    messageDiv.innerHTML = '<div class="message-bubble">' + text + '</div>';
    messagesDiv.appendChild(messageDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

async function sendQuickMessage(message) {
    addMessage(message, 'user');
    
    // Show typing indicator
    const typingId = 'typing-' + Date.now();
    addTypingIndicator(typingId);
    
    try {
        const response = await fetch('chatbot_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        });
        const data = await response.json();
        removeTypingIndicator(typingId);
        addMessage(data.reply, 'bot');
        showQuickActions();
    } catch (error) {
        removeTypingIndicator(typingId);
        addMessage('Sorry, I encountered an error. Please try again. 😔', 'bot');
        showQuickActions();
    }
}

function addTypingIndicator(id) {
    const messagesDiv = document.getElementById('chatbotMessages');
    const typingDiv = document.createElement('div');
    typingDiv.id = id;
    typingDiv.className = 'chat-message bot';
    typingDiv.innerHTML = '<div class="message-bubble" style="padding: 0.5rem 1rem;"><span style="animation: blink 1.4s infinite;">●</span><span style="animation: blink 1.4s infinite 0.2s;">●</span><span style="animation: blink 1.4s infinite 0.4s;">●</span></div>';
    messagesDiv.appendChild(typingDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    
    // Add blink animation if not exists
    if (!document.getElementById('blinkStyle')) {
        const style = document.createElement('style');
        style.id = 'blinkStyle';
        style.textContent = '@keyframes blink { 0%, 60%, 100% { opacity: 0.3; } 30% { opacity: 1; } }';
        document.head.appendChild(style);
    }
}

function removeTypingIndicator(id) {
    const typingDiv = document.getElementById(id);
    if (typingDiv) typingDiv.remove();
}

function showQuickActions() {
    const messagesDiv = document.getElementById('chatbotMessages');
    const existing = document.getElementById('quickActions');
    if (existing) existing.remove();
    const quickDiv = document.createElement('div');
    quickDiv.id = 'quickActions';
    quickDiv.className = 'quick-actions';
    quickDiv.innerHTML = `
        <button class="quick-btn" onclick="sendQuickMessage('How do I register?')">📝 Register</button>
        <button class="quick-btn" onclick="sendQuickMessage('What documents do I need?')">📄 Documents</button>
        <button class="quick-btn" onclick="sendQuickMessage('I forgot my password')">🔑 Password</button>
        <button class="quick-btn" onclick="sendQuickMessage('How long does approval take?')">⏱️ Approval</button>
        <button class="quick-btn" onclick="sendQuickMessage('What is my student ID format?')">🆔 ID Format</button>
        <button class="quick-btn" onclick="sendQuickMessage('Who can I contact for help?')">📞 Contact</button>
        <button class="quick-btn" onclick="sendQuickMessage('What browsers are supported?')">🌐 Browser</button>
        <button class="quick-btn" onclick="sendQuickMessage('How to request unscheduled subjects?')">📚 Unscheduled</button>
    `;
    messagesDiv.appendChild(quickDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}
</script>

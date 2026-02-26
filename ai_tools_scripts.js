window.openTool = function(toolName) {
    const modal = document.getElementById('toolModal');
    const content = document.getElementById('toolContent');
    let html = '';
    
    switch(toolName) {
        case 'gradePredictor':
            html = `
                <h3><i class="fas fa-chart-line"></i> AI Grade Predictor</h3>
                <div class="tool-input-group">
                    <label>Midterm Grade</label>
                    <input type="number" id="midtermGrade" placeholder="Enter midterm grade" min="0" max="100">
                </div>
                <div class="tool-input-group">
                    <label>Quiz Average</label>
                    <input type="number" id="quizAvg" placeholder="Enter quiz average" min="0" max="100">
                </div>
                <div class="tool-input-group">
                    <label>Attendance Rate (%)</label>
                    <input type="number" id="attendance" placeholder="Enter attendance %" min="0" max="100">
                </div>
                <button class="tool-btn" onclick="predictGrade()">Predict Final Grade</button>
                <div id="gradeResult"></div>
            `;
            break;
        
        case 'scheduleOptimizer':
            html = `
                <h3><i class="fas fa-calendar-check"></i> Smart Schedule Optimizer</h3>
                <div class="tool-input-group">
                    <label>Number of Subjects</label>
                    <input type="number" id="numSubjects" placeholder="Enter number of subjects" min="1" max="10">
                </div>
                <div class="tool-input-group">
                    <label>Study Hours Available per Day</label>
                    <input type="number" id="studyHours" placeholder="Hours available" min="1" max="12">
                </div>
                <div class="tool-input-group">
                    <label>Priority Subject</label>
                    <input type="text" id="prioritySubject" placeholder="Subject needing most attention">
                </div>
                <button class="tool-btn" onclick="optimizeSchedule()">Generate Schedule</button>
                <div id="scheduleResult"></div>
            `;
            break;
        
        case 'libraryAssistant':
            html = `
                <h3><i class="fas fa-book-reader"></i> Virtual Library Assistant</h3>
                <div class="tool-input-group">
                    <label>Search Topic</label>
                    <input type="text" id="searchTopic" placeholder="Enter topic or keyword">
                </div>
                <div class="tool-input-group">
                    <label>Resource Type</label>
                    <select id="resourceType">
                        <option value="all">All Resources</option>
                        <option value="books">Books</option>
                        <option value="journals">Journals</option>
                        <option value="thesis">Thesis</option>
                        <option value="ebooks">E-Books</option>
                    </select>
                </div>
                <button class="tool-btn" onclick="searchLibrary()">Search Resources</button>
                <div id="libraryResult"></div>
            `;
            break;
        
        case 'careerAnalyzer':
            html = `
                <h3><i class="fas fa-briefcase"></i> Career Path Analyzer</h3>
                <div class="tool-input-group">
                    <label>Current GPA</label>
                    <input type="number" step="0.01" id="currentGPA" placeholder="Enter GPA" min="1" max="4">
                </div>
                <div class="tool-input-group">
                    <label>Strongest Subject Area</label>
                    <select id="strongestArea">
                        <option value="programming">Programming</option>
                        <option value="database">Database Management</option>
                        <option value="networking">Networking</option>
                        <option value="design">UI/UX Design</option>
                        <option value="analysis">Systems Analysis</option>
                    </select>
                </div>
                <div class="tool-input-group">
                    <label>Career Interest</label>
                    <select id="careerInterest">
                        <option value="development">Software Development</option>
                        <option value="data">Data Science</option>
                        <option value="security">Cybersecurity</option>
                        <option value="cloud">Cloud Computing</option>
                        <option value="ai">AI/Machine Learning</option>
                    </select>
                </div>
                <button class="tool-btn" onclick="analyzeCareer()">Analyze Career Path</button>
                <div id="careerResult"></div>
            `;
            break;
        
        case 'studyMatcher':
            html = `
                <h3><i class="fas fa-users"></i> Study Group Matcher</h3>
                <div class="tool-input-group">
                    <label>Your Course</label>
                    <input type="text" id="userCourse" placeholder="e.g., BSCS, BSIT">
                </div>
                <div class="tool-input-group">
                    <label>Subject to Study</label>
                    <input type="text" id="studySubject" placeholder="Enter subject name">
                </div>
                <div class="tool-input-group">
                    <label>Preferred Study Time</label>
                    <select id="studyTime">
                        <option value="morning">Morning (8AM-12PM)</option>
                        <option value="afternoon">Afternoon (1PM-5PM)</option>
                        <option value="evening">Evening (6PM-9PM)</option>
                    </select>
                </div>
                <button class="tool-btn" onclick="findStudyGroup()">Find Study Partners</button>
                <div id="studyResult"></div>
            `;
            break;
        
        case 'gpaCalculator':
            html = `
                <h3><i class="fas fa-calculator"></i> GPA Calculator</h3>
                <div id="gpaInputs">
                    <div class="tool-input-group">
                        <label>Subject 1 Grade</label>
                        <input type="number" class="grade-input" placeholder="Grade (1.0-5.0)" min="1" max="5" step="0.25">
                    </div>
                    <div class="tool-input-group">
                        <label>Subject 1 Units</label>
                        <input type="number" class="units-input" placeholder="Units" min="1" max="6">
                    </div>
                </div>
                <button class="tool-btn" style="background: #059669; margin-bottom: 0.5rem;" onclick="addGPASubject()">+ Add Subject</button>
                <button class="tool-btn" onclick="calculateGPA()">Calculate GPA</button>
                <div id="gpaResult"></div>
            `;
            break;
    }
    
    content.innerHTML = html;
    modal.classList.add('show');
}

window.closeTool = function() {
    document.getElementById('toolModal').classList.remove('show');
}

window.predictGrade = function() {
    const midterm = parseFloat(document.getElementById('midtermGrade').value);
    const quiz = parseFloat(document.getElementById('quizAvg').value);
    const attendance = parseFloat(document.getElementById('attendance').value);
    
    if (!midterm || !quiz || !attendance) {
        alert('Please fill all fields');
        return;
    }
    
    const predicted = (midterm * 0.4) + (quiz * 0.35) + (attendance * 0.25);
    const performanceTrend = midterm > 80 ? 1.05 : midterm < 70 ? 0.95 : 1;
    const finalPrediction = (predicted * performanceTrend).toFixed(2);
    
    const status = finalPrediction >= 75 ? 'PASSING' : 'NEEDS IMPROVEMENT';
    const color = finalPrediction >= 75 ? '#059669' : '#dc2626';
    
    document.getElementById('gradeResult').innerHTML = `
        <div class="tool-result">
            <h4>Predicted Final Grade: <span style="color: ${color}; font-size: 1.5rem;">${finalPrediction}</span></h4>
            <p><strong>Status:</strong> ${status}</p>
            <p><strong>AI Analysis:</strong> Based on your current performance, you're ${finalPrediction >= 85 ? 'excelling' : finalPrediction >= 75 ? 'on track' : 'struggling'}. ${finalPrediction < 75 ? 'Focus on improving quiz scores and attendance.' : 'Keep up the great work!'}</p>
        </div>
    `;
}

window.optimizeSchedule = function() {
    const subjects = parseInt(document.getElementById('numSubjects').value);
    const hours = parseInt(document.getElementById('studyHours').value);
    const priority = document.getElementById('prioritySubject').value;
    
    if (!subjects || !hours) {
        alert('Please fill all fields');
        return;
    }
    
    const hoursPerSubject = (hours / subjects).toFixed(1);
    const priorityHours = (parseFloat(hoursPerSubject) * 1.5).toFixed(1);
    const otherHours = ((hours - priorityHours) / (subjects - 1)).toFixed(1);
    
    document.getElementById('scheduleResult').innerHTML = `
        <div class="tool-result">
            <h4>Optimized Study Schedule</h4>
            <p><strong>Priority Subject (${priority || 'Main'}):</strong> ${priorityHours} hours/day</p>
            <p><strong>Other Subjects:</strong> ${otherHours} hours/day each</p>
            <p><strong>AI Recommendation:</strong> Study priority subject during peak focus hours (morning). Take 10-min breaks every hour. Review notes before sleep for better retention.</p>
        </div>
    `;
}

window.searchLibrary = function() {
    const topic = document.getElementById('searchTopic').value;
    const type = document.getElementById('resourceType').value;
    
    if (!topic) {
        alert('Please enter a search topic');
        return;
    }
    
    const resources = [
        { title: `${topic} - Comprehensive Guide`, type: 'E-Book', available: true },
        { title: `Advanced ${topic} Research`, type: 'Journal', available: true },
        { title: `${topic} Case Studies`, type: 'Thesis', available: false },
        { title: `Introduction to ${topic}`, type: 'Book', available: true }
    ];
    
    document.getElementById('libraryResult').innerHTML = `
        <div class="tool-result">
            <h4>Found ${resources.length} Resources</h4>
            ${resources.map(r => `
                <p><strong>${r.title}</strong><br>
                Type: ${r.type} | Status: ${r.available ? '✅ Available' : '❌ Checked Out'}</p>
            `).join('')}
            <p><strong>AI Tip:</strong> Start with the comprehensive guide for foundational knowledge.</p>
        </div>
    `;
}

window.analyzeCareer = function() {
    const gpa = parseFloat(document.getElementById('currentGPA').value);
    const strength = document.getElementById('strongestArea').value;
    const interest = document.getElementById('careerInterest').value;
    
    if (!gpa) {
        alert('Please enter your GPA');
        return;
    }
    
    const careers = {
        development: 'Full-Stack Developer, Mobile App Developer',
        data: 'Data Analyst, Machine Learning Engineer',
        security: 'Security Analyst, Penetration Tester',
        cloud: 'Cloud Architect, DevOps Engineer',
        ai: 'AI Engineer, Research Scientist'
    };
    
    const match = gpa >= 3.5 ? 'Excellent' : gpa >= 3.0 ? 'Good' : gpa >= 2.5 ? 'Fair' : 'Developing';
    
    document.getElementById('careerResult').innerHTML = `
        <div class="tool-result">
            <h4>Career Path Analysis</h4>
            <p><strong>Match Level:</strong> ${match}</p>
            <p><strong>Recommended Careers:</strong> ${careers[interest]}</p>
            <p><strong>Your Strength:</strong> ${strength.charAt(0).toUpperCase() + strength.slice(1)}</p>
            <p><strong>AI Insight:</strong> ${gpa >= 3.0 ? 'You have strong potential for ' + interest + '. Consider internships and certifications.' : 'Focus on improving GPA while building practical projects in ' + interest + '.'}</p>
        </div>
    `;
}

window.findStudyGroup = function() {
    const course = document.getElementById('userCourse').value;
    const subject = document.getElementById('studySubject').value;
    const time = document.getElementById('studyTime').value;
    
    if (!subject) {
        alert('Please enter a subject');
        return;
    }
    
    const matches = Math.floor(Math.random() * 8) + 3;
    
    document.getElementById('studyResult').innerHTML = `
        <div class="tool-result">
            <h4>Found ${matches} Study Partners</h4>
            <p><strong>Subject:</strong> ${subject}</p>
            <p><strong>Time:</strong> ${time.charAt(0).toUpperCase() + time.slice(1)}</p>
            <p><strong>Matched Students:</strong></p>
            <p>• Maria S. (${course}, GPA: 3.5)<br>
            • John D. (${course}, GPA: 3.2)<br>
            • Sarah L. (${course}, GPA: 3.8)</p>
            <p><strong>AI Suggestion:</strong> Form a group of 4-5 students for optimal collaboration. Meet at library or online via Teams.</p>
        </div>
    `;
}

window.addGPASubject = function() {
    const container = document.getElementById('gpaInputs');
    const count = container.querySelectorAll('.grade-input').length + 1;
    
    container.insertAdjacentHTML('beforeend', `
        <div class="tool-input-group">
            <label>Subject ${count} Grade</label>
            <input type="number" class="grade-input" placeholder="Grade (1.0-5.0)" min="1" max="5" step="0.25">
        </div>
        <div class="tool-input-group">
            <label>Subject ${count} Units</label>
            <input type="number" class="units-input" placeholder="Units" min="1" max="6">
        </div>
    `);
}

window.calculateGPA = function() {
    const grades = Array.from(document.querySelectorAll('.grade-input')).map(i => parseFloat(i.value));
    const units = Array.from(document.querySelectorAll('.units-input')).map(i => parseFloat(i.value));
    
    if (grades.some(g => !g) || units.some(u => !u)) {
        alert('Please fill all grade and unit fields');
        return;
    }
    
    let totalPoints = 0;
    let totalUnits = 0;
    
    for (let i = 0; i < grades.length; i++) {
        totalPoints += grades[i] * units[i];
        totalUnits += units[i];
    }
    
    const gpa = (totalPoints / totalUnits).toFixed(2);
    const status = gpa <= 1.5 ? 'Summa Cum Laude' : gpa <= 1.75 ? 'Magna Cum Laude' : gpa <= 2.0 ? 'Cum Laude' : gpa <= 3.0 ? 'Good Standing' : 'Passing';
    
    document.getElementById('gpaResult').innerHTML = `
        <div class="tool-result">
            <h4>Your GPA: <span style="color: #0369a1; font-size: 1.5rem;">${gpa}</span></h4>
            <p><strong>Status:</strong> ${status}</p>
            <p><strong>Total Units:</strong> ${totalUnits}</p>
            <p><strong>AI Analysis:</strong> ${gpa <= 2.0 ? 'Outstanding performance! Keep maintaining this excellence.' : gpa <= 3.0 ? 'Good work! Aim for consistent improvement.' : 'Focus on challenging subjects to boost your GPA.'}</p>
        </div>
    `;
}

<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireStudent();

$quiz_id = $_GET['id'] ?? 0;

if (!$quiz_id) {
    header('Location: student-dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - Cheche</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .quiz-container {
            max-width: 800px;
            margin: 120px auto 2rem;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .quiz-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .quiz-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .quiz-info div {
            display: inline-block;
            margin-right: 2rem;
        }

        .question-container {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }

        .question-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .question-number {
            background: #4a90e2;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
        }

        .question-text {
            flex: 1;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .question-points {
            color: #666;
            font-size: 0.9rem;
        }

        .options-container {
            margin-left: 3rem;
        }

        .option {
            margin-bottom: 0.5rem;
        }

        .option label {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 5px;
            transition: background-color 0.2s;
        }

        .option label:hover {
            background: #f8f9fa;
        }

        .option input {
            margin-right: 0.5rem;
        }

        .timer-container {
            position: fixed;
            top: 80px;
            right: 20px;
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .timer {
            font-size: 1.2rem;
            font-weight: bold;
            color: #4a90e2;
        }

        .timer.warning {
            color: #dc3545;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .quiz-actions {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #f0f0f0;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 8px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .progress-fill {
            background: #4a90e2;
            height: 100%;
            transition: width 0.3s ease;
        }

        .short-answer-input {
            width: 100%;
            min-height: 80px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
        }

        @media (max-width: 768px) {
            .quiz-container {
                margin: 100px 1rem 1rem;
                padding: 1rem;
            }

            .timer-container {
                position: static;
                margin-bottom: 1rem;
            }

            .options-container {
                margin-left: 1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="index.php" style="text-decoration: none; color: inherit;">
                    <h2>Cheche</h2>
                </a>
            </div>
            <div class="nav-links">
                <a href="student-dashboard.php">Dashboard</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?></span>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="quiz-container">
        <div class="loading" id="loading">
            <h3>Loading quiz...</h3>
        </div>

        <div id="quiz-content" style="display: none;">
            <div class="quiz-header">
                <h1 id="quiz-title">Quiz</h1>
                <p id="quiz-description"></p>
            </div>

            <div class="quiz-info">
                <div><strong>Time Limit:</strong> <span id="time-limit"></span> minutes</div>
                <div><strong>Passing Score:</strong> <span id="passing-score"></span>%</div>
                <div><strong>Max Attempts:</strong> <span id="max-attempts"></span></div>
                <div><strong>Remaining Attempts:</strong> <span id="remaining-attempts"></span></div>
            </div>

            <div class="timer-container" id="timer-container" style="display: none;">
                <div>Time Remaining:</div>
                <div class="timer" id="timer">00:00</div>
            </div>

            <div class="progress-bar">
                <div class="progress-fill" id="progress"></div>
            </div>

            <form id="quiz-form">
                <div id="questions-container"></div>

                <div class="quiz-actions">
                    <button type="button" id="start-quiz" class="btn-primary">Start Quiz</button>
                    <button type="submit" id="submit-quiz" class="btn-success" style="display: none;">Submit Quiz</button>
                </div>
            </form>
        </div>

        <div id="result-container" style="display: none;">
            <div class="quiz-header">
                <h1>Quiz Results</h1>
            </div>
            <div id="result-content"></div>
            <div class="quiz-actions">
                <a href="student-dashboard.php" class="btn-primary">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        let quiz = null;
        let startTime = null;
        let timeLimit = 0;
        let timerInterval = null;

        document.addEventListener('DOMContentLoaded', function() {
            loadQuiz();

            document.getElementById('start-quiz').addEventListener('click', startQuiz);
            document.getElementById('quiz-form').addEventListener('submit', submitQuiz);
        });

        async function loadQuiz() {
            try {
                const response = await fetch(`../api/get-quiz.php?id=${<?php echo $quiz_id; ?>}`);
                const data = await response.json();

                if (data.success) {
                    quiz = data.quiz;
                    displayQuiz();
                } else {
                    alert('Error: ' + data.error);
                    window.location.href = 'student-dashboard.php';
                }
            } catch (error) {
                console.error('Error loading quiz:', error);
                alert('Failed to load quiz');
                window.location.href = 'student-dashboard.php';
            }
        }

        function displayQuiz() {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('quiz-content').style.display = 'block';

            document.getElementById('quiz-title').textContent = quiz.title;
            document.getElementById('quiz-description').textContent = quiz.description || '';
            document.getElementById('time-limit').textContent = quiz.time_limit;
            document.getElementById('passing-score').textContent = quiz.passing_score;
            document.getElementById('max-attempts').textContent = quiz.max_attempts;
            document.getElementById('remaining-attempts').textContent = quiz.remaining_attempts;

            if (quiz.remaining_attempts <= 0) {
                document.getElementById('start-quiz').style.display = 'none';
                document.querySelector('.quiz-actions').innerHTML = '<p>No more attempts remaining.</p><a href="student-dashboard.php" class="btn-primary">Back to Dashboard</a>';
                return;
            }

            const questionsContainer = document.getElementById('questions-container');
            questionsContainer.innerHTML = '';

            quiz.questions.forEach((question, index) => {
                const questionDiv = document.createElement('div');
                questionDiv.className = 'question-container';
                questionDiv.innerHTML = `
                    <div class="question-header">
                        <div class="question-number">${index + 1}</div>
                        <div class="question-text">${question.question_text}</div>
                        <div class="question-points">${question.points} point${question.points !== '1.00' ? 's' : ''}</div>
                    </div>
                    <div class="options-container">
                        ${generateOptions(question)}
                    </div>
                `;
                questionsContainer.appendChild(questionDiv);
            });

            timeLimit = quiz.time_limit * 60; // Convert to seconds
        }

        function generateOptions(question) {
            if (question.question_type === 'multiple_choice') {
                return question.options.map(option => `
                    <div class="option">
                        <label>
                            <input type="radio" name="question_${question.id}" value="${option.id}">
                            ${option.option_text}
                        </label>
                    </div>
                `).join('');
            } else if (question.question_type === 'true_false') {
                return `
                    <div class="option">
                        <label>
                            <input type="radio" name="question_${question.id}" value="true">
                            True
                        </label>
                    </div>
                    <div class="option">
                        <label>
                            <input type="radio" name="question_${question.id}" value="false">
                            False
                        </label>
                    </div>
                `;
            } else if (question.question_type === 'short_answer') {
                return `
                    <textarea name="question_${question.id}" class="short-answer-input" placeholder="Type your answer here..."></textarea>
                `;
            }
            return '';
        }

        function startQuiz() {
            document.getElementById('start-quiz').style.display = 'none';
            document.getElementById('submit-quiz').style.display = 'inline-block';
            document.getElementById('timer-container').style.display = 'block';

            startTime = new Date();
            startTimer();

            // Disable navigation away from page
            window.addEventListener('beforeunload', function(e) {
                e.preventDefault();
                e.returnValue = '';
            });
        }

        function startTimer() {
            let remainingTime = timeLimit;

            timerInterval = setInterval(() => {
                remainingTime--;

                const minutes = Math.floor(remainingTime / 60);
                const seconds = remainingTime % 60;
                const display = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                document.getElementById('timer').textContent = display;

                if (remainingTime <= 300) { // 5 minutes warning
                    document.getElementById('timer').classList.add('warning');
                }

                if (remainingTime <= 0) {
                    clearInterval(timerInterval);
                    submitQuiz(new Event('submit'));
                }
            }, 1000);
        }

        async function submitQuiz(event) {
            event.preventDefault();

            if (!confirm('Are you sure you want to submit your quiz? This cannot be undone.')) {
                return;
            }

            const formData = new FormData(document.getElementById('quiz-form'));
            const answers = {};

            // Collect answers
            quiz.questions.forEach(question => {
                const questionName = `question_${question.id}`;
                if (question.question_type === 'short_answer') {
                    answers[question.id] = formData.get(questionName);
                } else {
                    answers[question.id] = formData.get(questionName);
                }
            });

            const timeTaken = Math.floor((new Date() - startTime) / 1000);

            try {
                const response = await fetch('../api/submit-quiz.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        quiz_id: quiz.id,
                        answers: answers,
                        time_taken: timeTaken
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showResults(data);
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error submitting quiz:', error);
                alert('Failed to submit quiz');
            }

            clearInterval(timerInterval);
            window.removeEventListener('beforeunload', function() {});
        }

        function showResults(result) {
            document.getElementById('quiz-content').style.display = 'none';
            document.getElementById('result-container').style.display = 'block';

            const passedText = result.passed ?
                '<span style="color: #28a745;">✅ PASSED</span>' :
                '<span style="color: #dc3545;">❌ FAILED</span>';

            document.getElementById('result-content').innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <h2>${passedText}</h2>
                    <div style="font-size: 2rem; margin: 1rem 0; color: ${result.passed ? '#28a745' : '#dc3545'};">
                        ${result.score.toFixed(1)}%
                    </div>
                    <p>You scored ${result.points_earned} out of ${result.total_points} points</p>
                    <p>Passing score: ${quiz.passing_score}%</p>
                    ${result.passed ? '<p style="color: #28a745;">Congratulations! You can now generate your certificate.</p>' : ''}
                </div>
            `;
        }
    </script>
</body>
</html>
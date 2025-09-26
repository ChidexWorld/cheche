<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/session.php';

requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $data = $_POST;
}

$quiz_id = $data['quiz_id'] ?? 0;
$answers = $data['answers'] ?? [];
$time_taken = intval($data['time_taken'] ?? 0);

if (!$quiz_id || empty($answers)) {
    http_response_code(400);
    echo json_encode(['error' => 'Quiz ID and answers are required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get quiz details
    $stmt = $conn->prepare("
        SELECT q.*, c.instructor_id
        FROM quizzes q
        JOIN courses c ON q.course_id = c.id
        WHERE q.id = ? AND q.is_active = 1
    ");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        http_response_code(404);
        echo json_encode(['error' => 'Quiz not found']);
        exit;
    }

    // Check if student is enrolled in the course
    $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $quiz['course_id']]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$enrollment) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Check remaining attempts
    $stmt = $conn->prepare("SELECT COUNT(*) as attempt_count FROM quiz_attempts WHERE student_id = ? AND quiz_id = ?");
    $stmt->execute([$_SESSION['user_id'], $quiz_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $attempts_count = $result['attempt_count'];

    if ($attempts_count >= $quiz['max_attempts']) {
        http_response_code(403);
        echo json_encode(['error' => 'No more attempts remaining']);
        exit;
    }

    // Get quiz questions
    $stmt = $conn->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY order_number ASC");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_points = 0;
    $earned_points = 0;

    // Create attempt record
    $attempt_number = $attempts_count + 1;
    $stmt = $conn->prepare("INSERT INTO quiz_attempts (student_id, quiz_id, attempt_number, started_at, time_taken) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->execute([$_SESSION['user_id'], $quiz_id, $attempt_number, $time_taken]);
    $attempt_id = $conn->lastInsertId();

    // Process each question
    foreach ($questions as $question) {
        $total_points += $question['points'];
        $question_id = $question['id'];
        $user_answer = $answers[$question_id] ?? null;

        if ($user_answer === null) continue;

        $points_earned = 0;
        $is_correct = false;

        if ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'true_false') {
            // Check if selected option is correct
            $stmt = $conn->prepare("SELECT is_correct FROM quiz_options WHERE id = ? AND question_id = ?");
            $stmt->execute([$user_answer, $question_id]);
            $option = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($option && $option['is_correct']) {
                $is_correct = true;
                $points_earned = $question['points'];
            }

            // Store response
            $stmt = $conn->prepare("INSERT INTO quiz_responses (attempt_id, question_id, option_id, is_correct, points_earned) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$attempt_id, $question_id, $user_answer, $is_correct, $points_earned]);

        } elseif ($question['question_type'] === 'short_answer') {
            // For short answer, instructor needs to grade manually
            $stmt = $conn->prepare("INSERT INTO quiz_responses (attempt_id, question_id, answer_text, is_correct, points_earned) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$attempt_id, $question_id, $user_answer, false, 0]);
        }

        $earned_points += $points_earned;
    }

    // Calculate score percentage
    $score_percentage = $total_points > 0 ? ($earned_points / $total_points) * 100 : 0;
    $passed = $score_percentage >= $quiz['passing_score'];

    // Update attempt with final score
    $stmt = $conn->prepare("UPDATE quiz_attempts SET score = ?, max_score = ?, passed = ?, completed_at = NOW() WHERE id = ?");
    $stmt->execute([$score_percentage, 100, $passed, $attempt_id]);

    echo json_encode([
        'success' => true,
        'attempt_id' => $attempt_id,
        'score' => $score_percentage,
        'passed' => $passed,
        'points_earned' => $earned_points,
        'total_points' => $total_points
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit quiz: ' . $e->getMessage()]);
}
?>
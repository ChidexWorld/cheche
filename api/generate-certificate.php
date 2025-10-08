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

$course_id = $data['course_id'] ?? 0;

if (!$course_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Course ID is required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Check if student is enrolled in the course
    $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$enrollment) {
        http_response_code(403);
        echo json_encode(['error' => 'Not enrolled in this course']);
        exit;
    }

    // Check if certificate already exists
    $stmt = $conn->prepare("SELECT * FROM certificates WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $existing_certificate = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_certificate) {
        echo json_encode([
            'success' => true,
            'certificate' => $existing_certificate,
            'already_exists' => true
        ]);
        exit;
    }

    // Get course details
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        http_response_code(404);
        echo json_encode(['error' => 'Course not found']);
        exit;
    }

    // Get student details
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check course completion requirements
    $completion_percentage = floatval($enrollment['progress'] ?? 0);

    // Check if there's a quiz for this course and if student passed it
    $stmt = $conn->prepare("SELECT * FROM quizzes WHERE course_id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$course_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    $quiz_score = null;
    $quiz_required = false;

    if ($quiz) {
        $quiz_required = true;

        // Get student's best quiz attempt
        $stmt = $conn->prepare("
            SELECT * FROM quiz_attempts
            WHERE student_id = ? AND quiz_id = ? AND passed = 1
            ORDER BY score DESC LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id'], $quiz['id']]);
        $best_attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$best_attempt) {
            http_response_code(400);
            echo json_encode(['error' => 'Must pass the course quiz to earn certificate']);
            exit;
        }

        $quiz_score = $best_attempt['score'];
    }

    // Check minimum completion percentage (80% of videos watched)
    if ($completion_percentage < 80) {
        http_response_code(400);
        echo json_encode(['error' => 'Must complete at least 80% of the course to earn certificate']);
        exit;
    }

    // Generate unique certificate number
    $certificate_number = 'CHECHE-' . strtoupper(substr(md5($course_id . $_SESSION['user_id'] . time()), 0, 8));

    // Prepare certificate data
    $certificate_data = json_encode([
        'student_name' => $student['full_name'],
        'course_title' => $course['title'],
        'course_description' => $course['description'],
        'completion_date' => date('Y-m-d H:i:s'),
        'quiz_score' => $quiz_score,
        'completion_percentage' => $completion_percentage
    ]);

    // Create certificate
    $stmt = $conn->prepare("
        INSERT INTO certificates (student_id, course_id, certificate_number, completion_percentage, quiz_score, certificate_data)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $course_id, $certificate_number, $completion_percentage, $quiz_score, $certificate_data]);

    $certificate_id = $conn->lastInsertId();

    // Get the created certificate
    $stmt = $conn->prepare("SELECT * FROM certificates WHERE id = ?");
    $stmt->execute([$certificate_id]);
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'certificate' => $certificate,
        'message' => 'Certificate generated successfully!'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate certificate: ' . $e->getMessage()]);
}
?>
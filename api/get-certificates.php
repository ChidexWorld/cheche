<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

try {
    $database = new Database();
    $conn = $database->getConnection();

    if (isStudent()) {
        // Get student's certificates
        $stmt = $conn->prepare("
            SELECT c.*, co.title as course_title, co.description as course_description
            FROM certificates c
            JOIN courses co ON c.course_id = co.id
            WHERE c.student_id = ?
            ORDER BY c.issued_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif (isInstructor()) {
        // Get certificates for instructor's courses
        $stmt = $conn->prepare("
            SELECT c.*, co.title as course_title, u.full_name as student_name
            FROM certificates c
            JOIN courses co ON c.course_id = co.id
            JOIN users u ON c.student_id = u.id
            WHERE co.instructor_id = ?
            ORDER BY c.issued_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Decode certificate data for each certificate
    foreach ($certificates as &$certificate) {
        if ($certificate['certificate_data']) {
            $certificate['data'] = json_decode($certificate['certificate_data'], true);
        }
    }

    echo json_encode(['success' => true, 'certificates' => $certificates]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get certificates: ' . $e->getMessage()]);
}
?>
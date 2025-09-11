<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireInstructor();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$price = floatval($_POST['price'] ?? 0);

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Course title is required']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO courses (title, description, instructor_id, category, price) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$title, $description, $_SESSION['user_id'], $category, $price])) {
        $course_id = $conn->lastInsertId();
        header('Location: ../instructor-dashboard.php?tab=courses&success=Course created successfully');
    } else {
        header('Location: ../instructor-dashboard.php?tab=create-course&error=Failed to create course');
    }
} catch (Exception $e) {
    header('Location: ../instructor-dashboard.php?tab=create-course&error=Failed to create course');
}
?>
<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireInstructor();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../instructor-dashboard.php?tab=create-course&error=Invalid request method');
    exit();
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$price = floatval($_POST['price'] ?? 0);

if (empty($title)) {
    header('Location: ../instructor-dashboard.php?tab=create-course&error=Course title is required');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create course in database
    try {
        $stmt = $db->prepare("
            INSERT INTO courses (title, description, instructor_id, category, price, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        if ($stmt->execute([
            $title,
            $description,
            $_SESSION['user_id'],
            $category,
            $price
        ])) {
            $course_id = $db->lastInsertId();
            header('Location: ../instructor-dashboard.php?tab=courses&success=Course created successfully');
        } else {
            header('Location: ../instructor-dashboard.php?tab=create-course&error=Failed to create course');
        }
    } catch (Exception $e) {
        // Fallback to file-based database
        $course_data = [
            'title' => $title,
            'description' => $description,
            'instructor_id' => $_SESSION['user_id'],
            'category' => $category,
            'price' => $price
        ];

        $course_id = $db->insert('courses', $course_data);
        if ($course_id) {
            header('Location: ../instructor-dashboard.php?tab=courses&success=Course created successfully');
        } else {
            header('Location: ../instructor-dashboard.php?tab=create-course&error=Failed to create course');
        }
    }
} catch (Exception $e) {
    header('Location: ../instructor-dashboard.php?tab=create-course&error=Failed to create course: ' . $e->getMessage());
}
?>
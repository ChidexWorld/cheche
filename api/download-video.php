<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Get video ID
$video_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$video_id) {
    header('Location: ../index.php');
    exit();
}

// Get video details from database
$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT * FROM videos WHERE id = ?");
$stmt->execute([$video_id]);
$video = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$video) {
    header('Location: ../index.php');
    exit();
}

// Get the file path
$file_path = __DIR__ . '/../' . $video['video_path'];

if (!file_exists($file_path)) {
    die('Video file not found.');
}

// Get file information
$file_size = filesize($file_path);
$file_name = basename($video['video_path']);

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Length: ' . $file_size);
header('Cache-Control: no-cache');
header('Pragma: no-cache');

// Output file content
readfile($file_path);
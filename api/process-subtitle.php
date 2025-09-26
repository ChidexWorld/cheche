<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/subtitle-processor.php';

requireInstructor();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $data = $_POST;
}

$subtitle_id = $data['subtitle_id'] ?? 0;
$action = $data['action'] ?? '';

if (!$subtitle_id || !$action) {
    http_response_code(400);
    echo json_encode(['error' => 'Subtitle ID and action are required']);
    exit;
}

try {
    $database = new Database();
    $processor = new SubtitleProcessor($database);

    switch ($action) {
        case 'translate':
            $translated_path = $processor->translateSubtitleFile($subtitle_id);
            echo json_encode([
                'success' => true,
                'message' => 'Subtitle translated successfully',
                'translated_path' => $translated_path
            ]);
            break;

        case 'merge':
            $merged_path = $processor->mergeSubtitleWithVideo($subtitle_id);
            echo json_encode([
                'success' => true,
                'message' => 'Video merged with subtitles successfully',
                'merged_path' => $merged_path
            ]);
            break;

        case 'status':
            $subtitle_info = $processor->getSubtitleInfo(null);
            echo json_encode([
                'success' => true,
                'subtitle_info' => $subtitle_info
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Processing failed: ' . $e->getMessage()]);
}
?>
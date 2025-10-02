<?php
require_once 'config/database.php';
require_once 'config/subtitle-processor.php';

$db = new Database();
$processor = new SubtitleProcessor($db);
$conn = $db->getConnection();

echo "=== Re-translating Existing Subtitles to Igbo ===\n\n";

// Get all subtitles with valid paths
if ($conn === $db) {
    $subtitles = $db->select('subtitles');
} else {
    $stmt = $conn->query("SELECT * FROM subtitles WHERE translation_status = 'completed'");
    $subtitles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

foreach ($subtitles as $subtitle) {
    $subtitle_id = $subtitle['id'];
    $video_id = $subtitle['video_id'];

    echo "Processing subtitle ID: $subtitle_id (Video ID: $video_id)\n";

    // Skip if path is absolute (old format)
    if (strpos($subtitle['original_file_path'], 'C:\\') === 0 || strpos($subtitle['original_file_path'], 'C:/') === 0) {
        echo "Old absolute path format. Skipping.\n\n";
        continue;
    }

    try {
        // Re-translate the subtitle
        echo "Re-translating subtitles...\n";
        $processor->translateSubtitleFile($subtitle_id);
        echo "✓ Successfully re-translated subtitle ID: $subtitle_id\n\n";
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n\n";
    }
}

echo "=== Re-translation Complete ===\n";
?>

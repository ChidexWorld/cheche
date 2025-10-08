<?php
require_once 'config/database.php';
require_once 'config/subtitle-processor.php';

$db = new Database();
$processor = new SubtitleProcessor($db);
$conn = $db->getConnection();

echo "=== Converting Existing Subtitles to VTT ===\n\n";

// Get all subtitles
if ($conn === $db) {
    $subtitles = $db->select('subtitles');
} else {
    $stmt = $conn->query("SELECT * FROM subtitles WHERE translation_status = 'completed'");
    $subtitles = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

foreach ($subtitles as $subtitle) {
    $subtitle_id = $subtitle['id'];
    $translated_path = $subtitle['translated_file_path'];

    echo "Processing subtitle ID: $subtitle_id\n";
    echo "Current path: $translated_path\n";

    // Skip if already VTT
    if (strpos($translated_path, '.vtt') !== false) {
        echo "Already VTT format. Skipping.\n\n";
        continue;
    }

    // Skip if path is absolute (old format)
    if (strpos($translated_path, 'C:\\') === 0 || strpos($translated_path, 'C:/') === 0) {
        echo "Old absolute path format. Skipping.\n\n";
        continue;
    }

    // Convert SRT to VTT
    try {
        $srt_path_absolute = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $translated_path);

        if (!file_exists($srt_path_absolute)) {
            echo "File not found: $srt_path_absolute. Skipping.\n\n";
            continue;
        }

        $vtt_filename = str_replace('.srt', '.vtt', basename($translated_path));
        $vtt_path_absolute = dirname($srt_path_absolute) . DIRECTORY_SEPARATOR . $vtt_filename;
        $vtt_path_relative = 'uploads/subtitles/' . $vtt_filename;

        $processor->convertSrtToVtt($srt_path_absolute, $vtt_path_absolute);

        // Update database
        if ($conn === $db) {
            $db->update('subtitles',
                ['translated_file_path' => $vtt_path_relative],
                ['id' => $subtitle_id]
            );
        } else {
            $stmt = $conn->prepare("UPDATE subtitles SET translated_file_path = ? WHERE id = ?");
            $stmt->execute([$vtt_path_relative, $subtitle_id]);
        }

        echo "✓ Converted to VTT: $vtt_path_relative\n\n";

    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n\n";
    }
}

echo "=== Conversion Complete ===\n";
?>

<?php
echo "<h1>PHP Upload Configuration Test</h1>";

echo "<h2>Current PHP Upload Settings:</h2>";
echo "<ul>";
echo "<li><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</li>";
echo "<li><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</li>";
echo "<li><strong>max_execution_time:</strong> " . ini_get('max_execution_time') . " seconds</li>";
echo "<li><strong>max_input_time:</strong> " . ini_get('max_input_time') . " seconds</li>";
echo "<li><strong>memory_limit:</strong> " . ini_get('memory_limit') . "</li>";
echo "</ul>";

echo "<h2>Application Settings:</h2>";
require_once 'config/env.php';
echo "<ul>";
echo "<li><strong>UPLOAD_MAX_SIZE:</strong> " . Env::get('UPLOAD_MAX_SIZE', '100MB') . " bytes (" . round(Env::getInt('UPLOAD_MAX_SIZE', 100*1024*1024) / (1024*1024), 1) . " MB)</li>";
echo "<li><strong>ALLOWED_VIDEO_EXTENSIONS:</strong> " . implode(', ', Env::getArray('ALLOWED_VIDEO_EXTENSIONS', ['mp4'])) . "</li>";
echo "<li><strong>UPLOAD_DIR:</strong> " . Env::get('UPLOAD_DIR', 'uploads/videos/') . "</li>";
echo "</ul>";

echo "<h2>Effective Maximum Upload Size:</h2>";
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
$memory_limit = ini_get('memory_limit');

function parseSize($size) {
    $unit = strtoupper(substr($size, -1));
    $value = (int) $size;
    switch($unit) {
        case 'G': return $value * 1024 * 1024 * 1024;
        case 'M': return $value * 1024 * 1024;
        case 'K': return $value * 1024;
        default: return $value;
    }
}

$upload_max_bytes = parseSize($upload_max);
$post_max_bytes = parseSize($post_max);
$memory_limit_bytes = parseSize($memory_limit);

$effective_limit = min($upload_max_bytes, $post_max_bytes);

echo "<p><strong>Effective limit:</strong> " . round($effective_limit / (1024*1024), 1) . " MB</p>";

if ($effective_limit < Env::getInt('UPLOAD_MAX_SIZE', 100*1024*1024)) {
    echo "<p style='color: red;'><strong>Warning:</strong> PHP limits are lower than application settings!</p>";
} else {
    echo "<p style='color: green;'><strong>Good:</strong> PHP limits support the application settings!</p>";
}
?>
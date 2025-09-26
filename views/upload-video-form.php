<?php
// Get instructor courses for the select dropdown
try {
    $stmt = $db->prepare("SELECT id, title FROM courses WHERE instructor_id = ? ORDER BY title ASC");
    $stmt->execute([$_SESSION["user_id"]]);
    $instructor_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback to file-based database
    $instructor_courses = $db->select('courses', ['instructor_id' => $_SESSION["user_id"]], 'title ASC');
}

// Pre-select course if coming from course management
$selected_course_id = $_GET['course_id'] ?? '';
?>

<form action="../api/upload-video.php" method="POST" enctype="multipart/form-data" style="max-width: 600px;">
    <div class="form-group" style="margin-bottom: 1.5rem;">
        <label for="course_id" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Select Course:</label>
        <select name="course_id" id="course_id" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
            <option value="">Choose a course...</option>
            <?php foreach ($instructor_courses as $course): ?>
                <option value="<?php echo $course['id']; ?>" <?php echo $selected_course_id == $course['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($course['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (empty($instructor_courses)): ?>
            <small style="color: #888;">You need to <a href="?tab=create">create a course</a> first before uploading videos.</small>
        <?php endif; ?>
    </div>

    <div class="form-group" style="margin-bottom: 1.5rem;">
        <label for="title" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Video Title:</label>
        <input type="text" name="title" id="title" required maxlength="255"
               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
               placeholder="Enter a descriptive title for your video">
    </div>

    <div class="form-group" style="margin-bottom: 1.5rem;">
        <label for="description" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Description (Optional):</label>
        <textarea name="description" id="description" rows="4"
                  style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; resize: vertical;"
                  placeholder="Describe what students will learn in this video"></textarea>
    </div>

    <div class="form-group" style="margin-bottom: 1.5rem;">
        <label for="video_file" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Video File:</label>
        <input type="file" name="video_file" id="video_file" required accept="video/*"
               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
        <small style="color: #888; display: block; margin-top: 0.5rem;">
            Supported formats: MP4, AVI, MOV, WMV, MKV, WebM, FLV<br>
            Maximum file size: 500MB
        </small>
    </div>

    <div class="form-group" style="margin-bottom: 1.5rem;">
        <label for="subtitle_file" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
            📝 Subtitle File (Optional):
        </label>
        <input type="file" name="subtitle_file" id="subtitle_file" accept=".srt,.vtt,.ass,.ssa"
               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
        <small style="color: #888; display: block; margin-top: 0.5rem;">
            ✨ <strong>Auto Igbo Translation:</strong> Upload English subtitles and they will be automatically translated to Igbo<br>
            Supported formats: SRT, VTT, ASS, SSA<br>
            Maximum file size: 10MB
        </small>

        <div id="subtitle-info" style="background: #e8f4fd; border: 1px solid #bee5eb; border-radius: 4px; padding: 1rem; margin-top: 0.5rem; display: none;">
            <h5 style="margin: 0 0 0.5rem 0; color: #0c5460;">🌍 How Automatic Translation Works:</h5>
            <ol style="margin: 0; color: #0c5460; font-size: 0.9rem;">
                <li>Upload your English subtitle file (.srt recommended)</li>
                <li>System automatically translates text to Igbo language</li>
                <li>Translated subtitles are merged with your video</li>
                <li>Students can watch with Igbo subtitles embedded</li>
            </ol>
        </div>
    </div>

    <div class="form-group" style="margin-bottom: 1.5rem;">
        <button type="submit" class="btn-primary" style="padding: 0.75rem 2rem; font-size: 1rem;"
                <?php echo empty($instructor_courses) ? 'disabled' : ''; ?>>
            Upload Video
        </button>
        <?php if (empty($instructor_courses)): ?>
            <p style="color: #888; margin-top: 0.5rem;">Create a course first to upload videos.</p>
        <?php endif; ?>
    </div>
</form>

<div style="background: #f8f9fa; padding: 1.5rem; border-radius: 4px; margin-top: 2rem;">
    <h4 style="margin-top: 0;">Upload Tips:</h4>
    <ul style="margin-bottom: 0;">
        <li>Use descriptive titles that clearly explain the video content</li>
        <li>Keep file sizes under 500MB for optimal upload speed</li>
        <li>MP4 format is recommended for best compatibility</li>
        <li>Add detailed descriptions to help students understand the content</li>
        <li><strong>New:</strong> Upload subtitles for automatic Igbo translation and video merging</li>
    </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const subtitleInput = document.getElementById('subtitle_file');
    const subtitleInfo = document.getElementById('subtitle-info');

    if (subtitleInput) {
        subtitleInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                subtitleInfo.style.display = 'block';
            } else {
                subtitleInfo.style.display = 'none';
            }
        });
    }
});
</script>
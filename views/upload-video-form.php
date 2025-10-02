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

<!-- Upload Progress Overlay -->
<div id="upload-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background: white; padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%; text-align: center;">
        <h3 style="margin-top: 0;">Uploading Video...</h3>
        <div id="upload-progress-container" style="width: 100%; background: #e0e0e0; border-radius: 10px; height: 30px; margin: 1rem 0; overflow: hidden;">
            <div id="upload-progress-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.9rem;"></div>
        </div>
        <p id="upload-status" style="margin: 0.5rem 0; color: #666;">Preparing upload...</p>
        <p id="upload-size" style="margin: 0.5rem 0; font-size: 0.9rem; color: #888;"></p>
    </div>
</div>

<!-- Error Message Container -->
<div id="error-container" style="display: none; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
    <strong>⚠️ Error:</strong> <span id="error-message"></span>
</div>

<form id="video-upload-form" action="../api/upload-video.php" method="POST" enctype="multipart/form-data" style="max-width: 600px;">
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
        <div id="file-info" style="display: none; margin-top: 0.5rem; padding: 0.5rem; background: #e8f4fd; border-radius: 4px; font-size: 0.9rem;">
            <strong>Selected:</strong> <span id="file-name"></span><br>
            <strong>Size:</strong> <span id="file-size"></span>
        </div>
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
        <button type="submit" id="upload-btn" class="btn-primary" style="padding: 0.75rem 2rem; font-size: 1rem;"
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
    const form = document.getElementById('video-upload-form');
    const videoInput = document.getElementById('video_file');
    const subtitleInput = document.getElementById('subtitle_file');
    const subtitleInfo = document.getElementById('subtitle-info');
    const uploadOverlay = document.getElementById('upload-overlay');
    const progressBar = document.getElementById('upload-progress-bar');
    const uploadStatus = document.getElementById('upload-status');
    const uploadSize = document.getElementById('upload-size');
    const errorContainer = document.getElementById('error-container');
    const errorMessage = document.getElementById('error-message');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const uploadBtn = document.getElementById('upload-btn');

    // Maximum file sizes
    const MAX_VIDEO_SIZE = 500 * 1024 * 1024; // 500MB
    const MAX_SUBTITLE_SIZE = 10 * 1024 * 1024; // 10MB

    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    // Show error message
    function showError(message) {
        errorMessage.textContent = message;
        errorContainer.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Hide error message
    function hideError() {
        errorContainer.style.display = 'none';
    }

    // Video file selection handler
    if (videoInput) {
        videoInput.addEventListener('change', function() {
            hideError();

            if (this.files && this.files.length > 0) {
                const file = this.files[0];
                const size = file.size;

                // Display file info
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(size);
                fileInfo.style.display = 'block';

                // Validate file size
                if (size > MAX_VIDEO_SIZE) {
                    showError(`Video file is too large (${formatFileSize(size)}). Maximum allowed size is 500MB. Please compress your video or choose a smaller file.`);
                    this.value = '';
                    fileInfo.style.display = 'none';
                    uploadBtn.disabled = true;
                    return;
                } else {
                    uploadBtn.disabled = false;
                }

                // Validate file type
                const validExtensions = ['mp4', 'avi', 'mov', 'wmv', 'mkv', 'webm', 'flv'];
                const extension = file.name.split('.').pop().toLowerCase();

                if (!validExtensions.includes(extension)) {
                    showError(`Invalid file type. Please upload a video file (${validExtensions.join(', ').toUpperCase()}).`);
                    this.value = '';
                    fileInfo.style.display = 'none';
                    uploadBtn.disabled = true;
                    return;
                }
            } else {
                fileInfo.style.display = 'none';
            }
        });
    }

    // Subtitle file selection handler
    if (subtitleInput) {
        subtitleInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                const file = this.files[0];
                const size = file.size;

                subtitleInfo.style.display = 'block';

                // Validate subtitle file size
                if (size > MAX_SUBTITLE_SIZE) {
                    showError(`Subtitle file is too large (${formatFileSize(size)}). Maximum allowed size is 10MB.`);
                    this.value = '';
                    subtitleInfo.style.display = 'none';
                    return;
                }

                // Validate subtitle file type
                const validExtensions = ['srt', 'vtt', 'ass', 'ssa'];
                const extension = file.name.split('.').pop().toLowerCase();

                if (!validExtensions.includes(extension)) {
                    showError(`Invalid subtitle file type. Please upload a subtitle file (${validExtensions.join(', ').toUpperCase()}).`);
                    this.value = '';
                    subtitleInfo.style.display = 'none';
                    return;
                }
            } else {
                subtitleInfo.style.display = 'none';
            }
        });
    }

    // Form submission handler
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            hideError();

            // Validate form
            const courseId = document.getElementById('course_id').value;
            const title = document.getElementById('title').value;
            const videoFile = videoInput.files[0];

            if (!courseId) {
                showError('Please select a course.');
                return;
            }

            if (!title.trim()) {
                showError('Please enter a video title.');
                return;
            }

            if (!videoFile) {
                showError('Please select a video file to upload.');
                return;
            }

            // Final size check
            if (videoFile.size > MAX_VIDEO_SIZE) {
                showError(`Video file is too large (${formatFileSize(videoFile.size)}). Maximum allowed size is 500MB.`);
                return;
            }

            // Show upload overlay
            uploadOverlay.style.display = 'flex';
            uploadStatus.textContent = 'Preparing upload...';
            uploadSize.textContent = `Total size: ${formatFileSize(videoFile.size)}`;
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';

            // Disable form
            uploadBtn.disabled = true;

            // Create FormData
            const formData = new FormData(form);

            // Create XMLHttpRequest for progress tracking
            const xhr = new XMLHttpRequest();

            // Upload progress handler
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percentComplete + '%';
                    progressBar.textContent = percentComplete + '%';

                    if (percentComplete < 100) {
                        uploadStatus.textContent = `Uploading... ${formatFileSize(e.loaded)} of ${formatFileSize(e.total)}`;
                    } else {
                        uploadStatus.textContent = 'Processing video...';
                    }
                }
            });

            // Upload complete handler
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    // Check for redirect in response
                    const responseURL = xhr.responseURL || '';

                    if (responseURL.includes('success=')) {
                        uploadStatus.textContent = '✓ Upload complete!';
                        progressBar.style.background = 'linear-gradient(90deg, #28a745, #218838)';

                        setTimeout(function() {
                            window.location.href = responseURL;
                        }, 1000);
                    } else if (responseURL.includes('error=')) {
                        const urlParams = new URLSearchParams(responseURL.split('?')[1]);
                        const error = urlParams.get('error') || 'Upload failed';

                        uploadOverlay.style.display = 'none';
                        showError(decodeURIComponent(error));
                        uploadBtn.disabled = false;
                    } else {
                        // Follow the redirect
                        window.location.href = responseURL;
                    }
                } else {
                    uploadOverlay.style.display = 'none';
                    showError('Upload failed. Server returned status: ' + xhr.status);
                    uploadBtn.disabled = false;
                }
            });

            // Upload error handler
            xhr.addEventListener('error', function() {
                uploadOverlay.style.display = 'none';
                showError('Upload failed. Please check your internet connection and try again.');
                uploadBtn.disabled = false;
            });

            // Upload abort handler
            xhr.addEventListener('abort', function() {
                uploadOverlay.style.display = 'none';
                showError('Upload was cancelled.');
                uploadBtn.disabled = false;
            });

            // Send request
            xhr.open('POST', '../api/upload-video.php', true);
            xhr.send(formData);
        });
    }
});
</script>
<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireInstructor();

$video_id = $_GET['video_id'] ?? 0;

if (!$video_id) {
    header('Location: instructor-dashboard.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Verify instructor owns this video
if ($conn === $database) {
    $video = $database->selectOne('videos', ['id' => $video_id]);
    if ($video) {
        $course = $database->selectOne('courses', ['id' => $video['course_id']]);
        $owns_video = $course && $course['instructor_id'] == $_SESSION['user_id'];
    } else {
        $owns_video = false;
    }
} else {
    $stmt = $conn->prepare("
        SELECT v.*, c.title as course_title FROM videos v
        JOIN courses c ON v.course_id = c.id
        WHERE v.id = ? AND c.instructor_id = ?
    ");
    $stmt->execute([$video_id, $_SESSION['user_id']]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    $owns_video = $video !== false;
}

if (!$owns_video) {
    header('Location: instructor-dashboard.php');
    exit();
}

// Get subtitle information
if ($conn === $database) {
    $subtitle = $database->selectOne('subtitles', ['video_id' => $video_id]);
} else {
    $stmt = $conn->prepare("SELECT * FROM subtitles WHERE video_id = ?");
    $stmt->execute([$video_id]);
    $subtitle = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subtitles - <?php echo htmlspecialchars($video['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .subtitle-container {
            max-width: 800px;
            margin: 120px auto 2rem;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }

        .process-step {
            background: #f8f9fa;
            border-left: 4px solid #dee2e6;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 8px 8px 0;
        }

        .process-step.active {
            border-left-color: #007bff;
            background: #e3f2fd;
        }

        .process-step.completed {
            border-left-color: #28a745;
            background: #d4edda;
        }

        .process-step.failed {
            border-left-color: #dc3545;
            background: #f8d7da;
        }

        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            margin: 1rem 0;
            transition: all 0.3s ease;
        }

        .upload-area:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }

        .upload-area.dragover {
            border-color: #007bff;
            background: #e3f2fd;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="index.php" style="text-decoration: none; color: inherit;">
                    <h2>Cheche</h2>
                </a>
            </div>
            <div class="nav-links">
                <a href="instructor-dashboard.php">Dashboard</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Instructor'); ?></span>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="subtitle-container">
        <div style="margin-bottom: 2rem;">
            <a href="instructor-dashboard.php?tab=courses" style="color: #007bff; text-decoration: none;">← Back to Courses</a>
        </div>

        <h1>🎬 Subtitle Management</h1>
        <h2 style="color: #666; margin-bottom: 2rem;"><?php echo htmlspecialchars($video['title']); ?></h2>

        <?php if ($subtitle): ?>
            <div style="background: #e3f2fd; border: 1px solid #bbdefb; border-radius: 8px; padding: 1rem; margin-bottom: 2rem;">
                <h3 style="margin: 0 0 1rem 0;">📋 Subtitle Status</h3>

                <div class="process-step <?php echo $subtitle['translation_status'] === 'completed' ? 'completed' : ($subtitle['translation_status'] === 'failed' ? 'failed' : 'active'); ?>">
                    <h4 style="margin: 0 0 0.5rem 0;">
                        🌍 Translation Status
                        <span class="status-indicator status-<?php echo $subtitle['translation_status']; ?>">
                            <?php echo ucfirst($subtitle['translation_status']); ?>
                        </span>
                    </h4>
                    <p style="margin: 0;">Converting English subtitles to Igbo language</p>
                </div>

                <div class="process-step <?php echo $subtitle['merge_status'] === 'completed' ? 'completed' : ($subtitle['merge_status'] === 'failed' ? 'failed' : ($subtitle['translation_status'] === 'completed' ? 'active' : '')); ?>">
                    <h4 style="margin: 0 0 0.5rem 0;">
                        🎥 Video Merge Status
                        <span class="status-indicator status-<?php echo $subtitle['merge_status']; ?>">
                            <?php echo ucfirst($subtitle['merge_status']); ?>
                        </span>
                    </h4>
                    <p style="margin: 0;">Embedding Igbo subtitles into video file</p>
                </div>

                <div style="background: white; border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                    <h4 style="margin: 0 0 1rem 0;">📁 Files Generated</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div>
                            <strong>Original Subtitle:</strong><br>
                            <?php echo $subtitle['original_file_path'] ? '✅ Uploaded' : '❌ Not found'; ?>
                        </div>
                        <div>
                            <strong>Igbo Translation:</strong><br>
                            <?php echo $subtitle['translated_file_path'] ? '✅ Generated' : '⏳ Pending'; ?>
                        </div>
                        <div>
                            <strong>Merged Video:</strong><br>
                            <?php echo $subtitle['merged_video_path'] ? '✅ Ready' : '⏳ Processing'; ?>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                    <?php if ($subtitle['translation_status'] === 'failed'): ?>
                        <button onclick="retryTranslation(<?php echo $subtitle['id']; ?>)" class="btn-primary">
                            🔄 Retry Translation
                        </button>
                    <?php endif; ?>

                    <?php if ($subtitle['merge_status'] === 'failed'): ?>
                        <button onclick="retryMerge(<?php echo $subtitle['id']; ?>)" class="btn-primary">
                            🔄 Retry Merge
                        </button>
                    <?php endif; ?>

                    <?php if ($subtitle['merged_video_path'] && file_exists($subtitle['merged_video_path'])): ?>
                        <a href="course.php?id=<?php echo $video['course_id']; ?>&video=<?php echo $video_id; ?>" class="btn-success">
                            🎬 Preview Video with Subtitles
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="upload-area" id="subtitle-upload-area">
                <div style="margin-bottom: 1rem;">
                    <h3>📤 Upload Subtitle File</h3>
                    <p style="color: #666; margin: 0;">Drop your subtitle file here or click to browse</p>
                </div>

                <form id="subtitle-upload-form" enctype="multipart/form-data">
                    <input type="hidden" name="video_id" value="<?php echo $video_id; ?>">
                    <input type="file" name="subtitle_file" id="subtitle_file" accept=".srt,.vtt,.ass,.ssa" style="display: none;">
                    <button type="button" onclick="document.getElementById('subtitle_file').click()" class="btn-primary">
                        Choose Subtitle File
                    </button>
                </form>

                <div style="margin-top: 1rem; font-size: 0.9rem; color: #666;">
                    <strong>Supported formats:</strong> SRT, VTT, ASS, SSA<br>
                    <strong>Auto-translation:</strong> English → Igbo<br>
                    <strong>Max file size:</strong> 10MB
                </div>
            </div>

            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                <h4 style="margin: 0 0 0.5rem 0;">💡 How it works:</h4>
                <ol style="margin: 0; padding-left: 1.5rem;">
                    <li>Upload your English subtitle file (.srt recommended)</li>
                    <li>System automatically translates text to Igbo language</li>
                    <li>Translated subtitles are merged with your video</li>
                    <li>Students can watch with embedded Igbo subtitles</li>
                </ol>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const subtitleInput = document.getElementById('subtitle_file');
            const uploadForm = document.getElementById('subtitle-upload-form');
            const uploadArea = document.getElementById('subtitle-upload-area');

            if (subtitleInput) {
                subtitleInput.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        uploadSubtitle(this.files[0]);
                    }
                });
            }

            if (uploadArea) {
                uploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('dragover');
                });

                uploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('dragover');
                });

                uploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('dragover');

                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        uploadSubtitle(files[0]);
                    }
                });
            }
        });

        async function uploadSubtitle(file) {
            const formData = new FormData();
            formData.append('video_id', <?php echo $video_id; ?>);
            formData.append('subtitle_file', file);

            try {
                document.getElementById('subtitle-upload-area').innerHTML = '<p>📤 Uploading and processing subtitle...</p>';

                const response = await fetch('../api/upload-subtitle.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('Subtitle uploaded successfully! Translation and merging in progress...');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                    location.reload();
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('Failed to upload subtitle. Please try again.');
                location.reload();
            }
        }

        async function retryTranslation(subtitleId) {
            try {
                const response = await fetch('../api/process-subtitle.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        subtitle_id: subtitleId,
                        action: 'translate'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Translation restarted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Retry error:', error);
                alert('Failed to retry translation. Please try again.');
            }
        }

        async function retryMerge(subtitleId) {
            try {
                const response = await fetch('../api/process-subtitle.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        subtitle_id: subtitleId,
                        action: 'merge'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Video merge restarted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Retry error:', error);
                alert('Failed to retry merge. Please try again.');
            }
        }
    </script>
</body>
</html>
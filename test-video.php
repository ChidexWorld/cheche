<!DOCTYPE html>
<html>
<head>
    <title>Video Test</title>
</head>
<body>
    <h1>Video Playback Test</h1>

    <h2>MP4 Video Test</h2>
    <video controls width="640" height="360">
        <source src="uploads/videos/video_1757774715_68c5837bad1e7.mp4" type="video/mp4">
        Your browser does not support MP4 videos.
    </video>

    <h2>AVI Video Test (Original)</h2>
    <video controls width="640" height="360">
        <source src="uploads/videos/video_1757860648_68c6d32899326.avi" type="video/x-msvideo">
        Your browser does not support AVI videos.
    </video>

    <h2>Converted MP4 Video Test</h2>
    <video controls width="640" height="360">
        <source src="uploads/videos/video_1757860648_68c6d32899326.mp4" type="video/mp4">
        Your browser does not support MP4 videos.
    </video>

    <h2>Direct Links</h2>
    <p><a href="uploads/videos/video_1757774715_68c5837bad1e7.mp4" target="_blank">Download Original MP4 Video</a></p>
    <p><a href="uploads/videos/video_1757860648_68c6d32899326.avi" target="_blank">Download Original AVI Video</a></p>
    <p><a href="uploads/videos/video_1757860648_68c6d32899326.mp4" target="_blank">Download Converted MP4 Video</a></p>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const videos = document.querySelectorAll('video');
            videos.forEach((video, index) => {
                video.addEventListener('error', function(e) {
                    console.error(`Video ${index + 1} error:`, e);
                    const errorDiv = document.createElement('div');
                    errorDiv.style.cssText = 'background: #ffcdd2; color: #d32f2f; padding: 10px; margin: 10px 0;';
                    errorDiv.textContent = `Video ${index + 1} failed to load. Format may not be supported.`;
                    video.parentNode.insertBefore(errorDiv, video.nextSibling);
                });

                video.addEventListener('loadeddata', function() {
                    console.log(`Video ${index + 1} loaded successfully`);
                });
            });
        });
    </script>
</body>
</html>
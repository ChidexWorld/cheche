document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Load popular courses on homepage
    if (document.getElementById('coursesGrid')) {
        loadPopularCourses();
    }

    // Update stats with animation
    animateStats();

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
});

function loadPopularCourses() {
    fetch('api/get-courses.php?limit=6')
        .then(response => response.json())
        .then(data => {
            const coursesGrid = document.getElementById('coursesGrid');
            if (data.success && data.courses) {
                coursesGrid.innerHTML = data.courses.map(course => `
                    <div class="course-card">
                        <div class="course-thumbnail">
                            📹 ${course.title}
                        </div>
                        <div class="course-content">
                            <h3>${course.title}</h3>
                            <p>${course.description || 'Learn new skills with this comprehensive course.'}</p>
                            <div class="course-meta">
                                <span>By ${course.instructor_name}</span>
                                <span>${course.video_count || 0} videos</span>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                coursesGrid.innerHTML = `
                    <div class="course-card">
                        <div class="course-thumbnail">📹 Web Development</div>
                        <div class="course-content">
                            <h3>Complete Web Development</h3>
                            <p>Learn HTML, CSS, JavaScript and more</p>
                            <div class="course-meta">
                                <span>By Expert Instructor</span>
                                <span>12 videos</span>
                            </div>
                        </div>
                    </div>
                    <div class="course-card">
                        <div class="course-thumbnail">🎨 Design</div>
                        <div class="course-content">
                            <h3>UI/UX Design Fundamentals</h3>
                            <p>Master the art of user interface design</p>
                            <div class="course-meta">
                                <span>By Design Pro</span>
                                <span>8 videos</span>
                            </div>
                        </div>
                    </div>
                    <div class="course-card">
                        <div class="course-thumbnail">💻 Programming</div>
                        <div class="course-content">
                            <h3>Python for Beginners</h3>
                            <p>Start your programming journey with Python</p>
                            <div class="course-meta">
                                <span>By Code Master</span>
                                <span>15 videos</span>
                            </div>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading courses:', error);
        });
}

function animateStats() {
    const stats = document.querySelectorAll('.stat h3');
    stats.forEach(stat => {
        const target = parseInt(stat.textContent);
        let current = 0;
        const increment = target / 100;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            stat.textContent = Math.floor(current) + '+';
        }, 20);
    });
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });

    const emailFields = form.querySelectorAll('input[type="email"]');
    emailFields.forEach(field => {
        if (field.value && !isValidEmail(field.value)) {
            showFieldError(field, 'Please enter a valid email address');
            isValid = false;
        }
    });

    const passwordField = form.querySelector('input[name="password"]');
    const confirmPasswordField = form.querySelector('input[name="confirm_password"]');
    if (passwordField && confirmPasswordField) {
        if (passwordField.value !== confirmPasswordField.value) {
            showFieldError(confirmPasswordField, 'Passwords do not match');
            isValid = false;
        }
    }

    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '0.9rem';
    errorDiv.style.marginTop = '5px';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
    field.style.borderColor = '#dc3545';
}

function clearFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    field.style.borderColor = '#ddd';
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Video player functions
function initializeVideoPlayer(videoElement) {
    if (!videoElement) return;
    
    videoElement.addEventListener('loadedmetadata', function() {
        updateProgress(this, 0);
    });
    
    videoElement.addEventListener('timeupdate', function() {
        updateProgress(this, this.currentTime);
    });
    
    videoElement.addEventListener('ended', function() {
        markVideoComplete(this.dataset.videoId);
    });
}

function updateProgress(video, currentTime) {
    if (!video.dataset.videoId) return;
    
    const videoId = video.dataset.videoId;
    const duration = video.duration;
    const progress = (currentTime / duration) * 100;
    
    // Update progress in database every 10 seconds
    if (Math.floor(currentTime) % 10 === 0) {
        fetch('api/update-progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                video_id: videoId,
                watched_duration: currentTime,
                progress: progress
            })
        });
    }
}

function markVideoComplete(videoId) {
    fetch('api/complete-video.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            video_id: videoId,
            completed: true
        })
    });
}

// Download functionality
function downloadVideo(videoPath, videoTitle) {
    const link = document.createElement('a');
    link.href = videoPath;
    link.download = videoTitle + '.mp4';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
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

    // Initialize password toggles
    initializePasswordToggles();

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });

    // MODAL FUNCTIONALITY - moved here to consolidate DOMContentLoaded events
    // Close modal when clicking on close button or outside modal
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-close') || e.target.classList.contains('modal')) {
            e.preventDefault();
            const modal = e.target.closest('.modal') || e.target;
            if (modal && modal.classList.contains('modal')) {
                modal.style.setProperty('display', 'none', 'important');
            }
        }
    });

    // Handle enrollment with AJAX
    document.addEventListener('click', function(e) {
        if (e.target.matches('a[href*="enroll.php"]')) {
            e.preventDefault();
            const enrollUrl = e.target.href;

            fetch(enrollUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Fallback to original behavior
                window.location.href = enrollUrl;
            });
        }
    });

    // Handle unenrollment with AJAX
    document.addEventListener('click', function(e) {
        if (e.target.matches('a[href*="unenroll.php"]')) {
            e.preventDefault();
            const unenrollUrl = e.target.href;

            fetch(unenrollUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Fallback to original behavior
                window.location.href = unenrollUrl;
            });
        }
    });
});

function loadPopularCourses() {
    // Determine the correct API path based on current location
    const isInViews = window.location.pathname.includes('/views/');
    const apiPath = isInViews ? '../api/get-courses.php?limit=6' : 'api/get-courses.php?limit=6';
    fetch(apiPath)
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

                // Apply translation to dynamically loaded content
                if (typeof currentLanguage !== 'undefined' && typeof translateDynamicContent === 'function') {
                    translateDynamicContent(currentLanguage);
                }
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

                // Apply translation to fallback content
                if (typeof currentLanguage !== 'undefined' && typeof translateDynamicContent === 'function') {
                    translateDynamicContent(currentLanguage);
                }
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
        const updateProgressPath = window.location.pathname.includes('/views/') ? '../api/update-progress.php' : 'api/update-progress.php';
        fetch(updateProgressPath, {
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
    const completeVideoPath = window.location.pathname.includes('/views/') ? '../api/complete-video.php' : 'api/complete-video.php';
    fetch(completeVideoPath, {
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

// Password toggle functionality
function initializePasswordToggles() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(function(passwordInput) {
        // Skip if already wrapped
        if (passwordInput.parentNode.classList.contains('password-container')) {
            return;
        }
        
        // Create container
        const container = document.createElement('div');
        container.className = 'password-container';
        
        // Create toggle button
        const toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.className = 'password-toggle';
        toggleButton.innerHTML = '<span class="eye-icon">👁️</span><span class="eye-slash">🙈</span>';
        
        // Wrap input in container
        passwordInput.parentNode.insertBefore(container, passwordInput);
        container.appendChild(passwordInput);
        container.appendChild(toggleButton);
        
        // Add click event
        toggleButton.addEventListener('click', function() {
            togglePasswordVisibility(passwordInput, toggleButton);
        });
    });
}

function togglePasswordVisibility(passwordInput, toggleButton) {
    const eyeIcon = toggleButton.querySelector('.eye-icon');
    const eyeSlash = toggleButton.querySelector('.eye-slash');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.style.display = 'none';
        eyeSlash.style.display = 'inline-block';
    } else {
        passwordInput.type = 'password';
        eyeIcon.style.display = 'inline-block';
        eyeSlash.style.display = 'none';
    }
}

// Modal functionality
function showModal(modalId) {
    console.log('showModal called with:', modalId);
    const modal = document.getElementById(modalId);
    console.log('Modal element found:', modal);
    if (modal) {
        modal.style.setProperty('display', 'block', 'important');
        console.log('Modal display style set to:', modal.style.display);
        console.log('Modal computed style:', window.getComputedStyle(modal).display);
    } else {
        console.error('Modal not found with ID:', modalId);
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.setProperty('display', 'none', 'important');
    }
}


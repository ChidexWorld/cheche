<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheche - Learn New Skills Online</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/language-dropdown.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h2>Cheche</h2>
            </div>
            <div class="nav-links">
                <div class="language-dropdown">
                    <button class="language-toggle" onclick="toggleDropdown()">
                        🌍 <span id="currentLang">English</span> ▼
                    </button>
                    <div class="dropdown-content" id="languageDropdown">
                        <a href="#" onclick="changeLanguage('en')">English</a>
                        <a href="#" onclick="changeLanguage('ig')">Igbo</a>
                    </div>
                </div>
                <a href="#courses" data-translate>Courses</a>
                <a href="#about" data-translate>About</a>
                <a href="login.php" class="btn-secondary" data-translate>Login</a>
                <a href="register.php" class="btn-primary" data-translate>Get Started</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 data-translate>Master New Skills with Expert-Led Courses</h1>
                    <p data-translate>Access high-quality video courses from industry professionals. Learn at your own pace, download content for offline viewing, and advance your career.</p>
                    <div class="hero-buttons">
                        <a href="register.php" class="btn-primary" data-translate>Start Learning</a>
                        <a href="#courses" class="btn-outline" data-translate>Browse Courses</a>
                    </div>
                </div>
                <div class="hero-image">
                    <div class="video-preview">
                        <div class="play-button">▶</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="features">
        <div class="container">
            <h2 data-translate>Why Choose Cheche?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🎥</div>
                    <h3 data-translate>HD Video Content</h3>
                    <p data-translate>Watch high-quality video lessons with clear audio and visuals</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⬇️</div>
                    <h3 data-translate>Download & Watch Offline</h3>
                    <p data-translate>Download videos to watch anytime, anywhere without internet</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👨‍🏫</div>
                    <h3 data-translate>Expert Instructors</h3>
                    <p data-translate>Learn from industry professionals and experienced educators</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3 data-translate>Track Progress</h3>
                    <p data-translate>Monitor your learning journey with detailed progress tracking</p>
                </div>
            </div>
        </div>
    </section>

    <section id="courses" class="courses-preview">
        <div class="container">
            <h2 data-translate>Popular Courses</h2>
            <div class="courses-grid" id="coursesGrid">
                <!-- Courses will be loaded dynamically -->
            </div>
            <div class="text-center">
                <a href="courses.php" class="btn-primary" data-translate>View All Courses</a>
            </div>
        </div>
    </section>

    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2 data-translate>About Cheche</h2>
                    <p data-translate>Cheche is a modern e-learning platform designed to make quality education accessible to everyone. Our platform connects passionate instructors with eager learners, creating a community focused on skill development and professional growth.</p>
                    <p data-translate>Whether you're looking to advance your career, learn a new hobby, or develop technical skills, Cheche provides the tools and content you need to succeed.</p>
                </div>
                <div class="stats">
                    <div class="stat">
                        <h3 id="studentCount">500+</h3>
                        <p data-translate>Active Students</p>
                    </div>
                    <div class="stat">
                        <h3 id="courseCount">50+</h3>
                        <p data-translate>Courses Available</p>
                    </div>
                    <div class="stat">
                        <h3 id="instructorCount">25+</h3>
                        <p data-translate>Expert Instructors</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Cheche</h3>
                    <p data-translate>Your gateway to learning new skills and advancing your career.</p>
                </div>
                <div class="footer-section">
                    <h4 data-translate>Quick Links</h4>
                    <a href="#courses" data-translate>Courses</a>
                    <a href="#about" data-translate>About</a>
                    <a href="register.php" data-translate>Sign Up</a>
                    <a href="login.php" data-translate>Login</a>
                </div>
                <div class="footer-section">
                    <h4 data-translate>For Instructors</h4>
                    <a href="register.php" data-translate>Become an Instructor</a>
                    <a href="instructor-dashboard.php" data-translate>Instructor Portal</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p data-translate>&copy; 2025 Cheche. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/language.js"></script>
</body>
</html>
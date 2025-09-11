<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheche - Learn New Skills Online</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h2>Cheche</h2>
            </div>
            <div class="nav-links">
                <a href="#courses">Courses</a>
                <a href="#about">About</a>
                <a href="login.php" class="btn-secondary">Login</a>
                <a href="register.php" class="btn-primary">Get Started</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Master New Skills with Expert-Led Courses</h1>
                    <p>Access high-quality video courses from industry professionals. Learn at your own pace, download content for offline viewing, and advance your career.</p>
                    <div class="hero-buttons">
                        <a href="register.php" class="btn-primary">Start Learning</a>
                        <a href="#courses" class="btn-outline">Browse Courses</a>
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
            <h2>Why Choose Cheche?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🎥</div>
                    <h3>HD Video Content</h3>
                    <p>Watch high-quality video lessons with clear audio and visuals</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⬇️</div>
                    <h3>Download & Watch Offline</h3>
                    <p>Download videos to watch anytime, anywhere without internet</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👨‍🏫</div>
                    <h3>Expert Instructors</h3>
                    <p>Learn from industry professionals and experienced educators</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Track Progress</h3>
                    <p>Monitor your learning journey with detailed progress tracking</p>
                </div>
            </div>
        </div>
    </section>

    <section id="courses" class="courses-preview">
        <div class="container">
            <h2>Popular Courses</h2>
            <div class="courses-grid" id="coursesGrid">
                <!-- Courses will be loaded dynamically -->
            </div>
            <div class="text-center">
                <a href="courses.php" class="btn-primary">View All Courses</a>
            </div>
        </div>
    </section>

    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About Cheche</h2>
                    <p>Cheche is a modern e-learning platform designed to make quality education accessible to everyone. Our platform connects passionate instructors with eager learners, creating a community focused on skill development and professional growth.</p>
                    <p>Whether you're looking to advance your career, learn a new hobby, or develop technical skills, Cheche provides the tools and content you need to succeed.</p>
                </div>
                <div class="stats">
                    <div class="stat">
                        <h3 id="studentCount">500+</h3>
                        <p>Active Students</p>
                    </div>
                    <div class="stat">
                        <h3 id="courseCount">50+</h3>
                        <p>Courses Available</p>
                    </div>
                    <div class="stat">
                        <h3 id="instructorCount">25+</h3>
                        <p>Expert Instructors</p>
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
                    <p>Your gateway to learning new skills and advancing your career.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <a href="#courses">Courses</a>
                    <a href="#about">About</a>
                    <a href="register.php">Sign Up</a>
                    <a href="login.php">Login</a>
                </div>
                <div class="footer-section">
                    <h4>For Instructors</h4>
                    <a href="register.php">Become an Instructor</a>
                    <a href="instructor-dashboard.php">Instructor Portal</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Cheche. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
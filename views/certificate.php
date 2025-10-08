<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$certificate_id = $_GET['id'] ?? 0;

if (!$certificate_id) {
    header('Location: student-dashboard.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get certificate details
$stmt = $conn->prepare("
    SELECT c.*, co.title as course_title, co.description as course_description,
           u.full_name as student_name, i.full_name as instructor_name
    FROM certificates c
    JOIN courses co ON c.course_id = co.id
    JOIN users u ON c.student_id = u.id
    JOIN users i ON co.instructor_id = i.id
    WHERE c.id = ?
");
$stmt->execute([$certificate_id]);
$certificate = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$certificate) {
    header('Location: student-dashboard.php');
    exit();
}

// Check access permissions
$can_view = false;
if (isStudent() && $certificate['student_id'] == $_SESSION['user_id']) {
    $can_view = true;
} elseif (isInstructor() && $certificate['course_id']) {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->execute([$certificate['course_id'], $_SESSION['user_id']]);
    $course_check = $stmt->fetch(PDO::FETCH_ASSOC);
    $can_view = $course_check !== false;
}

if (!$can_view) {
    header('Location: student-dashboard.php');
    exit();
}

$certificate_data = json_decode($certificate['certificate_data'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?php echo htmlspecialchars($certificate['certificate_number']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Times New Roman', serif;
        }

        .certificate-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .certificate {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 3rem 2rem;
            position: relative;
        }

        .certificate::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="20" cy="80" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="80" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
        }

        .certificate-content {
            position: relative;
            z-index: 1;
        }

        .certificate-header {
            margin-bottom: 2rem;
        }

        .certificate-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .certificate-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .certificate-body {
            margin: 2rem 0;
        }

        .student-name {
            font-size: 2rem;
            font-weight: bold;
            margin: 1rem 0;
            text-decoration: underline;
            text-decoration-color: rgba(255,255,255,0.5);
        }

        .course-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 1rem 0;
            font-style: italic;
        }

        .certificate-details {
            background: white;
            color: #333;
            padding: 2rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .detail-label {
            font-weight: bold;
            color: #666;
            font-size: 0.9rem;
        }

        .detail-value {
            font-size: 1.1rem;
            margin-top: 0.25rem;
        }

        .certificate-footer {
            text-align: center;
            padding-top: 2rem;
            border-top: 2px solid #eee;
        }

        .signature-line {
            border-top: 2px solid #333;
            width: 200px;
            margin: 2rem auto 0.5rem;
        }

        .actions {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
        }

        .btn {
            margin: 0 0.5rem;
        }

        @media print {
            body {
                background: white;
            }

            .navbar, .actions {
                display: none;
            }

            .certificate-container {
                max-width: none;
                margin: 0;
                box-shadow: none;
            }
        }

        @media (max-width: 768px) {
            .certificate {
                padding: 2rem 1rem;
            }

            .certificate-title {
                font-size: 2rem;
            }

            .student-name {
                font-size: 1.5rem;
            }

            .course-title {
                font-size: 1.2rem;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }
        }

        .verification-badge {
            display: inline-flex;
            align-items: center;
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        .medal-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ffd700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
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
                <a href="<?php echo isInstructor() ? 'instructor-dashboard.php' : 'student-dashboard.php'; ?>">Dashboard</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?></span>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="certificate-container">
        <div class="certificate">
            <div class="certificate-content">
                <div class="certificate-header">
                    <div class="medal-icon">🏆</div>
                    <h1 class="certificate-title">Certificate of Completion</h1>
                    <p class="certificate-subtitle">This is to certify that</p>
                </div>

                <div class="certificate-body">
                    <div class="student-name"><?php echo htmlspecialchars($certificate['student_name']); ?></div>

                    <p style="font-size: 1.2rem; margin: 1.5rem 0;">has successfully completed the course</p>

                    <div class="course-title"><?php echo htmlspecialchars($certificate['course_title']); ?></div>

                    <?php if ($certificate['quiz_score']): ?>
                        <p style="margin-top: 1rem;">with a quiz score of <strong><?php echo number_format($certificate['quiz_score'], 1); ?>%</strong></p>
                    <?php endif; ?>

                    <div class="verification-badge">
                        ✅ Verified Certificate #<?php echo htmlspecialchars($certificate['certificate_number']); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="certificate-details">
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Certificate Number</div>
                    <div class="detail-value"><?php echo htmlspecialchars($certificate['certificate_number']); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Issue Date</div>
                    <div class="detail-value"><?php echo date('F d, Y', strtotime($certificate['issued_at'])); ?></div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Course Completion</div>
                    <div class="detail-value"><?php echo number_format($certificate['completion_percentage'], 1); ?>%</div>
                </div>

                <?php if ($certificate['quiz_score']): ?>
                <div class="detail-item">
                    <div class="detail-label">Quiz Score</div>
                    <div class="detail-value"><?php echo number_format($certificate['quiz_score'], 1); ?>%</div>
                </div>
                <?php endif; ?>
            </div>

            <div class="certificate-footer">
                <p><strong>Instructor:</strong> <?php echo htmlspecialchars($certificate['instructor_name']); ?></p>

                <div class="signature-line"></div>
                <p style="font-size: 0.9rem; color: #666;">Authorized Signature</p>

                <p style="margin-top: 2rem; font-size: 0.9rem; color: #666;">
                    This certificate can be verified at cheche.edu/verify using certificate number:
                    <strong><?php echo htmlspecialchars($certificate['certificate_number']); ?></strong>
                </p>
            </div>
        </div>

        <div class="actions">
            <button onclick="window.print()" class="btn-primary">🖨️ Print Certificate</button>
            <button onclick="downloadCertificate()" class="btn-secondary">📄 Download PDF</button>
            <a href="<?php echo isInstructor() ? 'instructor-dashboard.php' : 'student-dashboard.php'; ?>" class="btn">← Back to Dashboard</a>
        </div>
    </div>

    <script>
        function downloadCertificate() {
            // Simple implementation - in production you'd want to generate a proper PDF
            window.print();
        }

        // Add some certificate validation
        document.addEventListener('DOMContentLoaded', function() {
            // Add a subtle animation
            const certificate = document.querySelector('.certificate');
            certificate.style.opacity = '0';
            certificate.style.transform = 'scale(0.95)';
            certificate.style.transition = 'all 0.5s ease';

            setTimeout(() => {
                certificate.style.opacity = '1';
                certificate.style.transform = 'scale(1)';
            }, 100);
        });
    </script>
</body>
</html>
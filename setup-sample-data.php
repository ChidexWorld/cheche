<?php
require_once 'config/database.php';

echo "Setting up sample data for Cheche Learning Platform...<br><br>";

$database = new Database();

// Create sample users
$users_data = [
    [
        'username' => 'instructor1',
        'email' => 'instructor@cheche.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role' => 'instructor',
        'full_name' => 'John Smith',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    [
        'username' => 'student1',
        'email' => 'student@cheche.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role' => 'student',
        'full_name' => 'Alice Johnson',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    [
        'username' => 'student2',
        'email' => 'student2@cheche.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role' => 'student',
        'full_name' => 'Bob Wilson',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]
];

// Insert users
foreach ($users_data as $user) {
    $user_id = $database->insert('users', $user);
    echo "Created user: {$user['full_name']} (ID: $user_id)<br>";
}

// Create sample courses
$courses_data = [
    [
        'title' => 'Introduction to Web Development',
        'description' => 'Learn the basics of HTML, CSS, and JavaScript',
        'instructor_id' => 1,
        'category' => 'Web Development',
        'price' => 99.99,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    [
        'title' => 'Advanced PHP Programming',
        'description' => 'Master PHP for web development and backend systems',
        'instructor_id' => 1,
        'category' => 'Backend Development',
        'price' => 149.99,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    [
        'title' => 'Database Design Fundamentals',
        'description' => 'Learn database design principles and SQL',
        'instructor_id' => 1,
        'category' => 'Database',
        'price' => 79.99,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]
];

// Insert courses
foreach ($courses_data as $course) {
    $course_id = $database->insert('courses', $course);
    echo "Created course: {$course['title']} (ID: $course_id)<br>";
}

// Create sample videos
$videos_data = [
    [
        'course_id' => 1,
        'title' => 'Introduction to HTML',
        'description' => 'Learn the basics of HTML structure',
        'video_path' => 'uploads/videos/html-intro.mp4',
        'duration' => 1200,
        'order_number' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'course_id' => 1,
        'title' => 'CSS Styling Basics',
        'description' => 'Introduction to CSS styling',
        'video_path' => 'uploads/videos/css-basics.mp4',
        'duration' => 1800,
        'order_number' => 2,
        'created_at' => date('Y-m-d H:i:s')
    ],
    [
        'course_id' => 2,
        'title' => 'PHP Variables and Functions',
        'description' => 'Understanding PHP basics',
        'video_path' => 'uploads/videos/php-basics.mp4',
        'duration' => 2400,
        'order_number' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ]
];

// Insert videos
foreach ($videos_data as $video) {
    $video_id = $database->insert('videos', $video);
    echo "Created video: {$video['title']} (ID: $video_id)<br>";
}

// Create sample enrollments
$enrollments_data = [
    [
        'student_id' => 2,
        'course_id' => 1,
        'enrolled_at' => date('Y-m-d H:i:s'),
        'progress' => 25.00
    ],
    [
        'student_id' => 3,
        'course_id' => 1,
        'enrolled_at' => date('Y-m-d H:i:s'),
        'progress' => 0.00
    ]
];

// Insert enrollments
foreach ($enrollments_data as $enrollment) {
    $enrollment_id = $database->insert('enrollments', $enrollment);
    echo "Created enrollment (ID: $enrollment_id)<br>";
}

echo "<br><strong>✅ Sample data created successfully!</strong><br><br>";

// Show summary
$user_count = $database->count('users');
$course_count = $database->count('courses');
$video_count = $database->count('videos');
$enrollment_count = $database->count('enrollments');

echo "Summary:<br>";
echo "- Users: $user_count<br>";
echo "- Courses: $course_count<br>";
echo "- Videos: $video_count<br>";
echo "- Enrollments: $enrollment_count<br><br>";

echo "<strong>Test Accounts:</strong><br>";
echo "Instructor: instructor1 / password123<br>";
echo "Student: student1 / password123<br>";
echo "Student: student2 / password123<br><br>";

echo "<a href='index.php'>Go to Homepage</a> | ";
echo "<a href='views/courses.php'>View Courses</a> | ";
echo "<a href='views/login.php'>Login</a>";
?>
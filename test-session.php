<?php
session_start();
$_SESSION['user_id'] = 9;
$_SESSION['username'] = 'student1';
$_SESSION['role'] = 'student';

echo "Session set. <a href='views/course-preview.php?id=4'>Go to course preview</a>";
?>
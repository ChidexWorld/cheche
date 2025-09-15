<?php
// Redirect to new location - preserve query parameters
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$redirect = 'views/course.php' . ($queryString ? '?' . $queryString : '');
header('Location: ' . $redirect, true, 301);
exit();
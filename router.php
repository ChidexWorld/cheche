<?php
// Router file to handle requests
if (php_sapi_name() == 'cli-server') {
    // Static file handling
    $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $url;
    
    // Serve static files directly
    if (is_file($file)) {
        return false;
    }

    // Handle the root URL
    if ($url == '/') {
        require __DIR__ . '/index.php';
        return true;
    }

    // Handle other PHP files
    if (preg_match('/\.php$/', $url)) {
        require __DIR__ . $url;
        return true;
    }
}

// If no match, serve index.php
require __DIR__ . '/index.php';
?>
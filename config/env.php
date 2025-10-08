<?php

class Env {
    private static $loaded = false;

    public static function load($file = null) {
        if (self::$loaded) {
            return;
        }

        $envFile = $file ?: __DIR__ . '/../.env';

        // Only load .env file if it exists (for local development)
        // On Render, environment variables are set at the system level
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $line = trim($line);

                // Skip comments
                if (strpos($line, '#') === 0) {
                    continue;
                }

                // Parse key=value pairs
                $parts = explode('=', $line, 2);
                if (count($parts) !== 2) {
                    continue;
                }

                $key = trim($parts[0]);
                $value = trim($parts[1]);

                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }

                // Set environment variable if not already set
                if (!isset($_ENV[$key]) && !getenv($key)) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }

        self::$loaded = true;
    }

    public static function get($key, $default = null) {
        // Ensure env is loaded
        self::load();

        // Try $_ENV first, then getenv()
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        $value = getenv($key);
        return $value !== false ? $value : $default;
    }

    public static function getInt($key, $default = 0) {
        return (int) self::get($key, $default);
    }

    public static function getBool($key, $default = false) {
        $value = strtolower(self::get($key, $default));
        return in_array($value, ['true', '1', 'yes', 'on'], true);
    }

    public static function getArray($key, $default = []) {
        $value = self::get($key, '');
        return empty($value) ? $default : array_map('trim', explode(',', $value));
    }
}

// Auto-load environment on include
Env::load();
?>
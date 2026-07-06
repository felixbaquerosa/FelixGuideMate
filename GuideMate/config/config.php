<?php

declare(strict_types=1);

/** Load key=value pairs from .env into the process environment. */
(function (): void {
    $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (!is_file($envPath)) {
        return;
    }
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);

        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'")) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
})();

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return match (strtolower($value)) {
        'true' => true,
        'false' => false,
        'null' => null,
        default => $value,
    };
}

return [
    'app' => [
        'name' => env('APP_NAME', 'GuideMate'),
        'env' => env('APP_ENV', 'local'),
        'debug' => (bool) env('APP_DEBUG', true),
        'url' => env('APP_URL', ''),
        'timezone' => env('APP_TIMEZONE', 'Asia/Manila'),
        'locale' => env('APP_LOCALE', 'en'),
        'region' => 'Cebu, Philippines',
    ],
    'db' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => (int) env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'guidemate'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name' => env('SESSION_NAME', 'guidemate_session'),
        'lifetime' => (int) env('SESSION_LIFETIME', 7200),
    ],
    'mail' => [
        'from' => env('MAIL_FROM', ''),
        'from_name' => env('MAIL_FROM_NAME', 'GuideMate'),
        'host' => env('MAIL_HOST', ''),
        'port' => (int) env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
    ],
    'weather' => [
        'api_key' => env('OPENWEATHER_API_KEY', ''),
    ],
    'sms' => [
        'api_key' => env('SMS_API_KEY', ''),
        'sender' => env('SMS_SENDER', 'GuideMate'),
    ],
    'currency' => [
        'usd_php_rate' => (float) env('USD_PHP_RATE', 56.0),
    ],
    'maps' => [
        'google_api_key' => env('GOOGLE_MAPS_API_KEY', ''),
        'mapbox_access_token' => env('MAPBOX_ACCESS_TOKEN', ''),
        'mapillary_access_token' => env('MAPILLARY_ACCESS_TOKEN', ''),
    ],
    'hero' => [
        // 1080p default for smooth autoplay; 4K optional via HERO_VIDEO_4K (heavier, may stutter).
        'video_4k' => env('HERO_VIDEO_4K', 'https://videos.pexels.com/video-files/3571264/3571264-uhd_3840_2160_30fps.mp4'),
        'video_hd' => env('HERO_VIDEO_HD', 'https://videos.pexels.com/video-files/3571264/3571264-hd_1920_1080_30fps.mp4'),
        'poster' => env('HERO_POSTER', 'https://images.unsplash.com/photo-1518509562904-e7ef99cdcc86?auto=format&fit=crop&w=1920&q=70'),
    ],
];

<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Auth;

/**
 * Global helper functions available throughout the app and views.
 */

if (!function_exists('e')) {
    /**
     * Escape a value for safe HTML output.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /**
     * Build an absolute-path URL respecting the app base path (subfolder support).
     */
    function url(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        return App::basePath() . $path;
    }
}

if (!function_exists('asset')) {
    /**
     * URL to a file in public/assets.
     */
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    /**
     * Send a redirect and stop execution.
     */
    function redirect(string $path): void
    {
        $location = str_starts_with($path, 'http') ? $path : url($path);
        header('Location: ' . $location);
        exit;
    }
}

if (!function_exists('request_snapshot')) {
    /**
     * Snapshot the validation errors + old input from the session exactly once
     * per request, then clear them. This lets old() and errors() be called in
     * any order (controller or view) without one wiping the other.
     *
     * @return array{errors: array<string,string>, old: array<string,mixed>}
     */
    function request_snapshot(): array
    {
        static $snapshot = null;
        if ($snapshot === null) {
            $snapshot = [
                'errors' => $_SESSION['_errors'] ?? [],
                'old' => $_SESSION['_old'] ?? [],
            ];
            unset($_SESSION['_errors'], $_SESSION['_old']);
        }
        return $snapshot;
    }
}

if (!function_exists('old')) {
    /**
     * Retrieve previously submitted input after a validation redirect.
     */
    function old(string $key, string $default = ''): string
    {
        $value = request_snapshot()['old'][$key] ?? $default;
        return is_string($value) ? $value : $default;
    }
}

if (!function_exists('flash')) {
    /**
     * Set or get a one-time flash message.
     */
    function flash(string $key, ?string $message = null): ?string
    {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}

if (!function_exists('flash_keep_old')) {
    /**
     * Store submitted input for redisplay, plus an errors bag, then redirect back.
     *
     * @param array<string, string> $errors
     * @param array<string, mixed> $input
     */
    function flash_keep_old(array $errors, array $input, string $back): void
    {
        $_SESSION['_errors'] = $errors;
        $_SESSION['_old'] = $input;
        redirect($back);
    }
}

if (!function_exists('errors')) {
    /**
     * Pull the validation errors bag (cleared after read).
     *
     * @return array<string, string>
     */
    function errors(): array
    {
        return request_snapshot()['errors'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Render a hidden CSRF input for forms.
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(Auth::csrfToken()) . '">';
    }
}

if (!function_exists('method_field')) {
    /**
     * Spoof a non-POST HTTP method (PUT/DELETE).
     */
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('auth_user')) {
    /**
     * Currently authenticated user array, or null.
     *
     * @return array<string, mixed>|null
     */
    function auth_user(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('abort')) {
    /**
     * Stop the request with an HTTP status and a friendly error page.
     */
    function abort(int $status, string $message = ''): void
    {
        http_response_code($status);
        \App\Core\View::render('errors/error', [
            'status' => $status,
            'message' => $message,
            'title' => "Error {$status}",
        ]);
        exit;
    }
}

if (!function_exists('money')) {
    /**
     * Format amount stored in PHP using the visitor's display currency (not checkout).
     */
    function money(float|int|string $amount): string
    {
        $php = (float) $amount;
        $code = strtoupper((string) ($_SESSION['_currency'] ?? 'USD'));
        $row = \App\Core\LocaleCatalog::currency($code);
        if ($row === null) {
            return '₱' . number_format($php, 2);
        }
        if ($code === 'PHP') {
            return $row['symbol'] . number_format($php, 2);
        }
        $rate = (float) $row['php_per_unit'];
        if ($rate <= 0) {
            return $row['symbol'] . number_format($php, 2);
        }
        $converted = $php / $rate;
        $decimals = in_array($code, ['JPY', 'KRW', 'VND', 'IDR', 'HUF'], true) ? 0 : 2;
        return $row['symbol'] . number_format($converted, $decimals);
    }
}

if (!function_exists('guide_badge')) {
    function guide_badge(int $guideId): string
    {
        return \App\Models\User::guideBadge($guideId);
    }
}

if (!function_exists('__')) {
    function __(string $key, string $default = ''): string
    {
        return \App\Core\Lang::get($key, $default !== '' ? $default : $key);
    }
}

if (!function_exists('stars')) {
    /**
     * Render a star-rating string (filled/half/empty) for a 0-5 value.
     */
    function stars(float $rating): string
    {
        $rating = max(0.0, min(5.0, $rating));
        $full = (int) floor($rating);
        $half = ($rating - $full) >= 0.5 ? 1 : 0;
        $empty = 5 - $full - $half;
        return str_repeat('★', $full) . str_repeat('⯨', $half) . str_repeat('☆', $empty);
    }
}

if (!function_exists('absolute_url')) {
    /**
     * Build a full URL for mobile clients and external links.
     */
    function absolute_url(string $path = '/'): string
    {
        $configured = (string) (App::config('app')['url'] ?? '');
        if ($configured !== '') {
            return rtrim($configured, '/') . '/' . ltrim($path, '/');
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return rtrim($scheme . '://' . $host . App::basePath(), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('api_img_src')) {
    /**
     * Absolute image URL for the mobile API.
     */
    function api_img_src(?string $path, string $fallbackSeed = 'guidemate'): string
    {
        if ($path === null || $path === '') {
            return "https://picsum.photos/seed/{$fallbackSeed}/1200/800";
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return absolute_url(ltrim($path, '/'));
    }
}

if (!function_exists('img_src')) {
    /**
     * Resolve an image path: external URLs pass through; otherwise treat as an
     * uploaded asset under public/. Falls back to a placeholder.
     */
    function img_src(?string $path, string $fallbackSeed = 'guidemate'): string
    {
        if ($path === null || $path === '') {
            return "https://picsum.photos/seed/{$fallbackSeed}/1200/800";
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return url(ltrim($path, '/'));
    }
}

if (!function_exists('active_when')) {
    /**
     * Echo "active" when the current path matches, for nav highlighting.
     */
    function active_when(string $path): string
    {
        $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = App::basePath();
        if ($base !== '' && str_starts_with($current, $base)) {
            $current = substr($current, strlen($base)) ?: '/';
        }
        return rtrim($current, '/') === rtrim($path, '/') ? 'active' : '';
    }
}

if (!function_exists('time_ago')) {
    /**
     * Human-friendly relative time, e.g. "3 days ago".
     */
    function time_ago(string $datetime): string
    {
        $ts = strtotime($datetime);
        if ($ts === false) {
            return $datetime;
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return 'just now';
        }
        $units = [31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute'];
        foreach ($units as $secs => $label) {
            if ($diff >= $secs) {
                $count = (int) floor($diff / $secs);
                return $count . ' ' . $label . ($count > 1 ? 's' : '') . ' ago';
            }
        }
        return 'just now';
    }
}

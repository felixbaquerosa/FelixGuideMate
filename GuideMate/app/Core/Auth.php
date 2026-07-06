<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Authentication for PUBLIC users (tourists & guides) only.
 *
 * Admins authenticate through a completely separate portal — see AdminAuth.
 * This guard stores its state under the session key "user_id", which is
 * independent from the admin's "admin_id", so the two never interfere:
 * an admin and a tourist/guide can be signed in side by side in the same
 * browser, and logging one out never affects the other.
 */
final class Auth
{
    private const SESSION_KEY = 'user_id';

    private static ?array $cachedUser = null;

    /**
     * Attempt to log a public user in. Admin accounts are rejected here on
     * purpose (they must use the admin portal). Suspended users are blocked.
     */
    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if ($user === null || !password_verify($password, $user['password'])) {
            return false;
        }
        if ($user['role'] === 'admin' || (int) $user['is_active'] === 0) {
            return false;
        }
        self::login($user);
        return true;
    }

    /**
     * @param array<string, mixed> $user
     */
    public static function login(array $user): void
    {
        // Regenerate the id to prevent fixation while KEEPING existing session
        // data (e.g. a separate admin_id), so the sessions stay isolated.
        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = (int) $user['id'];
        self::$cachedUser = $user;
    }

    /**
     * Log out only the public user — leaves any admin session intact.
     */
    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        self::$cachedUser = null;
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }
        self::$cachedUser = User::find((int) $_SESSION[self::SESSION_KEY]);
        return self::$cachedUser;
    }

    /** Reload the signed-in user from the database (e.g. after admin flags change). */
    public static function refreshUser(): void
    {
        self::$cachedUser = null;
        if (self::check()) {
            self::$cachedUser = User::find((int) $_SESSION[self::SESSION_KEY]);
        }
    }

    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION[self::SESSION_KEY] : null;
    }

    public static function hasRole(string $role): bool
    {
        $user = self::user();
        return $user !== null && $user['role'] === $role;
    }

    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }

    /**
     * Generate (once) and return the CSRF token for this session.
     */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verifyCsrf(string $token): bool
    {
        return !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }
}

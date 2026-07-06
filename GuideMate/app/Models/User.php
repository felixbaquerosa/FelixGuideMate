<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM users WHERE id = ?', [$id]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByEmail(string $email): ?array
    {
        return Database::first('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public static function emailExists(string $email): bool
    {
        return Database::first('SELECT id FROM users WHERE email = ?', [$email]) !== null;
    }

    /**
     * Create a user with a hashed password. Returns the new id.
     * Guides start with a 'pending' verification status; everyone else 'none'.
     */
    public static function create(string $name, string $email, string $password, string $role): int
    {
        $guideStatus = $role === 'guide' ? 'pending' : 'none';
        return Database::insert(
            'INSERT INTO users (name, email, password, role, guide_status) VALUES (?, ?, ?, ?, ?)',
            [$name, $email, password_hash($password, PASSWORD_BCRYPT), $role, $guideStatus]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function updateProfile(int $id, array $data): void
    {
        Database::run(
            'UPDATE users SET name = ?, phone = ?, location = ?, bio = ? WHERE id = ?',
            [$data['name'], $data['phone'], $data['location'], $data['bio'], $id]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return Database::all('SELECT * FROM users ORDER BY created_at DESC');
    }

    /**
     * Users that an admin can manage (tourists & guides) — excludes admins.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function manageable(): array
    {
        return Database::all('SELECT * FROM users WHERE role <> "admin" ORDER BY created_at DESC');
    }

    public static function updateAvatar(int $id, string $path): void
    {
        Database::run('UPDATE users SET avatar = ? WHERE id = ?', [$path, $id]);
    }

    public static function updatePassword(int $id, string $password): void
    {
        Database::run(
            'UPDATE users SET password = ? WHERE id = ?',
            [password_hash($password, PASSWORD_BCRYPT), $id]
        );
    }

    public static function setActive(int $id, bool $active): void
    {
        Database::run('UPDATE users SET is_active = ? WHERE id = ?', [$active ? 1 : 0, $id]);
    }

    public static function delete(int $id): void
    {
        Database::run('DELETE FROM users WHERE id = ?', [$id]);
    }

    public static function countByRole(string $role): int
    {
        $row = Database::first('SELECT COUNT(*) AS c FROM users WHERE role = ?', [$role]);
        return (int) ($row['c'] ?? 0);
    }

    /**
     * Update a guide's verification status (and optional admin note).
     */
    public static function setGuideStatus(int $id, string $status, ?string $note = null): void
    {
        Database::run(
            'UPDATE users SET guide_status = ?, guide_review_note = ?, guide_reviewed_at = NOW() WHERE id = ?',
            [$status, $note, $id]
        );
    }

    public static function appendGuideNote(int $id, string $note): void
    {
        Database::run(
            'UPDATE users SET guide_review_note = CONCAT(COALESCE(guide_review_note, ""), ?, "\n") WHERE id = ?',
            ['[Warning ' . date('Y-m-d') . '] ' . $note, $id]
        );
    }

    public static function warnGuide(int $id, string $note): void
    {
        Database::run(
            'UPDATE users SET guide_warned = 1, guide_warning_note = ? WHERE id = ?',
            [$note, $id]
        );
    }

    public static function clearGuideWarning(int $id): void
    {
        Database::run(
            'UPDATE users SET guide_warned = 0, guide_warning_note = NULL WHERE id = ?',
            [$id]
        );
    }

    public static function isGuideWarned(int $id): bool
    {
        $row = Database::first('SELECT guide_warned FROM users WHERE id = ?', [$id]);
        return $row !== null && (int) ($row['guide_warned'] ?? 0) === 1;
    }

    /** @return string Verified|TopRated|Elite|'' */
    public static function guideBadge(int $guideId): string
    {
        $user = self::find($guideId);
        if ($user === null || ($user['guide_status'] ?? '') !== 'approved') {
            return '';
        }
        $completed = Booking::countCompletedForGuide($guideId);
        if ($completed >= 20) {
            return 'Elite';
        }
        $stats = Database::first(
            'SELECT AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
             FROM reviews r JOIN listings l ON l.id = r.listing_id WHERE l.user_id = ?',
            [$guideId]
        );
        $avg = (float) ($stats['avg_rating'] ?? 0);
        $count = (int) ($stats['review_count'] ?? 0);
        if ($avg >= 4.5 && $count >= 5) {
            return 'TopRated';
        }
        return 'Verified';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function adminAccount(): ?array
    {
        return Database::first('SELECT * FROM users WHERE role = "admin" ORDER BY id ASC LIMIT 1');
    }

    /**
     * List guide accounts, optionally filtered by verification status.
     * Without a filter, pending applications are surfaced first.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function guides(?string $status = null): array
    {
        if ($status !== null) {
            return Database::all(
                'SELECT * FROM users WHERE role = "guide" AND guide_status = ? ORDER BY created_at DESC',
                [$status]
            );
        }
        return Database::all(
            'SELECT * FROM users WHERE role = "guide"
             ORDER BY FIELD(guide_status, "pending", "rejected", "approved", "none"), created_at DESC'
        );
    }

    public static function countGuidesByStatus(string $status): int
    {
        $row = Database::first(
            'SELECT COUNT(*) AS c FROM users WHERE role = "guide" AND guide_status = ?',
            [$status]
        );
        return (int) ($row['c'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function registrationsPerMonth(int $months = 12): array
    {
        return Database::all(
            'SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, COUNT(*) AS total
             FROM users
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY month ORDER BY month ASC',
            [$months]
        );
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public static function exportRows(): array
    {
        $rows = [];
        foreach (Database::all(
            'SELECT id, name, email, role, guide_status, is_active, created_at FROM users ORDER BY created_at DESC'
        ) as $u) {
            $rows[] = [
                $u['id'],
                $u['name'],
                $u['email'],
                $u['role'],
                $u['guide_status'],
                (int) $u['is_active'] === 1 ? 'yes' : 'no',
                $u['created_at'],
            ];
        }
        return $rows;
    }

    public static function setTotpSecret(int $id, string $secret): void
    {
        Database::run('UPDATE users SET admin_totp_secret = ? WHERE id = ?', [$secret, $id]);
    }

    public static function enableTotp(int $id): void
    {
        Database::run('UPDATE users SET admin_totp_enabled = 1 WHERE id = ?', [$id]);
    }

    public static function disableTotp(int $id): void
    {
        Database::run(
            'UPDATE users SET admin_totp_enabled = 0, admin_totp_secret = NULL WHERE id = ?',
            [$id]
        );
    }
}

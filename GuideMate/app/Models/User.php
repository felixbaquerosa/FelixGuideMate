<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    /**
     * Roles that offer services and go through the verification workflow
     * (document upload + admin approval via the `guide_status` column).
     */
    public const PROVIDER_ROLES = ['guide', 'rental_admin', 'hotel_admin'];

    /**
     * Human-readable labels for the provider roles.
     */
    public const PROVIDER_LABELS = [
        'guide' => 'Tour Guide',
        'rental_admin' => 'Rental Partner',
        'hotel_admin' => 'Hotel Partner',
    ];

    public static function isProviderRole(string $role): bool
    {
        return in_array($role, self::PROVIDER_ROLES, true);
    }

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
     * Provider roles (guide, rental_admin, hotel_admin) start 'pending' until
     * an admin reviews their submitted documents; everyone else 'none'.
     */
    public static function create(string $name, string $email, string $password, string $role): int
    {
        $guideStatus = self::isProviderRole($role) ? 'pending' : 'none';
        return Database::insert(
            'INSERT INTO users (name, email, password, role, guide_status) VALUES (?, ?, ?, ?, ?)',
            [$name, $email, password_hash($password, PASSWORD_BCRYPT), $role, $guideStatus]
        );
    }

    /**
     * Find a user previously linked to a social provider.
     *
     * @return array<string, mixed>|null
     */
    public static function findByOAuth(string $provider, string $oauthId): ?array
    {
        return Database::first(
            'SELECT * FROM users WHERE oauth_provider = ? AND oauth_id = ?',
            [$provider, $oauthId]
        );
    }

    /**
     * Create an ANONYMOUS tourist from a social login. We deliberately store no
     * real email/password — only the provider + its opaque user id — so the
     * admin can never see the Google/Facebook email or password. A throwaway
     * internal email satisfies the NOT NULL/UNIQUE column but is never shown.
     */
    public static function createOAuthUser(string $provider, string $oauthId, ?string $name = null): int
    {
        // Use the real Google/Facebook display name when we have it; only fall
        // back to a generic label if the provider gave us nothing. The name is
        // NOT the email, so showing it keeps the account confidential.
        $label = trim((string) $name);
        if ($label === '') {
            $label = $provider === 'facebook' ? 'Facebook User' : 'Google User';
        }
        // Non-identifying placeholder email; not a real, reachable address.
        $email = $provider . '_' . bin2hex(random_bytes(10)) . '@social.guidemate.local';
        // Random, unusable password hash (social users never log in by password).
        $password = password_hash(bin2hex(random_bytes(24)), PASSWORD_BCRYPT);

        return Database::insert(
            'INSERT INTO users (name, email, password, role, guide_status, oauth_provider, oauth_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$label, $email, $password, 'tourist', 'none', $provider, $oauthId]
        );
    }

    /**
     * Backfill a social account's display name when it's still the generic
     * placeholder ("Google User" / "Facebook User"). Never overwrites a name
     * the user may have already customised.
     */
    public static function renameIfPlaceholder(int $id, string $currentName, string $newName): void
    {
        $newName = trim($newName);
        if ($newName === '') {
            return;
        }
        if (!preg_match('/^(Google|Facebook) User$/', trim($currentName))) {
            return;
        }
        Database::run('UPDATE users SET name = ? WHERE id = ?', [$newName, $id]);
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

    /**
     * Record that the user was just active (drives online/offline in chat).
     * Best-effort: silently ignored if the column has not been migrated yet.
     */
    public static function touchLastSeen(int $id): void
    {
        try {
            Database::run('UPDATE users SET last_seen_at = NOW() WHERE id = ?', [$id]);
        } catch (\Throwable $e) {
            // last_seen_at column not present yet — ignore.
        }
    }

    /**
     * A user counts as online if they pinged the API within the last 90s.
     */
    public static function isOnline(int $id, int $withinSeconds = 90): bool
    {
        $row = Database::first('SELECT last_seen_at FROM users WHERE id = ?', [$id]);
        $seen = $row['last_seen_at'] ?? null;
        if ($seen === null || $seen === '') {
            return false;
        }
        return (time() - strtotime((string) $seen)) <= $withinSeconds;
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
     * All provider accounts (guides, rental & hotel partners), optionally
     * filtered by verification status. Used by the admin applications queue.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function providers(?string $status = null): array
    {
        if ($status !== null) {
            return Database::all(
                'SELECT * FROM users
                 WHERE role IN ("guide","rental_admin","hotel_admin") AND guide_status = ?
                 ORDER BY created_at DESC',
                [$status]
            );
        }
        return Database::all(
            'SELECT * FROM users
             WHERE role IN ("guide","rental_admin","hotel_admin")
             ORDER BY FIELD(guide_status, "pending", "rejected", "approved", "none"), created_at DESC'
        );
    }

    public static function countProvidersByStatus(string $status): int
    {
        $row = Database::first(
            'SELECT COUNT(*) AS c FROM users
             WHERE role IN ("guide","rental_admin","hotel_admin") AND guide_status = ?',
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

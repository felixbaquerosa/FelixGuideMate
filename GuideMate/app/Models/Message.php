<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Message
{
    public static function send(int $senderId, int $receiverId, string $body, ?int $listingId = null): int
    {
        return Database::insert(
            'INSERT INTO messages (sender_id, receiver_id, listing_id, body) VALUES (?, ?, ?, ?)',
            [$senderId, $receiverId, $listingId, $body]
        );
    }

    /**
     * List the most recent message per conversation partner for a user.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function conversations(int $userId): array
    {
        return self::conversationList($userId, archived: false);
    }

    /**
     * Conversations the user has archived.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function archivedConversations(int $userId): array
    {
        return self::conversationList($userId, archived: true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function conversationList(int $userId, bool $archived): array
    {
        $archivedClause = $archived
            ? 'cs.is_archived = 1'
            : '(cs.is_archived IS NULL OR cs.is_archived = 0)';

        return Database::all(
            'SELECT u.id AS partner_id, u.name AS partner_name, u.avatar AS partner_avatar,
                    m.body AS last_body, m.created_at AS last_at,
                    SUM(CASE WHEN m.receiver_id = :uid AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread,
                    COALESCE(cs.is_pinned, 0) AS is_pinned
             FROM messages m
             JOIN users u ON u.id = IF(m.sender_id = :uid2, m.receiver_id, m.sender_id)
             LEFT JOIN conversation_settings cs ON cs.user_id = :uid5 AND cs.partner_id = u.id
             WHERE (m.sender_id = :uid3 OR m.receiver_id = :uid4)
               AND ' . $archivedClause . '
             GROUP BY u.id, u.name, u.avatar, cs.is_pinned
             ORDER BY COALESCE(cs.is_pinned, 0) DESC, MAX(m.created_at) DESC',
            [
                'uid' => $userId,
                'uid2' => $userId,
                'uid3' => $userId,
                'uid4' => $userId,
                'uid5' => $userId,
            ]
        );
    }

    /**
     * Full thread between two users.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function thread(int $userId, int $partnerId): array
    {
        return Database::all(
            'SELECT * FROM messages
             WHERE (sender_id = :a AND receiver_id = :b) OR (sender_id = :b2 AND receiver_id = :a2)
             ORDER BY created_at ASC',
            ['a' => $userId, 'b' => $partnerId, 'a2' => $userId, 'b2' => $partnerId]
        );
    }

    /**
     * Messages newer than the given ID in a thread.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function since(int $userId, int $partnerId, int $sinceId): array
    {
        return Database::all(
            'SELECT * FROM messages
             WHERE id > ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
             ORDER BY created_at ASC',
            [$sinceId, $userId, $partnerId, $partnerId, $userId]
        );
    }

    public static function markRead(int $userId, int $partnerId): void
    {
        Database::run(
            'UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?',
            [$userId, $partnerId]
        );
    }

    public static function unreadCount(int $userId): int
    {
        $row = Database::first('SELECT COUNT(*) AS c FROM messages WHERE receiver_id = ? AND is_read = 0', [$userId]);
        return (int) ($row['c'] ?? 0);
    }

    public static function deleteBetween(int $userId, int $partnerId): void
    {
        Database::run(
            'DELETE FROM messages
             WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)',
            [$userId, $partnerId, $partnerId, $userId]
        );
    }
}

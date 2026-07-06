<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Geo;

final class Booking
{
    public static function create(int $listingId, int $userId, string $date, int $guests, float $total, string $notes): int
    {
        $token = bin2hex(random_bytes(16));
        $id = Database::insert(
            'INSERT INTO bookings (listing_id, user_id, booking_date, guests, total_amount, notes, verify_token)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$listingId, $userId, $date, $guests, $total, $notes, $token]
        );
        return $id;
    }

    public static function ensureVerifyToken(int $id): string
    {
        $row = Database::first('SELECT verify_token FROM bookings WHERE id = ?', [$id]);
        if ($row !== null && !empty($row['verify_token'])) {
            return (string) $row['verify_token'];
        }
        $token = bin2hex(random_bytes(16));
        Database::run('UPDATE bookings SET verify_token = ? WHERE id = ?', [$token, $id]);
        return $token;
    }

    public static function verifyByToken(string $token, int $guideId): bool
    {
        $row = Database::first(
            'SELECT b.id FROM bookings b
             JOIN listings l ON l.id = b.listing_id
             WHERE b.verify_token = ? AND l.user_id = ? AND b.status IN ("confirmed","completed")',
            [$token, $guideId]
        );
        if ($row === null) {
            return false;
        }
        Database::run('UPDATE bookings SET verified_at = NOW() WHERE id = ?', [(int) $row['id']]);
        return true;
    }

    public static function applyPromo(int $id, int $promoId, float $discount): void
    {
        $booking = self::find($id);
        if ($booking === null) {
            return;
        }
        $newTotal = max(0, (float) $booking['total_amount'] - $discount);
        Database::run(
            'UPDATE bookings SET promo_code_id = ?, discount_amount = ?, total_amount = ? WHERE id = ?',
            [$promoId, $discount, $newTotal, $id]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first(
            'SELECT b.*, l.title AS listing_title, l.slug AS listing_slug, l.cover_image, l.user_id AS guide_id,
                    l.price_unit, u.name AS customer_name, u.email AS customer_email
             FROM bookings b
             JOIN listings l ON l.id = b.listing_id
             JOIN users u ON u.id = b.user_id
             WHERE b.id = ?',
            [$id]
        );
    }

    /**
     * Bookings made by a tourist.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forCustomer(int $userId): array
    {
        return Database::all(
            'SELECT b.*, l.title AS listing_title, l.slug AS listing_slug, l.cover_image, l.area,
                    p.status AS payment_status,
                    d.id AS dispute_id, d.status AS dispute_status
             FROM bookings b
             JOIN listings l ON l.id = b.listing_id
             LEFT JOIN payments p ON p.booking_id = b.id
             LEFT JOIN disputes d ON d.booking_id = b.id
             WHERE b.user_id = ? ORDER BY b.created_at DESC',
            [$userId]
        );
    }

    /**
     * Paid bookings with map coordinates for tourist navigation.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function navigableForCustomer(int $userId): array
    {
        $rows = Database::all(
            'SELECT b.id, b.booking_date, b.status, b.guests, b.listing_id,
                    l.title AS listing_title, l.slug AS listing_slug, l.area, l.address,
                    l.latitude, l.longitude
             FROM bookings b
             JOIN listings l ON l.id = b.listing_id
             JOIN payments p ON p.booking_id = b.id AND p.status = "paid"
             WHERE b.user_id = ?
               AND b.status IN ("confirmed", "completed")
             ORDER BY b.booking_date ASC, b.id ASC',
            [$userId]
        );

        $out = [];
        foreach ($rows as $row) {
            $coords = Geo::forListing($row);
            if ($coords === null) {
                continue;
            }
            $row['latitude'] = $coords['latitude'];
            $row['longitude'] = $coords['longitude'];
            $row['approximate'] = $coords['approximate'];
            $out[] = $row;
        }
        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function navigableForCustomerById(int $userId, int $bookingId): ?array
    {
        $row = Database::first(
            'SELECT b.id, b.booking_date, b.status, b.guests, b.listing_id,
                    l.title AS listing_title, l.slug AS listing_slug, l.area, l.address,
                    l.latitude, l.longitude
             FROM bookings b
             JOIN listings l ON l.id = b.listing_id
             JOIN payments p ON p.booking_id = b.id AND p.status = "paid"
             WHERE b.user_id = ? AND b.id = ?
               AND b.status IN ("confirmed", "completed")',
            [$userId, $bookingId]
        );
        if ($row === null) {
            return null;
        }
        $coords = Geo::forListing($row);
        if ($coords === null) {
            return null;
        }
        $row['latitude'] = $coords['latitude'];
        $row['longitude'] = $coords['longitude'];
        $row['approximate'] = $coords['approximate'];
        return $row;
    }

    /**
     * Paid, confirmed bookings with map pins for a browse category (things-to-do, hotels, etc.).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function mapListingsForCustomer(int $userId, string $categorySlug): array
    {
        $rows = Database::all(
            'SELECT l.id, l.title, l.slug, l.area, l.address, l.latitude, l.longitude, l.price
             FROM bookings b
             JOIN listings l ON l.id = b.listing_id
             JOIN categories c ON c.id = l.category_id
             JOIN payments p ON p.booking_id = b.id AND p.status = "paid"
             WHERE b.user_id = ? AND c.slug = ?
               AND b.status IN ("confirmed", "completed")
             ORDER BY b.booking_date ASC, b.id ASC',
            [$userId, $categorySlug]
        );

        $out = [];
        foreach ($rows as $row) {
            $coords = Geo::forListing($row);
            if ($coords === null) {
                continue;
            }
            $row['latitude'] = $coords['latitude'];
            $row['longitude'] = $coords['longitude'];
            $out[] = $row;
        }
        return $out;
    }

    public static function hasActivePaidBooking(int $listingId, int $userId): bool
    {
        return Database::first(
            'SELECT b.id FROM bookings b
             JOIN payments p ON p.booking_id = b.id AND p.status = "paid"
             WHERE b.listing_id = ? AND b.user_id = ?
               AND b.status IN ("confirmed", "completed")',
            [$listingId, $userId]
        ) !== null;
    }

    /**
     * Bookings received by a guide (across all their listings).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forGuide(int $guideId): array
    {
        return Database::all(
            'SELECT b.*, l.title AS listing_title, l.slug AS listing_slug,
                    u.name AS customer_name, p.status AS payment_status
             FROM bookings b
             JOIN listings l ON l.id = b.listing_id
             JOIN users u ON u.id = b.user_id
             LEFT JOIN payments p ON p.booking_id = b.id
             WHERE l.user_id = ? ORDER BY b.created_at DESC',
            [$guideId]
        );
    }

    public static function updateStatus(int $id, string $status): void
    {
        Database::run('UPDATE bookings SET status = ? WHERE id = ?', [$status, $id]);
    }

    /**
     * Guide cancels a booking. If the tourist already paid, the payment is
     * refunded automatically and the booking is marked refunded.
     *
     * @return array{refunded: bool, amount: float}
     */
    public static function cancelByGuide(int $id): array
    {
        $booking = self::find($id);
        if ($booking !== null && User::isGuideWarned((int) $booking['guide_id'])) {
            return ['refunded' => false, 'amount' => 0.0, 'blocked' => true];
        }

        $payment = Payment::forBooking($id);
        $wasPaid = $payment !== null && $payment['status'] === 'paid';
        $amount = (float) ($payment['amount'] ?? 0);

        if ($wasPaid) {
            Payment::refund($id);
            self::updateStatus($id, 'refunded');
        } else {
            self::updateStatus($id, 'cancelled');
        }

        return ['refunded' => $wasPaid, 'amount' => $amount];
    }

    public static function isPaid(int $id): bool
    {
        return Payment::isPaid($id);
    }

    /**
     * Fix bookings cancelled before auto-refund existed: still marked paid
     * while the booking is cancelled. Refund the tourist and set status refunded.
     */
    public static function reconcileOrphanRefunds(): void
    {
        $rows = Database::all(
            'SELECT b.id FROM bookings b
             INNER JOIN payments p ON p.booking_id = b.id
             WHERE b.status = "cancelled" AND p.status = "paid"'
        );
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            Payment::refund($id);
            self::updateStatus($id, 'refunded');
        }
    }

    /**
     * Is this listing already taken on the given date? A date counts as booked
     * once a booking for it is confirmed or completed (i.e. paid). Cancelled and
     * still-unpaid (pending) bookings do not hold the slot.
     */
    public static function isDateBooked(int $listingId, string $date, ?int $excludeBookingId = null): bool
    {
        if (GuideAvailability::isBlocked($listingId, $date)) {
            return true;
        }
        $sql = 'SELECT id FROM bookings
                WHERE listing_id = ? AND booking_date = ? AND status IN ("confirmed","completed")';
        $params = [$listingId, $date];
        if ($excludeBookingId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeBookingId;
        }
        return Database::first($sql, $params) !== null;
    }

    /**
     * Upcoming dates already taken (confirmed/completed) for a listing, so the
     * booking form can stop tourists from picking them.
     *
     * @return array<int, string>
     */
    public static function bookedDates(int $listingId): array
    {
        $booked = array_map(static fn($r) => (string) $r['booking_date'], Database::all(
            'SELECT booking_date FROM bookings
             WHERE listing_id = ? AND status IN ("confirmed","completed") AND booking_date >= CURDATE()
             ORDER BY booking_date',
            [$listingId]
        ));
        return array_values(array_unique(array_merge($booked, GuideAvailability::blockedDates($listingId))));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function dueReminders(): array
    {
        return Database::all(
            'SELECT b.*, l.title AS listing_title, u.email AS customer_email, g.email AS guide_email
             FROM bookings b
             JOIN listings l ON l.id = b.listing_id
             JOIN users u ON u.id = b.user_id
             JOIN users g ON g.id = l.user_id
             WHERE b.status = "confirmed" AND b.booking_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND b.reminder_sent = 0'
        );
    }

    public static function markReminderSent(int $id): void
    {
        Database::run('UPDATE bookings SET reminder_sent = 1 WHERE id = ?', [$id]);
    }

    public static function countCompletedForGuide(int $guideId): int
    {
        $row = Database::first(
            'SELECT COUNT(*) AS c FROM bookings b JOIN listings l ON l.id = b.listing_id WHERE l.user_id = ? AND b.status = "completed"',
            [$guideId]
        );
        return (int) ($row['c'] ?? 0);
    }

    public static function hasCompletedBooking(int $listingId, int $userId): bool
    {
        return Database::first(
            'SELECT id FROM bookings WHERE listing_id = ? AND user_id = ? AND status IN ("completed","confirmed")',
            [$listingId, $userId]
        ) !== null;
    }

    public static function countForGuide(int $guideId): int
    {
        $row = Database::first(
            'SELECT COUNT(*) AS c FROM bookings b JOIN listings l ON l.id = b.listing_id WHERE l.user_id = ?',
            [$guideId]
        );
        return (int) ($row['c'] ?? 0);
    }

    public static function revenueForGuide(int $guideId): float
    {
        $row = Database::first(
            'SELECT COALESCE(SUM(b.total_amount),0) AS total
             FROM bookings b
             JOIN listings l ON l.id = b.listing_id
             JOIN payments p ON p.booking_id = b.id
             WHERE l.user_id = ? AND p.status = "paid" AND b.status IN ("confirmed", "completed")',
            [$guideId]
        );
        return (float) ($row['total'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function bookingsPerMonth(int $months = 12): array
    {
        return Database::all(
            'SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, COUNT(*) AS total
             FROM bookings
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
            'SELECT b.id, l.title, u.name AS customer, b.booking_date, b.guests, b.total_amount, b.status, b.created_at
             FROM bookings b
             JOIN listings l ON l.id = b.listing_id
             JOIN users u ON u.id = b.user_id
             ORDER BY b.created_at DESC'
        ) as $b) {
            $rows[] = [
                $b['id'],
                $b['title'],
                $b['customer'],
                $b['booking_date'],
                $b['guests'],
                $b['total_amount'],
                $b['status'],
                $b['created_at'],
            ];
        }
        return $rows;
    }

    public static function guideRatingStats(int $guideId): array
    {
        $row = Database::first(
            'SELECT AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
             FROM reviews r JOIN listings l ON l.id = r.listing_id WHERE l.user_id = ?',
            [$guideId]
        );
        $completed = self::countCompletedForGuide($guideId);
        $bookings = self::countForGuide($guideId);
        return [
            'avg_rating' => round((float) ($row['avg_rating'] ?? 0), 1),
            'review_count' => (int) ($row['review_count'] ?? 0),
            'completed' => $completed,
            'conversion' => $bookings > 0 ? round($completed / $bookings * 100, 1) : 0.0,
        ];
    }
}

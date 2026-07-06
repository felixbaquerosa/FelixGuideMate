<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PromoCode
{
    /**
     * @return array<string, mixed>|null
     */
    public static function findByCode(string $code): ?array
    {
        return Database::first(
            'SELECT * FROM promo_codes WHERE code = ? AND is_active = 1',
            [strtoupper(trim($code))]
        );
    }

    /**
     * @return array{valid: bool, discount: float, id: ?int, message: string}
     */
    public static function validate(string $code, float $subtotal): array
    {
        $row = self::findByCode($code);
        if ($row === null) {
            return ['valid' => false, 'discount' => 0.0, 'id' => null, 'message' => 'Invalid promo code.'];
        }
        $today = date('Y-m-d');
        if (!empty($row['valid_from']) && $today < $row['valid_from']) {
            return ['valid' => false, 'discount' => 0.0, 'id' => null, 'message' => 'This promo code is not active yet.'];
        }
        if (!empty($row['valid_until']) && $today > $row['valid_until']) {
            return ['valid' => false, 'discount' => 0.0, 'id' => null, 'message' => 'This promo code has expired.'];
        }
        if ($row['max_uses'] !== null && (int) $row['uses_count'] >= (int) $row['max_uses']) {
            return ['valid' => false, 'discount' => 0.0, 'id' => null, 'message' => 'This promo code has reached its usage limit.'];
        }

        $discount = 0.0;
        if ($row['discount_percent'] !== null) {
            $discount = round($subtotal * ((float) $row['discount_percent'] / 100), 2);
        } elseif ($row['discount_amount'] !== null) {
            $discount = min($subtotal, (float) $row['discount_amount']);
        }

        return ['valid' => true, 'discount' => $discount, 'id' => (int) $row['id'], 'message' => 'Promo applied.'];
    }

    public static function incrementUse(int $id): void
    {
        Database::run('UPDATE promo_codes SET uses_count = uses_count + 1 WHERE id = ?', [$id]);
    }
}

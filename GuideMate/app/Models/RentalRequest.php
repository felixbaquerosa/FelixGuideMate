<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class RentalRequest
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return Database::all(
            'SELECT r.*, u.name AS user_name, u.email AS user_email
             FROM rental_requests r
             JOIN users u ON u.id = r.user_id
             ORDER BY r.created_at DESC'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM rental_requests WHERE id = ?', [$id]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(int $userId, array $data): int
    {
        $days = max(1, (int) ($data['rental_days'] ?? 1));
        $pricePerDay = (float) ($data['price_per_day'] ?? 0);
        $total = round($pricePerDay * $days, 2);

        return Database::insert(
            'INSERT INTO rental_requests (
                user_id, vehicle_id, vehicle_name, vehicle_type, shop_name, location,
                pickup_date, rental_days, total_amount, customer_name, customer_email,
                customer_phone, notes, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                (string) ($data['vehicle_id'] ?? ''),
                (string) ($data['vehicle_name'] ?? ''),
                (string) ($data['vehicle_type'] ?? ''),
                (string) ($data['shop_name'] ?? ''),
                (string) ($data['location'] ?? ''),
                (string) ($data['pickup_date'] ?? ''),
                $days,
                $total,
                (string) ($data['customer_name'] ?? ''),
                (string) ($data['customer_email'] ?? ''),
                (string) ($data['customer_phone'] ?? ''),
                (string) ($data['notes'] ?? ''),
                'pending',
            ]
        );
    }

    public static function updateStatus(int $id, string $status, ?string $adminNote = null): void
    {
        $contacted = in_array($status, ['contacted', 'approved', 'completed'], true)
            ? date('Y-m-d H:i:s')
            : null;

        Database::run(
            'UPDATE rental_requests
             SET status = ?, admin_note = COALESCE(?, admin_note), contacted_at = COALESCE(?, contacted_at)
             WHERE id = ?',
            [$status, $adminNote, $contacted, $id]
        );
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'contacted' => 'Contacted',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            default => ucfirst($status),
        };
    }

    /**
     * Catalog of rentable vehicles shown in the mobile app.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function vehicleCatalog(): array
    {
        return [
            [
                'id' => 'scooter',
                'name' => 'Honda Click 125i',
                'type' => 'Scooter',
                'price_per_day' => 500,
                'specs' => ['Automatic', '2 seats', 'Helmet included'],
                'shop' => 'Cebu Moto Rentals',
                'location' => 'Cebu City · Fuente Osmeña',
                'rating' => 4.8,
                'image' => 'scooter.jpg',
            ],
            [
                'id' => 'motorcycle',
                'name' => 'KTM 390 Adventure',
                'type' => 'Motorcycle',
                'price_per_day' => 1200,
                'specs' => ['Manual', '2 seats', 'Off-road ready'],
                'shop' => 'ADV Riders Cebu',
                'location' => 'Mandaue City · A.S. Fortuna',
                'rating' => 4.7,
                'image' => 'motorcycle.png',
            ],
            [
                'id' => 'ebike',
                'name' => 'City E-Bike',
                'type' => 'E-Bike',
                'price_per_day' => 350,
                'specs' => ['Electric', 'Pedal-assist', '60km range'],
                'shop' => 'GreenRide Mactan',
                'location' => 'Lapu-Lapu · Mactan Island',
                'rating' => 4.9,
                'image' => 'ebike.jpg',
            ],
            [
                'id' => 'sedan',
                'name' => 'Toyota Vios',
                'type' => 'Sedan',
                'price_per_day' => 1800,
                'specs' => ['5 seats', 'Aircon', 'Self-drive'],
                'shop' => 'Cebu Car Hub',
                'location' => 'Cebu City · IT Park',
                'rating' => 4.6,
                'image' => 'sedan.jpg',
            ],
            [
                'id' => 'suv',
                'name' => 'Kia Sportage',
                'type' => 'SUV',
                'price_per_day' => 2800,
                'specs' => ['5 seats', '4x4', 'Self-drive'],
                'shop' => 'Island SUV Rentals',
                'location' => 'Cebu City · SRP',
                'rating' => 4.7,
                'image' => 'suv.jpg',
            ],
            [
                'id' => 'van',
                'name' => 'Toyota HiAce Grandia',
                'type' => 'Van',
                'price_per_day' => 4500,
                'specs' => ['12 seats', 'With driver', 'Tours'],
                'shop' => 'Cebu Van Tours',
                'location' => 'Lapu-Lapu · near Airport',
                'rating' => 4.9,
                'image' => 'van.jpg',
            ],
        ];
    }
}

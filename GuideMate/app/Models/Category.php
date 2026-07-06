<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Category
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return Database::all('SELECT * FROM categories ORDER BY sort_order, name');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findBySlug(string $slug): ?array
    {
        return Database::first('SELECT * FROM categories WHERE slug = ?', [$slug]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM categories WHERE id = ?', [$id]);
    }

    /**
     * Categories with a count of approved listings in each.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function withCounts(): array
    {
        return Database::all(
            'SELECT c.*, COUNT(l.id) AS listing_count
             FROM categories c
             LEFT JOIN listings l ON l.category_id = c.id AND l.status = "approved"
             GROUP BY c.id
             ORDER BY c.sort_order, c.name'
        );
    }
}

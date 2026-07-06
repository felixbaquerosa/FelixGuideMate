<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Booking;
use App\Models\Favorite;
use App\Models\Listing;
use App\Models\Message;
use App\Models\User;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $role = $user['role'] ?? 'tourist';

        // Admins have their own separate portal (/admin) and never reach here
        // through the public user session.
        match ($role) {
            'guide' => $this->guide($user),
            default => $this->tourist($user),
        };
    }

    /**
     * @param array<string, mixed> $user
     */
    private function tourist(array $user): void
    {
        $bookings = Booking::forCustomer((int) $user['id']);
        $this->view('dashboard/tourist', [
            'title' => 'Dashboard',
            'user' => $user,
            'bookings' => array_slice($bookings, 0, 5),
            'bookingCount' => count($bookings),
            'favorites' => Favorite::forUser((int) $user['id']),
            'unread' => Message::unreadCount((int) $user['id']),
        ]);
    }

    /**
     * @param array<string, mixed> $user
     */
    private function guide(array $user): void
    {
        Booking::reconcileOrphanRefunds();
        Auth::refreshUser();
        $user = Auth::user() ?? $user;
        $listings = Listing::forOwner((int) $user['id']);
        $this->view('dashboard/guide', [
            'title' => 'Guide Dashboard',
            'user' => $user,
            'isWarned' => User::isGuideWarned((int) $user['id']),
            'listings' => $listings,
            'recentBookings' => array_slice(Booking::forGuide((int) $user['id']), 0, 6),
            'bookingCount' => Booking::countForGuide((int) $user['id']),
            'revenue' => Booking::revenueForGuide((int) $user['id']),
            'guideStats' => Booking::guideRatingStats((int) $user['id']),
            'guideBadge' => guide_badge((int) $user['id']),
            'unread' => Message::unreadCount((int) $user['id']),
        ]);
    }
}

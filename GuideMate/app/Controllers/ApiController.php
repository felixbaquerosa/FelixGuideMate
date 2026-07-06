<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ApiAuth;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Feedback;
use App\Models\Listing;
use App\Core\App;
use App\Models\PasswordReset;
use App\Models\Payment;
use App\Models\RentalRequest;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\RecommendationService;

final class ApiController extends Controller
{
    public function __construct()
    {
        $this->sendCors();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    public function home(): void
    {
        $userId = ApiAuth::id();
        $this->json([
            'categories' => array_map([$this, 'categoryPayload'], Category::withCounts()),
            'featured' => array_map([$this, 'listingPayload'], Listing::featured(8)),
            'recommended' => array_map(
                [$this, 'listingPayload'],
                RecommendationService::forUser($userId, 8)
            ),
            'areas' => [
                ['slug' => 'cebu-city', 'name' => 'Cebu City', 'tagline' => 'Heritage & food tours'],
                ['slug' => 'mactan', 'name' => 'Mactan', 'tagline' => 'Island hopping & resorts'],
                ['slug' => 'oslob', 'name' => 'Oslob', 'tagline' => 'Whale sharks & waterfalls'],
                ['slug' => 'moalboal', 'name' => 'Moalboal', 'tagline' => 'Diving & sardine runs'],
                ['slug' => 'bantayan', 'name' => 'Bantayan', 'tagline' => 'White-sand beaches'],
            ],
        ]);
    }

    public function categories(): void
    {
        $this->json([
            'categories' => array_map([$this, 'categoryPayload'], Category::withCounts()),
        ]);
    }

    public function listings(): void
    {
        $filters = [
            'q' => (string) $this->input('q', ''),
            'category' => (string) $this->input('category', ''),
            'area' => (string) $this->input('area', ''),
            'sort' => (string) $this->input('sort', ''),
            'min_rating' => (string) $this->input('min_rating', ''),
            'featured' => (string) $this->input('featured', ''),
        ];
        $listings = Listing::search($filters);
        $this->json([
            'listings' => array_map([$this, 'listingPayload'], $listings),
            'filters' => $filters,
        ]);
    }

    public function listing(string $slug): void
    {
        $listing = Listing::findBySlug($slug);
        if ($listing === null || $listing['status'] !== 'approved') {
            $this->json(['error' => 'Listing not found.'], 404);
            return;
        }

        $listingId = (int) $listing['id'];
        $reviews = Review::forListing($listingId);
        foreach ($reviews as &$review) {
            $review['images'] = ReviewImage::forReview((int) $review['id']);
        }
        unset($review);

        $gallery = Listing::gallery($listingId);
        $userId = ApiAuth::id();
        $favorited = $userId !== null && Favorite::exists($userId, $listingId);

        $this->json([
            'listing' => $this->listingPayload($listing, true),
            'gallery' => array_map(
                static fn (array $img): array => [
                    'id' => (int) $img['id'],
                    'url' => api_img_src((string) ($img['image_path'] ?? ''), 'listing' . $listingId),
                ],
                $gallery
            ),
            'reviews' => array_map(static fn (array $r): array => [
                'id' => (int) $r['id'],
                'rating' => (int) $r['rating'],
                'comment' => (string) ($r['comment'] ?? ''),
                'user_name' => (string) ($r['user_name'] ?? 'Traveler'),
                'created_at' => (string) ($r['created_at'] ?? ''),
            ], $reviews),
            'summary' => Review::summary($listingId),
            'favorited' => $favorited,
        ]);
    }

    public function login(): void
    {
        $body = $this->jsonBody();
        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->json(['error' => 'Email and password are required.'], 422);
            return;
        }

        $existing = User::findByEmail($email);
        if ($existing !== null && $existing['role'] === 'admin') {
            $this->json(['error' => 'Administrators must use the web admin portal.'], 403);
            return;
        }
        if ($existing !== null && (int) $existing['is_active'] === 0) {
            $this->json(['error' => 'This account has been suspended.'], 403);
            return;
        }

        if (!Auth::attempt($email, $password)) {
            $this->json(['error' => 'Invalid email or password.'], 401);
            return;
        }

        $user = Auth::user();
        if ($user === null) {
            $this->json(['error' => 'Login failed.'], 500);
            return;
        }

        $token = ApiAuth::issue((int) $user['id']);
        Auth::logout();

        $this->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function register(): void
    {
        $body = $this->jsonBody();
        $name = trim((string) ($body['name'] ?? ''));
        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            $this->json(['error' => 'Name, email, and password are required.'], 422);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['error' => 'Please enter a valid email address.'], 422);
            return;
        }
        if (User::emailExists($email)) {
            $this->json(['error' => 'That email is already registered.'], 422);
            return;
        }

        $userId = User::create($name, $email, $password, 'tourist');

        $user = User::find($userId);
        if ($user === null) {
            $this->json(['error' => 'Registration failed.'], 500);
            return;
        }

        $token = ApiAuth::issue($userId);
        $this->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function forgotPassword(): void
    {
        $body = $this->jsonBody();
        $email = strtolower(trim((string) ($body['email'] ?? '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['error' => 'Please enter a valid email address.'], 422);
            return;
        }

        // Build the response the same way whether or not the email exists, so we
        // never reveal which addresses are registered.
        $response = [
            'message' => 'If that email is registered, we sent a password reset link. '
                . 'Please check your inbox (and your spam folder).',
        ];

        $user = User::findByEmail($email);
        if ($user !== null && ($user['role'] ?? '') !== 'admin') {
            $token = PasswordReset::create($email);
            // Build the link from the host the phone actually connected to (e.g. the
            // LAN IP), so the link is reachable from the device rather than localhost.
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $link = rtrim($scheme . '://' . $host . App::basePath(), '/') . '/reset-password/' . $token;
            NotificationService::passwordReset($email, $token, $link);

            $mailConfigured = trim((string) (App::config('mail')['from'] ?? '')) !== '';
            $debug = (bool) (App::config('app')['debug'] ?? false);
            // Dev fallback: if no SMTP is configured yet, hand the link back so the
            // reset flow is testable end-to-end without a mail server.
            if ($debug && !$mailConfigured) {
                $response['dev_reset_url'] = $link;
            }
        }

        $this->json($response);
    }

    public function me(): void
    {
        $user = ApiAuth::user();
        if ($user === null) {
            $this->json(['error' => 'Unauthorized.'], 401);
            return;
        }
        $this->json(['user' => $this->userPayload($user)]);
    }

    public function logout(): void
    {
        ApiAuth::revoke();
        $this->json(['success' => true]);
    }

    public function changePassword(): void
    {
        $user = ApiAuth::user();
        if ($user === null) {
            $this->json(['error' => 'Unauthorized.'], 401);
            return;
        }

        $body = $this->jsonBody();
        $current = (string) ($body['current_password'] ?? '');
        $new = (string) ($body['new_password'] ?? '');

        if ($current === '' || $new === '') {
            $this->json(['error' => 'Current and new password are required.'], 422);
            return;
        }
        if (!password_verify($current, (string) $user['password'])) {
            $this->json(['error' => 'Your current password is incorrect.'], 401);
            return;
        }
        if (strlen($new) < 8) {
            $this->json(['error' => 'New password must be at least 8 characters.'], 422);
            return;
        }

        User::updatePassword((int) $user['id'], $new);
        $this->json(['success' => true]);
    }

    public function deleteAccount(): void
    {
        $user = ApiAuth::user();
        if ($user === null) {
            $this->json(['error' => 'Unauthorized.'], 401);
            return;
        }

        $body = $this->jsonBody();
        $password = (string) ($body['password'] ?? '');

        if ($password === '') {
            $this->json(['error' => 'Please enter your password to confirm.'], 422);
            return;
        }
        if (!password_verify($password, (string) $user['password'])) {
            $this->json(['error' => 'Incorrect password.'], 401);
            return;
        }

        ApiAuth::revoke();
        User::delete((int) $user['id']);
        $this->json(['success' => true]);
    }

    public function bookings(): void
    {
        $userId = ApiAuth::id();
        if ($userId === null) {
            $this->json(['error' => 'Unauthorized.'], 401);
            return;
        }

        Booking::reconcileOrphanRefunds();
        $bookings = Booking::forCustomer($userId);
        $this->json([
            'bookings' => array_map([$this, 'bookingPayload'], $bookings),
        ]);
    }

    public function tripMap(): void
    {
        $userId = ApiAuth::id();
        if ($userId === null) {
            $this->json(['error' => 'Unauthorized.'], 401);
            return;
        }

        // Backfill any missing listing coordinates, then load only the bookings
        // that are paid + confirmed/completed (the map is gated behind a booking).
        Listing::syncCoordinatesForBookings($userId);
        $rows = Booking::navigableForCustomer($userId);

        $today = date('Y-m-d');
        $pins = [];
        foreach ($rows as $b) {
            $address = trim((string) ($b['address'] ?? ''));
            $area = (string) ($b['area'] ?? '');
            $title = (string) ($b['listing_title'] ?? '');
            $googleQuery = $title . ($address !== '' ? ', ' . $address : ', ' . ($area !== '' ? $area : 'Cebu') . ', Philippines');
            $date = (string) ($b['booking_date'] ?? '');

            $pins[] = [
                'id' => (int) $b['id'],
                'title' => $title,
                'area' => $area,
                'address' => $address,
                'googleQuery' => $googleQuery,
                'date' => $date,
                'dateLabel' => $date !== '' ? date('M j, Y', strtotime($date)) : '',
                'lat' => (float) $b['latitude'],
                'lng' => (float) $b['longitude'],
                'upcoming' => $date !== '' && $date >= $today,
                'approximate' => !empty($b['approximate']),
            ];
        }

        $maps = App::config('maps') ?? [];
        $this->json([
            'bookings' => $pins,
            'mapbox_token' => trim((string) ($maps['mapbox_access_token'] ?? '')),
            'mapillary_token' => trim((string) ($maps['mapillary_access_token'] ?? '')),
        ]);
    }

    public function createBooking(): void
    {
        $userId = ApiAuth::id();
        if ($userId === null) {
            $this->json(['error' => 'Unauthorized.'], 401);
            return;
        }

        $body = $this->jsonBody();
        $listingId = (int) ($body['listing_id'] ?? 0);
        $date = trim((string) ($body['booking_date'] ?? ''));
        $guests = max(1, (int) ($body['guests'] ?? 1));
        $notes = trim((string) ($body['notes'] ?? ''));

        // Payment details coming from the mobile checkout (GCash / InstaPay / card).
        $allowedMethods = ['gcash', 'instapay', 'card'];
        $method = strtolower(trim((string) ($body['payment_method'] ?? 'card')));
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'card';
        }
        $paymentReference = trim((string) ($body['payment_reference'] ?? ''));
        $cardLast4 = preg_replace('/\D/', '', (string) ($body['card_last4'] ?? '')) ?? '';

        $listing = Listing::find($listingId);
        if ($listing === null || $listing['status'] !== 'approved') {
            $this->json(['error' => 'Listing not available.'], 404);
            return;
        }
        if ($date === '') {
            $this->json(['error' => 'Booking date is required.'], 422);
            return;
        }
        if (Booking::isDateBooked($listingId, $date)) {
            $this->json(['error' => 'That date is already booked.'], 422);
            return;
        }

        $price = Listing::effectivePrice($listing);
        $total = $price * $guests;

        // Keep a human-readable note of how the customer paid so it shows in admin.
        $methodLabel = match ($method) {
            'gcash' => 'GCash / e-Wallet QR',
            'instapay' => 'InstaPay QR',
            default => 'Debit/Credit Card',
        };
        $paymentNote = 'Paid via ' . $methodLabel;
        if ($cardLast4 !== '') {
            $paymentNote .= ' ****' . substr($cardLast4, -4);
        }
        if ($paymentReference !== '') {
            $paymentNote .= ' · Ref: ' . $paymentReference;
        }
        $fullNotes = trim($notes === '' ? $paymentNote : ($notes . "\n" . $paymentNote));

        $bookingId = Booking::create($listingId, $userId, $date, $guests, $total, $fullNotes);
        Payment::create($bookingId, $total, $method, 'paid');
        Booking::updateStatus($bookingId, 'confirmed');

        // Notify the admin/guide so they can process the booking immediately.
        $this->notifyAdminsOfBooking($listing, $userId, $date, $guests, $total, $methodLabel);

        $booking = Booking::find($bookingId);
        $this->json([
            'booking' => $booking !== null ? $this->bookingPayload($booking) : null,
        ], 201);
    }

    public function favorites(): void
    {
        $userId = ApiAuth::id();
        if ($userId === null) {
            $this->json(['error' => 'Unauthorized.'], 401);
            return;
        }

        $listings = Favorite::forUser($userId);
        $this->json([
            'listings' => array_map([$this, 'listingPayload'], $listings),
        ]);
    }

    public function toggleFavorite(): void
    {
        $userId = ApiAuth::id();
        if ($userId === null) {
            $this->json(['error' => 'Unauthorized.'], 401);
            return;
        }

        $body = $this->jsonBody();
        $listingId = (int) ($body['listing_id'] ?? 0);
        if ($listingId <= 0) {
            $this->json(['error' => 'listing_id is required.'], 422);
            return;
        }

        $favorited = Favorite::toggle($userId, $listingId);
        $this->json(['favorited' => $favorited]);
    }

    public function rentalUnits(): void
    {
        $this->json([
            'vehicles' => array_map(
                static fn (array $v): array => [
                    'id' => (string) $v['id'],
                    'name' => (string) $v['name'],
                    'type' => (string) $v['type'],
                    'price_per_day' => (float) $v['price_per_day'],
                    'specs' => $v['specs'],
                    'shop' => (string) $v['shop'],
                    'location' => (string) $v['location'],
                    'rating' => (float) $v['rating'],
                    'image' => api_img_src('uploads/rentals/' . $v['image'], 'rental-' . $v['id']),
                ],
                RentalRequest::vehicleCatalog()
            ),
        ]);
    }

    public function createRental(): void
    {
        $userId = ApiAuth::id();
        if ($userId === null) {
            $this->json(['error' => 'Unauthorized.'], 401);
            return;
        }

        $user = User::find($userId);
        if ($user === null) {
            $this->json(['error' => 'User not found.'], 404);
            return;
        }

        $body = $this->jsonBody();
        $vehicleId = trim((string) ($body['vehicle_id'] ?? ''));
        $pickupDate = trim((string) ($body['pickup_date'] ?? ''));
        $phone = trim((string) ($body['customer_phone'] ?? ''));

        if ($vehicleId === '' || $pickupDate === '') {
            $this->json(['error' => 'vehicle_id and pickup_date are required.'], 422);
            return;
        }

        $vehicle = null;
        foreach (RentalRequest::vehicleCatalog() as $item) {
            if ($item['id'] === $vehicleId) {
                $vehicle = $item;
                break;
            }
        }
        if ($vehicle === null) {
            $this->json(['error' => 'Unknown vehicle.'], 404);
            return;
        }

        $id = RentalRequest::create($userId, [
            'vehicle_id' => $vehicleId,
            'vehicle_name' => (string) ($body['vehicle_name'] ?? $vehicle['name']),
            'vehicle_type' => (string) ($body['vehicle_type'] ?? $vehicle['type']),
            'shop_name' => (string) ($body['shop_name'] ?? $vehicle['shop']),
            'location' => (string) ($body['location'] ?? $vehicle['location']),
            'pickup_date' => $pickupDate,
            'rental_days' => (int) ($body['rental_days'] ?? 1),
            'price_per_day' => (float) ($body['price_per_day'] ?? $vehicle['price_per_day']),
            'customer_name' => (string) $user['name'],
            'customer_email' => (string) $user['email'],
            'customer_phone' => $phone,
            'notes' => trim((string) ($body['notes'] ?? '')),
        ]);

        $this->json(['request' => ['id' => $id, 'status' => 'pending']], 201);
    }

    /**
     * Accept app feedback from any tourist (signed in or not). When a valid
     * bearer token is present we attach the account so admins can follow up.
     */
    public function submitFeedback(): void
    {
        $body = $this->jsonBody();

        $message = trim((string) ($body['message'] ?? ''));
        if ($message === '') {
            $this->json(['error' => 'Please enter your feedback before sending.'], 422);
            return;
        }
        if (mb_strlen($message) > 2000) {
            $message = mb_substr($message, 0, 2000);
        }

        $rating = (int) ($body['rating'] ?? 0);
        if ($rating < 0 || $rating > 5) {
            $rating = 0;
        }

        $category = strtolower(trim((string) ($body['category'] ?? 'general')));
        if (!in_array($category, Feedback::CATEGORIES, true)) {
            $category = 'general';
        }

        $user = ApiAuth::user();
        $name = trim((string) ($body['name'] ?? ''));
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        if ($user !== null) {
            $name = (string) $user['name'];
            $email = (string) $user['email'];
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['error' => 'Please enter a valid email address.'], 422);
            return;
        }

        $id = Feedback::create([
            'user_id' => $user !== null ? (int) $user['id'] : null,
            'name' => $name,
            'email' => $email,
            'rating' => $rating,
            'category' => $category,
            'message' => $message,
        ]);

        $this->json([
            'feedback' => ['id' => $id],
            'message' => 'Thank you! Your feedback helps us improve GuideMate.',
        ], 201);
    }

    /**
     * List the signed-in tourist's own feedback, including its review status,
     * so they can tell whether an admin has looked at it.
     */
    public function myFeedback(): void
    {
        $user = ApiAuth::user();
        if ($user === null) {
            $this->json(['error' => 'Unauthorized.'], 401);
            return;
        }

        $items = array_map(static function (array $f): array {
            return [
                'id' => (int) $f['id'],
                'rating' => (int) $f['rating'],
                'category' => (string) $f['category'],
                'message' => (string) $f['message'],
                'status' => (string) $f['status'],
                'created_at' => (string) $f['created_at'],
            ];
        }, Feedback::forUser((int) $user['id']));

        $this->json(['feedback' => $items]);
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return $_POST;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function sendCors(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }

    /**
     * @param array<string, mixed> $category
     * @return array<string, mixed>
     */
    private function categoryPayload(array $category): array
    {
        return [
            'id' => (int) $category['id'],
            'name' => (string) $category['name'],
            'slug' => (string) $category['slug'],
            'icon' => (string) ($category['icon'] ?? ''),
            'listing_count' => (int) ($category['listing_count'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $listing
     * @return array<string, mixed>
     */
    private function listingPayload(array $listing, bool $detailed = false): array
    {
        $payload = [
            'id' => (int) $listing['id'],
            'title' => (string) $listing['title'],
            'slug' => (string) $listing['slug'],
            'summary' => (string) ($listing['summary'] ?? ''),
            'area' => (string) ($listing['area'] ?? ''),
            'price' => Listing::effectivePrice($listing),
            'price_unit' => (string) ($listing['price_unit'] ?? 'person'),
            'currency' => 'PHP',
            'rating' => round((float) ($listing['avg_rating'] ?? 0), 1),
            'review_count' => (int) ($listing['review_count'] ?? 0),
            'category' => (string) ($listing['category_name'] ?? ''),
            'category_slug' => (string) ($listing['category_slug'] ?? ''),
            'image' => api_img_src((string) ($listing['cover_image'] ?? ''), 'listing' . $listing['id']),
            'featured' => (bool) ($listing['is_featured'] ?? false),
            'duration' => (string) ($listing['duration'] ?? ''),
            'owner_name' => (string) ($listing['owner_name'] ?? ''),
        ];

        if ($detailed) {
            $payload['description'] = (string) ($listing['description'] ?? '');
            $payload['included'] = (string) ($listing['included'] ?? '');
            $payload['not_included'] = (string) ($listing['not_included'] ?? '');
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private function userPayload(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
            'avatar' => api_img_src((string) ($user['avatar'] ?? ''), 'user' . $user['id']),
        ];
    }

    /**
     * Email the admin(s) and the listing's guide that a paid booking just came
     * in, so they can process it immediately.
     *
     * @param array<string, mixed> $listing
     */
    private function notifyAdminsOfBooking(
        array $listing,
        int $customerId,
        string $date,
        int $guests,
        float $total,
        string $methodLabel
    ): void {
        try {
            $customer = User::find($customerId);
            $customerName = $customer['name'] ?? 'A customer';
            $title = (string) ($listing['title'] ?? 'a listing');

            // Recipients: all admins + the guide who owns the listing.
            $recipients = Database::all("SELECT email, name FROM users WHERE role = 'admin'");
            $owner = User::find((int) ($listing['user_id'] ?? 0));
            if ($owner !== null && !empty($owner['email'])) {
                $recipients[] = ['email' => $owner['email'], 'name' => $owner['name']];
            }

            $subject = 'New paid booking — ' . $title;
            $body = "{$customerName} booked \"{$title}\".\n\n"
                . "Date: {$date}\n"
                . "Guests: {$guests}\n"
                . 'Amount paid: PHP ' . number_format($total, 2) . "\n"
                . "Payment: {$methodLabel}\n\n"
                . 'Please review and process this booking in the admin panel.';

            $seen = [];
            foreach ($recipients as $r) {
                $email = trim((string) ($r['email'] ?? ''));
                if ($email === '' || isset($seen[$email])) {
                    continue;
                }
                $seen[$email] = true;
                NotificationService::adminBookingPlaced($email, $subject, $body);
            }
        } catch (\Throwable $e) {
            // Notifications are best-effort; never block the booking on them.
        }
    }

    /**
     * @param array<string, mixed> $booking
     * @return array<string, mixed>
     */
    private function bookingPayload(array $booking): array
    {
        return [
            'id' => (int) $booking['id'],
            'listing_id' => (int) $booking['listing_id'],
            'listing_title' => (string) ($booking['listing_title'] ?? ''),
            'listing_slug' => (string) ($booking['listing_slug'] ?? ''),
            'image' => api_img_src((string) ($booking['cover_image'] ?? ''), 'listing' . $booking['listing_id']),
            'booking_date' => (string) $booking['booking_date'],
            'guests' => (int) $booking['guests'],
            'total_amount' => (float) $booking['total_amount'],
            'status' => (string) $booking['status'],
            'area' => (string) ($booking['area'] ?? ''),
        ];
    }
}

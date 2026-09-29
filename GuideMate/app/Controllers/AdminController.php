<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Dispute;
use App\Models\Feedback;
use App\Models\GuideDocument;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Services\NotificationService;

final class AdminController extends Controller
{
    public function index(): void
    {
        Booking::reconcileOrphanRefunds();
        $this->view('dashboard/admin', [
            'title' => 'Admin Dashboard',
            'stats' => [
                'tourists' => User::countByRole('tourist'),
                'guides' => User::countByRole('guide'),
                'listings' => Listing::count('approved'),
                'pending' => Listing::count('pending'),
                'pendingGuides' => User::countProvidersByStatus('pending'),
                'openDisputes' => Dispute::countOpen(),
                'revenue' => Payment::totalRevenue(),
            ],
            'pending' => Listing::pending(),
            'pendingGuides' => User::providers('pending'),
            'recentReviews' => Review::recent(6),
        ], 'admin');
    }

    public function feedback(): void
    {
        $this->view('admin/feedback', [
            'title' => 'App Feedback',
            'items' => Feedback::all(),
        ], 'admin');
    }

    public function updateFeedbackStatus(string $id): void
    {
        $this->verifyCsrf();
        $status = (string) $this->input('status', '');
        if (!in_array($status, Feedback::STATUSES, true)) {
            flash('error', 'Invalid feedback status.');
            redirect('/admin/feedback');
        }
        Feedback::markStatus((int) $id, $status);
        AuditLog::recordAction('feedback.status', 'app_feedback', (int) $id, ['status' => $status]);
        flash('success', 'Feedback updated.');
        redirect('/admin/feedback');
    }

    public function guides(): void
    {
        // Only applications awaiting review are listed. Once approved (or
        // rejected) a provider drops off this queue. Approved providers are
        // managed from the Users page (where they can be revoked). This queue
        // covers all provider roles: guides, rental partners and hotel partners.
        $guides = User::providers('pending');
        $documents = [];
        foreach ($guides as $g) {
            $documents[(int) $g['id']] = GuideDocument::forUser((int) $g['id']);
        }
        $this->view('admin/guides', [
            'title' => 'Partner Applications',
            'guides' => $guides,
            'documents' => $documents,
        ], 'admin');
    }

    public function approveGuide(string $id): void
    {
        $this->verifyCsrf();
        $guide = User::find((int) $id);
        $role = (string) ($guide['role'] ?? '');
        if ($guide === null || !User::isProviderRole($role)) {
            flash('error', 'This application could not be approved.');
            redirect('/admin/guides');
        }

        User::setGuideStatus((int) $id, 'approved');
        AuditLog::recordAction('guide.approve', 'user', (int) $id);
        $label = User::PROVIDER_LABELS[$role] ?? 'partner';
        NotificationService::guideApproved($guide['email'], $guide['name'], $label);
        $this->notifyPartnerDecision((int) $id, true);
        flash('success', 'Application Approved');
        redirect('/admin/guides');
    }

    public function rejectGuide(string $id): void
    {
        $this->verifyCsrf();
        $guide = User::find((int) $id);
        $role = (string) ($guide['role'] ?? '');
        if ($guide === null || !User::isProviderRole($role)) {
            flash('error', 'This application could not be rejected.');
            redirect('/admin/guides');
        }

        $note = trim((string) $this->input('note', ''));
        User::setGuideStatus((int) $id, 'rejected', $note !== '' ? $note : null);
        AuditLog::recordAction('guide.reject', 'user', (int) $id, ['note' => $note]);
        $label = User::PROVIDER_LABELS[$role] ?? 'partner';
        NotificationService::guideRejected($guide['email'], $guide['name'], $note, $label);
        $this->notifyPartnerDecision((int) $id, false, $note);
        flash('info', 'Application Rejected');
        redirect('/admin/guides');
    }

    /**
     * Revoke a previously approved partner (from the Users page). They lose the
     * ability to publish and must re-submit documents to be reviewed again.
     */
    public function revokeGuide(string $id): void
    {
        $this->verifyCsrf();
        $guide = User::find((int) $id);
        $role = (string) ($guide['role'] ?? '');
        if ($guide !== null && User::isProviderRole($role)) {
            $label = User::PROVIDER_LABELS[$role] ?? 'partner';
            User::setGuideStatus((int) $id, 'rejected', 'Your ' . strtolower($label) . ' verification was revoked by an administrator.');
            AuditLog::recordAction('guide.revoke', 'user', (int) $id);
            flash('info', $guide['name'] . '\'s verification has been revoked.');
        }
        redirect('/admin/users');
    }

    public function listings(): void
    {
        $this->view('admin/listings', [
            'title' => 'Manage Listings',
            'pending' => Listing::pending(),
            'all' => Listing::search(['sort' => 'newest']),
        ], 'admin');
    }

    public function updateListingStatus(string $id): void
    {
        $this->verifyCsrf();
        $status = (string) $this->input('status', '');
        if (in_array($status, ['approved', 'rejected', 'pending'], true)) {
            Listing::setStatus((int) $id, $status);
            AuditLog::recordAction('listing.status', 'listing', (int) $id, ['status' => $status]);
            flash('success', 'Listing ' . $status . '.');
        }
        redirect('/admin/listings');
    }

    public function toggleFeatured(string $id): void
    {
        $this->verifyCsrf();
        $listing = Listing::find((int) $id);
        if ($listing !== null) {
            Listing::setFeatured((int) $id, (int) $listing['is_featured'] === 0);
            flash('success', 'Featured status updated.');
        }
        redirect('/admin/listings');
    }

    public function users(): void
    {
        $this->view('admin/users', [
            'title' => 'Manage Users',
            'users' => User::manageable(),
        ], 'admin');
    }

    public function toggleUser(string $id): void
    {
        $this->verifyCsrf();
        $user = User::find((int) $id);
        if ($user !== null && $user['role'] !== 'admin') {
            $active = (int) $user['is_active'] === 0;
            User::setActive((int) $id, $active);
            AuditLog::recordAction('user.toggle_active', 'user', (int) $id, ['active' => $active]);
            flash('success', 'User status updated.');
        }
        redirect('/admin/users');
    }

    public function disputes(): void
    {
        $this->view('admin/disputes', [
            'title' => 'Disputes',
            'disputes' => Dispute::pending(),
        ], 'admin');
    }

    public function resolveDispute(string $id): void
    {
        $this->verifyCsrf();
        $dispute = Dispute::find((int) $id);
        if ($dispute === null) {
            abort(404, 'Dispute not found.');
        }

        $action = (string) $this->input('action', '');
        $note = trim((string) $this->input('admin_note', ''));
        $bookingId = (int) $dispute['booking_id'];
        $guideId = (int) $dispute['guide_id'];
        $disputeId = (int) $dispute['id'];

        if ($action === 'refund') {
            Payment::refund($bookingId);
            Booking::updateStatus($bookingId, 'refunded');
            Dispute::resolve($disputeId, 'resolved_refund', $note !== '' ? $note : 'Refund approved.');
            User::appendGuideNote($guideId, 'Dispute #' . $disputeId . ': refund issued to tourist.');
            AuditLog::recordAction('dispute.refund', 'dispute', $disputeId);
            flash('success', 'Refund processed and dispute resolved.');
        } elseif ($action === 'warning') {
            $warningNote = $note !== '' ? $note : 'Warning issued after dispute #' . $disputeId . '.';
            Dispute::resolve($disputeId, 'resolved_warning', $warningNote);
            // Keep booking as disputed so the guide cannot mark it complete again;
            // the tourist has already used their one report for this booking.
            User::warnGuide($guideId, $warningNote);
            $this->notifyGuideWarning($guideId, $warningNote);
            AuditLog::recordAction('dispute.warning', 'dispute', $disputeId);
            flash('success', 'Guide warned. They were notified by message and booking actions are restricted until you clear the warning.');
        } elseif ($action === 'reject') {
            if ($dispute['booking_status'] === 'disputed') {
                Booking::updateStatus($bookingId, 'confirmed');
            }
            Dispute::resolve($disputeId, 'rejected', $note !== '' ? $note : 'Report rejected.');
            AuditLog::recordAction('dispute.reject', 'dispute', $disputeId);
            flash('info', 'Dispute rejected.');
        } elseif ($action === 'reviewing') {
            Dispute::resolve($disputeId, 'reviewing', $note !== '' ? $note : null);
            flash('info', 'Dispute marked as under review.');
        } else {
            flash('error', 'Unknown action.');
        }

        redirect('/admin/disputes');
    }

    public function clearGuideWarning(string $id): void
    {
        $this->verifyCsrf();
        $user = User::find((int) $id);
        $fallback = '/admin/users';
        // Warnings can apply to any service provider (guide, hotel or rental
        // partner), so allow clearing for all of them — not just guides.
        if ($user === null || !User::isProviderRole((string) ($user['role'] ?? ''))) {
            abort(404, 'Provider not found.');
        }
        if (!User::isGuideWarned((int) $id)) {
            flash('info', 'This partner has no active warning.');
            redirect($this->adminSafeReturn($fallback));
        }

        User::clearGuideWarning((int) $id);
        AuditLog::recordAction('guide.clear_warning', 'user', (int) $id);
        $this->notifyGuideWarningCleared((int) $id);
        flash('success', 'Account unrestricted. This partner can manage bookings again and was notified.');
        redirect($this->adminSafeReturn($fallback));
    }

    /**
     * Allow posting forms to send the admin back to Messages (or another
     * admin page) after unrestricting, without open-redirecting off-site.
     */
    private function adminSafeReturn(string $fallback): string
    {
        $return = trim((string) $this->input('return', $fallback));
        if ($return === '' || $return[0] !== '/' || str_starts_with($return, '//') || !str_starts_with($return, '/admin')) {
            return $fallback;
        }
        return $return;
    }

    /**
     * Admin inbox — conversations with providers, including their replies to
     * warnings. Uses the shared "Administrator" account so every admin sees the
     * same history (warnings are sent from that same account).
     */
    public function messages(): void
    {
        $adminId = $this->adminMessagingId();
        $this->view('admin/messages', [
            'title' => 'Messages',
            'conversations' => Message::conversations($adminId),
            'partner' => null,
            'thread' => [],
            'adminId' => $adminId,
        ], 'admin');
    }

    public function messageThread(string $partner): void
    {
        $adminId = $this->adminMessagingId();
        $partnerId = (int) $partner;
        $partnerUser = User::find($partnerId);
        if ($partnerUser === null) {
            abort(404, 'Conversation not found.');
        }
        Message::markRead($adminId, $partnerId);
        $this->view('admin/messages', [
            'title' => 'Messages',
            'conversations' => Message::conversations($adminId),
            'partner' => $partnerUser,
            'thread' => Message::thread($adminId, $partnerId),
            'adminId' => $adminId,
        ], 'admin');
    }

    public function sendMessage(): void
    {
        $this->verifyCsrf();
        $adminId = $this->adminMessagingId();
        $receiverId = (int) $this->input('receiver_id', 0);
        $body = trim((string) $this->input('body', ''));

        if ($receiverId === 0 || $receiverId === $adminId || $body === '') {
            redirect('/admin/messages' . ($receiverId ? '/' . $receiverId : ''));
        }
        if (User::find($receiverId) === null) {
            abort(404, 'Recipient not found.');
        }

        Message::send($adminId, $receiverId, $body);
        AuditLog::recordAction('admin.message', 'user', $receiverId);
        flash('success', 'Reply sent.');
        redirect('/admin/messages/' . $receiverId);
    }

    /**
     * The shared "Administrator" user id used for all admin messaging, so
     * replies land in one place regardless of which admin is signed in.
     */
    private function adminMessagingId(): int
    {
        $admin = User::adminAccount();
        return (int) ($admin['id'] ?? AdminAuth::id() ?? 0);
    }

    public function analytics(): void
    {
        $this->view('admin/analytics', [
            'title' => 'Analytics',
            'bookingsByMonth' => Booking::bookingsPerMonth(12),
            'topListings' => Listing::topByBookings(10),
            'usersByMonth' => User::registrationsPerMonth(12),
            'disputeStats' => Dispute::rateStats(),
            'revenue' => Payment::totalRevenue(),
            'auditLog' => AuditLog::recent(25),
        ], 'admin');
    }

    public function exportBookings(): void
    {
        $rows = Booking::exportRows();
        $this->streamCsv('bookings.csv', [
            'ID', 'Listing', 'Customer', 'Date', 'Guests', 'Total', 'Status', 'Created',
        ], $rows);
    }

    public function exportUsers(): void
    {
        $rows = User::exportRows();
        $this->streamCsv('users.csv', [
            'ID', 'Name', 'Email', 'Role', 'Guide Status', 'Active', 'Joined',
        ], $rows);
    }

    public function exportDisputes(): void
    {
        $rows = [];
        foreach (Dispute::all() as $d) {
            $rows[] = [
                $d['id'],
                $d['listing_title'],
                $d['tourist_name'],
                $d['guide_name'],
                Dispute::typeLabel((string) $d['problem_type']),
                Dispute::statusLabel((string) $d['status']),
                $d['total_amount'],
                $d['created_at'],
            ];
        }
        $this->streamCsv('disputes.csv', [
            'ID', 'Listing', 'Tourist', 'Guide', 'Problem', 'Status', 'Booking Total', 'Created',
        ], $rows);
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, array<int, mixed>> $rows
     */
    private function streamCsv(string $filename, array $headers, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        if ($out === false) {
            abort(500, 'Could not export CSV.');
        }
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    private function notifyGuideWarning(int $guideId, string $note): void
    {
        $admin = User::adminAccount();
        if ($admin === null) {
            return;
        }
        $body = "Administrator Warning\n\n"
            . "You have received an official warning from the GuideMate Administrator.\n\n"
            . "Reason: {$note}\n\n"
            . "Your booking actions (Confirm / Complete / Cancel) are restricted until an administrator clears this warning. "
            . "Please review this message and wait for admin follow-up.";
        Message::send((int) $admin['id'], $guideId, $body);
    }

    private function notifyGuideWarningCleared(int $guideId): void
    {
        $admin = User::adminAccount();
        if ($admin === null) {
            return;
        }
        $body = "Administrator Notice\n\n"
            . "Your account has been reviewed and unrestricted by the Administrator. "
            . "You may Confirm, Complete, and Cancel bookings again.";
        Message::send((int) $admin['id'], $guideId, $body);
    }

    private function notifyPartnerDecision(int $partnerId, bool $approved, string $note = ''): void
    {
        $admin = User::adminAccount();
        if ($admin === null) {
            return;
        }
        if ($approved) {
            $body = "Application Approved\n\n"
                . "Your partner application has been approved by the GuideMate Administrator. "
                . "You can now use your partner account on GuideMate.";
        } else {
            $body = "Application Rejected\n\n"
                . "Your partner application was not approved.";
            if ($note !== '') {
                $body .= "\n\nReason: {$note}";
            }
        }
        Message::send((int) $admin['id'], $partnerId, $body);
    }
}

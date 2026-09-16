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
        if ($guide !== null && $guide['role'] === 'guide') {
            User::setGuideStatus((int) $id, 'approved');
            AuditLog::recordAction('guide.approve', 'user', (int) $id);
            NotificationService::guideApproved($guide['email'], $guide['name']);
            flash('success', $guide['name'] . ' has been approved as a verified guide.');
        }
        redirect('/admin/guides');
    }

    public function rejectGuide(string $id): void
    {
        $this->verifyCsrf();
        $guide = User::find((int) $id);
        if ($guide !== null && $guide['role'] === 'guide') {
            $note = trim((string) $this->input('note', ''));
            User::setGuideStatus((int) $id, 'rejected', $note !== '' ? $note : null);
            AuditLog::recordAction('guide.reject', 'user', (int) $id, ['note' => $note]);
            NotificationService::guideRejected($guide['email'], $guide['name'], $note);
            flash('info', $guide['name'] . '\'s application was rejected.');
        }
        redirect('/admin/guides');
    }

    /**
     * Revoke a previously approved guide (from the Users page). They lose the
     * ability to publish and must re-submit documents to be reviewed again.
     */
    public function revokeGuide(string $id): void
    {
        $this->verifyCsrf();
        $guide = User::find((int) $id);
        if ($guide !== null && $guide['role'] === 'guide') {
            User::setGuideStatus((int) $id, 'rejected', 'Your guide verification was revoked by an administrator.');
            AuditLog::recordAction('guide.revoke', 'user', (int) $id);
            flash('info', $guide['name'] . '\'s guide verification has been revoked.');
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
        if ($user === null || $user['role'] !== 'guide') {
            abort(404, 'Guide not found.');
        }
        if (!User::isGuideWarned((int) $id)) {
            flash('info', 'This guide has no active warning.');
            redirect('/admin/users');
        }

        User::clearGuideWarning((int) $id);
        AuditLog::recordAction('guide.clear_warning', 'user', (int) $id);
        $this->notifyGuideWarningCleared((int) $id);
        flash('success', 'Guide warning cleared. They can manage bookings again.');
        redirect('/admin/users');
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
            . "Your GuideMate warning has been reviewed and cleared by the Administrator. "
            . "You may manage bookings again.";
        Message::send((int) $admin['id'], $guideId, $body);
    }
}

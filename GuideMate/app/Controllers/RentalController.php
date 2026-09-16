<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\RentalRequest;

/**
 * Rental Partner dashboard — manages incoming vehicle rental requests.
 * This functionality used to live in the admin portal (/admin/rentals) and has
 * been relocated here so rental partners run it from their own dashboard.
 */
final class RentalController extends Controller
{
    public function requests(): void
    {
        $this->view('rental/requests', [
            'title' => 'Rental Requests',
            'requests' => RentalRequest::all(),
        ]);
    }

    public function updateStatus(string $id): void
    {
        $this->verifyCsrf();
        $status = (string) $this->input('status', '');
        if (!in_array($status, ['pending', 'approved', 'contacted', 'cancelled', 'completed'], true)) {
            flash('error', 'Invalid rental status.');
            redirect('/dashboard/rentals');
        }
        $note = trim((string) $this->input('admin_note', ''));
        RentalRequest::updateStatus((int) $id, $status, $note !== '' ? $note : null);
        flash('success', 'Rental request updated.');
        redirect('/dashboard/rentals');
    }

    /**
     * Refund a paid reservation (resolves any open problem report).
     */
    public function refund(string $id): void
    {
        $this->verifyCsrf();
        $rental = RentalRequest::find((int) $id);
        if ($rental === null) {
            flash('error', 'Reservation not found.');
            redirect('/dashboard/rentals');
        }
        if (($rental['payment_status'] ?? 'unpaid') !== 'paid') {
            flash('error', 'Only a paid reservation can be refunded.');
            redirect('/dashboard/rentals');
        }
        $note = trim((string) $this->input('owner_note', ''));
        RentalRequest::refund((int) $id, $note !== '' ? $note : null);
        flash('success', 'Reservation refunded and the customer has been notified.');
        redirect('/dashboard/rentals');
    }

    /**
     * Dismiss a tourist's problem report without a refund.
     */
    public function rejectReport(string $id): void
    {
        $this->verifyCsrf();
        $note = trim((string) $this->input('owner_note', ''));
        RentalRequest::rejectReport((int) $id, $note !== '' ? $note : null);
        flash('success', 'Report marked as reviewed.');
        redirect('/dashboard/rentals');
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Upload;
use App\Models\User;
use RuntimeException;

final class ProfileController extends Controller
{
    public function edit(): void
    {
        $this->view('profile/edit', [
            'title' => 'My Profile',
            'user' => Auth::user(),
            'errors' => errors(),
        ]);
    }

    public function update(): void
    {
        $this->verifyCsrf();
        $userId = (int) Auth::id();

        $errors = $this->requireFields(['name']);
        if ($errors !== []) {
            flash_keep_old($errors, [], '/profile');
        }

        // Optional profile photo upload (images only).
        $avatarPath = null;
        if (Upload::present($_FILES['avatar'] ?? null)) {
            try {
                $avatarPath = Upload::store($_FILES['avatar'], 'avatars/' . $userId, ['jpg', 'jpeg', 'png', 'webp']);
            } catch (RuntimeException $e) {
                flash_keep_old(['avatar' => $e->getMessage()], [], '/profile');
            }
        }

        User::updateProfile($userId, [
            'name' => (string) $this->input('name', ''),
            'phone' => (string) $this->input('phone', ''),
            'location' => (string) $this->input('location', ''),
            'bio' => (string) $this->input('bio', ''),
        ]);

        if ($avatarPath !== null) {
            User::updateAvatar($userId, $avatarPath);
        }

        flash('success', 'Profile updated.');
        redirect('/profile');
    }
}

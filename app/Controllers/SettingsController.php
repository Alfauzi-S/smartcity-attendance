<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\User;

final class SettingsController extends Controller
{
    public function index(): void
    {
        Auth::require(Auth::SUPER);
        $this->render('super/settings', ['title' => t('Pengaturan Akun'), 'active' => 'settings']);
    }

    public function profile(): void
    {
        $user = Auth::require(Auth::SUPER);
        $name = input('name');
        if (mb_strlen($name) < 3) {
            flash('bad', 'Nama minimal 3 karakter');
        } else {
            User::update((int) $user['id'], ['name' => mb_substr($name, 0, 120)]);
            flash('ok', 'Profil disimpan');
        }
        redirect('admin/pengaturan');
    }

    public function password(): void
    {
        $user = Auth::require(Auth::SUPER);
        $this->changeOwnPassword($user, 'admin/pengaturan');
    }
}

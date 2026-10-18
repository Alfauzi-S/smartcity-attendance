<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\User;

final class SettingsController extends Controller
{
    public function index(): void
    {
        $user = $this->manager();
        $this->render('super/settings', ['title' => t('Pengaturan Akun'), 'active' => 'settings', 'assignments' => $user['role'] === Auth::EVENT ? \App\Models\Assignment::forUser((int) $user['id']) : []]);
    }

    public function profile(): void
    {
        $user = $this->manager();
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
        $user = $this->manager();
        $this->changeOwnPassword($user, 'admin/pengaturan');
    }
}

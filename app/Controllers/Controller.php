<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Event;
use App\Models\User;
use App\Services\Access;
use App\Services\Validator;

abstract class Controller
{
    protected function render(string $template, array $data = [], string $layout = 'app'): void
    {
        View::render($template, $data + ['user' => Auth::user()], $layout);
    }

    /**
     * Event yang sedang dikelola.
     * Admin Presensi selalu terkunci ke event yang ditugaskan Super Admin.
     * Super Admin bisa memilih lewat ?ev= (diingat di sesi).
     * Admin Event hanya bisa memilih event yang ditugaskan dan memiliki izin $perm;
     * meminta event lain lewat ?ev= atau body API ditolak (403) dan dicatat.
     */
    protected function currentEvent(array $user, ?int $requested = null, ?string $perm = null): ?array
    {
        if ($user['role'] === Auth::ADMIN) {
            return $user['event_id'] ? Event::find((int) $user['event_id']) : null;
        }
        $allowed = Access::eventIds($user, $perm);
        $explicit = $requested ?? (query('ev') !== '' ? (int) query('ev') : null);
        if ($explicit !== null && $allowed !== null && !in_array($explicit, $allowed, true)) {
            Access::deny($user, $explicit, $perm ?? 'event');
        }
        $id = $explicit ?? (int) ($_SESSION['ev'] ?? 0);
        if ($allowed !== null && !in_array($id, $allowed, true)) {
            $id = 0;
        }
        $event = $id ? Event::find($id) : null;
        if (!$event) {
            if ($allowed === null) {
                $event = Event::defaultEvent();
            } else {
                $mine = Access::events($user, $perm);
                $active = array_values(array_filter($mine, static fn ($e) => $e['status'] === 'Aktif'));
                $event = $active[0] ?? $mine[0] ?? null;
            }
        }
        if ($event) {
            $_SESSION['ev'] = (int) $event['id'];
        }
        return $event;
    }

    /** Pengguna yang boleh membuka halaman pengelolaan (/admin): Super Admin dan Admin Event. */
    protected function manager(): array
    {
        return Auth::require(Auth::SUPER, Auth::EVENT);
    }

    protected function page(): int
    {
        return max(1, (int) query('hal', '1'));
    }

    /** Ganti password akun yang sedang login (dipakai Admin dan Super Admin). */
    protected function changeOwnPassword(array $user, string $back): void
    {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        if (!password_verify($current, $user['password_hash'])) {
            flash('bad', 'Password saat ini salah');
            redirect($back);
        }
        if ($err = Validator::newPassword($new, $confirm)) {
            flash('bad', 'Password belum diganti', t($err));
            redirect($back);
        }
        User::setPassword((int) $user['id'], $new);
        session_regenerate_id(true);
        flash('ok', 'Password berhasil diperbarui');
        redirect($back);
    }
}

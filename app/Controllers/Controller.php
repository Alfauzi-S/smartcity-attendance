<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Event;
use App\Models\User;
use App\Services\Validator;

abstract class Controller
{
    protected function render(string $template, array $data = [], string $layout = 'app'): void
    {
        View::render($template, $data + ['user' => Auth::user()], $layout);
    }

    /**
     * Event yang sedang dikelola.
     * Admin Absensi selalu terkunci ke event yang ditugaskan Super Admin.
     * Super Admin bisa memilih lewat ?ev= (diingat di sesi).
     */
    protected function currentEvent(array $user, ?int $requested = null): ?array
    {
        if ($user['role'] === Auth::ADMIN) {
            return $user['event_id'] ? Event::find((int) $user['event_id']) : null;
        }
        $id = $requested ?? (int) (query('ev') !== '' ? query('ev') : ($_SESSION['ev'] ?? 0));
        $event = $id ? Event::find($id) : null;
        $event ??= Event::defaultEvent();
        if ($event) {
            $_SESSION['ev'] = (int) $event['id'];
        }
        return $event;
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

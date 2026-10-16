<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Event;
use App\Models\Participant;
use App\Models\StatusHistory;
use App\Services\AttendanceService;
use App\Services\Validator;

/** Pengelolaan peserta oleh Super Admin, termasuk ubah status manual. */
final class ParticipantController extends Controller
{
    private const PER_PAGE = 25;

    public function index(): void
    {
        $user = Auth::require(Auth::SUPER);
        $event = $this->currentEvent($user);
        $q = query('q');
        $st = query('st', 'all');
        $page = $this->page();
        $total = $event ? Participant::count((int) $event['id'], $q, $st) : 0;
        $this->render('super/participants/index', [
            'title' => t('Peserta'),
            'active' => 'participants',
            'event' => $event,
            'events' => Event::all(),
            'stats' => $event ? Event::stats((int) $event['id']) : null,
            'list' => $event ? Participant::search((int) $event['id'], $q, $st, self::PER_PAGE, ($page - 1) * self::PER_PAGE) : [],
            'total' => $total,
            'page' => $page,
            'pages' => (int) ceil($total / self::PER_PAGE),
            'q' => $q,
            'st' => $st,
        ]);
    }

    private function findOr404(string $id): array
    {
        $p = Participant::find((int) $id);
        if (!$p) {
            abort(404, 'Peserta tidak ditemukan');
        }
        return $p;
    }

    public function show(string $id): void
    {
        Auth::require(Auth::SUPER);
        $p = $this->findOr404($id);
        $this->render('super/participants/show', [
            'title' => t('Detail Peserta'),
            'active' => 'participants',
            'back' => 'admin/peserta',
            'p' => $p,
            'event' => Event::find((int) $p['event_id']),
            'history' => StatusHistory::forParticipant((int) $p['id']),
            'errors' => [],
            'editing' => query('ubah') === 'data',
            'openStatus' => in_array(query('status'), ['hadir', 'tidak'], true) ? query('status') : (query('ubah') === 'status' ? '1' : ''),
        ]);
    }

    public function update(string $id): void
    {
        Auth::require(Auth::SUPER);
        $p = $this->findOr404($id);
        $data = [
            'identity_number' => preg_replace('/\s+/', '', input('identity_number')) ?? '',
            'profession' => input('profession'),
            'full_name' => preg_replace('/\s+/', ' ', input('full_name')) ?? '',
            'phone' => Validator::normalizePhone(input('phone')),
            'institution' => input('institution'),
            'field_of_study' => input('field_of_study'),
        ];
        $errors = Validator::registration($data, (int) $p['event_id'], (int) $p['id']);
        if ($errors) {
            http_response_code(422);
            $this->render('super/participants/show', [
                'title' => t('Detail Peserta'), 'active' => 'participants', 'back' => 'admin/peserta',
                'p' => $data + $p, 'event' => Event::find((int) $p['event_id']),
                'history' => StatusHistory::forParticipant((int) $p['id']), 'errors' => $errors, 'editing' => true, 'openStatus' => '',
            ]);
            return;
        }
        Participant::updateData((int) $p['id'], $data);
        flash('ok', 'Data peserta diperbarui', $data['full_name']);
        redirect('admin/peserta/' . $p['id']);
    }

    public function status(string $id): void
    {
        $user = Auth::require(Auth::SUPER);
        $p = $this->findOr404($id);
        if (mb_strlen(input('reason')) < 3) {
            flash('bad', 'Perubahan gagal disimpan', 'Alasan perubahan wajib diisi (minimal 3 karakter).');
            redirect('admin/peserta/' . $p['id'], ['ubah' => 'status']);
        }
        $result = AttendanceService::changeStatus((int) $p['id'], input('new_status'), mb_substr(input('reason'), 0, 255), $user);
        if ($result['ok']) {
            flash('ok', 'Status berhasil diubah', t(':name: :message', ['name' => $p['full_name'], 'message' => t($result['message'])]));
        } else {
            flash('bad', 'Perubahan gagal disimpan', t($result['message']));
        }
        $back = input('back');
        redirect($back === 'list' ? 'admin/peserta' : 'admin/peserta/' . $p['id']);
    }
}

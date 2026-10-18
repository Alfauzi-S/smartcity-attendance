<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Event;
use App\Models\Participant;
use App\Models\ParticipantOptions;
use App\Models\StatusHistory;
use App\Services\AttendanceService;
use App\Models\AuditLog;
use App\Services\Access;
use App\Services\Validator;

/** Pengelolaan peserta oleh Super Admin, termasuk ubah status manual. */
final class ParticipantController extends Controller
{
    private const PER_PAGE = 25;

    public function index(): void
    {
        $user = $this->manager();
        $event = $this->currentEvent($user, null, 'participants');
        $q = query('q');
        $st = query('st', 'all');
        $page = $this->page();
        $total = $event ? Participant::count((int) $event['id'], $q, $st) : 0;
        $this->render('super/participants/index', [
            'title' => t('Peserta'),
            'active' => 'participants',
            'event' => $event,
            'events' => Access::events($user, 'participants'),
            'canStatus' => $event && Access::can($user, (int) $event['id'], 'status'),
            'stats' => $event ? Event::stats((int) $event['id']) : null,
            'list' => $event ? Participant::search((int) $event['id'], $q, $st, self::PER_PAGE, ($page - 1) * self::PER_PAGE) : [],
            'total' => $total,
            'page' => $page,
            'pages' => (int) ceil($total / self::PER_PAGE),
            'q' => $q,
            'st' => $st,
        ]);
    }

    /** Peserta diambil dari database, lalu event miliknya dicek terhadap izin pengguna (anti-IDOR). */
    private function findOr404(string $id, array $user, string $perm): array
    {
        $p = Participant::find((int) $id);
        if (!$p) {
            abort(404, 'Peserta tidak ditemukan');
        }
        if (!Access::can($user, (int) $p['event_id'], $perm)) {
            Access::deny($user, (int) $p['event_id'], $perm);
        }
        return $p;
    }

    public function show(string $id): void
    {
        $user = $this->manager();
        $p = $this->findOr404($id, $user, 'participants');
        $this->render('super/participants/show', [
            'title' => t('Detail Peserta'),
            'active' => 'participants',
            'back' => 'admin/peserta',
            'p' => $p,
            'event' => Event::find((int) $p['event_id']),
            'history' => StatusHistory::forParticipant((int) $p['id']),
            'canStatus' => Access::can($user, (int) $p['event_id'], 'status'),
            'errors' => [],
            'editing' => query('ubah') === 'data',
            'openStatus' => in_array(query('status'), ['hadir', 'tidak'], true) ? query('status') : (query('ubah') === 'status' ? '1' : ''),
        ]);
    }

    public function update(string $id): void
    {
        $user = $this->manager();
        $p = $this->findOr404($id, $user, 'participants');
        $data = [
            'identity_number' => preg_replace('/\s+/', '', input('identity_number')) ?? '',
            'profession' => ParticipantOptions::pick(input('profession'), input('profession_other')),
            'full_name' => preg_replace('/\s+/', ' ', input('full_name')) ?? '',
            'phone' => Validator::normalizePhone(input('phone')),
            'institution' => input('institution'),
            'field_of_study' => ParticipantOptions::pick(input('field_of_study'), input('field_of_study_other')),
        ];
        $errors = Validator::registration($data, (int) $p['event_id'], (int) $p['id']);
        if ($errors) {
            http_response_code(422);
            $this->render('super/participants/show', [
                'title' => t('Detail Peserta'), 'active' => 'participants', 'back' => 'admin/peserta',
                'p' => $data + $p, 'event' => Event::find((int) $p['event_id']),
                'history' => StatusHistory::forParticipant((int) $p['id']), 'errors' => $errors, 'editing' => true, 'openStatus' => '',
                'canStatus' => Access::can($user, (int) $p['event_id'], 'status'),
            ]);
            return;
        }
        Participant::updateData((int) $p['id'], $data);
        $changed = array_keys(array_filter($data, static fn ($v, $k) => (string) $v !== (string) $p[$k], ARRAY_FILTER_USE_BOTH));
        AuditLog::add($user, 'participant.update', (int) $p['event_id'], 'participant', $p['id'], ['fields' => $changed]);
        flash('ok', 'Data peserta diperbarui', $data['full_name']);
        redirect('admin/peserta/' . $p['id']);
    }

    public function status(string $id): void
    {
        $user = $this->manager();
        $p = $this->findOr404($id, $user, 'status');
        if (mb_strlen(input('reason')) < 3) {
            flash('bad', 'Perubahan gagal disimpan', 'Alasan perubahan wajib diisi (minimal 3 karakter).');
            redirect('admin/peserta/' . $p['id'], ['ubah' => 'status']);
        }
        $result = AttendanceService::changeStatus((int) $p['id'], input('new_status'), mb_substr(input('reason'), 0, 255), $user);
        AuditLog::add($user, 'participant.status', (int) $p['event_id'], 'participant', $p['id'], ['new' => input('new_status')], $result['ok'] ? 'ok' : 'failed');
        if ($result['ok']) {
            flash('ok', 'Status berhasil diubah', t(':name: :message', ['name' => $p['full_name'], 'message' => t($result['message'])]));
        } else {
            flash('bad', 'Perubahan gagal disimpan', t($result['message']));
        }
        $back = input('back');
        redirect($back === 'list' ? 'admin/peserta' : 'admin/peserta/' . $p['id']);
    }
}

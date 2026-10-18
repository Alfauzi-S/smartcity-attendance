<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Participant;
use App\Services\AttendanceService;

/** Endpoint JSON untuk scanner. Dilindungi login + token CSRF (header X-CSRF-Token). */
final class ApiController extends Controller
{
    private function context(array $body): array
    {
        $user = Auth::require(Auth::ADMIN, Auth::SUPER, Auth::EVENT);
        $requested = isset($body['event_id']) ? (int) $body['event_id'] : null;
        // event_id dari browser hanya dipakai setelah dicek terhadap assignment (lihat currentEvent)
        $event = $this->currentEvent($user, $user['role'] !== Auth::ADMIN ? $requested : null, 'attendance');
        if (!$event) {
            json_out(['ok' => false, 'state' => 'noevent', 'message' => t('Akun ini belum ditugaskan ke event.')], 409);
        }
        return [$user, $event];
    }

    private function respond(array $result, array $event): never
    {
        $out = [
            'ok' => true,
            'state' => $result['state'],
            'event' => ['att_start' => $event['att_start'], 'att_end' => $event['att_end'], 'name' => $event['name']],
        ];
        if (isset($result['participant'])) {
            $out['participant'] = AttendanceService::publicParticipant($result['participant']);
        }
        if (isset($result['code'])) {
            $out['code'] = $result['code'];
        }
        json_out($out);
    }

    public function validate(): void
    {
        $body = json_body();
        [, $event] = $this->context($body);
        $code = is_string($body['code'] ?? null) ? mb_substr($body['code'], 0, 300) : '';
        $this->respond(AttendanceService::validate($event, $code), $event);
    }

    public function save(): void
    {
        $body = json_body();
        [$user, $event] = $this->context($body);
        $pid = (int) ($body['participant_id'] ?? 0);
        $method = ($body['via'] ?? '') === 'cari' ? 'cari' : 'scan';
        $this->respond(AttendanceService::checkIn($event, $pid, $user, $method), $event);
    }

    /** Pencarian manual (nama / nomor identitas / kode) bila QR rusak atau tidak terbawa. */
    public function search(): void
    {
        $body = json_body();
        [, $event] = $this->context($body);
        $q = is_string($body['q'] ?? null) ? trim(mb_substr($body['q'], 0, 80)) : '';
        if (mb_strlen($q) < 2) {
            json_out(['ok' => true, 'items' => []]);
        }
        $items = array_map(
            [AttendanceService::class, 'publicParticipant'],
            Participant::search((int) $event['id'], $q, 'all', 8)
        );
        json_out(['ok' => true, 'items' => $items]);
    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class Event
{
    public const STATUSES = ['Draft', 'Aktif', 'Selesai', 'Ditutup'];
    public const REG_STATUSES = ['Dibuka', 'Ditutup'];
    public const ATT_STATUSES = ['notyet', 'open', 'closed'];

    public static function all(): array
    {
        return DB::all('SELECT * FROM events ORDER BY event_date DESC, id DESC');
    }

    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM events WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return DB::one('SELECT * FROM events WHERE slug = ?', [$slug]);
    }

    /** Event yang tampil untuk publik (status Aktif). */
    public static function publicList(): array
    {
        return DB::all("SELECT * FROM events WHERE status = 'Aktif' ORDER BY event_date ASC");
    }

    /** Event default untuk dashboard: Aktif terdekat, lalu yang terbaru. */
    public static function defaultEvent(): ?array
    {
        return DB::one("SELECT * FROM events WHERE status = 'Aktif' ORDER BY event_date ASC LIMIT 1")
            ?? DB::one('SELECT * FROM events ORDER BY event_date DESC, id DESC LIMIT 1');
    }

    public static function create(array $data): int
    {
        $data['slug'] = self::uniqueSlug($data['name']);
        $data['created_at'] = $data['updated_at'] = now();
        $data['att_status'] = $data['att_status'] ?? 'notyet';
        return DB::insert('events', $data);
    }

    public static function update(int $id, array $data): void
    {
        $data['updated_at'] = now();
        DB::update('events', $data, 'id = ?', [$id]);
    }

    public static function setSetting(int $id, string $field, string $value): bool
    {
        $allowed = [
            'reg_status' => self::REG_STATUSES,
            'att_status' => self::ATT_STATUSES,
            'status' => self::STATUSES,
        ];
        if (!isset($allowed[$field]) || !in_array($value, $allowed[$field], true)) {
            return false;
        }
        self::update($id, [$field => $value]);
        return true;
    }

    public static function delete(int $id): void
    {
        DB::run('DELETE FROM events WHERE id = ?', [$id]);
    }

    public static function participantCount(int $id): int
    {
        return (int) DB::value('SELECT COUNT(*) FROM participants WHERE event_id = ?', [$id]);
    }

    /**
     * Status registrasi untuk halaman publik.
     * @return array{0:bool,1:string} [dibuka?, pesan bila ditutup]
     */
    public static function registrationState(array $e): array
    {
        if ($e['status'] !== 'Aktif') {
            return [false, 'Event ini belum dibuka atau sudah berakhir.'];
        }
        if ($e['reg_status'] !== 'Dibuka') {
            return [false, 'Registrasi sudah ditutup penyelenggara.'];
        }
        if ($e['reg_start'] && today() < $e['reg_start']) {
            return [false, 'Registrasi dibuka mulai ' . date_id($e['reg_start']) . '.'];
        }
        if ($e['reg_end'] && today() > $e['reg_end']) {
            return [false, 'Periode registrasi berakhir pada ' . date_id($e['reg_end']) . '.'];
        }
        return [true, ''];
    }

    /** Ringkasan kehadiran satu event. */
    public static function stats(int $id): array
    {
        $s = ['total' => 0, 'hadir' => 0, 'belum' => 0, 'tidak' => 0];
        foreach (DB::all('SELECT status, COUNT(*) AS n FROM participants WHERE event_id = ? GROUP BY status', [$id]) as $row) {
            if (isset($s[$row['status']])) {
                $s[$row['status']] = (int) $row['n'];
            }
            $s['total'] += (int) $row['n'];
        }
        $s['notyet'] = $s['belum'] + $s['tidak'];
        $s['pct'] = pct($s['hadir'], $s['total']);
        return $s;
    }

    /** Jumlah check-in per 15 menit selama jendela absensi. */
    public static function checkinSlots(array $e): array
    {
        $start = self::minutes($e['att_start']);
        $end = max($start + 15, self::minutes($e['att_end']));
        $slots = [];
        for ($m = $start; $m < $end; $m += 15) {
            $slots[] = ['label' => sprintf('%02d:%02d', intdiv($m, 60), $m % 60), 'n' => 0];
        }
        $rows = DB::all(
            "SELECT checked_in_at FROM participants WHERE event_id = ? AND status = 'hadir' AND checked_in_at IS NOT NULL",
            [(int) $e['id']]
        );
        foreach ($rows as $r) {
            $ts = strtotime($r['checked_in_at']);
            if ($ts === false || date('Y-m-d', $ts) !== $e['event_date']) {
                continue;
            }
            $i = intdiv(((int) date('G', $ts) * 60 + (int) date('i', $ts)) - $start, 15);
            if (isset($slots[$i])) {
                $slots[$i]['n']++;
            }
        }
        return $slots;
    }

    private static function minutes(string $hm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hm . ':0'));
        return $h * 60 + $m;
    }

    public static function uniqueSlug(string $name, int $exceptId = 0): string
    {
        $base = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $name) ?: $name), '-'));
        $base = substr($base !== '' ? $base : 'event', 0, 60);
        $slug = $base;
        $i = 2;
        while (DB::value('SELECT id FROM events WHERE slug = ? AND id <> ?', [$slug, $exceptId])) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}

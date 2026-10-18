<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

/**
 * Variabel sertifikat kustom (Custom Variable Manager).
 * - scope 'global' (scope_key 'global'): dikelola Super Admin, nilainya bisa diganti per event (override) bila overridable.
 * - scope 'event'  (scope_key 'event:{id}'): dikelola admin event tersebut.
 * Sumber nilai hanya dari daftar yang didukung; tidak ada eval atau eksekusi kode.
 */
final class CertVariable
{
    public const KEY_RE = '/^[A-Z][A-Z0-9_]{1,39}$/';
    public const TYPES = ['text' => 'Teks', 'number' => 'Angka', 'date' => 'Tanggal', 'choice' => 'Pilihan', 'generator' => 'Otomatis (generator sistem)'];
    public const SOURCES = ['manual' => 'Input manual administrator', 'participant_field' => 'Data peserta', 'event_field' => 'Data event', 'generator' => 'Generator sistem'];

    /** Kolom yang boleh dibaca sebagai sumber data (whitelist). */
    public const PARTICIPANT_FIELDS = [
        'full_name' => 'Nama lengkap', 'institution' => 'Institusi', 'profession' => 'Profesi',
        'field_of_study' => 'Program studi', 'identity_number' => 'Nomor identitas', 'code' => 'Kode tiket',
    ];
    public const EVENT_FIELDS = [
        'name' => 'Nama event', 'organizer' => 'Penyelenggara', 'location' => 'Lokasi', 'event_date' => 'Tanggal event',
        'start_time' => 'Jam mulai', 'end_time' => 'Jam selesai', 'slug' => 'Slug event',
    ];
    /** Generator terdaftar (logika ada di Placeholders::generator). */
    public const GENERATORS = [
        'name_upper' => 'Nama peserta huruf kapital',
        'name_title' => 'Nama peserta Title Case',
        'issue_date_long' => 'Tanggal terbit panjang (17 Oktober 2026)',
        'issue_date_numeric' => 'Tanggal terbit angka (17/10/2026)',
        'event_date_long' => 'Tanggal event panjang',
        'event_year' => 'Tahun event',
    ];

    /** Variabel organisasi/penandatangan awal. Nilai sengaja kosong: diisi admin setelah dikonfirmasi pihak resmi. */
    private const SEED = [
        ['UNIVERSITY', 'Nama universitas', 'Data organisasi, mis. Universitas Mulawarman'],
        ['FACULTY', 'Nama fakultas', 'Data organisasi'],
        ['FACULTY_CODE', 'Kode fakultas', 'Dipakai di pola nomor bila disetujui TU'],
        ['FACULTY_LETTER_CODE', 'Kode surat resmi fakultas', 'Isi hanya setelah dikonfirmasi TU'],
        ['STUDY_PROGRAM', 'Nama program studi', 'Data organisasi'],
        ['STUDY_PROGRAM_CODE', 'Kode program studi', 'Dipakai di pola nomor bila disetujui TU'],
        ['SIGNER_NAME', 'Nama penandatangan', 'Mis. nama Dekan beserta gelar'],
        ['SIGNER_TITLE', 'Jabatan penandatangan', 'Mis. Dekan Fakultas Teknik'],
        ['SIGNER_ID', 'NIP penandatangan', 'Opsional'],
        ['EVENT_CODE', 'Kode event', 'Isi per event (override)'],
        ['EVENT_CATEGORY', 'Kategori event', 'Mis. Seminar, Workshop. Isi per event'],
        ['EVENT_BATCH', 'Angkatan / batch event', 'Isi per event bila ada'],
    ];

    public static function seedDefaults(): void
    {
        foreach (self::SEED as [$key, $label, $desc]) {
            if (DB::value("SELECT id FROM cert_variables WHERE scope_key = 'global' AND var_key = ?", [$key])) {
                continue;
            }
            DB::insert('cert_variables', [
                'scope' => 'global', 'scope_key' => 'global', 'event_id' => null, 'var_key' => $key, 'label' => $label,
                'var_type' => 'text', 'source' => 'manual', 'source_field' => null, 'choices' => null, 'value' => null,
                'default_value' => null, 'required' => $key === 'SIGNER_ID' || $key === 'EVENT_BATCH' ? 0 : 1,
                'overridable' => 1, 'description' => $desc, 'active' => 1, 'created_by' => null, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM cert_variables WHERE id = ?', [$id]);
    }

    public static function globals(): array
    {
        return DB::all("SELECT * FROM cert_variables WHERE scope_key = 'global' ORDER BY var_key");
    }

    public static function ofEvent(int $eventId): array
    {
        return DB::all('SELECT * FROM cert_variables WHERE scope_key = ? ORDER BY var_key', ['event:' . $eventId]);
    }

    /** @return array<int,string> variable_id => nilai override untuk event ini */
    public static function overrides(int $eventId): array
    {
        $out = [];
        foreach (DB::all('SELECT variable_id, value FROM cert_variable_values WHERE event_id = ?', [$eventId]) as $r) {
            $out[(int) $r['variable_id']] = (string) $r['value'];
        }
        return $out;
    }

    /**
     * Definisi efektif untuk satu event: variabel event menimpa variabel global dengan key sama.
     * Nilai manual sudah memperhitungkan override event lalu default.
     * @return array<string, array> key => definisi + 'resolved_manual' + 'origin'
     */
    public static function effective(int $eventId): array
    {
        $out = [];
        $ov = self::overrides($eventId);
        foreach (self::globals() as $v) {
            if (!(int) $v['active']) {
                continue;
            }
            $value = (string) ($v['value'] ?? '');
            $origin = 'global';
            if ((int) $v['overridable'] && isset($ov[(int) $v['id']]) && $ov[(int) $v['id']] !== '') {
                $value = $ov[(int) $v['id']];
                $origin = 'override';
            }
            if ($value === '' && (string) $v['default_value'] !== '') {
                $value = (string) $v['default_value'];
                $origin = 'default';
            }
            $out[$v['var_key']] = $v + ['resolved_manual' => $value, 'origin' => $origin];
        }
        foreach (self::ofEvent($eventId) as $v) {
            if (!(int) $v['active']) {
                continue;
            }
            $value = (string) ($v['value'] ?? '');
            $origin = 'event';
            if ($value === '' && (string) $v['default_value'] !== '') {
                $value = (string) $v['default_value'];
                $origin = 'default';
            }
            $out[$v['var_key']] = $v + ['resolved_manual' => $value, 'origin' => $origin];
        }
        return $out;
    }

    public static function choices(array $v): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", (string) $v['choices'])), static fn ($c) => $c !== ''));
    }

    /** Validasi satu nilai terhadap tipe variabel. @return string|null pesan error */
    public static function checkValue(array $v, string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        if (mb_strlen($value) > 500) {
            return t(':key terlalu panjang (maks. 500 karakter).', ['key' => $v['var_key']]);
        }
        return match ($v['var_type']) {
            'number' => preg_match('/^-?\d+(?:[.,]\d+)?$/', $value) ? null : t(':key harus berupa angka.', ['key' => $v['var_key']]),
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) !== false ? null : t(':key harus berupa tanggal (YYYY-MM-DD).', ['key' => $v['var_key']]),
            'choice' => in_array($value, self::choices($v), true) ? null : t(':key harus salah satu dari pilihan yang tersedia.', ['key' => $v['var_key']]),
            default => null,
        };
    }

    /** Validasi data form definisi variabel. @return array<string,string> */
    public static function validate(array $d, string $scopeKey, int $exceptId = 0): array
    {
        $e = [];
        if (!preg_match(self::KEY_RE, $d['var_key'])) {
            $e['var_key'] = t('Key memakai huruf kapital, angka, dan garis bawah (2–40 karakter), diawali huruf.');
        } elseif (isset(\App\Services\Cert\Placeholders::BUILTIN[$d['var_key']]) || isset(\App\Services\Cert\Placeholders::ISSUE_KEYS[$d['var_key']])) {
            $e['var_key'] = t('Key ini sudah dipakai variabel bawaan sistem.');
        } elseif (DB::value('SELECT id FROM cert_variables WHERE scope_key = ? AND var_key = ? AND id <> ?', [$scopeKey, $d['var_key'], $exceptId])) {
            $e['var_key'] = t('Key sudah ada pada cakupan ini.');
        }
        if (mb_strlen($d['label']) < 2 || mb_strlen($d['label']) > 120) {
            $e['label'] = t('Label wajib diisi (2–120 karakter).');
        }
        if (!isset(self::TYPES[$d['var_type']])) {
            $e['var_type'] = t('Tipe tidak dikenal.');
        }
        if (!isset(self::SOURCES[$d['source']])) {
            $e['source'] = t('Sumber nilai tidak dikenal.');
        } elseif ($d['source'] === 'participant_field' && !isset(self::PARTICIPANT_FIELDS[$d['source_field']])) {
            $e['source_field'] = t('Pilih kolom data peserta yang tersedia.');
        } elseif ($d['source'] === 'event_field' && !isset(self::EVENT_FIELDS[$d['source_field']])) {
            $e['source_field'] = t('Pilih kolom data event yang tersedia.');
        } elseif ($d['source'] === 'generator' && !isset(self::GENERATORS[$d['source_field']])) {
            $e['source_field'] = t('Pilih generator yang terdaftar.');
        }
        if (($d['var_type'] === 'generator') !== ($d['source'] === 'generator') && isset(self::TYPES[$d['var_type']], self::SOURCES[$d['source']])) {
            $e['var_type'] = t('Tipe "Otomatis" harus memakai sumber "Generator sistem", dan sebaliknya.');
        }
        if ($d['var_type'] === 'choice' && count(self::choices($d)) < 2) {
            $e['choices'] = t('Isi minimal dua pilihan, satu per baris.');
        }
        foreach (['value', 'default_value'] as $f) {
            if ($d['source'] === 'manual' && $d[$f] !== '' && ($err = self::checkValue($d, $d[$f]))) {
                $e[$f] = $err;
            }
        }
        if (mb_strlen($d['description']) > 255) {
            $e['description'] = t('Keterangan maksimal 255 karakter.');
        }
        return $e;
    }

    public static function save(array $d, string $scope, ?int $eventId, int $by, int $id = 0): int
    {
        $row = [
            'label' => $d['label'], 'var_type' => $d['var_type'], 'source' => $d['source'],
            'source_field' => $d['source'] === 'manual' ? null : $d['source_field'],
            'choices' => $d['var_type'] === 'choice' ? implode("\n", self::choices($d)) : null,
            'value' => $d['source'] === 'manual' && $d['value'] !== '' ? $d['value'] : null,
            'default_value' => $d['default_value'] !== '' ? $d['default_value'] : null,
            'required' => $d['required'] ? 1 : 0, 'overridable' => $scope === 'global' && $d['overridable'] ? 1 : 0,
            'description' => $d['description'] !== '' ? $d['description'] : null, 'active' => $d['active'] ? 1 : 0, 'updated_at' => now(),
        ];
        if ($id) {
            DB::update('cert_variables', $row, 'id = ?', [$id]);
            return $id;
        }
        return DB::insert('cert_variables', $row + [
            'scope' => $scope, 'scope_key' => $scope === 'global' ? 'global' : 'event:' . $eventId, 'event_id' => $eventId,
            'var_key' => $d['var_key'], 'created_by' => $by, 'created_at' => now(),
        ]);
    }

    public static function setOverride(int $variableId, int $eventId, string $value, int $by): void
    {
        if ($value === '') {
            DB::run('DELETE FROM cert_variable_values WHERE variable_id = ? AND event_id = ?', [$variableId, $eventId]);
            return;
        }
        if (DB::value('SELECT id FROM cert_variable_values WHERE variable_id = ? AND event_id = ?', [$variableId, $eventId])) {
            DB::update('cert_variable_values', ['value' => $value, 'updated_by' => $by, 'updated_at' => now()], 'variable_id = ? AND event_id = ?', [$variableId, $eventId]);
        } else {
            DB::insert('cert_variable_values', ['variable_id' => $variableId, 'event_id' => $eventId, 'value' => $value, 'updated_by' => $by, 'updated_at' => now()]);
        }
    }
}

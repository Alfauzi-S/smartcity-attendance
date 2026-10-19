<?php
declare(strict_types=1);

namespace App\Services\Cert;

use App\Models\CertVariable;

/**
 * Mesin placeholder {KEY}. Hanya substitusi teks: tidak ada eval, HTML, atau eksekusi kode.
 * Nilai diambil dari: data bawaan (peserta/event/penerbitan) atau variabel kustom terdaftar.
 */
final class Placeholders
{
    public const RE = '/\{([A-Z][A-Z0-9_]{1,39})\}/';

    /** Variabel bawaan dari data peserta dan event. */
    public const BUILTIN = [
        'PARTICIPANT_NAME' => ['Nama lengkap peserta', 'participant'],
        'PARTICIPANT_ID' => ['Nomor identitas peserta (NIM/NIK)', 'participant'],
        'PARTICIPANT_INSTITUTION' => ['Institusi peserta', 'participant'],
        'PARTICIPANT_STUDY_PROGRAM' => ['Program studi peserta', 'participant'],
        'PARTICIPANT_PROFESSION' => ['Profesi peserta', 'participant'],
        'PARTICIPANT_CODE' => ['Kode tiket peserta', 'participant'],
        'EVENT_NAME' => ['Nama event', 'event'],
        'EVENT_ID' => ['ID event di sistem', 'event'],
        'EVENT_DATE' => ['Tanggal event (17 Oktober 2026)', 'event'],
        'EVENT_LOCATION' => ['Lokasi event', 'event'],
        'EVENT_TIME' => ['Jam event (08:00–12:00)', 'event'],
        'ORGANIZER_NAME' => ['Penyelenggara event', 'event'],
    ];

    /** Variabel penerbitan; nilainya baru ada saat nomor dialokasikan. */
    public const ISSUE_KEYS = [
        'CERTIFICATE_NUMBER' => 'Nomor sertifikat final',
        'SEQUENCE' => 'Nomor urut',
        'SEQUENCE_PADDED' => 'Nomor urut dengan nol di depan',
        'NUMBER' => 'Sama dengan SEQUENCE_PADDED',
        'ISSUE_DATE' => 'Tanggal terbit (17 Oktober 2026)',
        'DAY' => 'Tanggal terbit, angka hari (17)',
        'MONTH' => 'Bulan terbit, dua digit (10)',
        'MONTH_NAME' => 'Nama bulan terbit (Oktober)',
        'ROMAN_MONTH' => 'Bulan terbit angka Romawi (X)',
        'YEAR' => 'Tahun terbit (2026)',
        'VERIFICATION_URL' => 'Alamat halaman verifikasi',
    ];

    private const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    private const ROMAN = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    /** @return string[] key unik yang dipakai di teks */
    public static function keys(string $text): array
    {
        preg_match_all(self::RE, $text, $m);
        return array_values(array_unique($m[1]));
    }

    /** Tanggal panjang berbahasa Indonesia, tidak bergantung bahasa tampilan admin. */
    public static function longDate(?string $date): string
    {
        $ts = $date ? strtotime($date) : false;
        if ($ts === false) {
            return '';
        }
        return (int) date('j', $ts) . ' ' . self::MONTHS[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    }

    /** Token tanggal berdasarkan tanggal terbit. */
    public static function dateTokens(string $issueDate): array
    {
        $ts = strtotime($issueDate) ?: time();
        $n = (int) date('n', $ts);
        return [
            'ISSUE_DATE' => self::longDate(date('Y-m-d', $ts)),
            'DAY' => date('d', $ts),
            'MONTH' => date('m', $ts),
            'MONTH_NAME' => self::MONTHS[$n - 1],
            'ROMAN_MONTH' => self::ROMAN[$n - 1],
            'YEAR' => date('Y', $ts),
        ];
    }

    private static function builtin(string $key, array $event, ?array $p): ?string
    {
        return match ($key) {
            'PARTICIPANT_NAME' => $p['full_name'] ?? null,
            'PARTICIPANT_ID' => $p['identity_number'] ?? null,
            'PARTICIPANT_INSTITUTION' => $p['institution'] ?? null,
            'PARTICIPANT_STUDY_PROGRAM' => $p['field_of_study'] ?? null,
            'PARTICIPANT_PROFESSION' => $p['profession'] ?? null,
            'PARTICIPANT_CODE' => $p['code'] ?? null,
            'EVENT_NAME' => $event['name'],
            'EVENT_ID' => (string) $event['id'],
            'EVENT_DATE' => self::longDate($event['event_date']),
            'EVENT_LOCATION' => $event['location'],
            'EVENT_TIME' => $event['start_time'] . '–' . ($event['end_time'] !== '' ? $event['end_time'] : 'selesai'), // PDF selalu berbahasa Indonesia
            'ORGANIZER_NAME' => $event['organizer'],
            default => null,
        };
    }

    private static function generator(string $name, array $event, ?array $p, string $issueDate): ?string
    {
        $fullName = (string) ($p['full_name'] ?? '');
        return match ($name) {
            'name_upper' => $p ? mb_strtoupper($fullName) : null,
            'name_title' => $p ? mb_convert_case($fullName, MB_CASE_TITLE) : null,
            'issue_date_long' => self::longDate($issueDate),
            'issue_date_numeric' => date('d/m/Y', strtotime($issueDate) ?: time()),
            'event_date_long' => self::longDate($event['event_date']),
            'event_year' => substr((string) $event['event_date'], 0, 4),
            default => null,
        };
    }

    /**
     * Isi nilai untuk daftar key.
     * $issue berisi nilai penerbitan (CERTIFICATE_NUMBER, SEQUENCE, ...); key penerbitan yang belum tersedia
     * dianggap valid bila $allowPendingIssue (dipakai saat validasi sebelum nomor dialokasikan).
     * @return array{0: array<string,string>, 1: array<string,string>} [nilai, error per key]
     */
    public static function resolve(array $keys, array $event, ?array $participant, array $vars, string $issueDate, array $issue = [], bool $allowPendingIssue = false): array
    {
        $values = [];
        $errors = [];
        $dates = self::dateTokens($issueDate);
        foreach ($keys as $key) {
            if (isset(self::ISSUE_KEYS[$key])) {
                $v = $issue[$key] ?? $dates[$key] ?? null;
                if ($v === null || $v === '') {
                    if (!$allowPendingIssue) {
                        $errors[$key] = t('Variabel :key belum tersedia saat penerbitan.', ['key' => $key]);
                    }
                    continue;
                }
                $values[$key] = (string) $v;
                continue;
            }
            if (isset(self::BUILTIN[$key])) {
                if (self::BUILTIN[$key][1] === 'participant' && $participant === null) {
                    continue; // divalidasi per peserta saat generate
                }
                $v = trim((string) self::builtin($key, $event, $participant));
                if ($v === '') {
                    $errors[$key] = t('Variabel :key tidak mempunyai sumber data yang valid.', ['key' => $key]);
                } else {
                    $values[$key] = $v;
                }
                continue;
            }
            $def = $vars[$key] ?? null;
            if ($def === null) {
                $errors[$key] = t('Variabel {:key} tidak dikenal atau tidak aktif.', ['key' => $key]);
                continue;
            }
            $v = match ($def['source']) {
                'manual' => $def['resolved_manual'],
                'participant_field' => isset(CertVariable::PARTICIPANT_FIELDS[$def['source_field']])
                    ? ($participant === null ? null : (string) ($participant[$def['source_field']] ?? ''))
                    : false,
                'event_field' => isset(CertVariable::EVENT_FIELDS[$def['source_field']]) ? (string) ($event[$def['source_field']] ?? '') : false,
                'generator' => isset(CertVariable::GENERATORS[$def['source_field']])
                    ? (str_starts_with((string) $def['source_field'], 'name_') && $participant === null ? null : self::generator((string) $def['source_field'], $event, $participant, $issueDate))
                    : false,
                default => false,
            };
            if ($v === false) {
                $errors[$key] = t('Variabel :key tidak mempunyai sumber data yang valid.', ['key' => $key]);
                continue;
            }
            if ($v === null) {
                continue; // butuh data peserta; dicek saat generate
            }
            $v = trim($v);
            if ($v === '') {
                if ((int) $def['required']) {
                    $errors[$key] = $def['source'] === 'manual'
                        ? t(':key belum diisi.', ['key' => $key])
                        : t(':key belum dikonfigurasi.', ['key' => $key]);
                } else {
                    $values[$key] = ''; // variabel opsional: dikosongkan secara eksplisit
                }
                continue;
            }
            if ($err = CertVariable::checkValue($def, $v)) {
                $errors[$key] = $err;
                continue;
            }
            $values[$key] = $def['var_type'] === 'date' ? self::longDate($v) : $v;
        }
        return [$values, $errors];
    }

    /** Ganti placeholder dengan nilai. Placeholder tanpa nilai ditampilkan sebagai ⟨KEY⟩ (hanya terjadi di pratinjau). */
    public static function render(string $text, array $values): string
    {
        return (string) preg_replace_callback(self::RE, static fn ($m) => array_key_exists($m[1], $values) ? $values[$m[1]] : '⟨' . $m[1] . '⟩', $text);
    }
}

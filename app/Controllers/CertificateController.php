<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database as DB;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\CertAsset;
use App\Models\CertIssuance;
use App\Models\CertNumbering;
use App\Models\CertTemplate;
use App\Models\CertVariable;
use App\Models\Participant;
use App\Services\Access;
use App\Services\Cert\CertError;
use App\Services\Cert\Issuer;
use App\Services\Cert\Placeholders;
use App\Services\Cert\Upload;

/**
 * Menu Sertifikat (Super Admin dan Admin Event).
 * Setiap aksi mengambil objek dari database lalu memeriksa izin pada event pemilik objek tersebut,
 * sehingga mengganti ID di URL/form tidak membuka data event lain.
 */
final class CertificateController extends Controller
{
    private const PER_PAGE = 25;

    /* ---------- bantuan umum ---------- */

    private function requireMigrated(array $user): void
    {
        if (!DB::tableExists('cert_issuances')) {
            $this->render('super/cert/migrate', ['title' => t('Sertifikat'), 'active' => 'certificates', 'isSuper' => $user['role'] === Auth::SUPER]);
            exit;
        }
    }

    /** @return array{0:array,1:array} [pengguna, event aktif] */
    private function ctx(string $perm): array
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        if (!Access::any($user, $perm)) {
            Access::deny($user, null, $perm);
        }
        $event = $this->currentEvent($user, null, $perm);
        if (!$event) {
            $this->render('super/cert/none', ['title' => t('Sertifikat'), 'active' => 'certificates']);
            exit;
        }
        return [$user, $event];
    }

    private function screen(string $tpl, string $tab, array $user, array $event, array $data): void
    {
        $this->render('super/cert/' . $tpl, $data + [
            'title' => t('Sertifikat'),
            'active' => 'certificates',
            'tab' => $tab,
            'event' => $event,
            'events' => Access::events($user, Access::CERT_ANY),
        ]);
    }

    /** Objek milik event: cek izin di event pemiliknya. */
    private function owned(array $user, int $eventId, ?string $perm): array
    {
        return Access::requireEvent($user, $eventId, $perm);
    }

    private function back(string $path, array $event, array $q = []): never
    {
        redirect('admin/sertifikat' . ($path !== '' ? '/' . $path : ''), $q + ['ev' => $event['id']]);
    }

    private function wantsJson(): bool
    {
        return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
    }

    /* ---------- dashboard ---------- */

    public function dashboard(): void
    {
        [$user, $event] = $this->ctx(Access::CERT_ANY);
        $eid = (int) $event['id'];
        $ready = Issuer::readiness($event);
        $this->screen('dashboard', 'dashboard', $user, $event, [
            'ready' => $ready,
            'summary' => Issuer::summary($eid, $ready['settings']),
            'stats' => CertIssuance::stats($eid),
            'templates' => CertTemplate::ofEvent($eid),
            'jobs' => Issuer::jobs($eid, 3),
        ]);
    }

    /* ---------- template ---------- */

    public function templates(): void
    {
        [$user, $event] = $this->ctx('cert_manage');
        $this->screen('templates', 'templates', $user, $event, ['templates' => CertTemplate::ofEvent((int) $event['id'])]);
    }

    public function templateCreate(): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        $event = $this->owned($user, (int) input('event_id'), 'cert_manage');
        $name = mb_substr(input('name'), 0, 120);
        if (mb_strlen($name) < 3) {
            flash('bad', 'Template belum dibuat', 'Nama template minimal 3 karakter.');
            $this->back('template', $event);
        }
        $id = CertTemplate::create((int) $event['id'], $name, input('preset') === 'akademik', (int) $user['id']);
        AuditLog::add($user, 'cert.template.create', (int) $event['id'], 'template', $id, ['name' => $name, 'preset' => input('preset')]);
        flash('ok', 'Template dibuat', 'Unggah desain latar, lalu atur posisi elemen.');
        redirect('admin/sertifikat/template/' . $id);
    }

    private function template(string $id, array $user, string $perm = 'cert_manage'): array
    {
        $t = CertTemplate::find((int) $id);
        if (!$t) {
            abort(404, 'Template tidak ditemukan');
        }
        $event = $this->owned($user, (int) $t['event_id'], $perm);
        return [$t, $event];
    }

    public function editor(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$t, $event] = $this->template($id, $user);
        $eid = (int) $event['id'];
        $snap = CertTemplate::snapshot($t);
        $vars = CertVariable::effective($eid);
        $keys = [];
        foreach ($snap['elements'] as $el) {
            if ($el['type'] === 'text') {
                $keys = array_merge($keys, Placeholders::keys((string) $el['content']));
            }
        }
        $settings = Issuer::settings($eid);
        [, $varErrors] = Placeholders::resolve(array_unique($keys), $event, null, $vars, Issuer::issueDate($event, $settings), [], true);
        [$w, $h] = CertTemplate::size($t);
        $assets = array_map(static fn ($a) => [
            'id' => (int) $a['id'], 'kind' => $a['kind'], 'label' => $a['label'], 'approved' => (int) $a['approved'],
            'ratio' => $a['height_px'] ? (int) $a['width_px'] / (int) $a['height_px'] : 1,
            'url' => url('admin/sertifikat/aset/' . $a['id'] . '/gambar'),
        ], CertAsset::usable($eid));
        $this->screen('editor', 'templates', $user, $event, [
            'back' => 'admin/sertifikat/template?ev=' . $eid,
            't' => $t, 'w' => $w, 'h' => $h,
            'elements' => CertTemplate::elements($t),
            'assets' => $assets,
            'vars' => $vars,
            'issues' => Issuer::elementIssues($snap, $eid, false),
            'varErrors' => $varErrors,
            'participants' => Participant::search($eid, '', 'all', 30),
            'bgUrl' => $t['background_path'] && $t['background_type'] !== 'pdf' ? url('admin/sertifikat/template/' . $t['id'] . '/latar') : '',
        ]);
    }

    public function templateSave(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$t, $event] = $this->template($id, $user);
        if ($t['status'] !== 'Draft') {
            flash('bad', 'Template tidak dapat diubah', 'Hanya template Draft yang bisa diedit. Kembalikan ke Draft terlebih dahulu.');
            redirect('admin/sertifikat/template/' . $t['id']);
        }
        [$elements, $errors] = CertTemplate::sanitize(json_decode((string) ($_POST['elements'] ?? ''), true), (int) $event['id']);
        $format = array_key_exists(input('page_format'), CertTemplate::FORMATS) ? input('page_format') : 'A4';
        $data = [
            'name' => mb_substr(input('name'), 0, 120) ?: $t['name'],
            'page_format' => $format,
            'orientation' => input('orientation') === 'P' ? 'P' : 'L',
            'elements' => json_encode($elements, JSON_UNESCAPED_UNICODE),
        ];
        if ($format === 'custom') {
            $cw = (float) str_replace(',', '.', input('width_mm'));
            $ch = (float) str_replace(',', '.', input('height_mm'));
            if ($cw < 50 || $cw > 1000 || $ch < 50 || $ch > 1000) {
                $errors[] = t('Ukuran kustom harus 50–1000 mm.');
            } else {
                $data['width_mm'] = round($cw, 2);
                $data['height_mm'] = round($ch, 2);
            }
        }
        CertTemplate::update((int) $t['id'], $data, (int) $user['id']);
        AuditLog::add($user, 'cert.template.update', (int) $event['id'], 'template', $t['id'], ['elements' => count($elements), 'format' => $format]);
        if ($errors) {
            flash('bad', 'Template disimpan dengan catatan', implode(' ', $errors));
        } else {
            flash('ok', 'Template disimpan');
        }
        redirect('admin/sertifikat/template/' . $t['id']);
    }

    public function templateBackground(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$t, $event] = $this->template($id, $user);
        if ($t['status'] !== 'Draft') {
            flash('bad', 'Template tidak dapat diubah', 'Hanya template Draft yang bisa diedit.');
            redirect('admin/sertifikat/template/' . $t['id']);
        }
        $file = $_FILES['background'] ?? null;
        try {
            $isPdf = is_array($file) && is_string($file['tmp_name'] ?? null) && is_file($file['tmp_name'])
                && (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) === 'application/pdf';
            $up = $isPdf ? Upload::pdf($file, 'backgrounds') : Upload::image($file, 'backgrounds');
        } catch (CertError $e) {
            flash('bad', 'Desain gagal diunggah', $e->getMessage());
            redirect('admin/sertifikat/template/' . $t['id']);
        }
        $data = ['background_path' => $up['path'], 'background_type' => $isPdf ? 'pdf' : ($up['mime'] === 'image/png' ? 'png' : 'jpg'), 'background_w' => $up['w'], 'background_h' => $up['h']];
        if ($t['page_format'] !== 'custom') {
            $data['orientation'] = $up['w'] >= $up['h'] ? 'L' : 'P';
        }
        CertTemplate::update((int) $t['id'], $data, (int) $user['id']);
        AuditLog::add($user, 'cert.template.background', (int) $event['id'], 'template', $t['id'], ['type' => $data['background_type'], 'w' => $up['w'], 'h' => $up['h']]);
        flash('ok', 'Desain latar diunggah', $isPdf ? t('Latar PDF tidak dapat ditampilkan di editor; gunakan tombol Pratinjau PDF untuk melihat hasil.') : '');
        redirect('admin/sertifikat/template/' . $t['id']);
    }

    /** Samakan rasio halaman dengan rasio desain latar (lebar tetap). */
    public function templateFit(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$t, $event] = $this->template($id, $user);
        if ($t['status'] !== 'Draft' || !$t['background_w'] || !$t['background_h']) {
            redirect('admin/sertifikat/template/' . $t['id']);
        }
        [$w] = CertTemplate::size($t);
        $h = round($w * (int) $t['background_h'] / (int) $t['background_w'], 2);
        if ($t['background_type'] === 'pdf') {
            [$w, $h] = [(int) $t['background_w'] / 10, (int) $t['background_h'] / 10];
        }
        CertTemplate::update((int) $t['id'], ['page_format' => 'custom', 'width_mm' => $w, 'height_mm' => $h, 'orientation' => $w >= $h ? 'L' : 'P'], (int) $user['id']);
        AuditLog::add($user, 'cert.template.update', (int) $event['id'], 'template', $t['id'], ['fit_page' => [$w, $h]]);
        flash('ok', 'Ukuran halaman disesuaikan dengan desain', t(':w × :h mm', ['w' => $w, 'h' => $h]));
        redirect('admin/sertifikat/template/' . $t['id']);
    }

    public function templateStatus(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$t, $event] = $this->template($id, $user);
        $action = input('action');
        if ($action === 'activate') {
            if ($t['status'] !== 'Draft') {
                flash('bad', 'Hanya template Draft yang bisa diaktifkan');
                redirect('admin/sertifikat/template/' . $t['id']);
            }
            $snap = CertTemplate::snapshot($t);
            $bad = array_filter(Issuer::elementIssues($snap, (int) $event['id'], false), static fn ($i) => $i[0] === 'bad');
            $unknown = [];
            foreach ($snap['elements'] as $el) {
                foreach ($el['type'] === 'text' ? Placeholders::keys((string) $el['content']) : [] as $k) {
                    $vars ??= CertVariable::effective((int) $event['id']);
                    if (!isset(Placeholders::BUILTIN[$k]) && !isset(Placeholders::ISSUE_KEYS[$k]) && !isset($vars[$k])) {
                        $unknown[] = '{' . $k . '}';
                    }
                }
            }
            if ($bad || $unknown) {
                $msg = implode(' ', array_column($bad, 1));
                if ($unknown) {
                    $msg .= ' ' . t('Variabel tidak dikenal: :list.', ['list' => implode(', ', array_unique($unknown))]);
                }
                flash('bad', 'Template belum bisa diaktifkan', trim($msg));
                redirect('admin/sertifikat/template/' . $t['id']);
            }
            $vid = CertTemplate::activate($t, (int) $user['id']);
            AuditLog::add($user, 'cert.template.status', (int) $event['id'], 'template', $t['id'], ['status' => 'Active', 'version_id' => $vid]);
            flash('ok', 'Template diaktifkan', 'Versi template disimpan. Template Active lain di event ini diarsipkan.');
        } elseif ($action === 'draft' && in_array($t['status'], ['Active', 'Archived'], true)) {
            CertTemplate::setStatus((int) $t['id'], 'Draft', (int) $user['id']);
            AuditLog::add($user, 'cert.template.status', (int) $event['id'], 'template', $t['id'], ['status' => 'Draft']);
            flash('ok', 'Template dikembalikan ke Draft', 'Sertifikat yang sudah terbit tidak berubah.');
        } elseif ($action === 'archive' && $t['status'] !== 'Archived') {
            CertTemplate::setStatus((int) $t['id'], 'Archived', (int) $user['id']);
            AuditLog::add($user, 'cert.template.status', (int) $event['id'], 'template', $t['id'], ['status' => 'Archived']);
            flash('ok', 'Template diarsipkan', 'Riwayat dan sertifikat yang sudah terbit tetap tersimpan.');
        } else {
            flash('bad', 'Perubahan status tidak valid');
        }
        redirect('admin/sertifikat/template/' . $t['id']);
    }

    public function templatePreview(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$t, $event] = $this->template($id, $user);
        $participant = null;
        if (query('peserta') !== '') {
            $participant = Participant::find((int) query('peserta'));
            if (!$participant || (int) $participant['event_id'] !== (int) $event['id']) {
                abort(404, 'Peserta tidak ditemukan');
            }
        }
        try {
            [$pdf] = Issuer::preview($event, $t, $participant);
        } catch (CertError $e) {
            abort(422, 'Pratinjau gagal', $e->getMessage());
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="pratinjau-sertifikat.pdf"');
        header('Cache-Control: no-store');
        echo $pdf;
    }

    public function templateBackgroundFile(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$t] = $this->template($id, $user);
        $file = Upload::abs($t['background_path']);
        if (!$file || $t['background_type'] === 'pdf') {
            abort(404, 'File tidak ditemukan');
        }
        $this->sendImage($file);
    }

    private function sendImage(string $file): never
    {
        header('Content-Type: ' . ((new \finfo(FILEINFO_MIME_TYPE))->file($file) === 'image/png' ? 'image/png' : 'image/jpeg'));
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: private, max-age=3600');
        readfile($file);
        exit;
    }

    /* ---------- aset ---------- */

    public function assets(): void
    {
        [$user, $event] = $this->ctx('cert_manage');
        $this->screen('assets', 'assets', $user, $event, ['assets' => CertAsset::usable((int) $event['id'], query('arsip') === '1')]);
    }

    public function assetUpload(): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        $event = $this->owned($user, (int) input('event_id'), 'cert_manage');
        $kind = array_key_exists(input('kind'), CertAsset::KINDS) ? input('kind') : '';
        $label = mb_substr(input('label'), 0, 120);
        $global = input('scope') === 'global';
        if ($global && $user['role'] !== Auth::SUPER) {
            Access::deny($user, (int) $event['id'], 'cert_asset_global');
        }
        if ($kind === '' || mb_strlen($label) < 2) {
            flash('bad', 'Aset gagal diunggah', 'Pilih jenis aset dan isi nama aset.');
            $this->back('aset', $event);
        }
        try {
            $up = Upload::image($_FILES['file'] ?? null, 'assets');
        } catch (CertError $e) {
            flash('bad', 'Aset gagal diunggah', $e->getMessage());
            $this->back('aset', $event);
        }
        $id = CertAsset::create($global ? null : (int) $event['id'], $kind, $label, $up, $user);
        AuditLog::add($user, 'cert.asset.upload', $global ? null : (int) $event['id'], 'asset', $id, ['kind' => $kind, 'label' => $label, 'global' => $global]);
        $needs = in_array($kind, CertAsset::NEEDS_APPROVAL, true) && $user['role'] !== Auth::SUPER;
        flash('ok', 'Aset diunggah', $needs ? t('Tanda tangan dan stempel harus disetujui Super Admin sebelum dipakai di sertifikat final.') : '');
        $this->back('aset', $event);
    }

    private function asset(string $id, array $user, string $perm): array
    {
        $a = CertAsset::find((int) $id);
        if (!$a) {
            abort(404, 'Aset tidak ditemukan');
        }
        if ($a['event_id'] === null) {
            if (!Access::any($user, $perm)) {
                Access::deny($user, null, $perm);
            }
        } else {
            $this->owned($user, (int) $a['event_id'], $perm);
        }
        return $a;
    }

    public function assetImage(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        $a = $this->asset($id, $user, Access::CERT_ANY);
        $file = Upload::abs($a['file_path']);
        if (!$file) {
            abort(404, 'File tidak ditemukan');
        }
        $this->sendImage($file);
    }

    public function assetApprove(string $id): void
    {
        $user = Auth::require(Auth::SUPER);
        $this->requireMigrated($user);
        $a = $this->asset($id, $user, 'cert_manage');
        DB::update('cert_assets', ['approved' => 1, 'approved_by' => (int) $user['id'], 'approved_at' => now()], 'id = ?', [(int) $a['id']]);
        AuditLog::add($user, 'cert.asset.approve', $a['event_id'] ? (int) $a['event_id'] : null, 'asset', $a['id'], ['kind' => $a['kind'], 'label' => $a['label']]);
        flash('ok', 'Aset disetujui', $a['label']);
        redirect('admin/sertifikat/aset', ['ev' => input('ev')]);
    }

    public function assetArchive(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        $a = $this->asset($id, $user, 'cert_manage');
        if ($a['event_id'] === null && $user['role'] !== Auth::SUPER) {
            Access::deny($user, null, 'cert_asset_global');
        }
        $archived = (int) $a['archived'] ? 0 : 1;
        DB::update('cert_assets', ['archived' => $archived], 'id = ?', [(int) $a['id']]);
        AuditLog::add($user, 'cert.asset.archive', $a['event_id'] ? (int) $a['event_id'] : null, 'asset', $a['id'], ['archived' => $archived]);
        flash('ok', $archived ? 'Aset diarsipkan' : 'Aset dipulihkan', $archived ? t('Template yang memakai aset ini harus diganti asetnya sebelum generate.') : '');
        redirect('admin/sertifikat/aset', ['ev' => input('ev')]);
    }

    /* ---------- variabel ---------- */

    public function variables(): void
    {
        [$user, $event] = $this->ctx('cert_manage');
        $eid = (int) $event['id'];
        $edit = query('ubah') !== '' ? CertVariable::find((int) query('ubah')) : null;
        if ($edit && !($edit['scope'] === 'global' ? $user['role'] === Auth::SUPER : (int) $edit['event_id'] === $eid)) {
            $edit = null;
        }
        $vars = CertVariable::effective($eid);
        $errors = [];
        foreach ($vars as $k => $v) {
            if ($v['source'] === 'manual') {
                if ((int) $v['required'] && $v['resolved_manual'] === '') {
                    $errors[$k] = t(':key belum diisi.', ['key' => $k]);
                } elseif ($err = CertVariable::checkValue($v, $v['resolved_manual'])) {
                    $errors[$k] = $err;
                }
            }
        }
        $this->screen('variables', 'variables', $user, $event, [
            'globals' => CertVariable::globals(),
            'eventVars' => CertVariable::ofEvent($eid),
            'overrides' => CertVariable::overrides($eid),
            'effective' => $vars,
            'varErrors' => $errors,
            'edit' => $edit,
            'formErrors' => $_SESSION['var_errors'] ?? [],
            'old' => $_SESSION['var_old'] ?? null,
        ]);
        unset($_SESSION['var_errors'], $_SESSION['var_old']);
    }

    public function variableSave(): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        $event = $this->owned($user, (int) input('event_id'), 'cert_manage');
        $id = (int) input('id');
        $existing = $id ? CertVariable::find($id) : null;
        if ($id && !$existing) {
            abort(404, 'Variabel tidak ditemukan');
        }
        $scope = $existing ? $existing['scope'] : (input('scope') === 'global' ? 'global' : 'event');
        if ($scope === 'global' && $user['role'] !== Auth::SUPER) {
            Access::deny($user, (int) $event['id'], 'cert_variable_global');
        }
        if ($existing && $scope === 'event' && (int) $existing['event_id'] !== (int) $event['id']) {
            $event = $this->owned($user, (int) $existing['event_id'], 'cert_manage');
        }
        $d = [
            'var_key' => $existing ? $existing['var_key'] : strtoupper(input('var_key')),
            'label' => input('label'), 'var_type' => input('var_type', 'text'), 'source' => input('source', 'manual'),
            'source_field' => input('source_field'), 'choices' => (string) ($_POST['choices'] ?? ''), 'value' => input('value'),
            'default_value' => input('default_value'), 'required' => input('required') === '1', 'overridable' => input('overridable') === '1',
            'description' => input('description'), 'active' => input('active') === '1',
        ];
        $scopeKey = $scope === 'global' ? 'global' : 'event:' . $event['id'];
        $errors = CertVariable::validate($d, $scopeKey, $id);
        if ($errors) {
            $_SESSION['var_errors'] = $errors;
            $_SESSION['var_old'] = $d + ['id' => $id, 'scope' => $scope];
            flash('bad', 'Variabel belum disimpan', 'Periksa isian yang ditandai.');
            $this->back('variabel', $event, $id ? ['ubah' => $id] : []);
        }
        $vid = CertVariable::save($d, $scope, $scope === 'global' ? null : (int) $event['id'], (int) $user['id'], $id);
        AuditLog::add($user, $id ? 'cert.variable.update' : 'cert.variable.create', $scope === 'global' ? null : (int) $event['id'], 'variable', $vid, [
            'key' => $d['var_key'], 'scope' => $scope, 'type' => $d['var_type'], 'source' => $d['source'], 'active' => $d['active'],
        ]);
        flash('ok', $id ? 'Variabel diperbarui' : 'Variabel ditambahkan', '{' . $d['var_key'] . '}');
        $this->back('variabel', $event);
    }

    /** Nilai khusus event untuk variabel global yang boleh di-override. */
    public function variableValue(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        $event = $this->owned($user, (int) input('event_id'), 'cert_manage');
        $v = CertVariable::find((int) $id);
        if (!$v || $v['scope'] !== 'global' || !(int) $v['overridable']) {
            abort(404, 'Variabel tidak ditemukan');
        }
        $value = mb_substr(input('value'), 0, 500);
        if ($err = CertVariable::checkValue($v, $value)) {
            flash('bad', 'Nilai belum disimpan', $err);
            $this->back('variabel', $event);
        }
        CertVariable::setOverride((int) $v['id'], (int) $event['id'], $value, (int) $user['id']);
        AuditLog::add($user, 'cert.variable.value', (int) $event['id'], 'variable', $v['id'], ['key' => $v['var_key'], 'value' => $value]);
        flash('ok', 'Nilai variabel untuk event ini disimpan', '{' . $v['var_key'] . '}');
        $this->back('variabel', $event);
    }

    /* ---------- penomoran ---------- */

    public function numbering(): void
    {
        [$user, $event] = $this->ctx('cert_manage');
        $eid = (int) $event['id'];
        $versions = CertNumbering::versions($eid);
        $settings = Issuer::settings($eid);
        $issueDate = Issuer::issueDate($event, $settings);
        $examples = [];
        foreach (array_slice($versions, 0, 5) as $n) {
            [$values, $errors] = Placeholders::resolve(array_diff(Placeholders::keys($n['pattern']), CertNumbering::SEQ_KEYS), $event, null, CertVariable::effective($eid), $issueDate, [], true);
            $examples[$n['id']] = [CertNumbering::format($n, CertNumbering::peek($n, $issueDate), $values + Placeholders::dateTokens($issueDate)), $errors];
        }
        $this->screen('numbering', 'numbering', $user, $event, [
            'versions' => $versions, 'examples' => $examples, 'settings' => $settings,
            'used' => (int) DB::value('SELECT COUNT(*) FROM cert_issuances WHERE event_id = ?', [$eid]),
            'formErrors' => $_SESSION['num_errors'] ?? [], 'old' => $_SESSION['num_old'] ?? ($versions[0] ?? null),
        ]);
        unset($_SESSION['num_errors'], $_SESSION['num_old']);
    }

    public function numberingCreate(): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        $event = $this->owned($user, (int) input('event_id'), 'cert_manage');
        $d = ['pattern' => trim(input('pattern')), 'seq_padding' => (int) input('seq_padding', '4'), 'seq_scope' => input('seq_scope', 'event'),
            'seq_reset' => input('seq_reset', 'never'), 'note' => mb_substr(input('note'), 0, 255)];
        if ($d['seq_scope'] === 'global' && $user['role'] !== Auth::SUPER) {
            $errors = ['seq_scope' => t('Urutan global hanya dapat diatur Super Admin.')];
        } else {
            $errors = CertNumbering::validate($d, (int) $event['id']);
        }
        if ($errors) {
            $_SESSION['num_errors'] = $errors;
            $_SESSION['num_old'] = $d;
            flash('bad', 'Pola nomor belum disimpan', 'Periksa isian yang ditandai.');
            $this->back('nomor', $event);
        }
        $id = CertNumbering::createDraft((int) $event['id'], $d, (int) $user['id']);
        AuditLog::add($user, 'cert.numbering.create', (int) $event['id'], 'numbering', $id, $d);
        flash('ok', 'Pola nomor disimpan sebagai Draft', 'Super Admin perlu menyetujui pola ini setelah format dikonfirmasi fakultas/TU.');
        $this->back('nomor', $event);
    }

    public function numberingApprove(string $id): void
    {
        $user = Auth::require(Auth::SUPER);
        $this->requireMigrated($user);
        $n = CertNumbering::find((int) $id);
        if (!$n || $n['status'] !== 'draft') {
            abort(404, 'Pola nomor tidak ditemukan');
        }
        $event = $this->owned($user, (int) $n['event_id'], null);
        CertNumbering::approve($n, (int) $user['id']);
        AuditLog::add($user, 'cert.numbering.approve', (int) $event['id'], 'numbering', $n['id'], ['version' => (int) $n['version'], 'pattern' => $n['pattern']]);
        flash('ok', 'Pola nomor disetujui', $n['pattern']);
        $this->back('nomor', $event);
    }

    /* ---------- kelayakan ---------- */

    public function eligibility(): void
    {
        [$user, $event] = $this->ctx('cert_manage');
        $eid = (int) $event['id'];
        $settings = Issuer::settings($eid);
        $filter = in_array(query('f'), ['layak', 'tidak', 'belum', 'semua'], true) ? query('f') : 'belum';
        $q = mb_substr(query('q'), 0, 80);
        $page = $this->page();
        [$rows, $count] = Issuer::recipients($eid, $settings, $filter, $q, self::PER_PAGE, ($page - 1) * self::PER_PAGE);
        $this->screen('eligibility', 'eligibility', $user, $event, [
            'settings' => $settings, 'summary' => Issuer::summary($eid, $settings), 'rows' => $rows, 'count' => $count,
            'filter' => $filter, 'q' => $q, 'pg' => $page, 'pages' => (int) ceil($count / self::PER_PAGE),
            'ready' => Issuer::readiness($event)['ok'],
        ]);
    }

    public function eligibilitySave(): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        $event = $this->owned($user, (int) input('event_id'), 'cert_manage');
        $old = Issuer::settings((int) $event['id']);
        $mode = array_key_exists(input('eligibility'), Issuer::ELIGIBILITY) ? input('eligibility') : 'hadir';
        if ($mode === 'semua' && $user['role'] !== Auth::SUPER && $old['eligibility'] !== 'semua') {
            flash('bad', 'Pengaturan belum disimpan', 'Mode seluruh peserta terdaftar hanya dapat diaktifkan Super Admin.');
            $this->back('kelayakan', $event);
        }
        $date = input('issue_date');
        if ($date !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false)) {
            flash('bad', 'Pengaturan belum disimpan', 'Tanggal terbit tidak valid.');
            $this->back('kelayakan', $event);
        }
        $data = ['eligibility' => $mode, 'issue_date' => $date !== '' ? $date : null];
        if ($user['role'] === Auth::SUPER) {
            $data['require_approved_number'] = input('require_approved_number') === '1' ? 1 : 0;
        }
        Issuer::saveSettings((int) $event['id'], $data, (int) $user['id']);
        AuditLog::add($user, 'cert.settings', (int) $event['id'], 'settings', $event['id'], ['old' => array_intersect_key($old, $data), 'new' => $data]);
        flash('ok', 'Pengaturan kelayakan disimpan');
        $this->back('kelayakan', $event);
    }

    /* ---------- generate ---------- */

    public function generatePage(): void
    {
        [$user, $event] = $this->ctx('cert_manage');
        $ready = Issuer::readiness($event);
        $this->screen('generate', 'generate', $user, $event, [
            'ready' => $ready, 'summary' => Issuer::summary((int) $event['id'], $ready['settings']), 'jobs' => Issuer::jobs((int) $event['id'], 15),
        ]);
    }

    public function generateStart(): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        $event = $this->owned($user, (int) input('event_id'), 'cert_manage');
        $ready = Issuer::readiness($event);
        if (!$ready['ok']) {
            flash('bad', 'Generate diblokir', 'Konfigurasi sertifikat belum lengkap. Lihat laporan validasi.');
            $this->back('generate', $event);
        }
        $pending = Issuer::pendingIds((int) $event['id'], $ready['settings']);
        if (input('mode') === 'selected') {
            // ID dari browser hanya dipakai bila termasuk peserta layak event ini yang belum punya sertifikat
            $picked = array_map('intval', array_filter((array) ($_POST['ids'] ?? []), 'is_numeric'));
            $ids = array_values(array_intersect($pending, $picked));
        } else {
            $ids = $pending;
        }
        if (!$ids) {
            flash('info', 'Tidak ada sertifikat yang perlu dibuat', 'Semua peserta layak sudah memiliki sertifikat, atau pilihan tidak memenuhi syarat.');
            $this->back(input('mode') === 'selected' ? 'kelayakan' : 'generate', $event);
        }
        $jid = Issuer::createJob($event, $ids, $ready, $user);
        AuditLog::add($user, 'cert.job.create', (int) $event['id'], 'job', $jid, ['total' => count($ids), 'mode' => input('mode', 'all')]);
        redirect('admin/sertifikat/job/' . $jid);
    }

    public function generateOne(string $participantId): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        $p = Participant::find((int) $participantId);
        if (!$p) {
            abort(404, 'Peserta tidak ditemukan');
        }
        $event = $this->owned($user, (int) $p['event_id'], 'cert_manage');
        try {
            $r = Issuer::issue($event, $p, $user, Issuer::readiness($event));
            AuditLog::add($user, 'cert.generate', (int) $event['id'], 'certificate', $r['id'], ['number' => $r['cert_number'], 'single' => true]);
            flash('ok', 'Sertifikat dibuat', t(':name · :number', ['name' => $p['full_name'], 'number' => $r['cert_number']]));
            redirect('admin/sertifikat/' . $r['id']);
        } catch (CertError $e) {
            AuditLog::add($user, 'cert.generate.failed', (int) $event['id'], 'participant', $p['id'], ['message' => $e->getMessage()], 'failed');
            flash('bad', 'Sertifikat gagal dibuat', $e->getMessage());
            $this->back('kelayakan', $event);
        }
    }

    private function jobFor(string $id, array $user): array
    {
        $job = Issuer::job((int) $id);
        if (!$job) {
            abort(404, 'Proses tidak ditemukan');
        }
        return [$job, $this->owned($user, (int) $job['event_id'], 'cert_manage')];
    }

    public function job(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$job, $event] = $this->jobFor($id, $user);
        $this->screen('job', 'generate', $user, $event, ['job' => $job, 'errors' => json_decode((string) $job['errors'], true) ?: [], 'back' => 'admin/sertifikat/generate?ev=' . $event['id']]);
    }

    public function jobStep(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$job, $event] = $this->jobFor($id, $user);
        $result = Issuer::step($job, $event, $user);
        if ($this->wantsJson()) {
            json_out(['ok' => true] + $result);
        }
        redirect('admin/sertifikat/job/' . $job['id']);
    }

    /** Ulangi peserta yang gagal: job baru hanya untuk yang masih belum punya sertifikat. */
    public function jobRetry(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$job, $event] = $this->jobFor($id, $user);
        $ready = Issuer::readiness($event);
        $failed = array_map(static fn ($e) => (int) $e['pid'], json_decode((string) $job['errors'], true) ?: []);
        $ids = array_values(array_intersect(Issuer::pendingIds((int) $event['id'], $ready['settings']), $failed));
        if (!$ready['ok'] || !$ids) {
            flash('info', 'Tidak ada yang bisa diulang', $ready['ok'] ? '' : t('Konfigurasi sertifikat belum lengkap.'));
            redirect('admin/sertifikat/job/' . $job['id']);
        }
        $jid = Issuer::createJob($event, $ids, $ready, $user);
        AuditLog::add($user, 'cert.job.create', (int) $event['id'], 'job', $jid, ['total' => count($ids), 'retry_of' => (int) $job['id']]);
        redirect('admin/sertifikat/job/' . $jid);
    }

    /* ---------- sertifikat terbit ---------- */

    public function issued(): void
    {
        [$user, $event] = $this->ctx(Access::CERT_ANY);
        $eid = (int) $event['id'];
        $status = array_key_exists(query('st'), CertIssuance::STATUSES) ? query('st') : '';
        $q = mb_substr(query('q'), 0, 80);
        $page = $this->page();
        $total = CertIssuance::count($eid, $status, $q);
        $this->screen('issued', 'issued', $user, $event, [
            'items' => CertIssuance::list($eid, $status, $q, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'stats' => CertIssuance::stats($eid), 'status' => $status, 'q' => $q, 'pg' => $page, 'total' => $total,
            'pages' => (int) ceil($total / self::PER_PAGE), 'settings' => Issuer::settings($eid),
        ]);
    }

    private function issuance(string $id, array $user, string $perm): array
    {
        $i = CertIssuance::find((int) $id);
        if (!$i) {
            abort(404, 'Sertifikat tidak ditemukan');
        }
        return [$i, $this->owned($user, (int) $i['event_id'], $perm)];
    }

    public function show(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$i, $event] = $this->issuance($id, $user, Access::CERT_ANY);
        $this->screen('show', 'issued', $user, $event, [
            'i' => $i, 'history' => CertIssuance::history((int) $i['participant_id']), 'settings' => Issuer::settings((int) $event['id']),
            'back' => 'admin/sertifikat/terbit?ev=' . $event['id'],
        ]);
    }

    public function download(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$i] = $this->issuance($id, $user, Access::CERT_ANY);
        CertIssuance::stream($i, query('lihat') === '1' ? 'inline' : 'attachment');
    }

    public function publishOne(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$i, $event] = $this->issuance($id, $user, 'cert_publish');
        if ($why = Issuer::publishBlock($i, Issuer::settings((int) $event['id']))) {
            flash('bad', 'Publikasi diblokir', $why);
        } elseif (Issuer::publish($i, $user)) {
            AuditLog::add($user, 'cert.publish', (int) $event['id'], 'certificate', $i['id'], ['number' => $i['cert_number']]);
            flash('ok', 'Sertifikat dipublikasikan', 'Peserta kini dapat menemukan dan mengunduhnya.');
        }
        redirect('admin/sertifikat/' . $i['id']);
    }

    /** Publikasi massal: semua Generated di event ini, atau yang dipilih. */
    public function publishMany(): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        $event = $this->owned($user, (int) input('event_id'), 'cert_publish');
        $settings = Issuer::settings((int) $event['id']);
        $picked = array_map('intval', array_filter((array) ($_POST['ids'] ?? []), 'is_numeric'));
        $rows = DB::all("SELECT id FROM cert_issuances WHERE event_id = ? AND status = 'generated' ORDER BY id", [(int) $event['id']]);
        $ids = array_map('intval', array_column($rows, 'id'));
        if (input('mode') === 'selected') {
            $ids = array_values(array_intersect($ids, $picked));
        }
        $ok = 0;
        $blocked = 0;
        foreach ($ids as $iid) {
            $i = CertIssuance::find($iid);
            if (Issuer::publishBlock($i, $settings)) {
                $blocked++;
            } elseif (Issuer::publish($i, $user)) {
                $ok++;
                AuditLog::add($user, 'cert.publish', (int) $event['id'], 'certificate', $iid, ['number' => $i['cert_number'], 'bulk' => true]);
            }
        }
        flash($ok ? 'ok' : 'bad', $ok ? 'Sertifikat dipublikasikan' : 'Tidak ada sertifikat yang dipublikasikan',
            t(':ok dipublikasikan, :blocked diblokir karena pola nomor belum disetujui.', ['ok' => $ok, 'blocked' => $blocked]));
        $this->back('terbit', $event);
    }

    public function revoke(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$i, $event] = $this->issuance($id, $user, 'cert_revoke');
        $reason = trim(input('reason'));
        if (mb_strlen($reason) < 5) {
            flash('bad', 'Sertifikat belum dicabut', 'Alasan pencabutan wajib diisi (minimal 5 karakter).');
        } elseif (Issuer::revoke($i, $reason, $user)) {
            AuditLog::add($user, 'cert.revoke', (int) $event['id'], 'certificate', $i['id'], ['number' => $i['cert_number'], 'reason' => $reason]);
            flash('ok', 'Sertifikat dicabut', 'Nomor ini tidak akan dipakai lagi dan verifikasi menampilkan status tidak valid.');
        } else {
            flash('bad', 'Sertifikat sudah dicabut sebelumnya');
        }
        redirect('admin/sertifikat/' . $i['id']);
    }

    public function reissue(string $id): void
    {
        $user = $this->manager();
        $this->requireMigrated($user);
        [$i, $event] = $this->issuance($id, $user, 'cert_revoke');
        if (!Access::can($user, (int) $event['id'], 'cert_manage')) {
            Access::deny($user, (int) $event['id'], 'cert_manage');
        }
        $reason = trim(input('reason'));
        if (mb_strlen($reason) < 5) {
            flash('bad', 'Sertifikat belum diterbitkan ulang', 'Alasan penerbitan ulang wajib diisi (minimal 5 karakter).');
            redirect('admin/sertifikat/' . $i['id']);
        }
        if ($i['status'] === 'revoked') {
            flash('bad', 'Sertifikat ini sudah dicabut', 'Terbitkan ulang dari sertifikat aktif terbaru peserta.');
            redirect('admin/sertifikat/' . $i['id']);
        }
        $p = Participant::find((int) $i['participant_id']);
        try {
            $r = Issuer::issue($event, $p, $user, Issuer::readiness($event), null, $i, $reason);
        } catch (CertError $e) {
            flash('bad', 'Penerbitan ulang gagal', $e->getMessage());
            redirect('admin/sertifikat/' . $i['id']);
        }
        AuditLog::add($user, 'cert.reissue', (int) $event['id'], 'certificate', $r['id'], ['old' => $i['cert_number'], 'new' => $r['cert_number'], 'reason' => $reason]);
        flash('ok', 'Sertifikat diterbitkan ulang', t('Nomor baru :new menggantikan :old (dicabut). Publikasikan setelah diperiksa.', ['new' => $r['cert_number'], 'old' => $i['cert_number']]));
        redirect('admin/sertifikat/' . $r['id']);
    }

    /** Unduh beberapa PDF sekaligus. File dibaca dari disk satu per satu ke ZIP sementara. */
    public function zip(): void
    {
        [$user, $event] = $this->ctx(Access::CERT_ANY);
        if (!class_exists(\ZipArchive::class)) {
            flash('bad', 'ZIP tidak tersedia', 'Ekstensi PHP zip belum aktif di server.');
            $this->back('terbit', $event);
        }
        $where = "event_id = ? AND status <> 'revoked'";
        $params = [(int) $event['id']];
        if (in_array(query('st'), ['generated', 'published'], true)) {
            $where .= ' AND status = ?';
            $params[] = query('st');
        }
        $picked = array_values(array_filter(array_map('intval', explode(',', query('ids'))), static fn ($n) => $n > 0));
        if ($picked) {
            $where .= ' AND id IN (' . implode(',', $picked) . ')';
        }
        $rows = DB::all('SELECT i.id FROM cert_issuances i WHERE ' . $where . ' ORDER BY id LIMIT 5000', $params);
        if (!$rows) {
            flash('info', 'Tidak ada sertifikat untuk diunduh');
            $this->back('terbit', $event);
        }
        @set_time_limit(300);
        $tmp = tempnam(sys_get_temp_dir(), 'scz');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $names = [];
        foreach ($rows as $r) {
            $i = CertIssuance::find((int) $r['id']);
            $file = CertIssuance::abs($i);
            if ($file === null) {
                continue;
            }
            $base = trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $i['full_name']) ?: 'peserta'), '-');
            $name = $base . '_' . trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $i['cert_number']), '-') . '.pdf';
            $names[$name] = true;
            $zip->addFile($file, $name);
        }
        $zip->close();
        AuditLog::add($user, 'cert.download.zip', (int) $event['id'], 'zip', null, ['files' => count($names)]);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="sertifikat_' . $event['slug'] . '_' . date('Ymd-Hi') . '.zip"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: private, no-store');
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    public function history(): void
    {
        [$user, $event] = $this->ctx(Access::CERT_ANY);
        $eid = (int) $event['id'];
        $page = $this->page();
        $total = \App\Models\AuditLog::count([$eid], $eid, 'cert.');
        $this->screen('history', 'history', $user, $event, [
            'items' => AuditLog::list([$eid], $eid, 'cert.', 40, ($page - 1) * 40),
            'changed' => DB::all(
                "SELECT i.*, p.full_name, r.cert_number AS new_number, r.id AS new_id FROM cert_issuances i JOIN participants p ON p.id = i.participant_id
                 LEFT JOIN cert_issuances r ON r.replaces_id = i.id WHERE i.event_id = ? AND i.status = 'revoked' ORDER BY i.revoked_at DESC LIMIT 50",
                [$eid]
            ),
            'pg' => $page, 'pages' => (int) ceil($total / 40), 'total' => $total,
        ]);
    }
}

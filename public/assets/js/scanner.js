/* SmartCity Attendance — scanner QR (html5-qrcode) + validasi ke server */
(function () {
  'use strict';
  var T = window.T || function (s, p) { for (var k in (p || {})) { s = s.split(':' + k).join(p[k]); } return s; };
  var app = document.getElementById('scan-app');
  if (!app) return;

  var csrf = document.querySelector('meta[name="csrf-token"]').content;
  var eventId = parseInt(app.dataset.event, 10);
  var result = document.getElementById('scan-result');
  var scanner = document.getElementById('scanner');
  var over = document.getElementById('scan-over');
  var overT = document.getElementById('scan-over-t');
  var overP = document.getElementById('scan-over-p');
  var cap = document.getElementById('scan-cap');
  var btnSwitch = document.getElementById('cam-switch');
  var btnRetry = document.getElementById('cam-retry');
  var icons = document.getElementById('tpl-icons');

  var qr = null, cameras = [], camIndex = 0, running = false;
  var busy = false, holding = false, lastCode = '', lastAt = 0, lastAction = null, via = 'scan';

  function ic(name) {
    var n = icons && icons.content.querySelector('[data-i="' + name + '"]');
    return n ? n.innerHTML : '';
  }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; });
  }
  function tone(t) {
    scanner.classList.remove('ok', 'warn', 'bad');
    if (t) scanner.classList.add(t);
  }

  /* ---------- tampilan hasil ---------- */
  function personBlock(p) {
    if (!p) return '';
    return '<div class="card flat" style="width:100%;max-width:380px;text-align:left;background:var(--bg)">' +
      '<div class="idcard"><span class="av lg' + (p.status === 'hadir' ? ' t' : '') + '">' + esc(initials(p.name)) + '</span>' +
      '<div style="min-width:0"><b>' + esc(p.name) + '</b><span class="mono">' + esc(p.identity) + '</span></div></div>' +
      '<dl class="kv"><div><dt>' + T('Program studi') + '</dt><dd>' + esc(p.field) + '</dd></div>' +
      '<div><dt>' + T('Institusi') + '</dt><dd>' + esc(p.institution) + '</dd></div>' +
      '<div><dt>' + T('Kode') + '</dt><dd class="mono">' + esc(p.code) + '</dd></div></dl></div>';
  }
  function initials(n) {
    return (n || '').trim().split(/\s+/).slice(0, 2).map(function (w) { return w.charAt(0).toUpperCase(); }).join('') || '?';
  }
  function show(kind, icon, title, text, extra, actions) {
    result.innerHTML = '<div class="state s-' + kind + '"><span class="sic">' + ic(icon) + '</span><h3>' + title + '</h3>' +
      (text ? '<p>' + text + '</p>' : '') + (extra || '') + '<div class="acts">' + (actions || '') + '</div></div>';
    if (window.matchMedia('(max-width: 699px)').matches) result.scrollIntoView({ behavior: 'smooth', block: 'start' });
    var first = result.querySelector('.acts button');
    if (first) first.focus({ preventScroll: true });
  }
  var NEXT = '<button type="button" class="btn btn-primary lg block" data-act="next">' + T('Scan Berikutnya') + '</button>';

  function render(data) {
    var p = data.participant, st = data.state;
    switch (st) {
      case 'valid':
        tone('ok');
        show('info', 'user', T('QR valid, periksa identitas'), T('Cocokkan nama dengan peserta di depan Anda, lalu simpan kehadiran.'), personBlock(p),
          '<button type="button" class="btn btn-primary lg block" data-act="save" data-id="' + p.id + '">' + T('Simpan Kehadiran') + '</button>' +
          '<button type="button" class="btn btn-ghost block" data-act="next">' + T('Batal') + '</button>');
        break;
      case 'success':
        tone('ok');
        show('ok', 'check', T('Kehadiran tersimpan'), T(':name tercatat hadir pukul :time.', { name: esc(p.name), time: esc(p.checked_in_at) }), personBlock(p), NEXT);
        if (navigator.vibrate) navigator.vibrate(80);
        break;
      case 'dup':
        tone('warn');
        show('warn', 'alert', T('Sudah tercatat hadir'), p.checked_in_by
          ? T('Peserta ini sudah check-in pukul :time oleh :by. Tidak ada data yang diubah.', { time: esc(p.checked_in_at || '—'), by: esc(p.checked_in_by) })
          : T('Peserta ini sudah check-in pukul :time. Tidak ada data yang diubah.', { time: esc(p.checked_in_at || '—') }), personBlock(p), NEXT);
        break;
      case 'locked':
        tone('bad');
        show('bad', 'lock', T('Status Tidak Hadir'), T('Super Admin telah menetapkan peserta ini Tidak Hadir. QR tidak dapat dipakai; arahkan peserta ke Super Admin.'), personBlock(p), NEXT);
        break;
      case 'invalid':
        tone('bad');
        show('bad', 'x', T('QR tidak dikenali'), T('QR ini bukan QR peserta SmartCity Attendance. Minta peserta membuka QR dari tautan tiketnya, atau gunakan pencarian manual.'), '', NEXT);
        break;
      case 'notfound':
        tone('bad');
        show('bad', 'search', T('Peserta tidak ditemukan'), T('Kode :code tidak terdaftar di event ini. Pastikan peserta mendaftar di event yang benar.', { code: '<span class="mono">' + esc(data.code || '') + '</span>' }), '', NEXT);
        break;
      case 'notyet':
        tone('warn');
        show('warn', 'clock', T('Presensi belum dibuka'), T('Kehadiran belum bisa disimpan. Jadwal presensi :start–:end. Super Admin perlu membuka presensi.', { start: esc(data.event.att_start), end: esc(data.event.att_end) }), personBlock(p), NEXT);
        break;
      case 'closed':
        tone('bad');
        show('bad', 'clock', T('Presensi sudah ditutup'), T('Scan tidak lagi disimpan. Peserta yang terlambat dapat diproses manual oleh Super Admin.'), personBlock(p), NEXT);
        break;
      default:
        tone('bad');
        show('bad', 'alert', T('Terjadi kesalahan'), esc(data.message || T('Coba lagi.')), '', NEXT);
    }
  }

  function networkError() {
    tone('bad');
    show('bad', 'wifioff', T('Koneksi terputus'), T('Hasil scan belum terkirim ke server. Periksa sinyal atau Wi-Fi, lalu coba lagi.'), '',
      '<button type="button" class="btn btn-primary lg block" data-act="retry">' + T('Coba lagi') + '</button>' +
      '<button type="button" class="btn btn-ghost block" data-act="next">' + T('Batal') + '</button>');
  }

  function loading(text) {
    result.innerHTML = '<div class="state s-info"><span class="sic"><span class="spin" style="width:28px;height:28px"></span></span><h3>' + esc(text) + '</h3></div>';
  }

  /* ---------- server ---------- */
  function post(url, body) {
    body.event_id = eventId;
    var ctrl = window.AbortController ? new AbortController() : null;
    var timer = setTimeout(function () { if (ctrl) ctrl.abort(); }, 12000);
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify(body),
      signal: ctrl ? ctrl.signal : undefined
    }).then(function (r) {
      clearTimeout(timer);
      return r.json().catch(function () { return { ok: false, state: 'error', message: T('Respons server tidak valid (HTTP :status).', { status: r.status }) }; })
        .then(function (d) { d._status = r.status; return d; });
    }, function (err) { clearTimeout(timer); throw err; });
  }

  function handleMeta(d) {
    if (d.state === 'auth') { location.href = app.dataset.login; return true; }
    if (d.state === 'expired') {
      show('warn', 'alert', T('Sesi kedaluwarsa'), T('Muat ulang halaman untuk melanjutkan.'), '', '<button type="button" class="btn btn-primary lg block" onclick="location.reload()">' + T('Muat ulang') + '</button>');
      return true;
    }
    return false;
  }

  function validate(code, how) {
    via = how || 'scan';
    lastAction = function () { validate(code, via); };
    busy = true; hold();
    loading(T('Memeriksa QR…'));
    post(app.dataset.validate, { code: code }).then(function (d) {
      busy = false;
      if (!handleMeta(d)) render(d);
    }).catch(function () { busy = false; networkError(); });
  }

  function save(id) {
    lastAction = function () { save(id); };
    busy = true;
    loading(T('Menyimpan kehadiran…'));
    post(app.dataset.save, { participant_id: id, via: via }).then(function (d) {
      busy = false;
      if (!handleMeta(d)) render(d);
    }).catch(function () { busy = false; networkError(); });
  }

  result.addEventListener('click', function (e) {
    var b = e.target.closest('[data-act]');
    if (!b || busy) return;
    var act = b.getAttribute('data-act');
    if (act === 'save') { b.disabled = true; save(parseInt(b.getAttribute('data-id'), 10)); }
    if (act === 'retry' && lastAction) lastAction();
    if (act === 'next') next();
  });

  function next() {
    tone('');
    result.innerHTML = '<div class="state s-info"><span class="sic">' + ic('scan') + '</span><h3>' + T('Siap memindai') + '</h3><p>' + T('Arahkan kamera ke QR Code peserta berikutnya.') + '</p></div>';
    lastCode = ''; lastAction = null;
    resume();
    if (window.matchMedia('(max-width: 699px)').matches) scanner.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  /* ---------- kamera ---------- */
  function overlay(spin, title, text, icon) {
    over.hidden = false;
    over.querySelector('.in').innerHTML = (spin ? '<span class="spin"></span>' : '<span class="sic" style="width:58px;height:58px;border-radius:18px;background:var(--midnight);display:grid;place-items:center;color:var(--cyan)">' + ic(icon || 'camoff') + '</span>') +
      '<span>' + esc(title) + '</span><p>' + text + '</p>';
  }
  function hideOverlay() { over.hidden = true; }

  function hold() {
    holding = true;
    if (qr && running) { try { qr.pause(true); } catch (e) { /* abaikan */ } }
  }
  function resume() {
    holding = false;
    if (qr && running) { try { qr.resume(); } catch (e) { /* abaikan */ } }
  }

  function onScan(text) {
    if (busy || holding) return;
    var now = Date.now();
    if (text === lastCode && now - lastAt < 3000) return;
    lastCode = text; lastAt = now;
    validate(text);
  }

  function camError(err) {
    running = false;
    var msg = String((err && (err.name || err.message)) || err || '');
    btnRetry.hidden = false;
    if (/NotAllowed|Permission|denied/i.test(msg)) {
      overlay(false, T('Akses kamera ditolak'), T('Izinkan kamera lewat ikon gembok di bilah alamat browser, lalu tekan <b>Coba nyalakan kamera</b>. Sementara itu gunakan input manual.'), 'camoff');
    } else if (/NotReadable|TrackStart|in use/i.test(msg)) {
      overlay(false, T('Kamera sedang dipakai'), T('Tutup aplikasi lain yang memakai kamera, lalu coba lagi.'), 'camoff');
    } else {
      overlay(false, T('Kamera tidak tersedia'), T('Perangkat ini tidak memiliki kamera yang bisa dipakai. Gunakan input kode atau pencarian manual.'), 'camoff');
    }
    cap.hidden = true;
  }

  function start() {
    btnRetry.hidden = true;
    if (!window.isSecureContext) {
      overlay(false, T('Kamera butuh HTTPS'), T('Browser hanya mengizinkan kamera di situs HTTPS atau localhost. Minta pengelola server memasang SSL. Input manual tetap bisa dipakai.'), 'lock');
      cap.hidden = true;
      return;
    }
    if (typeof Html5Qrcode === 'undefined' || !navigator.mediaDevices) {
      camError('NotFound');
      return;
    }
    overlay(true, T('Menyalakan kamera…'), T('Izinkan akses kamera jika browser meminta.'));
    if (!qr) qr = new Html5Qrcode('reader', { verbose: false });
    var cam = cameras.length ? { deviceId: { exact: cameras[camIndex].id } } : { facingMode: 'environment' };
    qr.start(cam, {
      fps: 10,
      qrbox: function (w, h) { var s = Math.floor(Math.min(w, h) * 0.62); return { width: s, height: s }; },
      aspectRatio: 1
    }, onScan, function () { /* frame tanpa QR: abaikan */ }).then(function () {
      running = true;
      hideOverlay();
      cap.hidden = false;
      scanner.classList.add('live');
      if (holding) hold();
      Html5Qrcode.getCameras().then(function (list) {
        cameras = list || [];
        btnSwitch.hidden = cameras.length < 2;
      }).catch(function () { /* abaikan */ });
    }).catch(camError);
  }

  btnRetry.addEventListener('click', start);
  btnSwitch.addEventListener('click', function () {
    if (!qr || cameras.length < 2) return;
    camIndex = (camIndex + 1) % cameras.length;
    qr.stop().then(function () { running = false; start(); }).catch(start);
  });
  document.addEventListener('visibilitychange', function () {
    if (!qr) return;
    if (document.hidden && running) { qr.stop().then(function () { running = false; }).catch(function () {}); }
    else if (!document.hidden && !running && btnRetry.hidden) { start(); }
  });

  /* ---------- input manual ---------- */
  document.getElementById('manual-code').addEventListener('submit', function (e) {
    e.preventDefault();
    var v = this.code.value.trim();
    if (!v || busy) return;
    validate(v, 'cari');
    this.code.value = '';
  });

  var qInput = document.getElementById('manual-q');
  var list = document.getElementById('manual-results');
  var tmr = null;
  qInput.addEventListener('input', function () {
    clearTimeout(tmr);
    var q = qInput.value.trim();
    if (q.length < 2) { list.innerHTML = ''; return; }
    tmr = setTimeout(function () {
      list.innerHTML = '<p class="small muted">' + T('Mencari…') + '</p>';
      post(app.dataset.search, { q: q }).then(function (d) {
        if (handleMeta(d)) return;
        if (!d.items || !d.items.length) { list.innerHTML = '<p class="small muted">' + T('Tidak ada peserta yang cocok dengan ":q".', { q: esc(q) }) + '</p>'; return; }
        list.innerHTML = d.items.map(function (p) {
          var badge = { hadir: 'bdg-ok', belum: 'bdg-warn', tidak: 'bdg-bad' }[p.status] || 'bdg-mute';
          return '<button type="button" class="pc" data-code="' + esc(p.code) + '"><span class="av">' + esc(initials(p.name)) + '</span>' +
            '<span class="who"><b>' + esc(p.name) + '</b><span class="mono">' + esc(p.identity) + '</span> <span>· ' + esc(p.field) + '</span></span>' +
            '<span class="bdg ' + badge + '"><i></i>' + esc(p.status_label) + '</span></button>';
        }).join('');
      }).catch(function () { list.innerHTML = '<p class="small" style="color:var(--bad-ink)">' + T('Gagal mencari. Periksa koneksi.') + '</p>'; });
    }, 300);
  });
  list.addEventListener('click', function (e) {
    var b = e.target.closest('[data-code]');
    if (b && !busy) validate(b.getAttribute('data-code'), 'cari');
  });

  start();
})();

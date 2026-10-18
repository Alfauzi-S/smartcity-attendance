/* SmartCity Attendance — interaksi umum (tanpa framework) */
(function () {
  'use strict';
  var T = window.T || function (s, p) { for (var k in (p || {})) { s = s.split(':' + k).join(p[k]); } return s; };
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* ---------- toast ---------- */
  function dismissLater(t) { setTimeout(function () { if (t.parentNode) t.remove(); }, 6000); }
  $$('.toast').forEach(dismissLater);
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-dismiss]');
    if (b) b.closest('.toast').remove();
  });
  window.scaToast = function (type, title, text) {
    var box = $('#toasts');
    if (!box) return;
    var t = document.createElement('div');
    t.className = 'toast ' + type;
    t.setAttribute('role', 'status');
    var ti = document.createElement('span'); ti.className = 'ti'; ti.textContent = type === 'ok' ? '✓' : (type === 'bad' ? '!' : 'i');
    var d = document.createElement('div');
    var b = document.createElement('b'); b.textContent = title; d.appendChild(b);
    if (text) { var s = document.createElement('span'); s.textContent = text; d.appendChild(s); }
    t.appendChild(ti); t.appendChild(d);
    box.appendChild(t);
    dismissLater(t);
  };

  /* ---------- dialog / sheet ---------- */
  var lastFocus = null;
  function openDialog(el) {
    if (!el) return;
    lastFocus = document.activeElement;
    el.hidden = false;
    document.body.style.overflow = 'hidden';
    var f = el.querySelector('a[href],input:not([type=hidden]):not([disabled]),textarea,button:not([data-close])') || el.querySelector('button');
    if (f) setTimeout(function () { f.focus(); }, 30);
  }
  function closeDialog(el) {
    if (!el) return;
    el.hidden = true;
    document.body.style.overflow = '';
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }
  window.scaOpen = openDialog;
  document.addEventListener('click', function (e) {
    var o = e.target.closest('[data-open]');
    if (o) { e.preventDefault(); openDialog(document.getElementById(o.getAttribute('data-open'))); return; }
    var c = e.target.closest('[data-close]');
    if (c) { closeDialog(c.closest('.ov')); return; }
    if (e.target.classList && e.target.classList.contains('ov')) closeDialog(e.target);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { var ov = $$('.ov').filter(function (x) { return !x.hidden; }).pop(); if (ov) closeDialog(ov); }
  });
  $$('.ov').forEach(function (ov) { if (!ov.hidden) document.body.style.overflow = 'hidden'; });

  /* ---------- konfirmasi ---------- */
  var pending = null;
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.hasAttribute('data-confirm') && !f.dataset.confirmed) {
      e.preventDefault();
      pending = f;
      $('#confirm-text').textContent = f.getAttribute('data-confirm');
      var danger = /hapus|nonaktif|tutup|cabut|arsip|delete|deactivate|close|revoke|archive/i.test(f.getAttribute('data-confirm'));
      $('#confirm-ok').className = 'btn ' + (danger ? 'btn-danger' : 'btn-primary');
      openDialog($('#confirm-dialog'));
      return;
    }
    if (f.hasAttribute('data-loading')) {
      var btn = f.querySelector('button[type=submit],button:not([type])');
      if (btn && !btn.disabled) {
        setTimeout(function () {
          btn.classList.add('loading');
          btn.setAttribute('aria-busy', 'true');
          btn.innerHTML = '<span class="spin"></span>' + f.getAttribute('data-loading');
        }, 0);
      }
    }
  }, true);
  var ok = $('#confirm-ok');
  if (ok) ok.addEventListener('click', function () {
    if (!pending) return;
    pending.dataset.confirmed = '1';
    closeDialog($('#confirm-dialog'));
    if (pending.requestSubmit) pending.requestSubmit(); else pending.submit();
    pending = null;
  });

  /* ---------- salin ---------- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]');
    if (!b) return;
    var src = $(b.getAttribute('data-copy'));
    if (!src) return;
    var text = src.value !== undefined ? src.value : src.textContent;
    var done = function () { window.scaToast('ok', T('Tersalin'), T('Teks sudah ada di clipboard.')); };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done, function () { fallback(); });
    } else { fallback(); }
    function fallback() {
      src.select && src.select();
      try { document.execCommand('copy'); done(); } catch (err) { window.scaToast('bad', T('Gagal menyalin'), T('Salin manual dari kolom.')); }
    }
  });

  /* ---------- tampilkan password ---------- */
  $$('[data-show-password]').forEach(function (c) {
    c.addEventListener('change', function () { var i = $(c.getAttribute('data-show-password')); if (i) i.type = c.checked ? 'text' : 'password'; });
  });

  /* ---------- submit otomatis ---------- */
  $$('form[data-autosubmit] select').forEach(function (s) { s.addEventListener('change', function () { s.form.submit(); }); });
  document.addEventListener('change', function (e) {
    var all = e.target.closest('[data-check-all]');
    if (all) { $$('input[name="ids[]"]', all.closest('form')).forEach(function (c) { c.checked = all.checked; }); }
  });
  $$('select[data-autosubmit-field]').forEach(function (s) { s.addEventListener('change', function () { s.form.submit(); }); });
  $$('select[data-report-filter]').forEach(function (s) {
    s.addEventListener('change', function () { location.href = s.getAttribute('data-report-filter') + '?st=' + encodeURIComponent(s.value); });
  });

  /* ---------- QR tiket ---------- */
  $$('[data-qr]').forEach(function (el) {
    if (typeof QRCode === 'undefined') { el.textContent = T('QR tidak dapat dimuat. Gunakan kode di bawah.'); return; }
    new QRCode(el, { text: el.getAttribute('data-qr'), width: 512, height: 512, colorDark: '#0B1220', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
  });
  $$('[data-qr-download]').forEach(function (b) {
    b.addEventListener('click', function () {
      var box = document.getElementById(b.getAttribute('data-qr-download'));
      var src = box && (box.querySelector('canvas') || box.querySelector('img'));
      if (!src) { window.scaToast('bad', T('QR belum siap'), T('Muat ulang halaman lalu coba lagi.')); return; }
      var W = 720, H = 980, c = document.createElement('canvas');
      c.width = W; c.height = H;
      var x = c.getContext('2d');
      x.fillStyle = '#ffffff'; x.fillRect(0, 0, W, H);
      var g = x.createLinearGradient(0, 0, W, 0); g.addColorStop(0, '#35D5F2'); g.addColorStop(1, '#23C7A5');
      x.fillStyle = g; x.fillRect(0, 0, W, 14);
      x.fillStyle = '#64748B'; x.font = '600 22px Inter, sans-serif'; x.textAlign = 'center';
      x.fillText(T('QR CODE KEHADIRAN'), W / 2, 70);
      x.fillStyle = '#0B1220'; x.font = '700 26px Sora, Inter, sans-serif';
      wrap(x, b.getAttribute('data-event') || '', W / 2, 112, W - 80, 32);
      x.imageSmoothingEnabled = false;
      x.drawImage(src, 110, 170, 500, 500);
      x.font = '700 40px ui-monospace, Menlo, monospace'; x.fillStyle = '#0A7187';
      x.fillText(b.getAttribute('data-code'), W / 2, 740);
      x.font = '600 30px Sora, Inter, sans-serif'; x.fillStyle = '#0E1729';
      wrap(x, b.getAttribute('data-name'), W / 2, 800, W - 80, 38);
      x.font = '500 20px Inter, sans-serif'; x.fillStyle = '#64748B';
      x.fillText(T('Tunjukkan QR ini kepada petugas. Jangan dibagikan.'), W / 2, 920);
      var a = document.createElement('a');
      a.download = 'QR-' + b.getAttribute('data-code') + '.png';
      a.href = c.toDataURL('image/png');
      document.body.appendChild(a); a.click(); a.remove();
    });
  });
  function wrap(x, text, cx, y, max, lh) {
    var words = text.split(' '), line = '';
    for (var i = 0; i < words.length; i++) {
      var t = line ? line + ' ' + words[i] : words[i];
      if (x.measureText(t).width > max && line) { x.fillText(line, cx, y); line = words[i]; y += lh; } else { line = t; }
    }
    x.fillText(line, cx, y);
  }

  /* ---------- dialog ubah status (2 langkah) ---------- */
  $$('form[data-status-form]').forEach(function (f) {
    var s1 = f.querySelector('[data-step="1"]'), s2 = f.querySelector('[data-step="2"]');
    var tpl = document.getElementById('tpl-badges');
    f.querySelector('[data-next]').addEventListener('click', function () {
      var r = f.querySelector('input[name=new_status]:checked');
      var reason = f.querySelector('#reason');
      if (!r) { window.scaToast('bad', T('Pilih status baru'), T('Pilih Hadir atau Tidak Hadir.')); return; }
      if (reason.value.trim().length < 3) { reason.closest('.fld').classList.add('is-err'); reason.focus(); window.scaToast('bad', T('Alasan wajib diisi'), T('Tulis alasan singkat perubahan.')); return; }
      reason.closest('.fld').classList.remove('is-err');
      var to = f.querySelector('[data-to]');
      to.innerHTML = tpl ? tpl.content.querySelector('[data-b="' + r.value + '"]').innerHTML : r.value;
      f.querySelector('[data-reason]').textContent = reason.value.trim();
      s1.hidden = true; s2.hidden = false;
      var sb = s2.querySelector('button[type=submit]');
      sb.className = 'btn ' + (r.value === 'tidak' ? 'btn-danger' : 'btn-primary');
      sb.focus();
    });
    f.querySelector('[data-prev]').addEventListener('click', function () { s2.hidden = true; s1.hidden = false; });
  });

  /* ---------- pilihan "Lainnya (tulis sendiri)" ---------- */
  $$('select[data-other]').forEach(function (s) {
    var wrap = document.getElementById(s.getAttribute('data-other') + '_wrap');
    var inp = document.getElementById(s.getAttribute('data-other'));
    if (!wrap || !inp) return;
    s.addEventListener('change', function () {
      var on = s.value === 'Lainnya';
      wrap.hidden = !on;
      if (on) inp.focus(); else inp.value = '';
    });
  });
})();

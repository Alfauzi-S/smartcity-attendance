/* Editor template sertifikat: koordinat dalam persen terhadap halaman. Semua teks dipasang lewat textContent. */
(function () {
  'use strict';
  var T = window.T || function (s) { return s; };
  var dataEl = document.getElementById('cert-data');
  if (!dataEl) return;
  var D = JSON.parse(dataEl.textContent);
  var els = D.elements || [];
  var cv = document.getElementById('cv');
  var list = document.getElementById('ed-list');
  var hidden = document.getElementById('ed-elements');
  var props = document.getElementById('ed-props');
  var body = document.getElementById('ed-props-body');
  var varsBox = document.getElementById('ed-vars');
  var sel = null;
  var PT_MM = 0.352778;
  var dirty = false;

  function h(tag, attrs, text) {
    var n = document.createElement(tag);
    for (var k in (attrs || {})) {
      if (k === 'class') n.className = attrs[k];
      else if (k === 'style') n.style.cssText = attrs[k];
      else n.setAttribute(k, attrs[k]);
    }
    if (text !== undefined && text !== null) n.textContent = text;
    return n;
  }
  function uid() { return 'e' + Math.random().toString(36).slice(2, 9); }
  function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }
  function round(v) { return Math.round(v * 100) / 100; }
  function asset(id) { return (D.assets || []).filter(function (a) { return a.id === id; })[0] || null; }
  function label(el) {
    if (el.type === 'text') return (el.content || T('(teks kosong)')).slice(0, 40);
    if (el.type === 'qr') return T('QR verifikasi');
    return (el.label || D.kinds[el.slot] || T('Gambar'));
  }
  function sync() {
    hidden.value = JSON.stringify(els);
    document.getElementById('ed-count').textContent = T(':n elemen', { n: els.length });
  }
  function markDirty() { dirty = true; sync(); }
  window.addEventListener('beforeunload', function (e) { if (dirty && !D.readonly) { e.preventDefault(); e.returnValue = ''; } });
  document.getElementById('ed-form').addEventListener('submit', function () { dirty = false; sync(); });

  /* ---------- kanvas ---------- */
  function scale() { return cv.clientWidth / D.w; } // px per mm
  function draw() {
    cv.textContent = '';
    if (D.bg) cv.style.backgroundImage = 'url("' + D.bg.replace(/"/g, '%22') + '")';
    var s = scale();
    els.forEach(function (el, i) {
      var n = h('div', { class: 'cv-el cv-' + el.type + (sel === i ? ' on' : ''), 'data-i': i, tabindex: '0' });
      n.style.left = el.x + '%';
      n.style.top = el.y + '%';
      if (el.type === 'text') {
        n.style.width = el.w + '%';
        n.style.fontSize = (el.size * PT_MM * s) + 'px';
        n.style.lineHeight = el.lh;
        n.style.letterSpacing = (el.ls * PT_MM * s) + 'px';
        n.style.fontFamily = el.font === 'dejavuserif' ? 'Georgia, "DejaVu Serif", serif' : (el.font === 'dejavusanscondensed' ? '"DejaVu Sans Condensed", "Arial Narrow", sans-serif' : '"DejaVu Sans", Verdana, sans-serif');
        n.style.fontWeight = el.bold ? '700' : '400';
        n.style.fontStyle = el.italic ? 'italic' : 'normal';
        n.style.color = el.color;
        n.style.textAlign = { L: 'left', C: 'center', R: 'right', J: 'justify' }[el.align] || 'center';
        n.style.whiteSpace = el.fit ? 'nowrap' : 'pre-wrap';
        n.textContent = el.content || ' ';
      } else if (el.type === 'image') {
        n.style.width = el.w + '%';
        n.style.height = el.h + '%';
        var a = el.asset_id ? asset(el.asset_id) : null;
        if (a) {
          n.appendChild(h('img', { src: a.url, alt: a.label, draggable: 'false' }));
          if ((a.kind === 'signature' || a.kind === 'stamp') && !a.approved) n.appendChild(h('span', { class: 'cv-tag bad' }, T('Belum disetujui')));
        } else {
          n.classList.add('empty');
          n.appendChild(h('span', { class: 'cv-tag' }, label(el)));
        }
      } else {
        var side = cv.clientWidth * el.size / 100;
        n.style.width = side + 'px';
        n.style.height = side + 'px';
        n.appendChild(h('span', { class: 'cv-qr' }, 'QR'));
      }
      if (!D.readonly) n.appendChild(h('i', { class: 'cv-rs', 'data-rs': i, title: T('Ubah ukuran') }));
      cv.appendChild(n);
    });
    drawList();
  }
  function drawList() {
    list.textContent = '';
    if (!els.length) { list.appendChild(h('p', { class: 'small muted' }, T('Belum ada elemen. Tambahkan teks, gambar, atau QR.'))); }
    els.forEach(function (el, i) {
      var b = h('button', { type: 'button', class: 'ed-li' + (sel === i ? ' on' : ''), 'data-pick': i });
      b.appendChild(h('span', { class: 'bdg bdg-mute' }, el.type === 'text' ? T('Teks') : (el.type === 'qr' ? 'QR' : (D.kinds[el.slot] || T('Gambar')))));
      b.appendChild(h('span', { class: 'ed-lt' }, label(el)));
      list.appendChild(b);
    });
  }
  window.addEventListener('resize', draw);

  /* ---------- seret & ubah ukuran ---------- */
  var drag = null;
  cv.addEventListener('pointerdown', function (e) {
    var rs = e.target.closest('[data-rs]');
    var n = e.target.closest('.cv-el');
    if (!n) return;
    var i = +n.getAttribute('data-i');
    pick(i);
    if (D.readonly) return;
    e.preventDefault();
    var r = cv.getBoundingClientRect();
    drag = { i: i, rs: !!rs, sx: e.clientX, sy: e.clientY, x: els[i].x, y: els[i].y, w: els[i].w, h: els[i].h, size: els[i].size, W: r.width, H: r.height };
    cv.setPointerCapture(e.pointerId);
  });
  cv.addEventListener('pointermove', function (e) {
    if (!drag) return;
    var el = els[drag.i];
    var dx = (e.clientX - drag.sx) / drag.W * 100;
    var dy = (e.clientY - drag.sy) / drag.H * 100;
    if (drag.rs) {
      if (el.type === 'qr') el.size = round(clamp(drag.size + dx, 3, 40));
      else { el.w = round(clamp(drag.w + dx, 1, 100)); if (el.type === 'image') el.h = round(clamp(drag.h + dy, 1, 100)); }
    } else {
      el.x = round(clamp(drag.x + dx, 0, 99));
      el.y = round(clamp(drag.y + dy, 0, 99));
    }
    var n = cv.querySelector('[data-i="' + drag.i + '"]');
    if (n) { n.style.left = el.x + '%'; n.style.top = el.y + '%'; if (el.type !== 'qr') { n.style.width = el.w + '%'; if (el.type === 'image') n.style.height = el.h + '%'; } }
    if (drag.rs && el.type === 'qr') draw();
  });
  function endDrag() { if (drag) { drag = null; markDirty(); draw(); showProps(); } }
  cv.addEventListener('pointerup', endDrag);
  cv.addEventListener('pointercancel', endDrag);
  cv.addEventListener('keydown', function (e) {
    if (sel === null || D.readonly) return;
    var step = e.shiftKey ? 2 : 0.5;
    var el = els[sel];
    var map = { ArrowLeft: ['x', -step], ArrowRight: ['x', step], ArrowUp: ['y', -step], ArrowDown: ['y', step] };
    if (map[e.key]) { e.preventDefault(); el[map[e.key][0]] = round(clamp(el[map[e.key][0]] + map[e.key][1], 0, 99)); markDirty(); draw(); showProps(); }
  });

  /* ---------- pilih & properti ---------- */
  list.addEventListener('click', function (e) { var b = e.target.closest('[data-pick]'); if (b) pick(+b.getAttribute('data-pick')); });
  function pick(i) { sel = i; draw(); showProps(); var n = cv.querySelector('[data-i="' + i + '"]'); if (n) n.focus({ preventScroll: true }); }

  function field(lbl, input) { var f = h('div', { class: 'fld' }); var l = h('label', {}, lbl); f.appendChild(l); f.appendChild(input); return f; }
  function num(key, lbl, min, max, stepv) {
    var el = els[sel];
    var inp = h('input', { class: 'inp mono', type: 'number', min: min, max: max, step: stepv || 0.5, value: el[key] });
    inp.disabled = D.readonly;
    inp.addEventListener('input', function () { var v = parseFloat(inp.value); if (!isNaN(v)) { el[key] = round(clamp(v, min, max)); markDirty(); draw(); } });
    return field(lbl, inp);
  }
  function select(key, lbl, options) {
    var el = els[sel];
    var s = h('select', { class: 'inp' });
    Object.keys(options).forEach(function (k) { var o = h('option', { value: k }, options[k]); if (String(el[key]) === k) o.selected = true; s.appendChild(o); });
    s.disabled = D.readonly;
    s.addEventListener('change', function () { el[key] = s.value; markDirty(); draw(); showProps(); });
    return field(lbl, s);
  }
  function check(key, lbl) {
    var el = els[sel];
    var w = h('label', { class: 'chk' });
    var c = h('input', { type: 'checkbox' });
    c.checked = !!el[key];
    c.disabled = D.readonly;
    c.addEventListener('change', function () { el[key] = c.checked; markDirty(); draw(); });
    w.appendChild(c); w.appendChild(h('span', {}, lbl));
    return w;
  }
  var contentBox = null;
  function showProps() {
    if (sel === null || !els[sel]) { props.hidden = true; varsBox.hidden = true; return; }
    var el = els[sel];
    props.hidden = false;
    body.textContent = '';
    contentBox = null;
    document.getElementById('ed-props-title').textContent = label(el);
    if (el.type === 'text') {
      var ta = h('textarea', { class: 'inp', rows: '3', maxlength: '600' });
      ta.value = el.content;
      ta.disabled = D.readonly;
      ta.addEventListener('input', function () { el.content = ta.value; markDirty(); draw(); });
      contentBox = ta;
      body.appendChild(field(T('Isi teks (gunakan {VARIABEL})'), ta));
      var g = h('div', { class: 'g-form two' });
      g.appendChild(select('font', T('Font'), D.fonts));
      g.appendChild(num('size', T('Ukuran (pt)'), 4, 120, 0.5));
      g.appendChild(select('align', T('Perataan'), { L: T('Kiri'), C: T('Tengah'), R: T('Kanan'), J: T('Rata kiri-kanan') }));
      var col = h('input', { class: 'inp', type: 'color', value: el.color });
      col.disabled = D.readonly;
      col.addEventListener('input', function () { el.color = col.value.toUpperCase(); markDirty(); draw(); });
      g.appendChild(field(T('Warna'), col));
      g.appendChild(num('lh', T('Jarak baris'), 0.8, 3, 0.05));
      g.appendChild(num('ls', T('Jarak huruf (pt)'), -1, 10, 0.1));
      body.appendChild(g);
      var row = h('div', { class: 'row' });
      row.appendChild(check('bold', T('Tebal')));
      row.appendChild(check('italic', T('Miring')));
      row.appendChild(check('fit', T('Kecilkan agar muat 1 baris')));
      body.appendChild(row);
      varsBox.hidden = D.readonly;
      drawVars();
    } else if (el.type === 'image') {
      varsBox.hidden = true;
      var kinds = {}; Object.keys(D.kinds).forEach(function (k) { kinds[k] = D.kinds[k]; });
      body.appendChild(select('slot', T('Jenis slot'), kinds));
      var lb = h('input', { class: 'inp', maxlength: '60', value: el.label || '' });
      lb.disabled = D.readonly;
      lb.addEventListener('input', function () { el.label = lb.value; markDirty(); drawList(); });
      body.appendChild(field(T('Keterangan slot'), lb));
      var s = h('select', { class: 'inp' });
      s.appendChild(h('option', { value: '' }, T('— Belum diisi —')));
      (D.assets || []).filter(function (a) { return a.kind === el.slot || el.slot === 'image'; }).forEach(function (a) {
        var o = h('option', { value: a.id }, a.label + ((a.kind === 'signature' || a.kind === 'stamp') && !a.approved ? ' (' + T('belum disetujui') + ')' : ''));
        if (el.asset_id === a.id) o.selected = true;
        s.appendChild(o);
      });
      s.disabled = D.readonly;
      s.addEventListener('change', function () {
        el.asset_id = s.value ? +s.value : null;
        var a = asset(el.asset_id);
        if (a && a.ratio) { el.h = round(clamp(el.w * (D.w / D.h) / a.ratio, 1, 100)); } // sesuaikan kotak dengan rasio aset
        markDirty(); draw(); showProps();
      });
      body.appendChild(field(T('Aset resmi'), s));
      if (!(D.assets || []).some(function (a) { return a.kind === el.slot || el.slot === 'image'; })) body.appendChild(h('p', { class: 'hint small' }, T('Belum ada aset jenis ini. Unggah di tab Aset resmi.')));
    } else {
      varsBox.hidden = true;
      body.appendChild(h('p', { class: 'small muted' }, T('QR berisi tautan halaman verifikasi sertifikat (bukan QR absensi). Tautan dibuat otomatis saat generate.')));
    }
    var g2 = h('div', { class: 'g-form two' });
    g2.appendChild(num('x', T('Posisi X (%)'), 0, 99));
    g2.appendChild(num('y', T('Posisi Y (%)'), 0, 99));
    if (el.type === 'qr') g2.appendChild(num('size', T('Sisi QR (% lebar)'), 3, 40));
    else g2.appendChild(num('w', T('Lebar (%)'), 1, 100));
    if (el.type === 'image') g2.appendChild(num('h', T('Tinggi (%)'), 1, 100));
    body.appendChild(g2);
    if (!D.readonly) {
      var acts = h('div', { class: 'row' });
      [['up', T('Ke depan')], ['down', T('Ke belakang')], ['dup', T('Duplikat')], ['center', T('Tengahkan')], ['del', T('Hapus')]].forEach(function (a) {
        acts.appendChild(h('button', { type: 'button', class: 'btn ' + (a[0] === 'del' ? 'btn-danger' : 'btn-outline') + ' sm', 'data-act': a[0] }, a[1]));
      });
      body.appendChild(acts);
    }
  }
  body.addEventListener('click', function (e) {
    var b = e.target.closest('[data-act]');
    if (!b || sel === null) return;
    var a = b.getAttribute('data-act');
    var el = els[sel];
    if (a === 'del') { els.splice(sel, 1); sel = null; }
    else if (a === 'dup') { var c = JSON.parse(JSON.stringify(el)); c.id = uid(); c.y = clamp(c.y + 3, 0, 99); els.splice(sel + 1, 0, c); sel = sel + 1; }
    else if (a === 'up' && sel < els.length - 1) { els.splice(sel + 1, 0, els.splice(sel, 1)[0]); sel++; }
    else if (a === 'down' && sel > 0) { els.splice(sel - 1, 0, els.splice(sel, 1)[0]); sel--; }
    else if (a === 'center') { var wd = el.type === 'qr' ? el.size : el.w; el.x = round(clamp((100 - wd) / 2, 0, 99)); }
    markDirty(); draw(); showProps();
  });

  /* ---------- daftar variabel ---------- */
  var vq = document.getElementById('ed-var-q');
  function drawVars() {
    var box = document.getElementById('ed-vlist');
    box.textContent = '';
    var q = (vq.value || '').toLowerCase();
    (D.catalog || []).filter(function (v) { return !q || v.key.toLowerCase().indexOf(q) >= 0 || v.label.toLowerCase().indexOf(q) >= 0; }).forEach(function (v) {
      var b = h('button', { type: 'button', class: 'ed-var', 'data-key': v.key, title: v.label });
      b.appendChild(h('code', {}, '{' + v.key + '}'));
      b.appendChild(h('span', {}, v.group + ' · ' + v.label));
      box.appendChild(b);
    });
  }
  vq.addEventListener('input', drawVars);
  document.getElementById('ed-vlist').addEventListener('click', function (e) {
    var b = e.target.closest('[data-key]');
    if (!b || !contentBox || sel === null) return;
    var token = '{' + b.getAttribute('data-key') + '}';
    var s = contentBox.selectionStart || contentBox.value.length, en = contentBox.selectionEnd || s;
    contentBox.value = contentBox.value.slice(0, s) + token + contentBox.value.slice(en);
    els[sel].content = contentBox.value;
    markDirty(); draw();
    contentBox.focus();
    contentBox.selectionStart = contentBox.selectionEnd = s + token.length;
  });

  /* ---------- tambah elemen ---------- */
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-add]');
    if (!b || D.readonly) return;
    var k = b.getAttribute('data-add');
    var el;
    if (k === 'text') el = { id: uid(), type: 'text', content: T('Teks baru'), x: 20, y: 45, w: 60, font: 'dejavusans', size: 16, bold: false, italic: false, color: '#1F2937', align: 'C', lh: 1.25, ls: 0, fit: false };
    else if (k === 'qr') el = { id: uid(), type: 'qr', x: 5, y: 75, size: 11 };
    else el = { id: uid(), type: 'image', slot: k, label: D.kinds[k], asset_id: null, x: 40, y: 40, w: k === 'signature' ? 20 : 10, h: k === 'signature' ? 10 : 14 };
    els.push(el);
    sel = els.length - 1;
    markDirty(); draw(); showProps();
  });

  /* ---------- ukuran kustom ---------- */
  var fmt = document.getElementById('ed-format');
  function toggleCustom() { document.querySelectorAll('[data-custom]').forEach(function (n) { n.hidden = fmt.value !== 'custom'; }); }
  fmt.addEventListener('change', toggleCustom);
  toggleCustom();

  sync();
  draw();
})();

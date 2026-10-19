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
  var FM = D.fontMeta || {};
  var SWATCH = ['#FFFFFF', '#0B1F3A', '#123A5A', '#0F766E', '#14B8A6', '#5EEAD4', '#F1F5F9', '#B9C7D8', '#C8A24A', '#B42121', '#000000'];
  function lo(el) { return el.type === 'shape' ? -50 : 0; }
  function polyPts(el) {
    var p = (D.polygons || {})[el.shape] || [], out = [];
    for (var i = 0; i < p.length; i += 2) out.push((el.flip ? 100 - p[i] : p[i]) + ',' + p[i + 1]);
    return out.join(' ');
  }
  function svg(tag, attrs) { var n = document.createElementNS('http://www.w3.org/2000/svg', tag); for (var k in attrs) n.setAttribute(k, attrs[k]); return n; }
  function gradCss(el) {
    if (!el.fill) return 'transparent';
    if (!el.fill2) return el.fill;
    return 'linear-gradient(' + ({ h: '90deg', v: '180deg', d: '135deg' }[el.grad] || '90deg') + ',' + el.fill + ',' + el.fill2 + ')';
  }
  function asset(id) { return (D.assets || []).filter(function (a) { return a.id === id; })[0] || null; }
  function label(el) {
    if (el.type === 'text') return (el.content || T('(teks kosong)')).slice(0, 40);
    if (el.type === 'qr') return T('QR verifikasi');
    if (el.type === 'shape') return (D.shapes || {})[el.shape] || T('Bentuk');
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
        var fm = FM[el.font] || FM.dejavusans || ['sans-serif', 400, true, true];
        n.style.fontFamily = fm[0];
        n.style.fontWeight = el.bold && fm[2] ? '700' : String(fm[1]);
        n.style.fontStyle = el.italic && fm[3] ? 'italic' : 'normal';
        n.style.textDecoration = el.underline ? 'underline' : 'none';
        n.style.textTransform = el.upper ? 'uppercase' : 'none';
        n.style.opacity = el.opacity === undefined ? 1 : el.opacity;
        if (el.rot) { n.style.transform = 'rotate(' + el.rot + 'deg)'; n.style.transformOrigin = '0 0'; }
        n.style.color = el.color;
        n.style.textAlign = { L: 'left', C: 'center', R: 'right', J: 'justify' }[el.align] || 'center';
        n.style.whiteSpace = el.fit ? 'nowrap' : 'pre-wrap';
        n.textContent = el.content || ' ';
      } else if (el.type === 'shape') {
        n.style.width = el.w + '%';
        n.style.opacity = el.opacity;
        if (el.rot) n.style.transform = 'rotate(' + el.rot + 'deg)';
        var sw = (el.stroke && el.sw > 0) ? Math.max(1, el.sw * s) : 0;
        if (el.shape === 'line') {
          n.style.height = Math.max(2, sw) + 'px';
          n.style.marginTop = (-Math.max(2, sw) / 2) + 'px';
          n.style.transformOrigin = '50% 50%';
          n.style.background = el.dash ? 'repeating-linear-gradient(90deg,' + (el.stroke || '#94A3B8') + ' 0 ' + (sw * 3) + 'px,transparent 0 ' + (sw * 5) + 'px)' : (el.stroke || '#94A3B8');
        } else {
          n.style.height = el.h + '%';
          if (el.shape === 'rect' || el.shape === 'ellipse') {
            n.style.background = gradCss(el);
            n.style.borderRadius = el.shape === 'ellipse' ? '50%' : (el.radius * s) + 'px';
            if (sw) n.style.boxShadow = 'inset 0 0 0 ' + sw + 'px ' + el.stroke;
          } else {
            var g = svg('svg', { viewBox: '0 0 100 100', preserveAspectRatio: 'none', width: '100%', height: '100%' });
            var fillRef = el.fill || 'none';
            if (el.fill && el.fill2) {
              var gid = 'g' + i, v = { h: [0, 0, 1, 0], v: [0, 0, 0, 1], d: [0, 0, 1, 1] }[el.grad] || [0, 0, 1, 0];
              var lg = svg('linearGradient', { id: gid, x1: v[0], y1: v[1], x2: v[2], y2: v[3] });
              lg.appendChild(svg('stop', { offset: '0', 'stop-color': el.fill }));
              lg.appendChild(svg('stop', { offset: '1', 'stop-color': el.fill2 }));
              var defs = svg('defs', {}); defs.appendChild(lg); g.appendChild(defs);
              fillRef = 'url(#' + gid + ')';
            }
            var attrs = { points: polyPts(el), fill: fillRef, 'vector-effect': 'non-scaling-stroke' };
            if (sw) { attrs.stroke = el.stroke; attrs['stroke-width'] = sw; if (el.dash) attrs['stroke-dasharray'] = (sw * 3) + ' ' + (sw * 2); }
            g.appendChild(svg('polygon', attrs));
            n.appendChild(g);
          }
        }
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
      if (el.lock) { n.style.pointerEvents = 'none'; n.classList.add('locked'); }
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
      b.appendChild(h('span', { class: 'bdg bdg-mute' }, el.type === 'text' ? T('Teks') : (el.type === 'qr' ? 'QR' : (el.type === 'shape' ? T('Bentuk') : (D.kinds[el.slot] || T('Gambar'))))));
      b.appendChild(h('span', { class: 'ed-lt' }, label(el)));
      if (el.lock) b.appendChild(h('span', { class: 'small muted' }, T('terkunci')));
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
      else if (el.type === 'shape') { el.w = round(clamp(drag.w + dx, 0.2, 200)); if (el.shape !== 'line') el.h = round(clamp(drag.h + dy, 0.2, 200)); }
      else { el.w = round(clamp(drag.w + dx, 1, 100)); if (el.type === 'image') el.h = round(clamp(drag.h + dy, 1, 100)); }
    } else {
      el.x = round(clamp(drag.x + dx, lo(el), 99));
      el.y = round(clamp(drag.y + dy, lo(el), 99));
    }
    var n = cv.querySelector('[data-i="' + drag.i + '"]');
    var hasH = el.type === 'image' || (el.type === 'shape' && el.shape !== 'line');
    if (n) { n.style.left = el.x + '%'; n.style.top = el.y + '%'; if (el.type !== 'qr') { n.style.width = el.w + '%'; if (hasH) n.style.height = el.h + '%'; } }
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
    if (map[e.key]) { e.preventDefault(); el[map[e.key][0]] = round(clamp(el[map[e.key][0]] + map[e.key][1], lo(el), 99)); markDirty(); draw(); showProps(); }
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
  function color(key, lbl, allowNone) {
    var el = els[sel];
    var f = h('div', { class: 'fld' });
    f.appendChild(h('label', {}, lbl));
    var row = h('div', { class: 'ed-color' });
    var c = h('input', { class: 'inp', type: 'color', value: el[key] || '#FFFFFF', 'aria-label': lbl });
    var tx = h('input', { class: 'inp mono', maxlength: '7', value: el[key] || '', placeholder: allowNone ? T('Tanpa') : '#RRGGBB', 'aria-label': lbl + ' (hex)' });
    c.disabled = tx.disabled = D.readonly;
    function set(v, from) {
      var toggled = !el[key] !== !v;
      el[key] = v; if (from !== 'tx') tx.value = v; if (from !== 'c' && v) c.value = v;
      markDirty(); draw();
      if (toggled && key === 'fill2') showProps();
    }
    c.addEventListener('input', function () { set(c.value.toUpperCase(), 'c'); });
    tx.addEventListener('input', function () {
      var v = tx.value.trim().toUpperCase();
      if (/^#[0-9A-F]{6}$/.test(v)) set(v, 'tx'); else if (v === '' && allowNone) set('', 'tx');
    });
    row.appendChild(c); row.appendChild(tx);
    f.appendChild(row);
    if (!D.readonly) {
      var sw = h('div', { class: 'ed-sw' });
      SWATCH.forEach(function (hex) { var b = h('button', { type: 'button', title: hex, 'aria-label': hex, style: 'background:' + hex }); b.addEventListener('click', function () { set(hex); }); sw.appendChild(b); });
      if (allowNone) { var nb = h('button', { type: 'button', class: 'none', title: T('Tanpa'), 'aria-label': T('Tanpa') }); nb.addEventListener('click', function () { set(''); tx.value = ''; }); sw.appendChild(nb); }
      f.appendChild(sw);
    }
    return f;
  }
  function range(key, lbl, min, max, stepv, def) {
    var el = els[sel];
    if (el[key] === undefined) el[key] = def;
    var f = h('div', { class: 'fld' });
    var l = h('label', {}, lbl + ': ' + Math.round(el[key] * 100) + '%');
    var r = h('input', { type: 'range', min: min, max: max, step: stepv, value: el[key], class: 'ed-range' });
    r.disabled = D.readonly;
    r.addEventListener('input', function () { el[key] = round(parseFloat(r.value)); l.textContent = lbl + ': ' + Math.round(el[key] * 100) + '%'; markDirty(); draw(); });
    f.appendChild(l); f.appendChild(r);
    return f;
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
      g.appendChild(num('rot', T('Rotasi (°)'), -180, 180, 1));
      g.appendChild(num('lh', T('Jarak baris'), 0.8, 3, 0.05));
      g.appendChild(num('ls', T('Jarak huruf (pt)'), -1, 10, 0.1));
      body.appendChild(g);
      body.appendChild(color('color', T('Warna teks'), false));
      body.appendChild(range('opacity', T('Kepekatan'), 0.05, 1, 0.05, 1));
      var fm = FM[el.font] || [];
      var row = h('div', { class: 'row' });
      if (fm[2] !== false) row.appendChild(check('bold', T('Tebal')));
      if (fm[3] !== false) row.appendChild(check('italic', T('Miring')));
      row.appendChild(check('underline', T('Garis bawah')));
      row.appendChild(check('upper', T('HURUF KAPITAL')));
      row.appendChild(check('fit', T('Kecilkan agar muat 1 baris')));
      body.appendChild(row);
      if (fm[2] === false || fm[3] === false) body.appendChild(h('p', { class: 'hint small' }, T('Font ini punya ketebalan tetap. Pilih varian lain (mis. Sora SemiBold/ExtraBold) untuk ketebalan berbeda.')));
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
    } else if (el.type === 'shape') {
      varsBox.hidden = true;
      body.appendChild(select('shape', T('Bentuk'), D.shapes));
      if (el.shape === 'line') {
        body.appendChild(color('stroke', T('Warna garis'), false));
        var gl = h('div', { class: 'g-form two' });
        gl.appendChild(num('sw', T('Tebal garis (mm)'), 0.1, 10, 0.1));
        gl.appendChild(num('rot', T('Rotasi (°)'), -180, 180, 1));
        body.appendChild(gl);
        body.appendChild(h('div', { class: 'row' }, null)).appendChild(check('dash', T('Garis putus-putus')));
      } else {
        body.appendChild(color('fill', T('Warna isi'), true));
        var gc = color('fill2', T('Warna gradasi (opsional)'), true);
        body.appendChild(gc);
        if (el.fill2) body.appendChild(select('grad', T('Arah gradasi'), { h: T('Kiri → kanan'), v: T('Atas → bawah'), d: T('Diagonal') }));
        body.appendChild(color('stroke', T('Warna garis tepi'), true));
        var gs = h('div', { class: 'g-form two' });
        gs.appendChild(num('sw', T('Tebal tepi (mm)'), 0, 10, 0.1));
        gs.appendChild(num('rot', T('Rotasi (°)'), -180, 180, 1));
        if (el.shape === 'rect') gs.appendChild(num('radius', T('Sudut membulat (mm)'), 0, 50, 0.5));
        body.appendChild(gs);
        var rs = h('div', { class: 'row' });
        if ((D.polygons || {})[el.shape]) rs.appendChild(check('flip', T('Cerminkan')));
        rs.appendChild(check('dash', T('Tepi putus-putus')));
        body.appendChild(rs);
      }
      body.appendChild(range('opacity', T('Kepekatan'), 0.05, 1, 0.05, 1));
    } else {
      varsBox.hidden = true;
      body.appendChild(h('p', { class: 'small muted' }, T('QR berisi tautan halaman verifikasi sertifikat (bukan QR presensi). Tautan dibuat otomatis saat generate.')));
    }
    var g2 = h('div', { class: 'g-form two' });
    g2.appendChild(num('x', T('Posisi X (%)'), lo(el), 99));
    g2.appendChild(num('y', T('Posisi Y (%)'), lo(el), 99));
    if (el.type === 'qr') g2.appendChild(num('size', T('Sisi QR (% lebar)'), 3, 40));
    else if (el.type === 'shape') g2.appendChild(num('w', el.shape === 'line' ? T('Panjang (%)') : T('Lebar (%)'), 0.2, 200, 0.2));
    else g2.appendChild(num('w', T('Lebar (%)'), 1, 100));
    if (el.type === 'image') g2.appendChild(num('h', T('Tinggi (%)'), 1, 100));
    if (el.type === 'shape' && el.shape !== 'line') g2.appendChild(num('h', T('Tinggi (%)'), 0.2, 200, 0.2));
    body.appendChild(g2);
    body.appendChild(h('div', { class: 'row' })).appendChild(check('lock', T('Kunci di kanvas (pilih lewat daftar elemen)')));
    if (!D.readonly) {
      var acts = h('div', { class: 'row' });
      [['up', T('Ke depan')], ['down', T('Ke belakang')], ['front', T('Paling depan')], ['back', T('Paling belakang')], ['dup', T('Duplikat')], ['center', T('Tengahkan')], ['del', T('Hapus')]].forEach(function (a) {
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
    else if (a === 'front') { els.push(els.splice(sel, 1)[0]); sel = els.length - 1; }
    else if (a === 'back') { els.unshift(els.splice(sel, 1)[0]); sel = 0; }
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
    function txt(content, o) {
      var t = { id: uid(), type: 'text', content: content, x: 20, y: 45, w: 60, font: 'sora', size: 16, bold: false, italic: false, underline: false, upper: false, color: '#1F2937', opacity: 1, rot: 0, align: 'C', lh: 1.25, ls: 0, fit: false };
      for (var q in (o || {})) t[q] = o[q];
      return t;
    }
    function shp(shape, o) {
      var t = { id: uid(), type: 'shape', shape: shape, x: 35, y: 35, w: 30, h: 20, fill: '#14B8A6', fill2: '', grad: 'h', opacity: 1, stroke: '', sw: 0, radius: 0, rot: 0, flip: false, dash: false };
      for (var q in (o || {})) t[q] = o[q];
      return t;
    }
    var add = [];
    if (k === 'text') add.push(txt(T('Teks baru')));
    else if (k === 'qr') add.push({ id: uid(), type: 'qr', x: 5, y: 75, size: 11 });
    else if (k === 'shape') add.push(shp('rect'));
    else if (k === 'line') add.push(shp('line', { fill: '', stroke: '#14B8A6', sw: 0.8, w: 40, h: 1, x: 30, y: 50 }));
    else if (k === 'bg') { els.unshift(shp('rect', { x: 0, y: 0, w: 100, h: 100, fill: '#0B1F3A', fill2: '#123A5A', grad: 'd', lock: true })); sel = 0; markDirty(); draw(); showProps(); return; }
    else if (k === 'signblock') {
      var used = JSON.stringify(els);
      var n = ['', '2', '3'].filter(function (p) { return used.indexOf('{SIGNER' + p + '_NAME}') < 0; })[0];
      if (n === undefined) { window.alert(T('Sudah ada 3 blok penanda tangan. Maksimal 3 tanda tangan per template.')); return; }
      var bx = { '': 6, '2': 39.5, '3': 73 }[n];
      add.push(txt('{SIGNER' + n + '_TITLE}', { x: bx, y: 74, w: 21, size: 9, font: 'sora600', lh: 1.15 }));
      add.push({ id: uid(), type: 'image', slot: 'signature', label: T('Tanda tangan penanda tangan :n', { n: n || '1' }), asset_id: null, x: bx + 4, y: 78, w: 13, h: 8 });
      add.push(txt('{SIGNER' + n + '_NAME}', { x: bx, y: 87, w: 21, size: 9, font: 'sora600', underline: true }));
      add.push(txt('NIP. {SIGNER' + n + '_ID}', { x: bx, y: 91, w: 21, size: 8 }));
    }
    else add.push({ id: uid(), type: 'image', slot: k, label: D.kinds[k], asset_id: null, x: 40, y: 40, w: k === 'signature' ? 20 : 10, h: k === 'signature' ? 10 : 14 });
    if (els.length + add.length > (D.max || 80)) { window.alert(T('Maksimal :n elemen per template.', { n: D.max || 80 })); return; }
    add.forEach(function (x) { els.push(x); });
    sel = els.length - add.length;
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

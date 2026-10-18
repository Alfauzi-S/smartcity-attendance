/* Progres generate massal: memanggil endpoint step berulang sampai selesai. Aman dibuka di dua tab (server mengunci job). */
(function () {
  'use strict';
  var T = window.T || function (s, p) { for (var k in (p || {})) { s = s.split(':' + k).join(p[k]); } return s; };
  var box = document.getElementById('job');
  if (!box || box.getAttribute('data-active') !== '1') return;
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var manual = document.getElementById('job-manual');
  if (manual) manual.hidden = true;
  var labels = { running: T('Berjalan'), done: T('Selesai'), paused: T('Dijeda'), busy: T('Diproses di tab lain'), queued: T('Menunggu') };
  function set(id, v) { var n = document.getElementById(id); if (n) n.textContent = v; }
  function errors(list) {
    var c = document.getElementById('job-errors');
    if (!list || !list.length) return;
    c.textContent = '';
    list.forEach(function (e) {
      var d = document.createElement('div'); d.className = 'ck ck-bad';
      var s = document.createElement('span'); var b = document.createElement('b'); b.textContent = e.name; s.appendChild(b); s.appendChild(document.createTextNode(' — ' + e.message));
      d.appendChild(s); c.appendChild(d);
    });
  }
  var fails = 0;
  function step() {
    fetch(box.getAttribute('data-step'), { method: 'POST', headers: { 'X-CSRF-Token': csrf, 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) throw new Error('http ' + r.status); return r.json(); })
      .then(function (d) {
        fails = 0;
        set('job-state', labels[d.state] || d.state);
        if (d.total !== undefined) {
          document.getElementById('job-bar').style.width = (d.total ? Math.floor(d.cursor / d.total * 100) : 100) + '%';
          set('job-progress', T(':done dari :total diproses', { done: d.cursor, total: d.total }));
          set('job-ok', d.success); set('job-skip', d.skipped); set('job-fail', d.failed);
          errors(d.errors);
        }
        if (d.state === 'running') return setTimeout(step, 150);
        if (d.state === 'busy') return setTimeout(step, 3000);
        if (d.state === 'paused') { document.getElementById('job-msg').hidden = false; return; }
        if (d.state === 'done') { document.getElementById('job-done-link').hidden = false; setTimeout(function () { location.reload(); }, 800); }
      })
      .catch(function () {
        fails++;
        set('job-state', T('Koneksi terputus, mencoba lagi…'));
        if (fails < 6) setTimeout(step, 2000 * fails); else if (manual) manual.hidden = false;
      });
  }
  step();
})();

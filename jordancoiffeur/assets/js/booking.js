// Online-Buchung – Jordan Coiffeur & Barbershop
(() => {
  const API = '/api/';
  const $ = (s, r = document) => r.querySelector(s);
  const el = (tag, attrs = {}, html = '') => { const e = document.createElement(tag); Object.entries(attrs).forEach(([k, v]) => e.setAttribute(k, v)); e.innerHTML = html; return e; };
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const MONTHS = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
  const DOW = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
  const DAYS = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
  const pad = (n) => String(n).padStart(2, '0');
  const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  const parse = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
  const niceDate = (s) => { const d = parse(s); return `${DAYS[d.getDay()]}, ${d.getDate()}. ${MONTHS[d.getMonth()]}`; };

  let cat = null;
  const st = { step: 1, service: null, branch: null, staff: undefined, date: null, time: null, month: null };

  async function get(params) {
    const r = await fetch(API + '?' + new URLSearchParams(params));
    const j = await r.json();
    if (!r.ok) throw new Error(j.error || 'Fehler');
    return j;
  }

  function go(step) {
    st.step = step;
    document.querySelectorAll('[data-panel]').forEach((p) => { p.hidden = Number(p.dataset.panel) !== step; });
    document.querySelectorAll('.steps li').forEach((li) => {
      const n = Number(li.dataset.step);
      li.classList.toggle('active', n === step);
      li.classList.toggle('done', n < step);
    });
    $('#bk-back').hidden = step <= 1 || step >= 6;
    $('.steps').hidden = step >= 6;
    summary();
    if (step > 1) $('#booking').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function summary() {
    const set = (k, v) => { $(`[data-sum="${k}"]`).textContent = v || '–'; };
    set('service', st.service && `${st.service.name} (${st.service.duration} Min.)`);
    set('branch', st.branch && st.branch.name);
    set('staff', st.staff === null ? 'Egal – erster freier' : st.staff && st.staff.name);
    set('time', st.date && (niceDate(st.date) + (st.time ? `, ${st.time}` : '')));
  }

  function optionButtons(container, items, render, onPick, selected) {
    container.innerHTML = '';
    items.forEach((it) => {
      const b = el('button', { type: 'button', class: 'opt', 'aria-pressed': String(selected === it) }, render(it));
      b.addEventListener('click', () => onPick(it));
      container.append(b);
    });
  }

  function renderServices() {
    optionButtons($('#opt-services'), cat.services,
      (s) => `<strong>${esc(s.name)}</strong>${s.description ? `<span class="desc">${esc(s.description)}</span>` : ''}<span class="meta"><span>${s.duration} Min.</span><b>CHF ${esc(s.price)}</b></span>`,
      (s) => { st.service = s; st.date = st.time = null; renderServices(); afterService(); }, st.service);
  }

  let preBranch = false;
  function afterService() {
    if (cat.branches.length === 1) st.branch = cat.branches[0];
    if (cat.branches.length === 1 || (preBranch && st.branch)) { preBranch = false; afterBranch(); return; }
    renderBranches(); go(2);
  }

  function renderBranches() {
    optionButtons($('#opt-branches'), cat.branches,
      (b) => `<strong>${esc(b.name)}</strong><span class="desc">${esc(b.street)}, ${esc(b.city)}<br>${esc(b.phone)}</span>`,
      (b) => { if (st.branch !== b) { st.staff = undefined; st.date = st.time = null; } st.branch = b; renderBranches(); afterBranch(); }, st.branch);
  }

  function staffOf(branch) { return cat.staff.filter((s) => Number(s.branch_id) === Number(branch.id)); }

  function afterBranch() {
    const list = staffOf(st.branch);
    if (list.length <= 1) { st.staff = list[0] || null; openCalendar(); return; }
    renderStaff(); go(3);
  }

  function renderStaff() {
    const any = { id: null, name: 'Egal', any: true };
    const list = [any, ...staffOf(st.branch)];
    const sel = st.staff === null ? any : st.staff;
    optionButtons($('#opt-staff'), list,
      (s) => s.any ? `<span class="avatar">★</span><strong>Egal</strong><span class="desc">Erster freier Barber – meiste Termine</span>`
        : `<span class="avatar">${esc(s.name.trim().charAt(0))}</span><strong>${esc(s.name)}</strong>`,
      (s) => { st.staff = s.any ? null : s; st.date = st.time = null; renderStaff(); openCalendar(); }, sel);
  }

  /* ---------- Kalender ---------- */
  const today = new Date(); today.setHours(0, 0, 0, 0);
  function maxDate() { const d = new Date(today); d.setDate(d.getDate() + (cat.max_days || 60)); return d; }

  function openCalendar() {
    const m = st.date ? parse(st.date) : new Date(today);
    st.month = new Date(m.getFullYear(), m.getMonth(), 1);
    go(4);
    loadMonth();
  }

  let monthReq = 0;
  async function loadMonth() {
    const req = ++monthReq;
    const m = st.month;
    $('#cal-title').textContent = `${MONTHS[m.getMonth()]} ${m.getFullYear()}`;
    $('#cal-prev').disabled = m <= new Date(today.getFullYear(), today.getMonth(), 1);
    $('#cal-next').disabled = new Date(m.getFullYear(), m.getMonth() + 1, 1) > maxDate();
    $('.cal').classList.add('loading');
    drawMonth(new Set());
    try {
      const r = await get({ a: 'days', branch: st.branch.id, service: st.service.id, staff: st.staff ? st.staff.id : '', month: `${m.getFullYear()}-${pad(m.getMonth() + 1)}` });
      if (req !== monthReq) return;
      const free = new Set(r.days);
      drawMonth(free);
      if (!st.date) {
        if (r.days.length) pickDay(r.days[0]);
        else { $('#times-title').textContent = 'In diesem Monat ist nichts mehr frei.'; $('#time-grid').innerHTML = ''; }
      }
    } catch (e) {
      $('#times-title').textContent = e.message;
    } finally {
      if (req === monthReq) $('.cal').classList.remove('loading');
    }
  }

  function drawMonth(free) {
    const g = $('#cal-grid');
    g.innerHTML = DOW.map((d) => `<div class="dow">${d}</div>`).join('');
    const m = st.month;
    const offset = (new Date(m.getFullYear(), m.getMonth(), 1).getDay() + 6) % 7;
    for (let i = 0; i < offset; i++) g.append(el('div'));
    const days = new Date(m.getFullYear(), m.getMonth() + 1, 0).getDate();
    for (let d = 1; d <= days; d++) {
      const ds = `${m.getFullYear()}-${pad(m.getMonth() + 1)}-${pad(d)}`;
      const isFree = free.has(ds);
      const b = el('button', { type: 'button', class: 'day' + (isFree ? ' free' : '') + (st.date === ds ? ' sel' : '') + (ds === ymd(today) ? ' today' : ''), 'aria-label': niceDate(ds) + (isFree ? '' : ' – ausgebucht oder geschlossen') }, String(d));
      if (!isFree) b.disabled = true; else b.addEventListener('click', () => pickDay(ds));
      g.append(b);
    }
  }

  async function pickDay(ds) {
    st.date = ds; st.time = null;
    document.querySelectorAll('.day.sel').forEach((d) => d.classList.remove('sel'));
    const btn = [...document.querySelectorAll('.day.free')].find((b) => b.textContent === String(parse(ds).getDate()));
    if (btn) btn.classList.add('sel');
    summary();
    $('#times-title').textContent = niceDate(ds);
    const grid = $('#time-grid');
    grid.innerHTML = '<p class="muted">Lade freie Zeiten …</p>';
    try {
      const r = await get({ a: 'slots', branch: st.branch.id, service: st.service.id, staff: st.staff ? st.staff.id : '', date: ds });
      if (st.date !== ds) return;
      grid.innerHTML = '';
      if (!r.times.length) { grid.innerHTML = '<p class="empty">Keine freien Zeiten mehr an diesem Tag.</p>'; return; }
      r.times.forEach((t) => {
        const b = el('button', { type: 'button', class: 'time', 'aria-pressed': 'false' }, t);
        b.addEventListener('click', () => { st.time = t; summary(); go(5); setTimeout(() => $('#bk-form [name=name]').focus({ preventScroll: true }), 300); });
        grid.append(b);
      });
    } catch (e) { grid.innerHTML = `<p class="empty">${esc(e.message)}</p>`; }
  }

  $('#cal-prev').addEventListener('click', () => { st.month = new Date(st.month.getFullYear(), st.month.getMonth() - 1, 1); st.date = null; loadMonth(); });
  $('#cal-next').addEventListener('click', () => { st.month = new Date(st.month.getFullYear(), st.month.getMonth() + 1, 1); st.date = null; loadMonth(); });

  /* ---------- Formular ---------- */
  $('#bk-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = e.target;
    const errBox = $('#form-error');
    errBox.hidden = true;
    const data = Object.fromEntries(new FormData(f));
    if (data.name.trim().length < 2) return showErr('Bitte gib deinen Namen an.', f.name);
    if (!/^[+0-9 ()\/.-]{7,25}$/.test(data.phone.trim())) return showErr('Bitte gib eine gültige Telefonnummer an.', f.phone);
    if (data.email && !f.email.checkValidity()) return showErr('Die E-Mail-Adresse ist ungültig.', f.email);
    const btn = $('#bk-submit');
    btn.disabled = true; btn.textContent = 'Wird gebucht …';
    try {
      const r = await fetch(API + '?a=book', { method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...data, branch_id: st.branch.id, service_id: st.service.id, staff_id: st.staff ? st.staff.id : null, date: st.date, time: st.time }) });
      const j = await r.json();
      if (!r.ok) {
        if (r.status === 409) { showErr(j.error); st.time = null; setTimeout(() => { openCalendar(); pickDay(st.date); }, 1800); return; }
        throw new Error(j.error || 'Fehler');
      }
      const b = j.booking;
      $('#done-text').innerHTML = `<strong>${esc(b.service)}</strong><br>${esc(b.when)}<br>bei ${esc(b.staff)} · ${esc(b.address)}`;
      $('#done-mail').textContent = b.email_sent ? 'Du erhältst gleich eine Bestätigung per E-Mail.' : 'Speichere dir den Link unten, um den Termin bei Bedarf abzusagen.';
      $('#done-manage').href = b.manage_url;
      go(6);
    } catch (err) {
      showErr(err.message || 'Verbindung fehlgeschlagen. Bitte versuch es nochmals oder ruf uns an.');
    } finally {
      btn.disabled = false; btn.textContent = 'Verbindlich buchen';
    }
    function showErr(msg, field) { errBox.textContent = msg; errBox.hidden = false; if (field) field.focus(); }
  });

  $('#bk-back').addEventListener('click', () => {
    let s = st.step - 1;
    if (s === 3 && staffOf(st.branch).length <= 1) s = 2;
    if (s === 2 && cat.branches.length === 1) s = 1;
    if (s === 4) { openCalendar(); if (st.date) pickDay(st.date); return; }
    go(s);
  });

  /* ---------- Start ---------- */
  get({ a: 'catalog' }).then((c) => {
    cat = c;
    const pre = new URLSearchParams(location.search).get('filiale');
    if (pre) { st.branch = cat.branches.find((b) => String(b.id) === pre) || null; preBranch = !!st.branch; }
    renderServices();
    if (st.branch) renderBranches();
    go(1);
  }).catch(() => {
    $('#opt-services').innerHTML = '<p class="form-error">Die Buchung ist gerade nicht erreichbar. Bitte ruf uns an: <a href="tel:+41712201000">071 220 10 00</a></p>';
  });
})();

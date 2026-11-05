// Jordan Admin – Kalender & Verwaltung
(() => {
  const app = document.getElementById('app');
  const CSRF = app.dataset.csrf;
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const pad = (n) => String(n).padStart(2, '0');
  const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  const parseD = (s) => { const [y, m, d] = s.slice(0, 10).split('-').map(Number); return new Date(y, m - 1, d); };
  const addDays = (s, n) => { const d = parseD(s); d.setDate(d.getDate() + n); return ymd(d); };
  const t2m = (t) => { const [h, m] = t.split(':').map(Number); return h * 60 + m; };
  const m2t = (m) => `${pad(Math.floor(m / 60))}:${pad(m % 60)}`;
  const dtAdd = (dt, min) => { const d = parseD(dt); const m = t2m(dt.slice(11, 16)) + min; d.setDate(d.getDate() + Math.floor(m / 1440)); return `${ymd(d)} ${m2t(((m % 1440) + 1440) % 1440)}`; };
  const diffMin = (a, b) => Math.round((new Date(b.replace(' ', 'T')) - new Date(a.replace(' ', 'T'))) / 60000);
  const WD = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
  const WD_LONG = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
  const MON = ['Jan.', 'Feb.', 'März', 'Apr.', 'Mai', 'Juni', 'Juli', 'Aug.', 'Sep.', 'Okt.', 'Nov.', 'Dez.'];
  const isoDow = (s) => ((parseD(s).getDay() + 6) % 7) + 1; // 1 = Mo
  const fmtDay = (s) => { const d = parseD(s); return `${WD[d.getDay()]} ${d.getDate()}. ${MON[d.getMonth()]}`; };
  const PPM = () => parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--ppm')) || 1.6;

  async function api(a, data = {}) {
    const r = await fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF }, body: JSON.stringify({ a, ...data }) });
    if (r.status === 401) { location.reload(); throw new Error('Abgemeldet'); }
    const j = await r.json().catch(() => ({ error: 'Serverfehler' }));
    if (!r.ok) { const e = new Error(j.error || 'Fehler'); e.data = j; e.status = r.status; throw e; }
    return j;
  }
  function toast(msg, err = false) {
    const t = $('#toast'); t.textContent = msg; t.className = 'show' + (err ? ' err' : '');
    clearTimeout(toast.t); toast.t = setTimeout(() => { t.className = ''; }, 3200);
  }

  /* ---------- Zustand ---------- */
  let D = null; // Stammdaten
  const saved = (() => { try { return JSON.parse(localStorage.getItem('jc-admin') || '{}'); } catch { return {}; } })();
  const S = { view: 'cal', mode: saved.mode || 'day', date: ymd(new Date()), branch: saved.branch || null, weekStaff: saved.weekStaff || '', events: { bookings: [], blocks: [] } };
  const persist = () => { try { localStorage.setItem('jc-admin', JSON.stringify({ mode: S.mode, branch: S.branch, weekStaff: S.weekStaff })); } catch {} };

  const staffById = (id) => D.staff.find((s) => Number(s.id) === Number(id));
  const serviceById = (id) => D.services.find((s) => Number(s.id) === Number(id));
  const branchStaff = (bid, all = false) => D.staff.filter((s) => Number(s.branch_id) === Number(bid) && (all || Number(s.active)));
  const hoursOf = (bid, date) => D.hours.find((h) => Number(h.branch_id) === Number(bid) && Number(h.weekday) === isoDow(date));

  async function loadData() {
    D = await api('bootstrap');
    if (!S.branch || !D.branches.some((b) => Number(b.id) === Number(S.branch))) S.branch = D.branches[0]?.id;
    $('#branch-sel').innerHTML = D.branches.map((b) => `<option value="${b.id}">${esc(b.name)}</option>`).join('');
    $('#branch-sel').value = S.branch;
    fillWeekStaff();
  }
  function fillWeekStaff() {
    const sel = $('#staff-sel');
    sel.innerHTML = '<option value="">Alle Mitarbeiter</option>' + branchStaff(S.branch).map((s) => `<option value="${s.id}">${esc(s.name)}</option>`).join('');
    if (![...sel.options].some((o) => o.value === String(S.weekStaff))) S.weekStaff = '';
    sel.value = S.weekStaff;
  }

  /* ---------- Kalender ---------- */
  function range() {
    if (S.mode === 'day') return [S.date, S.date];
    const start = addDays(S.date, -(isoDow(S.date) - 1));
    return [start, addDays(start, 6)];
  }

  async function refresh(quiet = false) {
    const [from, to] = range();
    try {
      S.events = await api('events', { from, to });
    } catch (e) { if (!quiet) toast(e.message, true); return; }
    renderCal();
  }

  function columns() {
    const [from] = range();
    if (S.mode === 'day') {
      return branchStaff(S.branch).map((s) => ({ date: S.date, staff: s, label: `<span class="dot" style="background:${esc(s.color)}"></span>${esc(s.name)}` }));
    }
    const cols = [];
    for (let i = 0; i < 7; i++) {
      const d = addDays(from, i);
      const open = hoursOf(S.branch, d);
      const hasEv = S.events.bookings.some((b) => b.start.startsWith(d) && Number(b.branch_id) === Number(S.branch));
      if (!open && !hasEv && isoDow(d) === 7) continue; // Sonntag nur bei Bedarf
      cols.push({ date: d, staff: S.weekStaff ? staffById(S.weekStaff) : null, label: `${fmtDay(d)}`, today: d === ymd(new Date()) });
    }
    return cols;
  }

  function renderCal() {
    const cols = columns();
    const cal = $('#cal');
    const [from, to] = range();
    $('#range-title').textContent = S.mode === 'day'
      ? `${WD_LONG[parseD(S.date).getDay()]}, ${parseD(S.date).getDate()}. ${MON[parseD(S.date).getMonth()]} ${parseD(S.date).getFullYear()}`
      : `${fmtDay(from)} – ${fmtDay(to)}`;
    $('#date-pick').value = S.date;

    if (!cols.length) { cal.innerHTML = '<p style="padding:24px">Keine aktiven Mitarbeiter in dieser Filiale. Unter «Einstellungen» hinzufügen.</p>'; return; }

    // sichtbarer Zeitbereich
    let lo = 24 * 60, hi = 0;
    cols.forEach((c) => { const h = hoursOf(S.branch, c.date); if (h) { lo = Math.min(lo, t2m(h.open)); hi = Math.max(hi, t2m(h.close)); } });
    if (hi === 0) { lo = 8 * 60; hi = 19 * 60; }
    const evs = visibleBookings(cols);
    evs.forEach((b) => { lo = Math.min(lo, t2m(b.start.slice(11, 16))); hi = Math.max(hi, b.end.slice(0, 10) > b.start.slice(0, 10) ? 1440 : t2m(b.end.slice(11, 16))); });
    lo = Math.max(0, Math.floor((lo - 30) / 60) * 60);
    hi = Math.min(1440, Math.ceil((hi + 30) / 60) * 60);
    const ppm = PPM();
    const H = (hi - lo) * ppm;

    const inner = document.createElement('div');
    inner.className = 'cal-inner';
    inner.style.gridTemplateColumns = `56px repeat(${cols.length}, minmax(${S.mode === 'day' ? 160 : 120}px, 1fr))`;
    inner.innerHTML = `<div class="ch corner"></div>` + cols.map((c) => `<div class="ch${c.today ? ' today' : ''}">${c.label}</div>`).join('');
    const axis = document.createElement('div');
    axis.className = 'axis'; axis.style.height = H + 'px'; axis.style.position = 'sticky';
    for (let m = lo; m <= hi; m += 60) axis.insertAdjacentHTML('beforeend', `<div style="top:${(m - lo) * ppm}px">${m2t(m)}</div>`);
    axis.firstElementChild && (axis.firstElementChild.style.transform = 'none');
    inner.append(axis);

    const now = new Date(); const nowD = ymd(now); const nowM = now.getHours() * 60 + now.getMinutes();
    cols.forEach((c, ci) => {
      const col = document.createElement('div');
      col.className = 'col'; col.style.height = H + 'px'; col.dataset.ci = ci;
      // geschlossene Zeiten schraffieren
      const h = hoursOf(S.branch, c.date);
      const works = !c.staff || String(c.staff.workdays).includes(String(isoDow(c.date)));
      const shade = (a, b) => { if (b > a) col.insertAdjacentHTML('beforeend', `<div class="closed" style="top:${(a - lo) * ppm}px;height:${(b - a) * ppm}px"></div>`); };
      if (!h || !works) shade(lo, hi); else { shade(lo, t2m(h.open)); shade(t2m(h.close), hi); }
      if (c.date === nowD && nowM >= lo && nowM <= hi) col.insertAdjacentHTML('beforeend', `<div class="now" style="top:${(nowM - lo) * ppm}px"></div>`);

      // Sperrzeiten
      S.events.blocks.forEach((bl) => {
        const relevant = bl.staff_id ? (c.staff ? Number(bl.staff_id) === Number(c.staff.id) : Number(staffById(bl.staff_id)?.branch_id) === Number(S.branch))
          : (!bl.branch_id || Number(bl.branch_id) === Number(S.branch));
        if (!relevant) return;
        const seg = clip(bl.start, bl.end, c.date, lo, hi); if (!seg) return;
        const who = bl.staff_id ? (staffById(bl.staff_id)?.name || '') : 'Ganze Filiale';
        const e = document.createElement('div');
        e.className = 'ev block';
        e.style.cssText = `top:${(seg[0] - lo) * ppm}px;height:${Math.max(18, (seg[1] - seg[0]) * ppm - 2)}px;left:3px;right:3px`;
        e.innerHTML = `<b>${esc(bl.note || 'Abwesend')}</b><span class="t">${esc(S.mode === 'week' && !c.staff ? who : '')}</span>`;
        e.addEventListener('click', (ev) => { ev.stopPropagation(); blockDialog(bl); });
        col.append(e);
      });

      // Termine (nebeneinander, wenn sie sich überlappen)
      const mine = evs.filter((b) => b.start.slice(0, 10) <= c.date && b.end.slice(0, 10) >= c.date && (c.staff ? Number(b.staff_id) === Number(c.staff.id) : true));
      layout(mine).forEach(({ b, lane, lanes }) => {
        const seg = clip(b.start, b.end, c.date, lo, hi); if (!seg) return;
        const st = staffById(b.staff_id);
        const e = document.createElement('div');
        e.className = 'ev' + (b.status === 'noshow' ? ' noshow' : '');
        e.dataset.id = b.id;
        e.style.cssText = `--c:${st?.color || '#f2a541'};top:${(seg[0] - lo) * ppm}px;height:${Math.max(20, (seg[1] - seg[0]) * ppm - 2)}px;left:calc(${(lane / lanes) * 100}% + 3px);width:calc(${100 / lanes}% - 6px)`;
        e.innerHTML = `<b>${esc(b.name)}${b.source === 'online' ? '' : '<span class="tag">Salon</span>'}</b><span class="t">${b.start.slice(11, 16)}–${b.end.slice(11, 16)} · ${esc(b.service_name)}${S.mode === 'week' && !c.staff && st ? ' · ' + esc(st.name) : ''}</span>`;
        e.title = `${b.name}\n${b.service_name}\n${b.start.slice(11, 16)}–${b.end.slice(11, 16)}${b.phone ? '\n' + b.phone : ''}${b.note ? '\n' + b.note : ''}`;
        enableDrag(e, b, c, lo);
        col.append(e);
      });

      col.addEventListener('click', (ev) => {
        if (ev.target !== col && !ev.target.classList.contains('closed')) return;
        const y = ev.clientY - col.getBoundingClientRect().top;
        const step = Number(D.settings.slot_step || 15);
        const m = lo + Math.floor(y / ppm / step) * step;
        const staff = c.staff || branchStaff(S.branch)[0];
        bookingDialog({ staff_id: staff?.id, start: `${c.date} ${m2t(m)}` });
      });
      inner.append(col);
    });
    cal.innerHTML = '';
    cal.append(inner);
    calCtx = { cols, lo, ppm, inner };
    if (!renderCal.scrolled) {
      renderCal.scrolled = true;
      const first = evs.filter((b) => b.start.slice(0, 10) >= range()[0]).map((b) => t2m(b.start.slice(11, 16))).sort((x, y) => x - y)[0];
      const target = (S.mode === 'day' && S.date === nowD ? Math.max(lo, nowM - 60) : Math.max(lo, Math.min(first ?? lo, lo + 60) - 30)) - lo;
      cal.scrollTop = target * ppm;
    }
  }
  let calCtx = null;

  function visibleBookings(cols) {
    const ids = new Set(branchStaff(S.branch, true).map((s) => Number(s.id)));
    return S.events.bookings.filter((b) => ids.has(Number(b.staff_id)) || Number(b.branch_id) === Number(S.branch));
  }
  function clip(s, e, date, lo, hi) {
    if (s.slice(0, 10) > date || e.slice(0, 10) < date) return null;
    let a = s.slice(0, 10) < date ? 0 : t2m(s.slice(11, 16));
    let b = e.slice(0, 10) > date ? 1440 : t2m(e.slice(11, 16));
    a = Math.max(a, lo); b = Math.min(b, hi);
    return b > a ? [a, b] : null;
  }
  function layout(list) {
    const sorted = [...list].sort((a, b) => a.start.localeCompare(b.start) || b.end.localeCompare(a.end));
    const out = []; let group = []; let groupEnd = '';
    const flush = () => {
      const lanesEnd = [];
      group.forEach((it) => {
        let l = lanesEnd.findIndex((end) => end <= it.b.start);
        if (l === -1) { l = lanesEnd.length; lanesEnd.push(it.b.end); } else lanesEnd[l] = it.b.end;
        it.lane = l;
      });
      group.forEach((it) => { it.lanes = lanesEnd.length; out.push(it); });
      group = [];
    };
    sorted.forEach((b) => {
      if (group.length && b.start >= groupEnd) { flush(); groupEnd = ''; }
      group.push({ b });
      if (!groupEnd || b.end > groupEnd) groupEnd = b.end;
    });
    flush();
    return out;
  }

  /* Ziehen zum Verschieben */
  function enableDrag(el, b, col, lo) {
    let sx, sy, moved = false, ghost = null, target = null;
    el.addEventListener('pointerdown', (e) => {
      if (e.button !== 0) return;
      sx = e.clientX; sy = e.clientY; moved = false;
      el.setPointerCapture(e.pointerId);
    });
    el.addEventListener('pointermove', (e) => {
      if (sx === undefined) return;
      if (!moved && Math.hypot(e.clientX - sx, e.clientY - sy) < 6) return;
      moved = true;
      const { cols, ppm, inner } = calCtx;
      const colEls = $$('.col', inner);
      const hit = colEls.find((c) => { const r = c.getBoundingClientRect(); return e.clientX >= r.left && e.clientX <= r.right; }) || el.parentElement;
      const ci = Number(hit.dataset.ci);
      const step = Number(D.settings.slot_step || 15);
      const dur = diffMin(b.start, b.end);
      const origTop = t2m(b.start.slice(11, 16));
      let m = origTop + Math.round((e.clientY - sy) / ppm / step) * step;
      m = Math.max(0, Math.min(1440 - dur, m));
      target = { col: cols[ci], m };
      if (!ghost) { ghost = document.createElement('div'); ghost.className = 'ghost-slot'; el.style.opacity = '.4'; }
      ghost.style.top = (m - lo) * ppm + 'px'; ghost.style.height = dur * ppm + 'px';
      ghost.textContent = `${m2t(m)}–${m2t(m + dur)}`;
      hit.append(ghost);
    });
    const end = async () => {
      if (sx === undefined) return;
      sx = undefined;
      if (!moved) { bookingDialog(b); return; }
      ghost?.remove(); el.style.opacity = '';
      if (!target) return;
      const staffId = target.col.staff ? target.col.staff.id : b.staff_id;
      const start = `${target.col.date} ${m2t(target.m)}`;
      if (start === b.start && Number(staffId) === Number(b.staff_id)) return;
      await saveBooking({ ...b, staff_id: staffId, start, end: dtAdd(start, diffMin(b.start, b.end)) }, true);
    };
    el.addEventListener('pointerup', end);
    el.addEventListener('pointercancel', () => { sx = undefined; ghost?.remove(); el.style.opacity = ''; });
    el.addEventListener('click', (e) => e.stopPropagation());
  }

  async function saveBooking(data, fromDrag = false) {
    try {
      await api('booking.save', data);
      toast(fromDrag ? 'Termin verschoben.' : 'Gespeichert.');
      refresh(true);
      return true;
    } catch (e) {
      if (e.data?.conflict && confirm(e.message + '\n\nTrotzdem speichern (Doppelbelegung)?')) {
        await api('booking.save', { ...data, force: true });
        toast('Gespeichert (doppelt belegt).');
        refresh(true);
        return true;
      }
      if (!e.data?.conflict) toast(e.message, true);
      refresh(true);
      return false;
    }
  }

  /* ---------- Dialoge ---------- */
  const dlg = $('#dlg'); const form = $('#dlg-form');
  function openDialog(html, bind) { form.innerHTML = html; bind(form); dlg.showModal(); }
  dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close(); });

  function staffOptions(selected, withAll = false) {
    return (withAll ? `<option value="">— Ganze Filiale —</option>` : '') + D.branches.map((br) => {
      const list = D.staff.filter((s) => Number(s.branch_id) === Number(br.id) && (Number(s.active) || Number(s.id) === Number(selected)));
      return `<optgroup label="${esc(br.name)}">${list.map((s) => `<option value="${s.id}" ${Number(s.id) === Number(selected) ? 'selected' : ''}>${esc(s.name)}</option>`).join('')}</optgroup>`;
    }).join('');
  }

  function bookingDialog(b) {
    const isNew = !b.id;
    const svc = b.service_id ? serviceById(b.service_id) : null;
    const dur = b.end ? diffMin(b.start, b.end) : 30;
    const statusTxt = { confirmed: 'Bestätigt', noshow: 'Nicht erschienen', cancelled: 'Abgesagt' }[b.status] || '';
    openDialog(`
      <h2>${isNew ? 'Neuer Termin' : 'Termin bearbeiten'}</h2>
      ${!isNew ? `<p class="meta">${b.source === 'online' ? 'Online gebucht' : 'Im Salon eingetragen'} am ${esc(b.created_at)} · ${statusTxt}</p>` : ''}
      <label>Dienstleistung<select name="service_id">
        ${D.services.filter((s) => Number(s.active) || Number(s.id) === Number(b.service_id)).map((s) => `<option value="${s.id}" data-d="${s.duration}" ${Number(s.id) === Number(b.service_id) ? 'selected' : ''}>${esc(s.name)} (${s.duration} Min.)</option>`).join('')}
        <option value="" ${!isNew && !b.service_id ? 'selected' : ''}>Andere …</option>
      </select></label>
      <label data-other ${!isNew && !b.service_id ? '' : 'hidden'}>Bezeichnung<input name="service_name" value="${esc(!b.service_id ? b.service_name || '' : '')}"></label>
      <label>Mitarbeiter<select name="staff_id">${staffOptions(b.staff_id)}</select></label>
      <div class="three">
        <label>Datum<input type="date" name="date" value="${b.start.slice(0, 10)}" required></label>
        <label>Beginn<input type="time" name="time" value="${b.start.slice(11, 16)}" step="300" required></label>
        <label>Dauer (Min.)<input type="number" name="dur" value="${svc && isNew ? svc.duration : dur}" min="5" step="5" required></label>
      </div>
      <div><button type="button" class="b ghost sm" data-free>Freie Zeiten anzeigen</button><div class="slots" data-slots></div></div>
      <label>Name<input name="name" value="${esc(b.name || '')}" required autocomplete="off"></label>
      <div class="two">
        <label>Telefon<input name="phone" type="tel" value="${esc(b.phone || '')}"></label>
        <label>E-Mail<input name="email" type="email" value="${esc(b.email || '')}"></label>
      </div>
      <label>Bemerkung<textarea name="note" rows="2">${esc(b.note || '')}</textarea></label>
      ${isNew ? '<label class="chk"><input type="checkbox" name="notify" checked> Bestätigung per E-Mail an Kunden senden (wenn E-Mail angegeben)</label>' : ''}
      ${b.phone ? `<p class="meta"><a href="tel:${esc(b.phone.replace(/[^0-9+]/g, ''))}">📞 ${esc(b.phone)} anrufen</a></p>` : ''}
      <div class="foot">
        <div class="grp">
          ${!isNew ? `<button type="button" class="b danger sm" data-cancel>Absagen</button>
          <button type="button" class="b ghost sm" data-noshow>${b.status === 'noshow' ? 'Doch erschienen' : 'Nicht erschienen'}</button>` : ''}
        </div>
        <div class="grp"><button type="button" class="b ghost" data-close>Schliessen</button><button type="submit" class="b primary">Speichern</button></div>
      </div>`, (f) => {
      const sel = f.service_id;
      if (isNew && !b.service_id) sel.selectedIndex = 0;
      const syncSvc = () => {
        const o = sel.selectedOptions[0];
        $('[data-other]', f).hidden = !!o.value;
        if (o.dataset.d) f.dur.value = o.dataset.d;
      };
      if (isNew) syncSvc();
      sel.addEventListener('change', syncSvc);
      $('[data-close]', f).onclick = () => dlg.close();
      $('[data-free]', f).onclick = async () => {
        const box = $('[data-slots]', f);
        if (!f.service_id.value) { box.textContent = 'Bitte Dienstleistung wählen.'; return; }
        box.textContent = 'Lade …';
        try {
          const st = staffById(f.staff_id.value);
          const r = await api('slots', { branch_id: st.branch_id, staff_id: st.id, service_id: f.service_id.value, date: f.date.value });
          box.innerHTML = r.times.length ? r.times.map((t) => `<button type="button">${t}</button>`).join('') : 'Keine freie Zeit an diesem Tag.';
          $$('button', box).forEach((x) => x.onclick = () => { f.time.value = x.textContent; });
        } catch (e) { box.textContent = e.message; }
      };
      f.onsubmit = async (e) => {
        e.preventDefault();
        const start = `${f.date.value} ${f.time.value}`;
        const data = {
          id: b.id, staff_id: f.staff_id.value, service_id: f.service_id.value || null,
          service_name: f.service_id.value ? '' : (f.service_name.value || 'Termin'),
          start, end: dtAdd(start, Number(f.dur.value)), name: f.name.value.trim(), phone: f.phone.value.trim(),
          email: f.email.value.trim(), note: f.note.value.trim(), notify: f.notify?.checked,
        };
        if (!data.name) { f.name.focus(); return; }
        if (await saveBooking(data)) dlg.close();
      };
      if (!isNew) {
        $('[data-cancel]', f).onclick = async () => {
          if (!confirm('Termin absagen? Er wird aus dem Kalender entfernt.')) return;
          const notify = b.email ? confirm('Kunde per E-Mail über die Absage informieren?') : false;
          try { await api('booking.status', { id: b.id, status: 'cancelled', notify }); toast('Termin abgesagt.'); dlg.close(); refresh(true); if (S.view === 'list') renderList(); } catch (e) { toast(e.message, true); }
        };
        $('[data-noshow]', f).onclick = async () => {
          try { await api('booking.status', { id: b.id, status: b.status === 'noshow' ? 'confirmed' : 'noshow' }); dlg.close(); refresh(true); } catch (e) { toast(e.message, true); }
        };
      }
      setTimeout(() => (isNew ? f.name : f.service_id).focus(), 50);
    });
  }

  function blockDialog(bl) {
    const isNew = !bl.id;
    const allDay = bl.start.slice(11) === '00:00' && bl.end.slice(11) === '00:00';
    const endDate = allDay ? addDays(bl.end.slice(0, 10), -1) : bl.end.slice(0, 10);
    openDialog(`
      <h2>${isNew ? 'Abwesenheit / Sperre' : 'Abwesenheit bearbeiten'}</h2>
      <p class="meta">In dieser Zeit sind keine Online-Buchungen möglich (z. B. Ferien, Krankheit, Pause, Feiertag).</p>
      <label>Wer<select name="staff_id">${staffOptions(bl.staff_id, true)}</select></label>
      <label data-branch ${bl.staff_id ? 'hidden' : ''}>Filiale<select name="branch_id">
        <option value="">Alle Filialen</option>${D.branches.map((br) => `<option value="${br.id}" ${Number(br.id) === Number(bl.branch_id ?? S.branch) ? 'selected' : ''}>${esc(br.name)}</option>`).join('')}
      </select></label>
      <label class="chk"><input type="checkbox" name="allday" ${allDay ? 'checked' : ''}> Ganztägig</label>
      <div class="two">
        <label>Von<input type="date" name="d1" value="${bl.start.slice(0, 10)}" required></label>
        <label data-t>Zeit<input type="time" name="t1" value="${bl.start.slice(11, 16)}" step="300"></label>
        <label>Bis<input type="date" name="d2" value="${endDate}" required></label>
        <label data-t>Zeit<input type="time" name="t2" value="${bl.end.slice(11, 16)}" step="300"></label>
      </div>
      <label>Notiz<input name="note" value="${esc(bl.note || '')}" placeholder="z. B. Ferien, Mittagspause"></label>
      <div class="foot">
        <div class="grp">${!isNew ? '<button type="button" class="b danger sm" data-del>Löschen</button>' : ''}</div>
        <div class="grp"><button type="button" class="b ghost" data-close>Schliessen</button><button class="b primary" type="submit">Speichern</button></div>
      </div>`, (f) => {
      const sync = () => { $$('[data-t]', f).forEach((x) => { x.hidden = f.allday.checked; }); $('[data-branch]', f).hidden = !!f.staff_id.value; };
      f.allday.onchange = sync; f.staff_id.onchange = sync; sync();
      $('[data-close]', f).onclick = () => dlg.close();
      f.onsubmit = async (e) => {
        e.preventDefault();
        const start = f.allday.checked ? `${f.d1.value} 00:00` : `${f.d1.value} ${f.t1.value}`;
        const end = f.allday.checked ? `${addDays(f.d2.value, 1)} 00:00` : `${f.d2.value} ${f.t2.value}`;
        try {
          await api('block.save', { id: bl.id, staff_id: f.staff_id.value || null, branch_id: f.branch_id.value || null, start, end, note: f.note.value });
          toast('Gespeichert.'); dlg.close(); refresh(true);
        } catch (err) { toast(err.message, true); }
      };
      if (!isNew) $('[data-del]', f).onclick = async () => {
        if (!confirm('Abwesenheit löschen?')) return;
        await api('block.delete', { id: bl.id }); toast('Gelöscht.'); dlg.close(); refresh(true);
      };
    });
  }

  /* ---------- Liste ---------- */
  async function renderList() {
    const q = $('#search').value.trim();
    const box = $('#list');
    box.innerHTML = '<p>Lade …</p>';
    try {
      const { items } = await api('list', { q });
      if (!items.length) { box.innerHTML = `<p>${q ? 'Nichts gefunden.' : 'Keine kommenden Termine.'}</p>`; return; }
      let last = '';
      const rows = items.map((b) => {
        const d = b.start.slice(0, 10);
        const sep = !q && d !== last ? `<tr class="day-sep"><td colspan="6">${WD_LONG[parseD(d).getDay()]}, ${parseD(d).getDate()}. ${MON[parseD(d).getMonth()]} ${parseD(d).getFullYear()}</td></tr>` : '';
        last = d;
        const label = { confirmed: 'Bestätigt', cancelled: 'Abgesagt', noshow: 'Nicht erschienen' }[b.status];
        return `${sep}<tr class="clickable" data-id="${b.id}"><td>${q ? fmtDay(d) + ' ' : ''}${b.start.slice(11, 16)}</td><td><b>${esc(b.name)}</b><br><span class="hide-m" style="color:var(--muted)">${esc(b.phone || '')}</span></td>
          <td>${esc(b.service_name)}</td><td class="hide-m">${esc(b.staff_name)}<br><span style="color:var(--muted)">${esc(b.branch_name)}</span></td>
          <td class="hide-m">${b.source === 'online' ? 'Online' : 'Salon'}</td><td><span class="pill ${b.status}">${label}</span></td></tr>`;
      }).join('');
      box.innerHTML = `<table class="tbl"><thead><tr><th>Zeit</th><th>Kunde</th><th>Service</th><th class="hide-m">Barber</th><th class="hide-m">Quelle</th><th>Status</th></tr></thead><tbody>${rows}</tbody></table>`;
      $$('tr[data-id]', box).forEach((tr) => tr.onclick = () => {
        const b = items.find((x) => String(x.id) === tr.dataset.id);
        if (b.status === 'cancelled') { toast('Abgesagte Termine können nicht bearbeitet werden.'); return; }
        bookingDialog(b);
      });
    } catch (e) { box.innerHTML = `<p>${esc(e.message)}</p>`; }
  }
  let searchT;
  $('#search').addEventListener('input', () => { clearTimeout(searchT); searchT = setTimeout(renderList, 300); });

  /* ---------- Einstellungen ---------- */
  function renderSettings() {
    const s = D.settings;
    const dayBoxes = (val, name) => [1, 2, 3, 4, 5, 6, 7].map((d) => `<label title="${WD_LONG[d % 7]}"><input type="checkbox" name="${name}" value="${d}" ${String(val).includes(String(d)) ? 'checked' : ''}>${WD[d % 7]}</label>`).join('');
    $('#settings').innerHTML = `
    <div class="set-grid">
      <div class="card">
        <h2>Dienstleistungen &amp; Preise</h2>
        <p>Erscheinen auf der Webseite und in der Online-Buchung. Die Dauer bestimmt, wie lange der Termin im Kalender blockiert.</p>
        <div class="rows" id="svc-rows">
          <div class="row services head"><span></span><span>Name</span><span>Beschreibung</span><span>Dauer (Min.)</span><span>Preis</span><span>Aktiv</span><span></span></div>
          ${D.services.map((x) => svcRow(x)).join('')}
        </div>
        <div class="actions"><button class="b ghost sm" id="svc-add">+ Dienstleistung</button><button class="b primary sm" id="svc-save">Speichern</button></div>
      </div>
      <div class="card">
        <h2>Mitarbeiter</h2>
        <p>Farbe für den Kalender, Arbeitstage und ob der Mitarbeiter online buchbar ist. Ferien &amp; Pausen trägst du im Kalender als «Abwesenheit» ein.</p>
        <div class="rows" id="staff-rows">
          <div class="row staff head"><span></span><span>Name</span><span>Filiale</span><span>Farbe</span><span>Arbeitstage</span><span>Online</span><span>Aktiv</span></div>
          ${D.staff.map((x) => staffRow(x, dayBoxes)).join('')}
        </div>
        <div class="actions"><button class="b ghost sm" id="staff-add">+ Mitarbeiter</button><button class="b primary sm" id="staff-save">Speichern</button></div>
      </div>
      <div class="card">
        <h2>Filialen &amp; Öffnungszeiten</h2>
        <p>Leere Felder = geschlossen. Online buchbar ist nur innerhalb der Öffnungszeiten.</p>
        <div class="branch-grid" id="branch-rows">
          ${D.branches.map((br) => `
          <div data-bid="${br.id}">
            <div class="fields" style="grid-template-columns:1fr 1fr;margin-bottom:12px">
              <label class="field">Name<input name="name" value="${esc(br.name)}"></label>
              <label class="field">Telefon<input name="phone" value="${esc(br.phone)}"></label>
              <label class="field">Strasse<input name="street" value="${esc(br.street)}"></label>
              <label class="field">PLZ / Ort<input name="city" value="${esc(br.city)}"></label>
            </div>
            <label class="chk" style="margin-bottom:10px"><input type="checkbox" name="active" ${Number(br.active) ? 'checked' : ''}> Aktiv (auf Webseite und in Buchung sichtbar)</label>
            <div class="hours">${[1, 2, 3, 4, 5, 6, 7].map((d) => { const h = D.hours.find((x) => Number(x.branch_id) === Number(br.id) && Number(x.weekday) === d); return `<b>${WD[d % 7]}</b><input type="time" data-wd="${d}" data-k="open" value="${h ? h.open : ''}"><input type="time" data-wd="${d}" data-k="close" value="${h ? h.close : ''}">`; }).join('')}</div>
          </div>`).join('')}
        </div>
        <div class="actions"><button class="b primary sm" id="branch-save">Speichern</button></div>
      </div>
      <div class="card">
        <h2>Regeln für Online-Buchungen</h2>
        <div class="fields" id="rules">
          <label class="field">Zeitraster (Min.)<input type="number" name="slot_step" min="5" max="60" step="5" value="${esc(s.slot_step)}"><small>Startzeiten alle … Minuten</small></label>
          <label class="field">Vorlaufzeit (Min.)<input type="number" name="lead_minutes" min="0" value="${esc(s.lead_minutes)}"><small>Frühestens so viele Minuten im Voraus</small></label>
          <label class="field">Buchbar für (Tage)<input type="number" name="max_days" min="1" max="365" value="${esc(s.max_days)}"><small>So weit im Voraus buchbar</small></label>
          <label class="field">Online absagen bis (Std.)<input type="number" name="cancel_hours" min="0" value="${esc(s.cancel_hours)}"><small>Danach nur noch telefonisch</small></label>
        </div>
        <label class="chk" style="margin-top:12px"><input type="checkbox" name="notify_salon" id="notify_salon" ${s.notify_salon === '1' ? 'checked' : ''}> E-Mail an den Salon bei jeder Online-Buchung und -Absage</label>
        <div class="actions"><button class="b primary sm" id="rules-save">Speichern</button></div>
      </div>
      <div class="card">
        <h2>Passwort ändern</h2>
        <div class="fields" id="pw">
          <label class="field">Aktuelles Passwort<input type="password" name="old" autocomplete="current-password"></label>
          <label class="field">Neues Passwort (mind. 10 Zeichen)<input type="password" name="new" autocomplete="new-password" minlength="10"></label>
        </div>
        <div class="actions"><button class="b primary sm" id="pw-save">Passwort ändern</button></div>
      </div>
    </div>`;

    const moveBtns = (rows) => rows.addEventListener('click', (e) => {
      const m = e.target.closest('[data-up]'); if (!m) return;
      const row = m.closest('.row'); const prev = row.previousElementSibling;
      if (prev && !prev.classList.contains('head')) row.parentNode.insertBefore(row, prev);
    });
    moveBtns($('#svc-rows')); moveBtns($('#staff-rows'));
    $('#svc-add').onclick = () => $('#svc-rows').insertAdjacentHTML('beforeend', svcRow({ name: '', description: '', duration: 30, price: '', active: 1 }));
    $('#staff-add').onclick = () => $('#staff-rows').insertAdjacentHTML('beforeend', staffRow({ name: '', branch_id: S.branch, color: '#5bb3a9', workdays: '123456', bookable: 1, active: 1 }, dayBoxes));
    $('#svc-rows').addEventListener('click', (e) => { const d = e.target.closest('[data-del]'); if (d) { const r = d.closest('.row'); if (r.dataset.id) { r.querySelector('[name=active]').checked = false; toast('Deaktiviert – bestehende Termine bleiben erhalten. Speichern nicht vergessen.'); } else r.remove(); } });

    const saveWrap = (btn, fn) => { btn.onclick = async () => { btn.disabled = true; try { await fn(); toast('Gespeichert.'); await loadData(); renderSettings(); } catch (e) { toast(e.message, true); } finally { btn.disabled = false; } }; };
    saveWrap($('#svc-save'), () => api('services.save', { items: $$('#svc-rows .row:not(.head)').map((r) => ({
      id: r.dataset.id || null, name: $('[name=name]', r).value, description: $('[name=description]', r).value,
      duration: $('[name=duration]', r).value, price: $('[name=price]', r).value, active: $('[name=active]', r).checked })) }));
    saveWrap($('#staff-save'), () => api('staff.save', { items: $$('#staff-rows .row:not(.head)').map((r) => ({
      id: r.dataset.id || null, name: $('[name=name]', r).value, branch_id: $('[name=branch_id]', r).value, color: $('[name=color]', r).value,
      workdays: $$('[name^=wd]:checked', r).map((x) => x.value).join(''), bookable: $('[name=bookable]', r).checked, active: $('[name=active]', r).checked })) }));
    saveWrap($('#branch-save'), () => api('branches.save', { items: $$('#branch-rows [data-bid]').map((b) => ({
      id: b.dataset.bid, name: $('[name=name]', b).value, street: $('[name=street]', b).value, city: $('[name=city]', b).value, phone: $('[name=phone]', b).value,
      active: $('[name=active]', b).checked,
      hours: [1, 2, 3, 4, 5, 6, 7].map((d) => ({ weekday: d, open: $(`[data-wd="${d}"][data-k=open]`, b).value, close: $(`[data-wd="${d}"][data-k=close]`, b).value })) })) }));
    saveWrap($('#rules-save'), () => api('settings.save', { ...Object.fromEntries($$('#rules input').map((i) => [i.name, i.value])), notify_salon: $('#notify_salon').checked }));
    $('#pw-save').onclick = async () => {
      try { await api('password', { old: $('#pw [name=old]').value, new: $('#pw [name=new]').value }); toast('Passwort geändert.'); $$('#pw input').forEach((i) => { i.value = ''; }); } catch (e) { toast(e.message, true); }
    };
  }
  function svcRow(x) {
    return `<div class="row services" ${x.id ? `data-id="${x.id}"` : ''}>
      <span class="move" data-up title="Nach oben">▲</span>
      <input type="text" name="name" value="${esc(x.name)}" placeholder="Name">
      <input type="text" name="description" value="${esc(x.description || '')}" placeholder="Kurzbeschreibung">
      <input type="number" name="duration" value="${esc(x.duration)}" min="5" step="5">
      <input type="text" name="price" value="${esc(x.price)}" placeholder="ab 32.-">
      <label class="chk"><input type="checkbox" name="active" ${Number(x.active) ? 'checked' : ''}> aktiv</label>
      <button type="button" class="b ghost sm icon" data-del title="Entfernen">×</button>
    </div>`;
  }
  function staffRow(x, dayBoxes) {
    const uid = x.id || 'n' + Math.random().toString(36).slice(2, 7);
    return `<div class="row staff" ${x.id ? `data-id="${x.id}"` : ''}>
      <span class="move" data-up title="Nach oben">▲</span>
      <input type="text" name="name" value="${esc(x.name)}" placeholder="Name">
      <select name="branch_id">${D.branches.map((b) => `<option value="${b.id}" ${Number(b.id) === Number(x.branch_id) ? 'selected' : ''}>${esc(b.name)}</option>`).join('')}</select>
      <input type="color" name="color" value="${esc(x.color)}">
      <div class="days">${dayBoxes(x.workdays, 'wd' + uid)}</div>
      <label class="chk"><input type="checkbox" name="bookable" ${Number(x.bookable) ? 'checked' : ''}> online</label>
      <label class="chk"><input type="checkbox" name="active" ${Number(x.active) ? 'checked' : ''}> aktiv</label>
    </div>`;
  }

  /* ---------- Steuerung ---------- */
  function setView(v) {
    S.view = v;
    $$('.tabs button').forEach((b) => b.setAttribute('aria-selected', String(b.dataset.view === v)));
    $$('.view').forEach((s) => { s.hidden = s.dataset.view !== v; });
    if (v === 'cal') refresh();
    if (v === 'list') renderList();
    if (v === 'settings') renderSettings();
  }
  $$('.tabs button').forEach((b) => b.addEventListener('click', () => setView(b.dataset.view)));
  function setMode(m) {
    S.mode = m; persist();
    $$('.seg button').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.mode === m)));
    $('#staff-sel').hidden = m !== 'week';
    renderCal.scrolled = false;
    refresh();
  }
  $$('.seg button').forEach((b) => b.addEventListener('click', () => setMode(b.dataset.mode)));
  const shift = (n) => { S.date = addDays(S.date, S.mode === 'day' ? n : n * 7); renderCal.scrolled = false; refresh(); };
  $('#prev').onclick = () => shift(-1);
  $('#next').onclick = () => shift(1);
  $('#today').onclick = () => { S.date = ymd(new Date()); renderCal.scrolled = false; refresh(); };
  $('#date-pick').onchange = (e) => { if (e.target.value) { S.date = e.target.value; renderCal.scrolled = false; refresh(); } };
  $('#branch-sel').onchange = (e) => { S.branch = e.target.value; persist(); fillWeekStaff(); refresh(); };
  $('#staff-sel').onchange = (e) => { S.weekStaff = e.target.value; persist(); renderCal(); };
  $('#new-booking').onclick = () => {
    const now = new Date(); const step = Number(D.settings.slot_step || 15);
    const m = Math.ceil((now.getHours() * 60 + now.getMinutes()) / step) * step;
    const staff = (S.weekStaff && staffById(S.weekStaff)) || branchStaff(S.branch)[0];
    bookingDialog({ staff_id: staff?.id, start: `${S.date} ${m2t(Math.min(m, 23 * 60))}` });
  };
  $('#new-block').onclick = () => bookingBlockDefault();
  function bookingBlockDefault() {
    const staff = S.weekStaff ? staffById(S.weekStaff) : null;
    blockDialog({ staff_id: staff?.id || null, branch_id: S.branch, start: `${S.date} 12:00`, end: `${S.date} 13:00`, note: '' });
  }
  document.addEventListener('keydown', (e) => {
    if (dlg.open || S.view !== 'cal' || /INPUT|SELECT|TEXTAREA/.test(document.activeElement.tagName)) return;
    if (e.key === 'ArrowLeft') shift(-1);
    if (e.key === 'ArrowRight') shift(1);
    if (e.key === 't') $('#today').click();
  });

  // Neue Online-Buchungen automatisch anzeigen
  setInterval(() => { if (!document.hidden && !dlg.open && S.view === 'cal') refresh(true); }, 60000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden && S.view === 'cal' && D) refresh(true); });
  // Auf schmalen Bildschirmen etwas kompakter
  if (matchMedia('(max-width: 700px)').matches) document.documentElement.style.setProperty('--ppm', '1.3px');

  loadData().then(() => setMode(S.mode)).catch((e) => toast(e.message, true));
})();

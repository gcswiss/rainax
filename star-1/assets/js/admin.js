/* Star 1 Coiffeur — Verwaltung */
(() => {
  const API = new URL('../../api/admin.php', document.currentScript.src).href;
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const pad = (n) => String(n).padStart(2, '0');
  const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  const parse = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
  const addDays = (s, n) => { const d = parse(s); d.setDate(d.getDate() + n); return iso(d); };
  const deDate = (s) => { const d = parse(s); return `${['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'][d.getDay()]}, ${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`; };
  const chf = (v) => (v === null || v === undefined || v === '' ? '–' : 'CHF ' + (Number(v) % 1 === 0 ? Number(v) + '.–' : Number(v).toFixed(2)));
  const DAYNAMES = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
  const SHORT = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
  const VARIANTS = [['kurz', 'Kurz'], ['mittel', 'Mittel'], ['lang', 'Lang']];
  const ICON_PLUS = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>';

  let csrf = '';
  let staffCache = [];
  let servicesCache = [];
  let currentDate = iso(new Date());

  async function api(action, params = {}, body) {
    const opts = { credentials: 'same-origin', headers: {} };
    if (body !== undefined) {
      opts.method = 'POST';
      opts.headers['Content-Type'] = 'application/json';
      opts.headers['X-CSRF'] = csrf;
      opts.body = JSON.stringify(body);
    }
    const r = await fetch(API + '?' + new URLSearchParams({ action, ...params }), opts);
    let d;
    try { d = await r.json(); } catch { throw new Error('Server nicht erreichbar (PHP aktiv?).'); }
    if (r.status === 401 && action !== 'login') { showLogin(); throw new Error(d.error); }
    if (!d.ok) throw new Error(d.error || 'Fehler');
    return d;
  }

  function toast(msg, err = false) {
    const t = $('#toast');
    t.textContent = msg;
    t.classList.toggle('is-err', err);
    t.classList.add('is-on');
    clearTimeout(toast.t);
    toast.t = setTimeout(() => t.classList.remove('is-on'), 2800);
  }

  // ------------------------------------------------------------ Login
  function showLogin() {
    $('#app').hidden = true;
    $('#login').hidden = false;
    $('#l-user').focus();
  }

  $('#login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = $('.form__msg', e.target);
    msg.innerHTML = '';
    try {
      const d = await api('login', {}, Object.fromEntries(new FormData(e.target)));
      e.target.reset();
      start(d);
    } catch (err) {
      msg.innerHTML = `<div class="alert alert--err">${esc(err.message)}</div>`;
    }
  });

  $('#logout').addEventListener('click', async () => {
    try { await api('logout', {}, {}); } catch { /* egal */ }
    location.hash = '';
    showLogin();
  });

  let mustChange = false;
  function start(me) {
    csrf = me.csrf;
    mustChange = me.must_change;
    $('#who').textContent = 'Angemeldet: ' + me.user;
    $('#login').hidden = true;
    $('#app').hidden = false;
    route();
  }

  // ------------------------------------------------------------ Router
  const views = { kalender: viewCalendar, termine: viewList, team: viewStaff, leistungen: viewServices, zeiten: viewHours, abwesenheiten: viewBlocks, konto: viewAccount };
  function route() {
    let v = location.hash.slice(1) || 'kalender';
    if (!views[v]) v = 'kalender';
    if (mustChange) v = 'konto';
    $$('#side-nav a').forEach((a) => a.classList.toggle('is-active', a.dataset.view === v));
    $('#main').onclick = null;
    $('#main').innerHTML = '<p class="muted">Wird geladen …</p>';
    views[v]().catch((err) => { $('#main').innerHTML = `<div class="alert alert--err">${esc(err.message)}</div>`; });
  }
  window.addEventListener('hashchange', () => { if (!$('#app').hidden) route(); });

  async function loadMaster() {
    const [s, v] = await Promise.all([api('staff'), api('services')]);
    staffCache = s.staff;
    servicesCache = v.services;
  }

  // ------------------------------------------------------------ Dialog
  const modal = $('#modal');
  function openModal(title, bodyHtml, buttons, onSubmit) {
    $('#modal-title').textContent = title;
    $('#modal-body').innerHTML = bodyHtml;
    $('#modal-msg').innerHTML = '';
    $('#modal-foot').innerHTML = buttons;
    modal.onsubmitAction = onSubmit;
    modal.showModal();
    const first = $('#modal-body input:not([type=hidden]), #modal-body select');
    if (first) first.focus();
  }
  function modalError(msg) { $('#modal-msg').innerHTML = `<div class="alert alert--err">${esc(msg)}</div>`; }
  $('#modal-form').addEventListener('submit', async (e) => {
    const submitter = e.submitter;
    if (!submitter || submitter.value === 'cancel') return;
    e.preventDefault();
    const action = submitter.dataset.action || 'save';
    if (submitter.dataset.confirm && !confirm(submitter.dataset.confirm)) return;
    submitter.disabled = true;
    try {
      const keep = await modal.onsubmitAction?.(action, e.target);
      if (keep !== true) modal.close();
    } catch (err) {
      modalError(err.message);
    } finally {
      submitter.disabled = false;
    }
  });
  const formData = (form) => {
    const data = {};
    $$('[name]', form).forEach((el) => {
      if (el.type === 'checkbox') {
        if (el.name.endsWith('[]')) {
          const k = el.name.slice(0, -2);
          data[k] = data[k] || [];
          if (el.checked) data[k].push(el.value);
        } else data[el.name] = el.checked ? 1 : 0;
      } else data[el.name] = el.value;
    });
    return data;
  };

  // ------------------------------------------------------------ Tagesplan
  async function viewCalendar() {
    await loadMaster();
    const [day, ov] = await Promise.all([api('day', { date: currentDate }), api('overview')]);
    const now = new Date();
    const isToday = currentDate === iso(now);
    const nowMin = now.getHours() * 60 + now.getMinutes();
    const step = 30;
    const byKey = {};
    day.bookings.filter((b) => b.status === 'confirmed').forEach((b) => { byKey[b.staff_id + '@' + b.time] = b; });
    const blocked = new Set(day.blocked);
    const cancelled = day.bookings.filter((b) => b.status === 'cancelled');

    const rows = day.slots.map((t) => {
      const [h, m] = t.split(':').map(Number);
      const tm = h * 60 + m;
      const cls = [m === 0 && 'is-hour', isToday && nowMin >= tm && nowMin < tm + step && 'is-now'].filter(Boolean).join(' ');
      const cells = day.staff.map((s) => {
        const b = byKey[s.id + '@' + t];
        if (b) {
          return `<td><button class="bkg" style="--c:${esc(s.color)}" data-booking="${b.id}">
            ${b.source === 'online' ? '<em>Online</em>' : ''}<b>${esc(b.name)}</b><span>${esc(b.service_name.replace(/^.*? · /, ''))}${b.variant ? ' · ' + esc(b.variant) : ''}</span><span>${esc(b.phone)}</span></button></td>`;
        }
        if (blocked.has(s.id + '@' + t)) return '<td><div class="slot slot--blocked" title="Abwesend / gesperrt"></div></td>';
        return `<td><button class="slot" data-new="${s.id}|${t}" aria-label="Neuer Termin ${esc(s.name)} ${t}">+</button></td>`;
      }).join('');
      return `<tr class="${cls}"><th>${t}</th>${cells}</tr>`;
    }).join('');

    $('#main').innerHTML = `
      <div class="top">
        <h1>Tagesplan</h1>
        <div class="top__actions"><button class="btn btn--sm" id="add-booking">${ICON_PLUS} Neuer Termin</button></div>
      </div>
      <div class="stats">
        <div class="stat"><small>Termine heute</small><strong>${ov.today}</strong></div>
        <div class="stat"><small>Nächste 7 Tage</small><strong>${ov.week}</strong></div>
        <div class="stat"><small>Online-Buchungen (7 T.)</small><strong>${ov.online_7d}</strong></div>
        <div class="stat"><small>Umsatz heute (geplant)</small><strong>${chf(ov.revenue_today)}</strong></div>
      </div>
      <div class="top">
        <div class="datebar">
          <button class="icon-btn" id="d-prev" aria-label="Vorheriger Tag"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg></button>
          <button class="btn btn--ghost btn--sm" id="d-today">Heute</button>
          <button class="icon-btn" id="d-next" aria-label="Nächster Tag"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m9 18 6-6-6-6"/></svg></button>
          <strong>${esc(day.date_label)}</strong>
          <input type="date" id="d-pick" value="${currentDate}" aria-label="Datum wählen">
        </div>
        <span class="muted">${day.bookings.filter((b) => b.status === 'confirmed').length} Termine</span>
      </div>
      ${day.closed ? '<div class="closed-note">An diesem Tag ist der Salon laut Öffnungszeiten geschlossen. Termine können trotzdem manuell eingetragen werden.</div>' : ''}
      ${day.staff.length && day.slots.length ? `<div class="plan" style="--cols:${day.staff.length}"><table>
        <thead><tr><th></th>${day.staff.map((s) => `<th><span style="--c:${esc(s.color)}"></span>${esc(s.name)}</th>`).join('')}</tr></thead>
        <tbody>${rows}</tbody></table></div>` : (day.staff.length ? '' : '<div class="empty">An diesem Tag arbeitet niemand. Arbeitstage unter «Mitarbeiter» anpassen.</div>')}
      ${cancelled.length ? `<h2 style="font-size:1.05rem;margin:28px 0 12px">Abgesagte Termine</h2>
        <div class="table-wrap"><table class="tbl"><tbody>${cancelled.map((b) => `<tr class="is-click" data-booking="${b.id}"><td>${b.time}</td><td>${esc(b.name)}</td><td>${esc(b.service_name)}</td><td><span class="badge badge--off">abgesagt</span></td></tr>`).join('')}</tbody></table></div>` : ''}
    `;

    const goDate = (d) => { currentDate = d; viewCalendar(); };
    $('#d-prev').onclick = () => goDate(addDays(currentDate, -1));
    $('#d-next').onclick = () => goDate(addDays(currentDate, 1));
    $('#d-today').onclick = () => goDate(iso(new Date()));
    $('#d-pick').onchange = (e) => e.target.value && goDate(e.target.value);
    $('#add-booking').onclick = () => bookingForm({ date: currentDate });
    $('#main').onclick = (e) => {
      const n = e.target.closest('[data-new]');
      if (n) {
        const [sid, t] = n.dataset.new.split('|');
        bookingForm({ date: currentDate, time: t, staff_id: Number(sid) });
        return;
      }
      const b = e.target.closest('[data-booking]');
      if (b) showBooking(day.bookings.find((x) => x.id === Number(b.dataset.booking)));
    };
  }

  function showBooking(b) {
    const staff = staffCache.find((s) => s.id === b.staff_id);
    const body = `<dl class="details">
      <dt>Status</dt><dd>${b.status === 'confirmed' ? '<span class="badge badge--ok">bestätigt</span>' : '<span class="badge badge--off">abgesagt</span>'} <span class="badge">${b.source === 'online' ? 'online gebucht' : 'im Salon erfasst'}</span></dd>
      <dt>Datum</dt><dd>${deDate(b.date)}, ${b.time} Uhr</dd>
      <dt>Mitarbeiter</dt><dd>${esc(staff?.name || '–')}</dd>
      <dt>Leistung</dt><dd>${esc(b.service_name)}${b.variant ? ` (${esc(b.variant)})` : ''}</dd>
      <dt>Preis</dt><dd>${chf(b.price)}</dd>
      <dt>Kunde</dt><dd>${esc(b.name)}</dd>
      <dt>Telefon</dt><dd>${b.phone ? `<a href="tel:${esc(b.phone.replace(/\s/g, ''))}">${esc(b.phone)}</a>` : '–'}</dd>
      <dt>E-Mail</dt><dd>${b.email ? `<a href="mailto:${esc(b.email)}">${esc(b.email)}</a>` : '–'}</dd>
      <dt>Bemerkung</dt><dd>${esc(b.note) || '–'}</dd>
      <dt>Erfasst</dt><dd class="muted">${esc(b.created_at)} · Nr. ${b.id}</dd>
    </dl>`;
    const buttons = `
      <button class="btn btn--danger btn--sm" data-action="delete" data-confirm="Termin endgültig löschen?">Löschen</button>
      <span class="spacer"></span>
      ${b.status === 'confirmed'
        ? '<button class="btn btn--ghost btn--sm" data-action="cancel" data-confirm="Termin absagen? Der Zeitpunkt wird wieder frei.">Absagen</button>'
        : '<button class="btn btn--ghost btn--sm" data-action="restore">Wiederherstellen</button>'}
      <button class="btn btn--sm" data-action="edit">Bearbeiten</button>`;
    openModal('Termin', body, buttons, async (action) => {
      if (action === 'edit') { setTimeout(() => bookingForm(b), 0); return; }
      if (action === 'delete') await api('booking_delete', {}, { id: b.id });
      if (action === 'cancel') await api('booking_status', {}, { id: b.id, status: 'cancelled' });
      if (action === 'restore') await api('booking_status', {}, { id: b.id, status: 'confirmed' });
      toast({ delete: 'Termin gelöscht', cancel: 'Termin abgesagt', restore: 'Termin wiederhergestellt' }[action]);
      route();
    });
  }

  function bookingForm(b = {}) {
    const cats = [...new Set(servicesCache.map((s) => s.category))];
    const times = [];
    for (let m = 7 * 60; m < 21 * 60; m += 30) times.push(`${pad(Math.floor(m / 60))}:${pad(m % 60)}`);
    if (b.time && !times.includes(b.time)) times.push(b.time);
    const svcOptions = '<option value="">– wählen –</option>' + cats.map((c) => `<optgroup label="${esc(c)}">${
      servicesCache.filter((s) => s.category === c).map((s) => `<option value="${s.id}" ${s.id === b.service_id ? 'selected' : ''}>${esc(s.name)}${s.active ? '' : ' (inaktiv)'}</option>`).join('')
    }</optgroup>`).join('');
    const body = `
      <input type="hidden" name="id" value="${b.id || ''}">
      <div class="form__row form__row--3">
        <div class="field"><label>Datum *</label><input type="date" name="date" value="${b.date || currentDate}" required></div>
        <div class="field"><label>Uhrzeit *</label><select name="time" required>${times.sort().map((t) => `<option ${t === (b.time || '09:00') ? 'selected' : ''}>${t}</option>`).join('')}</select></div>
        <div class="field"><label>Mitarbeiter *</label><select name="staff_id" required>${staffCache.filter((s) => s.active || s.id === b.staff_id).map((s) => `<option value="${s.id}" ${s.id === b.staff_id ? 'selected' : ''}>${esc(s.name)}</option>`).join('')}</select></div>
      </div>
      <div class="form__row form__row--3">
        <div class="field" style="grid-column:span 2"><label>Leistung</label><select name="service_id">${svcOptions}</select></div>
        <div class="field"><label>Haarlänge</label><select name="variant"><option value="">–</option>${VARIANTS.map(([k, l]) => `<option value="${k}" ${b.variant === k ? 'selected' : ''}>${l}</option>`).join('')}</select></div>
      </div>
      <div class="form__row">
        <div class="field"><label>Name Kunde *</label><input name="name" value="${esc(b.name || '')}" required></div>
        <div class="field"><label>Telefon</label><input name="phone" type="tel" value="${esc(b.phone || '')}"></div>
      </div>
      <div class="form__row">
        <div class="field"><label>E-Mail</label><input name="email" type="email" value="${esc(b.email || '')}"></div>
        <div class="field"><label>Preis CHF (leer = laut Preisliste)</label><input name="price" type="number" step="0.05" min="0" value="${b.price ?? ''}"></div>
      </div>
      <div class="field"><label>Bemerkung</label><textarea name="note" style="min-height:70px">${esc(b.note || '')}</textarea></div>`;
    openModal(b.id ? 'Termin bearbeiten' : 'Neuer Termin', body,
      `<button class="btn btn--ghost btn--sm" value="cancel" formnovalidate>Abbrechen</button><button class="btn btn--sm" data-action="save">Speichern</button>`,
      async (action, form) => {
        const data = formData(form);
        if (b.id && data.price === String(b.price ?? '')) {
          // Preis nur übernehmen, wenn Leistung gleich bleibt – sonst neu berechnen
          if (Number(data.service_id) !== b.service_id || data.variant !== b.variant) data.price = '';
        }
        await api('booking_save', {}, data);
        toast('Termin gespeichert');
        if (data.date !== currentDate && location.hash.slice(1) !== 'termine') currentDate = data.date;
        route();
      });
  }

  // ------------------------------------------------------------ Liste
  const listFilter = { from: iso(new Date()), to: addDays(iso(new Date()), 30), status: 'confirmed', q: '' };
  async function viewList() {
    await loadMaster();
    $('#main').innerHTML = `
      <div class="top"><h1>Alle Termine</h1>
        <div class="top__actions"><a class="btn btn--ghost btn--sm" id="export" href="#">CSV exportieren</a><button class="btn btn--sm" id="add-booking">${ICON_PLUS} Neuer Termin</button></div></div>
      <form class="filters" id="filters">
        <div class="field"><label>Von</label><input type="date" name="from" value="${listFilter.from}"></div>
        <div class="field"><label>Bis</label><input type="date" name="to" value="${listFilter.to}"></div>
        <div class="field"><label>Status</label><select name="status">
          <option value="confirmed" ${listFilter.status === 'confirmed' ? 'selected' : ''}>Bestätigt</option>
          <option value="cancelled" ${listFilter.status === 'cancelled' ? 'selected' : ''}>Abgesagt</option>
          <option value="all" ${listFilter.status === 'all' ? 'selected' : ''}>Alle</option></select></div>
        <div class="field" style="flex:1"><label>Suche</label><input name="q" value="${esc(listFilter.q)}" placeholder="Name, Telefon, E-Mail, Leistung"></div>
      </form>
      <div id="list"></div>`;
    const load = async () => {
      Object.assign(listFilter, formData($('#filters')));
      $('#export').href = API + '?' + new URLSearchParams({ action: 'export', ...listFilter });
      const d = await api('bookings', listFilter);
      $('#list').innerHTML = d.bookings.length ? `<div class="table-wrap"><table class="tbl">
        <thead><tr><th>Datum</th><th>Zeit</th><th>Kunde</th><th>Telefon</th><th>Leistung</th><th>Mitarbeiter</th><th class="num">Preis</th><th>Status</th></tr></thead>
        <tbody>${d.bookings.map((b) => `<tr class="is-click" data-id="${b.id}">
          <td>${deDate(b.date)}</td><td>${b.time}</td><td><b>${esc(b.name)}</b></td><td>${esc(b.phone)}</td>
          <td>${esc(b.service_name)}${b.variant ? ` (${esc(b.variant)})` : ''}</td><td>${esc(b.staff_name)}</td><td class="num">${chf(b.price)}</td>
          <td>${b.status === 'confirmed' ? '<span class="badge badge--ok">bestätigt</span>' : '<span class="badge badge--off">abgesagt</span>'} ${b.source === 'online' ? '<span class="badge badge--gold">online</span>' : ''}</td></tr>`).join('')}</tbody></table></div>
        <p class="muted" style="margin-top:12px">${d.bookings.length} Termine · Summe ${chf(d.bookings.filter((b) => b.status === 'confirmed').reduce((a, b) => a + (Number(b.price) || 0), 0))}</p>`
        : '<div class="empty">Keine Termine gefunden.</div>';
      $$('#list tr[data-id]').forEach((tr) => {
        tr.onclick = () => {
          const b = d.bookings.find((x) => x.id === Number(tr.dataset.id));
          showBooking({ ...b, id: Number(b.id), staff_id: Number(b.staff_id), service_id: b.service_id === null ? null : Number(b.service_id) });
        };
      });
    };
    let t;
    $('#filters').addEventListener('input', () => { clearTimeout(t); t = setTimeout(load, 300); });
    $('#filters').addEventListener('submit', (e) => { e.preventDefault(); load(); });
    $('#add-booking').onclick = () => bookingForm({ date: iso(new Date()) });
    await load();
  }

  // ------------------------------------------------------------ Mitarbeiter
  async function viewStaff() {
    await loadMaster();
    $('#main').innerHTML = `
      <div class="top"><h1>Mitarbeiter</h1><button class="btn btn--sm" id="add">${ICON_PLUS} Neu</button></div>
      <p class="muted" style="margin-top:-8px">Wer aktiv ist und am gewählten Wochentag arbeitet, erscheint in der Online-Buchung.</p>
      <div class="table-wrap"><table class="tbl">
        <thead><tr><th>Name</th><th>Funktion</th><th>Arbeitstage</th><th>Status</th></tr></thead>
        <tbody>${staffCache.map((s) => `<tr class="is-click" data-id="${s.id}">
          <td><span class="dot" style="background:${esc(s.color)}"></span><b>${esc(s.name)}</b></td><td>${esc(s.role)}</td>
          <td>${[1, 2, 3, 4, 5, 6, 0].filter((d) => s.days.includes(d)).map((d) => SHORT[d]).join(' · ') || '–'}</td>
          <td>${s.active ? '<span class="badge badge--ok">aktiv</span>' : '<span class="badge badge--off">inaktiv</span>'}</td></tr>`).join('')}</tbody></table></div>`;
    $('#add').onclick = () => staffForm({ active: 1, days: [1, 2, 3, 4, 5, 6], color: '#5B9BD5', sort: staffCache.length + 1 });
    $$('tr[data-id]').forEach((tr) => { tr.onclick = () => staffForm(staffCache.find((s) => s.id === Number(tr.dataset.id))); });
  }

  function staffForm(s) {
    const body = `
      <input type="hidden" name="id" value="${s.id || ''}">
      <div class="form__row">
        <div class="field"><label>Name *</label><input name="name" value="${esc(s.name || '')}" required></div>
        <div class="field"><label>Funktion</label><input name="role" value="${esc(s.role || '')}" placeholder="z. B. Coiffeuse, Barber"></div>
      </div>
      <div class="field"><label>Arbeitstage</label><div class="days">${[1, 2, 3, 4, 5, 6, 0].map((d) =>
        `<label><input type="checkbox" name="days[]" value="${d}" ${s.days?.includes(d) ? 'checked' : ''}><span>${SHORT[d]}</span></label>`).join('')}</div></div>
      <div class="form__row form__row--3">
        <div class="field"><label>Farbe im Plan</label><input type="color" name="color" value="${esc(s.color || '#E3B341')}"></div>
        <div class="field"><label>Reihenfolge</label><input type="number" name="sort" value="${s.sort ?? 0}"></div>
        <div class="field"><label>Aktiv</label><label class="switch"><input type="checkbox" name="active" ${s.active ? 'checked' : ''}><span></span></label></div>
      </div>`;
    openModal(s.id ? 'Mitarbeiter bearbeiten' : 'Neuer Mitarbeiter', body,
      `${s.id ? '<button class="btn btn--danger btn--sm" data-action="delete" data-confirm="Mitarbeiter löschen? (Mit bestehenden Terminen wird er nur deaktiviert.)">Löschen</button><span class="spacer"></span>' : ''}
       <button class="btn btn--ghost btn--sm" value="cancel" formnovalidate>Abbrechen</button><button class="btn btn--sm" data-action="save">Speichern</button>`,
      async (action, form) => {
        if (action === 'delete') {
          const d = await api('staff_delete', {}, { id: s.id });
          toast(d.message || 'Gelöscht');
        } else {
          await api('staff_save', {}, formData(form));
          toast('Gespeichert');
        }
        route();
      });
  }

  // ------------------------------------------------------------ Leistungen
  async function viewServices() {
    await loadMaster();
    const cats = [...new Set(servicesCache.map((s) => s.category))];
    $('#main').innerHTML = `
      <div class="top"><h1>Leistungen &amp; Preise</h1><button class="btn btn--sm" id="add">${ICON_PLUS} Neu</button></div>
      <p class="muted" style="margin-top:-8px">Änderungen erscheinen sofort in der Preisliste der Webseite und in der Buchung. Bei drei Preisen wählt die Kundschaft die Haarlänge.</p>
      <div class="table-wrap"><table class="tbl">
        <thead><tr><th>Leistung</th><th class="num">Kurz / Einzelpreis</th><th class="num">Mittel</th><th class="num">Lang</th><th>Status</th></tr></thead>
        <tbody>${cats.map((c) => `<tr class="cat"><td colspan="5">${esc(c)}</td></tr>` + servicesCache.filter((s) => s.category === c).map((s) => `
          <tr class="is-click" data-id="${s.id}"><td><b>${esc(s.name)}</b></td><td class="num">${chf(s.price1)}</td><td class="num">${chf(s.price2)}</td><td class="num">${chf(s.price3)}</td>
          <td>${s.active ? '<span class="badge badge--ok">sichtbar</span>' : '<span class="badge badge--off">ausgeblendet</span>'}</td></tr>`).join('')).join('')}</tbody></table></div>`;
    $('#add').onclick = () => serviceForm({ active: 1, sort: servicesCache.length + 1 }, cats);
    $$('tr[data-id]').forEach((tr) => { tr.onclick = () => serviceForm(servicesCache.find((s) => s.id === Number(tr.dataset.id)), cats); });
  }

  function serviceForm(s, cats) {
    const body = `
      <input type="hidden" name="id" value="${s.id || ''}">
      <div class="form__row">
        <div class="field"><label>Kategorie *</label><input name="category" list="cats" value="${esc(s.category || '')}" required><datalist id="cats">${cats.map((c) => `<option value="${esc(c)}">`).join('')}</datalist></div>
        <div class="field"><label>Bezeichnung *</label><input name="name" value="${esc(s.name || '')}" required></div>
      </div>
      <div class="form__row form__row--3">
        <div class="field"><label>Preis kurz / Einzelpreis</label><input type="number" step="0.05" min="0" name="price1" value="${s.price1 ?? ''}"></div>
        <div class="field"><label>Preis mittel</label><input type="number" step="0.05" min="0" name="price2" value="${s.price2 ?? ''}"></div>
        <div class="field"><label>Preis lang</label><input type="number" step="0.05" min="0" name="price3" value="${s.price3 ?? ''}"></div>
      </div>
      <div class="form__row">
        <div class="field"><label>Reihenfolge</label><input type="number" name="sort" value="${s.sort ?? 0}"></div>
        <div class="field"><label>Sichtbar</label><label class="switch"><input type="checkbox" name="active" ${s.active ? 'checked' : ''}><span></span></label></div>
      </div>`;
    openModal(s.id ? 'Leistung bearbeiten' : 'Neue Leistung', body,
      `${s.id ? '<button class="btn btn--danger btn--sm" data-action="delete" data-confirm="Leistung löschen? Bestehende Termine bleiben erhalten.">Löschen</button><span class="spacer"></span>' : ''}
       <button class="btn btn--ghost btn--sm" value="cancel" formnovalidate>Abbrechen</button><button class="btn btn--sm" data-action="save">Speichern</button>`,
      async (action, form) => {
        if (action === 'delete') await api('service_delete', {}, { id: s.id });
        else await api('service_save', {}, formData(form));
        toast(action === 'delete' ? 'Gelöscht' : 'Gespeichert');
        route();
      });
  }

  // ------------------------------------------------------------ Öffnungszeiten
  async function viewHours() {
    const d = await api('settings');
    $('#main').innerHTML = `
      <div class="top"><h1>Öffnungszeiten &amp; Buchung</h1></div>
      <form id="hours-form">
        <div class="panel"><h2>Öffnungszeiten</h2><div class="hours-edit">${[1, 2, 3, 4, 5, 6, 0].map((day) => {
          const h = d.hours[day];
          return `<div class="hours-edit__row${h ? '' : ' is-off'}" data-day="${day}">
            <b>${DAYNAMES[day]}</b>
            <label class="switch" title="Geöffnet"><input type="checkbox" class="h-on" ${h ? 'checked' : ''}><span></span></label>
            <div class="hours-edit__times"><input type="time" class="h-from" step="1800" value="${h ? h[0] : '09:00'}"> – <input type="time" class="h-to" step="1800" value="${h ? h[1] : '18:00'}"></div></div>`;
        }).join('')}</div>
        <p class="muted" style="margin:14px 0 0;font-size:.88rem">Der letzte buchbare Termin beginnt 30 Minuten vor Ladenschluss.</p></div>
        <div class="panel"><h2>Online-Buchung</h2>
          <div class="form__row">
            <div class="field"><label>Buchbar bis (Tage im Voraus)</label><input type="number" name="horizon_days" min="1" max="365" value="${d.horizon_days}"></div>
            <div class="field"><label>Mindestvorlauf (Minuten)</label><input type="number" name="lead_minutes" min="0" step="15" value="${d.lead_minutes}"></div>
          </div>
          <p class="muted" style="margin:12px 0 0;font-size:.88rem">Termindauer: ${d.slot_minutes} Minuten pro Buchung.</p>
        </div>
        <div style="margin-top:16px"><button class="btn" type="submit">Speichern</button></div>
      </form>`;
    $$('.h-on').forEach((cb) => cb.addEventListener('change', () => cb.closest('.hours-edit__row').classList.toggle('is-off', !cb.checked)));
    $('#hours-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const hours = {};
      $$('.hours-edit__row').forEach((r) => {
        hours[r.dataset.day] = $('.h-on', r).checked ? [$('.h-from', r).value, $('.h-to', r).value] : null;
      });
      try {
        await api('settings_save', {}, { hours, ...formData(e.target) });
        toast('Gespeichert');
      } catch (err) { toast(err.message, true); }
    });
  }

  // ------------------------------------------------------------ Abwesenheiten
  async function viewBlocks() {
    await loadMaster();
    const d = await api('blocks');
    const today = iso(new Date());
    $('#main').innerHTML = `
      <div class="top"><h1>Abwesenheiten &amp; Sperrzeiten</h1></div>
      <p class="muted" style="margin-top:-8px">Ferien, Krankheit, Feiertage oder Pausen. In dieser Zeit sind keine Online-Buchungen möglich.</p>
      <form class="panel" id="block-form" style="margin-bottom:20px">
        <h2>Neue Abwesenheit</h2>
        <div class="form__row">
          <div class="field"><label>Wer</label><select name="staff_id"><option value="">Ganzer Salon</option>${staffCache.map((s) => `<option value="${s.id}">${esc(s.name)}</option>`).join('')}</select></div>
          <div class="field"><label>Grund</label><input name="reason" placeholder="z. B. Ferien, Feiertag, Mittagspause"></div>
        </div>
        <div class="form__row" style="margin-top:14px">
          <div class="field"><label>Von Datum *</label><input type="date" name="date_from" value="${today}" required></div>
          <div class="field"><label>Bis Datum</label><input type="date" name="date_to" value="${today}"></div>
        </div>
        <div class="form__row" style="margin-top:14px">
          <div class="field"><label>Von Uhrzeit (leer = ganztags)</label><input type="time" name="time_from" step="1800"></div>
          <div class="field"><label>Bis Uhrzeit</label><input type="time" name="time_to" step="1800"></div>
        </div>
        <div style="margin-top:16px"><button class="btn btn--sm" type="submit">${ICON_PLUS} Hinzufügen</button></div>
      </form>
      ${d.blocks.length ? `<div class="table-wrap"><table class="tbl">
        <thead><tr><th>Zeitraum</th><th>Uhrzeit</th><th>Wer</th><th>Grund</th><th></th></tr></thead>
        <tbody>${d.blocks.map((b) => `<tr>
          <td>${deDate(b.date_from)}${b.date_to !== b.date_from ? ' – ' + deDate(b.date_to) : ''}</td>
          <td>${b.time_from ? `${b.time_from} – ${b.time_to}` : 'ganztags'}</td>
          <td>${b.staff_name ? esc(b.staff_name) : '<span class="badge badge--gold">Ganzer Salon</span>'}</td>
          <td>${esc(b.reason) || '–'}</td>
          <td class="num"><button class="btn btn--danger btn--sm" data-del="${b.id}">Entfernen</button></td></tr>`).join('')}</tbody></table></div>`
        : '<div class="empty">Keine Abwesenheiten eingetragen.</div>'}`;
    $('#block-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      try {
        await api('block_save', {}, formData(e.target));
        toast('Abwesenheit eingetragen');
        route();
      } catch (err) { toast(err.message, true); }
    });
    $$('[data-del]').forEach((b) => {
      b.onclick = async () => {
        if (!confirm('Abwesenheit entfernen?')) return;
        await api('block_delete', {}, { id: Number(b.dataset.del) });
        toast('Entfernt');
        route();
      };
    });
  }

  // ------------------------------------------------------------ Konto
  async function viewAccount() {
    $('#main').innerHTML = `
      <div class="top"><h1>Konto</h1></div>
      ${mustChange ? '<div class="warn">Bitte ersetzen Sie das Startpasswort durch ein eigenes Passwort, bevor Sie fortfahren.</div>' : ''}
      <form class="panel form" id="pw-form">
        <h2>Passwort ändern</h2>
        <div class="field"><label>Aktuelles Passwort</label><input type="password" name="current" autocomplete="current-password" required></div>
        <div class="form__row">
          <div class="field"><label>Neues Passwort (mind. 10 Zeichen)</label><input type="password" name="new" autocomplete="new-password" minlength="10" required></div>
          <div class="field"><label>Neues Passwort wiederholen</label><input type="password" name="repeat" autocomplete="new-password" required></div>
        </div>
        <div class="form__msg"></div>
        <div><button class="btn" type="submit">Passwort speichern</button></div>
      </form>`;
    $('#pw-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const data = formData(e.target);
      const msg = $('.form__msg', e.target);
      if (data.new !== data.repeat) { msg.innerHTML = '<div class="alert alert--err">Die neuen Passwörter stimmen nicht überein.</div>'; return; }
      try {
        const d = await api('password', {}, { current: data.current, new: data.new });
        csrf = d.csrf;
        const wasForced = mustChange;
        mustChange = false;
        e.target.reset();
        toast('Passwort geändert');
        if (wasForced) { if (location.hash === '#kalender') route(); else location.hash = 'kalender'; }
        else msg.innerHTML = '<div class="alert alert--ok">Das Passwort wurde geändert.</div>';
      } catch (err) { msg.innerHTML = `<div class="alert alert--err">${esc(err.message)}</div>`; }
    });
  }

  // ------------------------------------------------------------ Start
  api('me').then((d) => (d.user ? start(d) : showLogin())).catch((err) => {
    showLogin();
    $('#login-form .form__msg').innerHTML = `<div class="alert alert--err">${esc(err.message)}</div>`;
  });
})();

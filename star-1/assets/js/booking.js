/* Star 1 Coiffeur — Online-Buchung: Datum → Uhrzeit → Coiffeur → Leistung → Angaben */
(() => {
  const API = new URL('../../api/public.php', document.currentScript.src).href;
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const pad = (n) => String(n).padStart(2, '0');
  const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  const parse = (s) => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
  const MONTHS = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
  const DAYS = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
  const VARIANTS = [['kurz', 'Kurze Haare'], ['mittel', 'Mittellange Haare'], ['lang', 'Lange Haare']];
  const chf = (v) => (v === null || v === undefined ? '' : 'CHF ' + (Number.isInteger(v) ? v + '.–' : v.toFixed(2)));
  const dateLabel = (s) => { const d = parse(s); return `${DAYS[d.getDay()]}, ${d.getDate()}. ${MONTHS[d.getMonth()]} ${d.getFullYear()}`; };

  const api = async (action, params = {}, body) => {
    const url = API + '?' + new URLSearchParams({ action, ...params });
    const r = await fetch(url, body ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) } : {});
    let d;
    try { d = await r.json(); } catch { throw new Error('Der Buchungsserver ist nicht erreichbar. Bitte rufen Sie uns an: 071 245 19 19'); }
    if (!d.ok) throw new Error(d.error || 'Fehler');
    return d;
  };

  const state = { step: 1, maxStep: 1, date: null, time: null, staff: null, service: null, variant: null };
  let info = null;
  let slots = {};
  let month = null;
  const dayCounts = {};

  // ------------------------------------------------------------ Navigation
  function go(step) {
    state.step = step;
    state.maxStep = Math.max(state.maxStep, step);
    $$('.pane').forEach((p) => { p.hidden = Number(p.dataset.pane) !== step; });
    $$('#steps li').forEach((li) => {
      const n = Number(li.dataset.step);
      li.classList.toggle('is-current', n === step);
      li.classList.toggle('is-done', n < step || (n <= state.maxStep && n !== step && reachable(n)));
    });
    $('#pane-foot').hidden = step === 1 || step === 6;
    document.body.classList.toggle('is-done', step === 6);
    if (step === 2) renderTimes();
    if (step === 3) renderPeople();
    if (step === 4) renderServices();
    if (step === 5) setTimeout(() => $('#b-name').focus({ preventScroll: true }), 50);
    summary();
    const top = $('#steps').getBoundingClientRect().top + window.scrollY - 100;
    if (window.scrollY > top) window.scrollTo({ top, behavior: 'smooth' });
  }
  const reachable = (n) => (n <= 1) || (n === 2 && state.date) || (n === 3 && state.time) || (n === 4 && state.staff !== null) || (n === 5 && state.service);

  $('#steps').addEventListener('click', (e) => {
    const li = e.target.closest('li');
    if (!li || state.step === 6) return;
    const n = Number(li.dataset.step);
    if (n !== state.step && reachable(n)) go(n);
  });
  $('#back').addEventListener('click', () => go(Math.max(1, state.step - 1)));

  function summary() {
    const set = (k, v) => { const dd = $(`[data-s="${k}"] dd`); dd.textContent = v || '–'; dd.classList.toggle('is-empty', !v); };
    set('date', state.date && dateLabel(state.date).replace(/^(\w+),/, (m, w) => w.slice(0, 2) + ','));
    set('time', state.time && state.time + ' Uhr');
    set('staff', state.staff === null ? '' : state.staff === 0 ? 'Keine Präferenz' : staffById(state.staff)?.name);
    const svc = serviceById(state.service);
    set('service', svc && (svc.name + (state.variant ? ` (${state.variant})` : '')));
    set('price', svc && chf(priceFor(svc, state.variant)));
  }

  const staffById = (id) => info.staff.find((s) => s.id === id);
  const serviceById = (id) => info.services.find((s) => s.id === id);
  const filled = (svc) => svc.prices.filter((p) => p !== null);
  const hasVariants = (svc) => filled(svc).length > 1;
  const priceFor = (svc, v) => {
    if (!hasVariants(svc)) return filled(svc)[0] ?? null;
    const i = VARIANTS.findIndex(([k]) => k === v);
    return i < 0 ? null : svc.prices[i];
  };

  // ------------------------------------------------------------ 1 Datum
  const today = () => parse(info.today);
  const lastDay = () => { const d = today(); d.setDate(d.getDate() + info.horizon_days); return d; };

  async function renderCalendar() {
    const grid = $('#cal-grid');
    const first = new Date(month.getFullYear(), month.getMonth(), 1);
    const days = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();
    const offset = (first.getDay() + 6) % 7;
    $('#cal-title').textContent = `${MONTHS[month.getMonth()]} ${month.getFullYear()}`;
    $('#cal-prev').disabled = first <= new Date(today().getFullYear(), today().getMonth(), 1);
    $('#cal-next').disabled = new Date(month.getFullYear(), month.getMonth() + 1, 1) > lastDay();

    const t = iso(today());
    let html = '<span class="cal__empty"></span>'.repeat(offset);
    for (let i = 1; i <= days; i++) {
      const key = iso(new Date(month.getFullYear(), month.getMonth(), i));
      const known = key in dayCounts;
      const inRange = key >= t && key <= iso(lastDay());
      const n = dayCounts[key] || 0;
      const cls = ['day', key === t && 'is-today', key === state.date && 'is-selected', !known && inRange && 'is-loading', n > 0 && n <= 4 && 'is-few'].filter(Boolean).join(' ');
      const dis = !inRange || (known && n === 0);
      html += `<button type="button" class="${cls}" data-date="${key}" ${dis ? 'disabled' : ''} aria-label="${dateLabel(key)}${dis ? ', nicht verfügbar' : ''}">${i}<i></i></button>`;
    }
    grid.innerHTML = html;

    // Verfügbarkeit des Monats nachladen
    const from = [iso(first), t].sort().pop();
    const to = [iso(new Date(month.getFullYear(), month.getMonth(), days)), iso(lastDay())].sort()[0];
    if (from <= to && !(from in dayCounts && to in dayCounts)) {
      try {
        const d = await api('days', { from, to });
        Object.assign(dayCounts, d.days);
      } catch (err) {
        $('#cal-grid').insertAdjacentHTML('afterend', `<div class="alert alert--err" style="margin-top:16px">${esc(err.message)}</div>`);
        return;
      }
      renderCalendar();
    }
  }

  $('#cal-prev').addEventListener('click', () => { month = new Date(month.getFullYear(), month.getMonth() - 1, 1); renderCalendar(); });
  $('#cal-next').addEventListener('click', () => { month = new Date(month.getFullYear(), month.getMonth() + 1, 1); renderCalendar(); });
  $('#cal-grid').addEventListener('click', async (e) => {
    const btn = e.target.closest('.day');
    if (!btn || btn.disabled) return;
    if (state.date !== btn.dataset.date) {
      state.date = btn.dataset.date;
      state.time = null;
      state.staff = null;
      state.maxStep = 2;
    }
    $$('.day').forEach((d) => d.classList.toggle('is-selected', d === btn));
    await loadSlots();
    go(2);
  });

  async function loadSlots() {
    $('#times').innerHTML = '<div class="empty">Freie Zeiten werden geladen …</div>';
    try {
      const d = await api('slots', { date: state.date });
      slots = Array.isArray(d.slots) ? {} : d.slots;
      dayCounts[state.date] = Object.keys(slots).length;
    } catch (err) {
      slots = {};
      $('#times').innerHTML = `<div class="alert alert--err">${esc(err.message)}</div>`;
    }
  }

  // ------------------------------------------------------------ 2 Uhrzeit
  function renderTimes() {
    $('#time-sub').textContent = dateLabel(state.date);
    const times = Object.keys(slots).sort();
    if (!times.length) {
      $('#times').innerHTML = '<div class="empty"><strong>Leider keine freien Termine</strong>Bitte wählen Sie ein anderes Datum.</div>';
      return;
    }
    const groups = [['Vormittag', (t) => t < '12:00'], ['Nachmittag', (t) => t >= '12:00' && t < '17:00'], ['Abend', (t) => t >= '17:00']];
    $('#times').innerHTML = groups.map(([label, fn]) => {
      const list = times.filter(fn);
      if (!list.length) return '';
      return `<div class="times__group"><h4>${label}</h4><div class="times__grid">${
        list.map((t) => `<button type="button" class="chip${t === state.time ? ' is-selected' : ''}" data-time="${t}">${t}</button>`).join('')
      }</div></div>`;
    }).join('');
  }
  $('#times').addEventListener('click', (e) => {
    const btn = e.target.closest('.chip');
    if (!btn) return;
    state.time = btn.dataset.time;
    if (state.staff && !(slots[state.time] || []).includes(state.staff)) state.staff = null;
    go(3);
  });

  // ------------------------------------------------------------ 3 Coiffeur
  function renderPeople() {
    $('#staff-sub').textContent = `${dateLabel(state.date)}, ${state.time} Uhr`;
    const free = slots[state.time] || [];
    const dow = parse(state.date).getDay();
    const working = info.staff.filter((s) => s.days.includes(dow));
    const anyIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/></svg>';
    let html = `<button type="button" class="person${state.staff === 0 ? ' is-selected' : ''}" data-id="0">
      <span class="person__av person__av--any">${anyIcon}</span><div><b>Keine Präferenz</b><span>Wer gerade frei ist</span></div></button>`;
    html += working.map((s) => {
      const ok = free.includes(s.id);
      const initials = s.name.split(/\s+/).map((w) => w[0]).join('').slice(0, 2).toUpperCase();
      return `<button type="button" class="person${state.staff === s.id ? ' is-selected' : ''}" data-id="${s.id}" ${ok ? '' : 'disabled'}>
        <span class="person__av" style="--c:${esc(s.color)}">${esc(initials)}</span>
        <div><b>${esc(s.name)}</b><span>${ok ? esc(s.role || 'Coiffeur') : 'zu dieser Zeit belegt'}</span></div></button>`;
    }).join('');
    $('#people').innerHTML = html;
  }
  $('#people').addEventListener('click', (e) => {
    const btn = e.target.closest('.person');
    if (!btn || btn.disabled) return;
    state.staff = Number(btn.dataset.id);
    go(4);
  });

  // ------------------------------------------------------------ 4 Leistung
  let svcCat = null;
  function renderServices() {
    const order = ['Herren', 'Damen', 'Kinder', 'Cut & Go'];
    const cats = [...new Set(info.services.map((s) => s.category))].sort((a, b) => {
      const ia = order.indexOf(a), ib = order.indexOf(b);
      return (ia < 0 ? 99 : ia) - (ib < 0 ? 99 : ib);
    });
    if (!svcCat || !cats.includes(svcCat)) svcCat = serviceById(state.service)?.category || cats[0];
    $('#svc-tabs').innerHTML = cats.map((c) => `<button type="button" class="tab" role="tab" data-cat="${esc(c)}" aria-selected="${c === svcCat}">${esc(c)}</button>`).join('');
    $('#svc-list').innerHTML = info.services.filter((s) => s.category === svcCat).map((s) => {
      const sel = s.id === state.service;
      const f = filled(s);
      const price = hasVariants(s) ? `ab ${chf(Math.min(...f))}` : chf(f[0] ?? null);
      const variants = sel && hasVariants(s)
        ? `<div class="svc__variants"><p>Bitte Haarlänge wählen:</p>${VARIANTS.map(([k, label], i) => s.prices[i] === null ? '' :
          `<button type="button" class="chip${state.variant === k ? ' is-selected' : ''}" data-variant="${k}">${label}<small>${chf(s.prices[i])}</small></button>`).join('')}</div>`
        : '';
      return `<div class="svc__item${sel ? ' is-selected' : ''}" data-id="${s.id}">
        <button type="button" aria-pressed="${sel}"><span class="svc__radio"></span><span class="svc__name">${esc(s.name)}</span><span class="svc__price">${price}</span></button>${variants}</div>`;
    }).join('') || '<div class="empty">Keine Leistungen in dieser Kategorie.</div>';
  }
  $('#svc-tabs').addEventListener('click', (e) => {
    const t = e.target.closest('.tab');
    if (!t) return;
    svcCat = t.dataset.cat;
    renderServices();
  });
  $('#svc-list').addEventListener('click', (e) => {
    const v = e.target.closest('[data-variant]');
    if (v) {
      state.variant = v.dataset.variant;
      go(5);
      return;
    }
    const item = e.target.closest('.svc__item');
    if (!item || !e.target.closest('.svc__item > button')) return;
    const svc = serviceById(Number(item.dataset.id));
    if (state.service !== svc.id) state.variant = null;
    state.service = svc.id;
    if (!hasVariants(svc)) {
      state.variant = null;
      go(5);
    } else {
      if (state.variant && priceFor(svc, state.variant) === null) state.variant = null;
      renderServices();
      summary();
    }
  });

  // ------------------------------------------------------------ 5 Angaben & Buchen
  let booked = null;
  $('#book-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const msg = $('.form__msg', form);
    msg.innerHTML = '';
    const svc = serviceById(state.service);
    if (!state.date || !state.time || state.staff === null || !svc || (hasVariants(svc) && !state.variant)) {
      msg.innerHTML = '<div class="alert alert--err">Bitte vervollständigen Sie zuerst alle Schritte.</div>';
      return;
    }
    if (!form.checkValidity()) {
      const bad = $(':invalid', form);
      msg.innerHTML = `<div class="alert alert--err">${bad?.id === 'b-consent' ? 'Bitte bestätigen Sie die Einwilligung.' : 'Bitte geben Sie Name und Telefonnummer korrekt an.'}</div>`;
      bad?.focus();
      return;
    }
    const btn = $('.bk__submit', form);
    btn.disabled = true;
    try {
      const data = Object.fromEntries(new FormData(form));
      booked = await api('book', {}, {
        ...data, date: state.date, time: state.time, staff_id: state.staff, service_id: state.service, variant: state.variant || '',
      });
      $('#done-sub').textContent = data.email ? `Eine Bestätigung wurde an ${data.email} gesendet.` : 'Wir freuen uns auf Ihren Besuch.';
      $('#done-list').innerHTML = [
        ['Datum', booked.date_label], ['Uhrzeit', booked.time + ' Uhr'], ['Bei', booked.staff],
        ['Leistung', booked.service], ['Preis', booked.price],
      ].map(([k, v]) => `<div><dt>${k}</dt><dd>${esc(v)}</dd></div>`).join('');
      go(6);
    } catch (err) {
      msg.innerHTML = `<div class="alert alert--err">${esc(err.message)}</div>`;
      if (/vergeben/.test(err.message)) {
        await loadSlots();
        state.time = null;
        state.staff = null;
        setTimeout(() => go(2), 1800);
      }
    } finally {
      btn.disabled = false;
    }
  });

  $('#ics').addEventListener('click', () => {
    if (!booked) return;
    const [y, m, d] = booked.date.split('-');
    const [h, i] = booked.time.split(':').map(Number);
    const end = h * 60 + i + (info.slot_minutes || 30);
    const stamp = new Date().toISOString().replace(/[-:]/g, '').replace(/\.\d+/, '');
    const ics = [
      'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Star 1 Coiffeur//Buchung//DE', 'BEGIN:VEVENT',
      `UID:star1-${booked.id}@star-1.ch`, `DTSTAMP:${stamp}`,
      `DTSTART;TZID=Europe/Zurich:${y}${m}${d}T${pad(h)}${pad(i)}00`,
      `DTEND;TZID=Europe/Zurich:${y}${m}${d}T${pad(Math.floor(end / 60))}${pad(end % 60)}00`,
      `SUMMARY:Coiffeur-Termin bei Star 1 (${booked.staff})`,
      `DESCRIPTION:${booked.service.replace(/[,;]/g, '\\$&')}`,
      'LOCATION:Star 1 Coiffeur\\, Untere Bahnhofstrasse 19\\, 9500 Wil',
      'END:VEVENT', 'END:VCALENDAR',
    ].join('\r\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([ics], { type: 'text/calendar' }));
    a.download = 'star1-termin.ics';
    a.click();
  });

  // ------------------------------------------------------------ Start
  (async () => {
    try {
      info = await api('info');
    } catch (err) {
      $('.bk__panel').innerHTML = `<div class="empty"><strong>Online-Buchung gerade nicht verfügbar</strong>${esc(err.message)}</div>`;
      return;
    }
    month = new Date(today().getFullYear(), today().getMonth(), 1);
    renderCalendar();
    go(1);
  })();
})();

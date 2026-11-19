/* Star 1 Coiffeur — Webseite */
(() => {
  const API = new URL('../../api/public.php', document.currentScript.src).href;
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];

  // Preise als Rückfall, falls die Schnittstelle (PHP) nicht erreichbar ist
  const FALLBACK = [
    ['Cut & Go', 'Damen', [38]], ['Cut & Go', 'Herren', [32]],
    ['Damen', 'Waschen, Schneiden, selber Föhnen', [44]],
    ['Damen', 'Waschen, Föhnen', [38, 43, 53]], ['Damen', 'Waschen, Glätten', [43, 48, 58]],
    ['Damen', 'Nur Färben', [63, 73, 78]], ['Damen', 'Nur Tönen', [38, 43, 58]],
    ['Damen', 'Foliensträhnen Oberkopf', [63, 78, 88]], ['Damen', 'Foliensträhnen ganzer Kopf', [88, 98, 135]],
    ['Damen', 'Balayage / Ombré', [null, 150, 180]], ['Damen', 'Bleichen', [60, 75, 80]],
    ['Damen', 'Ansatz färben', [48]], ['Damen', 'Augenbrauen mit Faden zupfen', [20]],
    ['Damen', 'Augenbrauen färben', [15]], ['Damen', 'Wimpern färben', [15]],
    ['Herren', 'Waschen, Schneiden, Frisieren', [38]], ['Herren', 'Maschinen-Haarschnitt', [28]],
    ['Herren', 'Nassrasur', [25]], ['Herren', 'Bart schneiden', [20]], ['Herren', 'Bart färben', [20]],
    ['Herren', 'Nur Färben', [20]], ['Herren', 'Nur Tönen', [38]], ['Herren', 'Bleichen', [48]],
    ['Herren', 'Augenbrauen mit Faden zupfen', [15]],
    ['Kinder', 'Jungs bis 10 Jahre', [20]], ['Kinder', 'Jungs bis 16 Jahre', [25]],
    ['Kinder', 'Mädchen bis 6 Jahre', [20]], ['Kinder', 'Mädchen bis 16 Jahre', [35]],
  ].map(([category, name, p]) => ({ category, name, prices: [p[0] ?? null, p[1] ?? null, p[2] ?? null] }));
  const FALLBACK_HOURS = { 0: null, 1: ['09:00', '19:00'], 2: ['09:00', '19:00'], 3: ['09:00', '19:00'], 4: ['09:00', '19:00'], 5: ['09:00', '19:00'], 6: ['08:00', '17:00'] };

  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const chf = (v) => (v === null || v === undefined ? '' : (Number.isInteger(v) ? v + '.–' : v.toFixed(2)));

  // Kopfzeile
  const header = $('.header');
  const onScroll = () => header && header.classList.toggle('is-solid', window.scrollY > 40);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  const burger = $('.burger');
  if (burger) {
    burger.addEventListener('click', () => {
      const open = document.body.classList.toggle('menu-open');
      burger.setAttribute('aria-expanded', open);
    });
    $$('.nav a').forEach((a) => a.addEventListener('click', () => {
      document.body.classList.remove('menu-open');
      burger.setAttribute('aria-expanded', 'false');
    }));
  }

  // Einblenden
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => entries.forEach((e) => {
      if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
    }), { rootMargin: '0px 0px -8% 0px' });
    $$('.reveal').forEach((el) => io.observe(el));
  } else {
    $$('.reveal').forEach((el) => el.classList.add('is-in'));
  }

  $$('[data-year]').forEach((el) => { el.textContent = new Date().getFullYear(); });

  // Öffnungszeiten & Status
  function renderHours(hours) {
    const table = $('#hours');
    const now = new Date();
    const dow = now.getDay();
    if (table) {
      $$('tr', table).forEach((tr) => {
        const d = Number(tr.dataset.d);
        const h = hours[d];
        tr.classList.toggle('is-today', d === dow);
        tr.lastElementChild.textContent = h ? `${h[0]} – ${h[1]}` : 'geschlossen';
        tr.lastElementChild.classList.toggle('closed', !h);
      });
    }
    const status = $('#open-status');
    if (status) {
      const h = hours[dow];
      const mins = now.getHours() * 60 + now.getMinutes();
      const toMin = (t) => Number(t.slice(0, 2)) * 60 + Number(t.slice(3));
      const open = h && mins >= toMin(h[0]) && mins < toMin(h[1]);
      status.innerHTML = `<span class="status-dot${open ? '' : ' is-closed'}"></span>${open ? 'Jetzt geöffnet' : 'Jetzt geschlossen'}`;
      let sub = '';
      if (open) sub = `Heute bis ${h[1]} Uhr`;
      else {
        for (let i = 0; i < 8; i++) {
          const d = (dow + i) % 7;
          const hh = hours[d];
          if (!hh) continue;
          if (i === 0 && mins >= toMin(hh[0])) continue;
          const names = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
          sub = `Öffnet ${i === 0 ? 'heute' : i === 1 ? 'morgen' : names[d]} um ${hh[0]} Uhr`;
          break;
        }
      }
      $('#open-sub').textContent = sub;
    }
  }

  // Preisliste
  function renderPrices(services) {
    const tabs = $('#price-tabs');
    const panels = $('#price-panels');
    if (!tabs || !panels) return;
    const order = ['Herren', 'Damen', 'Kinder', 'Cut & Go'];
    const cats = [...new Set(services.map((s) => s.category))].sort((a, b) => {
      const ia = order.indexOf(a), ib = order.indexOf(b);
      return (ia < 0 ? 99 : ia) - (ib < 0 ? 99 : ib);
    });
    tabs.innerHTML = cats.map((c, i) => `<button class="tab" role="tab" id="tab-${i}" aria-controls="panel-${i}" aria-selected="${i === 0}">${esc(c)}</button>`).join('');
    panels.innerHTML = cats.map((c, i) => {
      const list = services.filter((s) => s.category === c);
      const multi = list.some((s) => s.prices.filter((p) => p !== null).length > 1);
      const rows = list.map((s) => {
        const filled = s.prices.filter((p) => p !== null);
        const cells = multi
          ? (filled.length > 1 ? s.prices : [filled[0] ?? null, null, null]).map((p) => `<span>${chf(p)}</span>`).join('')
          : `<span>${chf(filled[0] ?? null)}</span>`;
        return `<div class="pricelist__row"><b>${esc(s.name)}</b>${cells}</div>`;
      }).join('');
      const head = multi
        ? '<div class="pricelist__head"><span style="text-align:left">Leistung</span><span>Kurz</span><span>Mittel</span><span>Lang</span></div>'
        : '<div class="pricelist__head"><span style="text-align:left">Leistung</span><span>CHF</span></div>';
      return `<div class="pricelist${multi ? '' : ' pricelist--single'}" role="tabpanel" id="panel-${i}" aria-labelledby="tab-${i}" ${i ? 'hidden' : ''}>${head}${rows}</div>`;
    }).join('');
    tabs.addEventListener('click', (e) => {
      const btn = e.target.closest('.tab');
      if (!btn) return;
      $$('.tab', tabs).forEach((t) => t.setAttribute('aria-selected', t === btn));
      $$('[role=tabpanel]', panels).forEach((p) => { p.hidden = p.id !== btn.getAttribute('aria-controls'); });
    });
  }

  if ($('#price-tabs') || $('#hours') || $('#open-status')) {
    renderHours(FALLBACK_HOURS);
    renderPrices(FALLBACK);
    fetch(API + '?action=info').then((r) => r.json()).then((d) => {
      if (!d.ok) return;
      renderHours(d.hours);
      if (d.services.length) renderPrices(d.services);
    }).catch(() => {});
  }

  // Promo-Video
  const promoBtn = $('.promo__play');
  if (promoBtn) {
    promoBtn.addEventListener('click', () => {
      const v = $('#promo');
      v.controls = true;
      v.play();
      promoBtn.remove();
    });
  }

  // Karte erst auf Wunsch laden
  const mapBtn = $('#map-load');
  if (mapBtn) {
    mapBtn.addEventListener('click', () => {
      $('#map').innerHTML = '<iframe title="Karte: Untere Bahnhofstrasse 19, Wil" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://maps.google.com/maps?q=Untere%20Bahnhofstrasse%2019%2C%209500%20Wil&amp;z=16&amp;output=embed"></iframe>';
    });
  }

  // Kontaktformular
  const form = $('#contact-form');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const msg = $('.form__msg', form);
      if (!form.checkValidity()) {
        msg.innerHTML = '<div class="alert alert--err">Bitte füllen Sie alle Pflichtfelder aus und bestätigen Sie den Datenschutz.</div>';
        return;
      }
      const btn = $('button[type=submit]', form);
      btn.disabled = true;
      try {
        const data = Object.fromEntries(new FormData(form));
        const r = await fetch(API + '?action=contact', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        const d = await r.json();
        if (!d.ok) throw new Error(d.error);
        form.reset();
        msg.innerHTML = '<div class="alert alert--ok">Vielen Dank! Wir melden uns so schnell wie möglich.</div>';
      } catch (err) {
        msg.innerHTML = `<div class="alert alert--err">${esc(err.message || 'Senden fehlgeschlagen. Bitte rufen Sie uns an: 071 245 19 19')}</div>`;
      } finally {
        btn.disabled = false;
      }
    });
  }
})();

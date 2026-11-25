/* Kita Farbewält — Menü, Einblenden, Zahlen, Tarifrechner, Formular */
(function () {
    'use strict';
    document.documentElement.classList.add('js');

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Kopfzeile beim Scrollen
    var header = document.querySelector('[data-header]');
    var onScroll = function () { header && header.classList.toggle('is-scrolled', window.scrollY > 10); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // Mobiles Menü
    var toggle = document.querySelector('[data-menu-toggle]');
    if (toggle) {
        var setMenu = function (open) {
            document.body.classList.toggle('menu-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        toggle.addEventListener('click', function () { setMenu(!document.body.classList.contains('menu-open')); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setMenu(false); });
        document.querySelectorAll('#menu a').forEach(function (a) { a.addEventListener('click', function () { setMenu(false); }); });
    }

    // Einblenden beim Scrollen
    var items = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && !reduce) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); }
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
        items.forEach(function (el) { io.observe(el); });
    } else {
        items.forEach(function (el) { el.classList.add('is-visible'); });
    }

    // Zahlen hochzählen, sobald sie sichtbar werden
    var counters = document.querySelectorAll('[data-count]');
    if (counters.length && 'IntersectionObserver' in window && !reduce) {
        var run = function (el) {
            var end = parseInt(el.dataset.count, 10), pre = el.dataset.prefix || '', suf = el.dataset.suffix || '';
            var start = null, dur = 1600;
            var step = function (ts) {
                if (!start) start = ts;
                var p = Math.min((ts - start) / dur, 1);
                var eased = 1 - Math.pow(1 - p, 3);
                el.textContent = pre + Math.round(end * eased) + suf;
                if (p < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        };
        var co = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) { run(en.target); co.unobserve(en.target); }
            });
        }, { threshold: 0.6 });
        counters.forEach(function (el) { co.observe(el); });
    }

    // Tarifrechner
    var calc = document.querySelector('[data-calc]');
    if (calc) {
        var fmt = function (n) {
            var parts = n.toFixed(2).split('.');
            return parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '’') + '.' + parts[1];
        };
        var outMonth = calc.querySelector('[data-out-month]');
        var outDay = calc.querySelector('[data-out-day]');
        var update = function () {
            var alter = calc.querySelector('input[name="alter"]:checked').value;
            var tage = +calc.querySelector('input[name="tage"]:checked').value;
            var rabatt = calc.querySelector('input[name="geschwister"]').checked ? 0.9 : 1;
            var preis = parseFloat(calc.dataset[alter]);
            var monat = preis * tage * parseFloat(calc.dataset.faktor) * rabatt;
            outMonth.textContent = fmt(monat);
            outDay.textContent = 'CHF ' + Math.round(preis * rabatt * 100) / 100 + ' pro Tag' + (rabatt < 1 ? ' (mit Rabatt)' : '');
            outMonth.animate && !reduce && outMonth.animate([{ opacity: .3, transform: 'translateY(6px)' }, { opacity: 1, transform: 'none' }], { duration: 350, easing: 'ease-out' });
        };
        calc.addEventListener('change', update);
        update();
    }

    // Anfrageformular
    var form = document.querySelector('[data-form]');
    if (form && window.fetch && window.FormData) {
        var msg = form.querySelector('[data-form-msg]');
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            form.querySelectorAll('.has-error').forEach(function (f) { f.classList.remove('has-error'); });
            var invalid = Array.prototype.filter.call(form.querySelectorAll('[required]'), function (el) {
                return el.type === 'checkbox' ? !el.checked : !el.value.trim() || (el.type === 'email' && !/^\S+@\S+\.\S+$/.test(el.value));
            });
            if (invalid.length) {
                invalid.forEach(function (el) { var f = el.closest('.field'); f && f.classList.add('has-error'); });
                msg.hidden = false;
                msg.className = 'form__msg form__msg--err';
                msg.textContent = 'Bitte füllt alle Pflichtfelder (*) aus.';
                invalid[0].focus();
                return;
            }
            form.classList.add('is-sending');
            fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    msg.hidden = false;
                    msg.className = 'form__msg ' + (data.ok ? 'form__msg--ok' : 'form__msg--err');
                    msg.textContent = data.message;
                    if (data.ok) form.reset();
                    msg.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
                })
                .catch(function () {
                    msg.hidden = false;
                    msg.className = 'form__msg form__msg--err';
                    msg.textContent = 'Das hat leider nicht geklappt. Bitte ruft uns an oder schreibt eine E-Mail.';
                })
                .finally(function () { form.classList.remove('is-sending'); });
        });
    }
})();

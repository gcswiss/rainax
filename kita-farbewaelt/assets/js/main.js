/* Kita Farbewält — Menü, Einblenden, Reels, Galerie, Tarifrechner, Formular */
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

    // Alltags-Videos: spielen, wenn sichtbar
    var vids = document.querySelectorAll('video[data-autoplay]');
    if (vids.length && 'IntersectionObserver' in window && !reduce) {
        var vo = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                var v = en.target, fig = v.closest('.reel');
                if (en.isIntersecting) {
                    if (v.preload === 'none') { v.preload = 'auto'; v.load(); }
                    var p = v.play();
                    if (p && p.then) p.then(function () { fig && fig.classList.add('is-playing'); }).catch(function () {});
                } else {
                    v.pause();
                    fig && fig.classList.remove('is-playing');
                }
            });
        }, { threshold: 0.5 });
        vids.forEach(function (v) { vo.observe(v); });
    }

    // Galerie mit Lightbox
    var gallery = document.querySelector('[data-gallery]');
    if (gallery) {
        var links = Array.prototype.slice.call(gallery.querySelectorAll('a'));
        var box, img, cap, idx = 0;
        var show = function (i) {
            idx = (i + links.length) % links.length;
            img.src = links[idx].getAttribute('href');
            img.alt = links[idx].dataset.caption || '';
            cap.textContent = links[idx].dataset.caption || '';
        };
        var close = function () {
            box.classList.remove('is-open');
            setTimeout(function () { box.remove(); box = null; }, 250);
            document.removeEventListener('keydown', keys);
        };
        var keys = function (e) {
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowRight') show(idx + 1);
            if (e.key === 'ArrowLeft') show(idx - 1);
        };
        links.forEach(function (a, i) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                box = document.createElement('div');
                box.className = 'lightbox';
                box.setAttribute('role', 'dialog');
                box.setAttribute('aria-modal', 'true');
                box.innerHTML = '<figure><img alt=""><p></p></figure>' +
                    '<button class="lb-close" aria-label="Schliessen">×</button>' +
                    (links.length > 1 ? '<button class="lb-prev" aria-label="Vorheriges Bild">➜</button><button class="lb-next" aria-label="Nächstes Bild">➜</button>' : '');
                document.body.appendChild(box);
                img = box.querySelector('img');
                cap = box.querySelector('p');
                show(i);
                requestAnimationFrame(function () { box.classList.add('is-open'); });
                box.addEventListener('click', function (ev) {
                    if (ev.target === box) close();
                    if (ev.target.closest('.lb-close')) close();
                    if (ev.target.closest('.lb-next')) show(idx + 1);
                    if (ev.target.closest('.lb-prev')) show(idx - 1);
                });
                document.addEventListener('keydown', keys);
                box.querySelector('.lb-close').focus();
            });
        });
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

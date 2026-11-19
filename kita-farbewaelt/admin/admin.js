/* Kita Farbewält — Admin: Listen, Uploads, Hinweis auf ungespeicherte Änderungen */
(function () {
    'use strict';

    var editor = document.querySelector('[data-editor]');
    var csrf = document.querySelector('[data-csrf]');
    var dirty = false;
    var note = document.querySelector('[data-dirty-note]');

    var markDirty = function () {
        if (dirty) return;
        dirty = true;
        if (note) { note.textContent = 'Ungespeicherte Änderungen'; note.classList.add('is-dirty'); }
    };

    if (editor) {
        editor.addEventListener('input', markDirty);
        editor.addEventListener('change', markDirty);
        editor.addEventListener('submit', function () { dirty = false; });
        window.addEventListener('beforeunload', function (e) {
            if (dirty) { e.preventDefault(); e.returnValue = ''; }
        });
    }

    // Listen-Einträge neu nummerieren, damit die Reihenfolge stimmt
    var renumber = function (list) {
        var base = list.dataset.name;
        var items = list.querySelector('[data-items]').children;
        Array.prototype.forEach.call(items, function (item, i) {
            item.querySelectorAll('[name]').forEach(function (el) {
                if (el.closest('[data-list]') !== list) return; // verschachtelte Listen auslassen
                var rest = el.name.substring(base.length);
                el.name = base + rest.replace(/^\[[^\]]*\]/, '[' + i + ']');
            });
        });
        var count = list.querySelector('[data-count]');
        if (count) count.textContent = items.length;
    };

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('button');
        if (!btn) return;
        var list = btn.closest('[data-list]');
        var item = btn.closest('[data-item]');

        if (btn.matches('[data-add]') && list) {
            var tpl = list.querySelector('template[data-template]');
            var holder = document.createElement('div');
            holder.innerHTML = tpl.innerHTML;
            var node = holder.firstElementChild;
            list.querySelector('[data-items]').appendChild(node);
            renumber(list);
            markDirty();
            var input = node.querySelector('input:not([type=hidden]), textarea');
            input && input.focus();
            return;
        }
        if (!item || !list) return;
        if (btn.matches('[data-up], [data-down], [data-remove]')) e.preventDefault();
        if (btn.matches('[data-up]') && item.previousElementSibling) {
            item.parentNode.insertBefore(item, item.previousElementSibling);
            renumber(list); markDirty();
        }
        if (btn.matches('[data-down]') && item.nextElementSibling) {
            item.parentNode.insertBefore(item.nextElementSibling, item);
            renumber(list); markDirty();
        }
        if (btn.matches('[data-remove]')) {
            var title = item.querySelector('[data-title]').textContent;
            if (confirm('«' + title + '» wirklich entfernen?')) {
                item.remove();
                renumber(list); markDirty();
            }
        }
    });

    // Titel eines Eintrags live aktualisieren
    document.addEventListener('input', function (e) {
        var item = e.target.closest('[data-item]');
        if (!item) return;
        var firstInput = item.querySelector('.item__body input:not([type=hidden]), .item__body textarea');
        if (e.target === firstInput) {
            item.querySelector('[data-title]').textContent = e.target.value || 'Eintrag';
        }
    });

    // Uploads
    document.addEventListener('change', function (e) {
        if (!e.target.matches('[data-file]')) return;
        var input = e.target;
        var media = input.closest('[data-media]');
        var file = input.files[0];
        if (!file) return;
        var status = media.querySelector('[data-status]');
        var fd = new FormData();
        fd.append('action', 'upload');
        fd.append('kind', media.dataset.kind);
        fd.append('csrf', csrf.value);
        fd.append('file', file);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.ADMIN.uploadUrl);
        xhr.setRequestHeader('Accept', 'application/json');
        media.classList.add('is-uploading');
        xhr.upload.onprogress = function (ev) {
            if (ev.lengthComputable) status.textContent = 'Lädt hoch … ' + Math.round(ev.loaded / ev.total * 100) + ' %';
        };
        xhr.onload = function () {
            media.classList.remove('is-uploading');
            var data;
            try { data = JSON.parse(xhr.responseText); } catch (err) {
                data = { ok: false, message: xhr.status === 413 ? 'Die Datei ist zu gross für den Server (max. ' + window.ADMIN.maxUpload + ').' : 'Hochladen fehlgeschlagen.' };
            }
            if (!data.ok) { status.textContent = data.message; status.className = 'media__status is-error'; return; }
            media.querySelector('[data-value]').value = data.path;
            var prev = media.querySelector('[data-preview]');
            var kind = media.dataset.kind;
            prev.innerHTML = kind === 'image' ? '<img alt="">' : kind === 'video' ? '<video muted playsinline preload="metadata"></video>' : '<a target="_blank" rel="noopener" class="media__file"></a>';
            var el = prev.firstElementChild;
            if (kind === 'file') { el.href = data.url; el.textContent = '📄 ' + data.path.split('/').pop(); } else { el.src = data.url; }
            var thumb = media.closest('[data-item]');
            if (thumb && kind === 'image') {
                var t = thumb.querySelector('.item__thumb');
                if (t && !t.querySelector('img')) t.innerHTML = '<img alt="">';
                if (t) t.querySelector('img').src = data.url;
            }
            media.querySelector('[data-clear]').hidden = false;
            status.textContent = 'Hochgeladen ✓ – jetzt noch speichern.';
            status.className = 'media__status is-ok';
            markDirty();
        };
        xhr.onerror = function () {
            media.classList.remove('is-uploading');
            status.textContent = 'Verbindung unterbrochen.';
            status.className = 'media__status is-error';
        };
        xhr.send(fd);
        input.value = '';
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-clear]');
        if (!btn) return;
        var media = btn.closest('[data-media]');
        media.querySelector('[data-value]').value = '';
        media.querySelector('[data-preview]').innerHTML = '<span class="media__empty">Noch keine Datei</span>';
        btn.hidden = true;
        markDirty();
    });

    // Mobile Seitenleiste
    var toggle = document.querySelector('[data-sidebar-toggle]');
    toggle && toggle.addEventListener('click', function () { document.body.classList.toggle('sidebar-open'); });

    var flash = document.querySelector('[data-flash]');
    if (flash) setTimeout(function () { flash.classList.add('is-hidden'); }, 5000);
})();

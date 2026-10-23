/* University A shared UI toolkit: toasts, confirm/prompt dialogs, loading states,
 * image fallbacks, file dropzones, counters, relative times, mobile nav.
 * Everything is progressive enhancement; pages still work if this fails. */
(function () {
    'use strict';

    document.documentElement.classList.add('js');

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var SS = window.SS = window.SS || {};

    function el(tag, attrs, children) {
        var n = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function (k) {
            if (k === 'class') n.className = attrs[k];
            else if (k === 'text') n.textContent = attrs[k];
            else n.setAttribute(k, attrs[k]);
        });
        (children || []).forEach(function (c) { if (c) n.appendChild(c); });
        return n;
    }

    /* ---------------- top progress bar ---------------- */
    var bar;
    function progressOn() {
        if (!bar) { bar = el('div', { id: 'ss-progress', 'aria-hidden': 'true' }); document.body.appendChild(bar); }
        void bar.offsetWidth;
        bar.classList.add('is-on');
    }
    function progressOff() { if (bar) bar.classList.remove('is-on'); }
    SS.progress = { on: progressOn, off: progressOff };

    /* ---------------- toasts ---------------- */
    var toastHost;
    var ICONS = { success: '\u2713', error: '\u2715', info: 'i' };

    /* message is always inserted as text. Pass {html:true} only for trusted markup. */
    SS.toast = function (message, opts) {
        opts = opts || {};
        var type = opts.type || 'info';
        var ms = opts.duration === undefined ? (type === 'error' ? 6000 : 4000) : opts.duration;
        if (!toastHost) {
            toastHost = el('div', { id: 'ss-toasts', role: 'region', 'aria-label': 'Notifications' });
            document.body.appendChild(toastHost);
        }
        var body = el('div', { 'class': 'ss-toast-body' });
        if (opts.html) body.innerHTML = message; else body.textContent = message;
        if (opts.action) {
            body.appendChild(el('a', { 'class': 'ss-toast-action', href: opts.action.href, text: opts.action.label }));
        }
        var close = el('button', { type: 'button', 'class': 'ss-toast-x', 'aria-label': 'Dismiss notification', text: '\u00d7' });
        var t = el('div', {
            'class': 'ss-toast ss-toast-' + type,
            role: type === 'error' ? 'alert' : 'status'
        }, [el('span', { 'class': 'ss-toast-icon', 'aria-hidden': 'true', text: ICONS[type] || 'i' }), body, close]);
        t.style.setProperty('--ss-toast-ms', ms + 'ms');
        toastHost.appendChild(t);
        while (toastHost.children.length > 4) dismiss(toastHost.firstChild);

        var timer = null;
        function arm() { if (ms > 0) timer = setTimeout(function () { dismiss(t); }, ms); }
        t.addEventListener('mouseenter', function () { clearTimeout(timer); });
        t.addEventListener('mouseleave', arm);
        close.addEventListener('click', function () { dismiss(t); });
        arm();
        return t;
    };

    function dismiss(t) {
        if (!t || !t.parentNode || t.classList.contains('is-leaving')) return;
        t.classList.add('is-leaving');
        setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, reduceMotion ? 0 : 220);
    }

    /* Show a toast for ?key=value and then strip the key from the address bar. */
    SS.flash = function (key, map) {
        var params = new URLSearchParams(window.location.search);
        var v = params.get(key);
        if (v && Object.prototype.hasOwnProperty.call(map, v)) {
            var m = map[v];
            SS.toast(m.text || m, { type: m.type || 'success' });
            params.delete(key);
            var qs = params.toString();
            window.history.replaceState({}, document.title, window.location.pathname + (qs ? '?' + qs : ''));
            return true;
        }
        return false;
    };

    /* ---------------- modal dialogs ---------------- */
    var focusSel = 'a[href],button:not([disabled]),input:not([disabled]):not([type=hidden]),textarea:not([disabled]),select:not([disabled]),[tabindex]:not([tabindex="-1"])';

    /* SS.dialog({title, message, confirmText, cancelText, danger, fields:[{name,label,type,placeholder,required,maxlength}]})
     * resolves to false (cancelled) or true / {name: value} when fields are given. */
    SS.dialog = function (o) {
        return new Promise(function (resolve) {
            var prev = document.activeElement;
            var titleId = 'ss-dlg-' + Date.now();
            var inputs = {};
            var body = el('div', { 'class': 'ss-modal-body' });
            if (o.message) body.appendChild(el('p', { text: o.message }));
            (o.fields || []).forEach(function (f, i) {
                var id = titleId + '-f' + i;
                var inp = el(f.type === 'textarea' ? 'textarea' : 'input', {
                    id: id, name: f.name, type: f.type === 'textarea' ? null : (f.type || 'text'),
                    placeholder: f.placeholder || '', autocomplete: 'off'
                });
                if (f.type === 'textarea') inp.setAttribute('rows', '3');
                if (f.maxlength) inp.setAttribute('maxlength', f.maxlength);
                if (f.required) inp.required = true;
                if (f.value) inp.value = f.value;
                inputs[f.name] = inp;
                body.appendChild(el('label', { 'for': id, text: f.label }));
                body.appendChild(inp);
            });
            var cancel = el('button', { type: 'button', 'class': 'btn', text: o.cancelText || 'Cancel' });
            var ok = el('button', { type: 'submit', 'class': 'btn btn-primary' + (o.danger ? ' btn-danger' : ''), text: o.confirmText || 'Confirm' });
            var form = el('form', { novalidate: 'novalidate' }, [
                el('h3', { id: titleId, text: o.title || 'Are you sure?' }), body,
                el('div', { 'class': 'ss-modal-actions' }, [cancel, ok])
            ]);
            var box = el('div', { 'class': 'ss-modal', role: o.danger ? 'alertdialog' : 'dialog', 'aria-modal': 'true', 'aria-labelledby': titleId }, [form]);
            var overlay = el('div', { 'class': 'ss-overlay' }, [box]);
            var prevOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            document.body.appendChild(overlay);

            function close(result) {
                document.removeEventListener('keydown', onKey, true);
                overlay.classList.add('is-leaving');
                document.body.style.overflow = prevOverflow;
                setTimeout(function () { if (overlay.parentNode) overlay.parentNode.removeChild(overlay); }, reduceMotion ? 0 : 150);
                if (prev && prev.focus) { try { prev.focus(); } catch (e) { /* element gone */ } }
                resolve(result);
            }
            function onKey(e) {
                if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(false); }
                else if (e.key === 'Tab') {
                    var f = box.querySelectorAll(focusSel);
                    if (!f.length) return;
                    var first = f[0], last = f[f.length - 1];
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                }
            }
            document.addEventListener('keydown', onKey, true);
            overlay.addEventListener('mousedown', function (e) { if (e.target === overlay) close(false); });
            cancel.addEventListener('click', function () { close(false); });
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var values = {}, bad = null;
                Object.keys(inputs).forEach(function (k) {
                    values[k] = inputs[k].value;
                    if (inputs[k].required && !inputs[k].value.trim()) { inputs[k].classList.add('is-invalid'); bad = bad || inputs[k]; }
                    else inputs[k].classList.remove('is-invalid');
                });
                if (bad) { bad.focus(); return; }
                close(o.fields && o.fields.length ? values : true);
            });
            setTimeout(function () {
                var firstInput = box.querySelector('input,textarea');
                (firstInput || (o.danger ? cancel : ok)).focus();
            }, 30);
        });
    };

    SS.confirm = function (o) { return SS.dialog(o); };

    /* ---------------- declarative confirm: data-confirm ----------------
     * <form data-confirm="Delete it?" data-confirm-title="Delete" data-confirm-ok="Delete" data-danger>
     * <a href=".." data-confirm="..."> */
    function confirmOpts(node) {
        return {
            title: node.getAttribute('data-confirm-title') || 'Please confirm',
            message: node.getAttribute('data-confirm'),
            confirmText: node.getAttribute('data-confirm-ok') || 'Confirm',
            danger: node.hasAttribute('data-danger')
        };
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[data-confirm]');
        if (!a) return;
        e.preventDefault();
        SS.confirm(confirmOpts(a)).then(function (yes) {
            if (yes) { progressOn(); window.location.href = a.href; }
        });
    });

    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!f.hasAttribute || !f.hasAttribute('data-confirm') || f.__ssConfirmed) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        var submitter = e.submitter;
        SS.confirm(confirmOpts(f)).then(function (yes) {
            if (!yes) return;
            f.__ssConfirmed = true;
            if (f.requestSubmit) f.requestSubmit(submitter || undefined); else f.submit();
        });
    }, true);

    /* ---------------- loading states on submit / navigation ---------------- */
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (e.defaultPrevented || !f.tagName || f.tagName !== 'FORM' || f.hasAttribute('data-no-loading')) return;
        if (f.getAttribute('method') && f.getAttribute('method').toLowerCase() === 'dialog') return;
        var btn = e.submitter || f.querySelector('button[type=submit],button:not([type]),input[type=submit]');
        progressOn();
        /* defer: disabling a button inside the event would drop its name/value from the request */
        setTimeout(function () {
            if (btn && btn.tagName === 'BUTTON' && !btn.classList.contains('is-loading')) {
                btn.__ssLabel = btn.textContent;
                btn.classList.add('is-loading');
                btn.setAttribute('aria-busy', 'true');
                var txt = btn.getAttribute('data-loading-text');
                if (txt) btn.textContent = txt;
            }
            Array.prototype.forEach.call(f.querySelectorAll('button[type=submit],button:not([type])'), function (b) { b.disabled = true; });
        }, 0);
    });

    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (a.target && a.target !== '_self') return;
        if (a.hasAttribute('download') || a.hasAttribute('data-confirm') || a.hasAttribute('data-no-progress')) return;
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) return;
        if (a.origin !== window.location.origin) return;
        progressOn();
    });

    /* restore UI when coming back via bfcache */
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        progressOff();
        Array.prototype.forEach.call(document.querySelectorAll('.is-loading'), function (b) {
            b.classList.remove('is-loading');
            b.removeAttribute('aria-busy');
            if (b.__ssLabel) b.textContent = b.__ssLabel;
        });
        Array.prototype.forEach.call(document.querySelectorAll('form button:disabled'), function (b) { b.disabled = false; });
    });

    /* ---------------- password show/hide ---------------- */
    function enhancePasswords(root) {
        Array.prototype.forEach.call((root || document).querySelectorAll('input[type=password]:not([data-no-toggle])'), function (inp) {
            if (inp.parentNode.classList.contains('pw-wrap')) return;
            var wrap = el('div', { 'class': 'pw-wrap' });
            inp.parentNode.insertBefore(wrap, inp);
            wrap.appendChild(inp);
            var t = el('button', { type: 'button', 'class': 'btn btn-sm pw-toggle', 'aria-label': 'Show password', text: 'Show' });
            t.addEventListener('click', function () {
                var show = inp.type === 'password';
                inp.type = show ? 'text' : 'password';
                t.textContent = show ? 'Hide' : 'Show';
                t.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
            wrap.appendChild(t);
        });
    }
    SS.enhancePasswords = enhancePasswords;

    /* ---------------- password strength: <input data-strength="#meterId"> ---------------- */
    function score(p) {
        var s = 0;
        if (p.length >= 8) s++;
        if (p.length >= 12) s++;
        if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++;
        if (/\d/.test(p)) s++;
        if (/[^A-Za-z0-9]/.test(p)) s++;
        return Math.min(s, 4);
    }
    function bindStrength() {
        Array.prototype.forEach.call(document.querySelectorAll('input[data-strength]'), function (inp) {
            var meter = document.querySelector(inp.getAttribute('data-strength'));
            if (!meter) return;
            var fill = meter.querySelector('i');
            var label = meter.nextElementSibling;
            var names = ['Too weak', 'Weak', 'Okay', 'Good', 'Strong'];
            var colors = ['#999', '#777', '#555', '#333', '#000'];
            inp.addEventListener('input', function () {
                var v = inp.value, s = v ? score(v) : -1;
                fill.style.width = v ? ((s + 1) * 20) + '%' : '0';
                fill.style.background = colors[Math.max(s, 0)];
                if (label) label.textContent = v ? 'Strength: ' + names[s] : '';
            });
        });
    }

    /* ---------------- confirm-password match: <input data-match="#otherId"> ---------------- */
    function bindMatch() {
        Array.prototype.forEach.call(document.querySelectorAll('input[data-match]'), function (inp) {
            var other = document.querySelector(inp.getAttribute('data-match'));
            if (!other) return;
            var msg = inp.closest('.field') && inp.closest('.field').querySelector('.field-error');
            function check() {
                var bad = inp.value && inp.value !== other.value;
                inp.setCustomValidity(bad ? 'Passwords do not match' : '');
                inp.classList.toggle('is-invalid', !!bad);
                if (msg) msg.textContent = bad ? 'Passwords do not match.' : '';
            }
            inp.addEventListener('input', check);
            other.addEventListener('input', check);
        });
    }

    /* ---------------- character counters: <input maxlength data-counter> ---------------- */
    function bindCounters() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-counter]'), function (inp) {
            var max = parseInt(inp.getAttribute('maxlength'), 10);
            if (!max) return;
            var c = el('span', { 'class': 'counter', 'aria-hidden': 'true' });
            inp.insertAdjacentElement('afterend', c);
            function upd() {
                var n = inp.value.length;
                c.textContent = n + ' / ' + max;
                c.classList.toggle('is-near', n >= max * 0.9 && n < max);
                c.classList.toggle('is-over', n >= max);
            }
            inp.addEventListener('input', upd);
            upd();
        });
    }

    /* ---------------- file dropzone: <label class="dropzone" data-max-mb="5"><input type=file accept=...> ---------------- */
    function fmtSize(b) {
        if (b < 1024) return b + ' B';
        if (b < 1048576) return (b / 1024).toFixed(0) + ' KB';
        return (b / 1048576).toFixed(1) + ' MB';
    }
    function bindDropzones() {
        Array.prototype.forEach.call(document.querySelectorAll('label.dropzone'), function (dz) {
            var input = dz.querySelector('input[type=file]');
            if (!input) return;
            var maxMb = parseFloat(dz.getAttribute('data-max-mb')) || 0;
            var accept = (input.getAttribute('accept') || '').split(',').map(function (s) { return s.trim().toLowerCase(); }).filter(Boolean);
            var preview = dz.querySelector('.dz-preview');
            var name = dz.querySelector('.dz-name');
            var sub = dz.querySelector('.dz-sub');
            var defName = name ? name.textContent : '';
            var defSub = sub ? sub.textContent : '';
            var url = null;

            function reset() {
                dz.classList.remove('has-file');
                if (name) name.textContent = defName;
                if (sub) sub.textContent = defSub;
                if (preview) preview.removeAttribute('src');
                if (url) { URL.revokeObjectURL(url); url = null; }
            }
            function acceptable(f) {
                if (!accept.length) return true;
                var ext = '.' + f.name.split('.').pop().toLowerCase();
                return accept.some(function (a) {
                    if (a.charAt(0) === '.') return a === ext;
                    if (a.slice(-2) === '/*') return f.type.indexOf(a.slice(0, -1)) === 0;
                    return a === f.type;
                });
            }
            function pick(f) {
                if (!f) { reset(); return true; }
                if (!acceptable(f)) { SS.toast('That file type is not supported.', { type: 'error' }); input.value = ''; reset(); return false; }
                if (maxMb && f.size > maxMb * 1048576) { SS.toast('File is too large (max ' + maxMb + ' MB).', { type: 'error' }); input.value = ''; reset(); return false; }
                dz.classList.add('has-file');
                if (name) name.textContent = f.name;
                if (sub) sub.textContent = fmtSize(f.size) + ' \u00b7 click to change';
                if (preview && f.type.indexOf('image/') === 0) {
                    if (url) URL.revokeObjectURL(url);
                    url = URL.createObjectURL(f);
                    preview.src = url;
                } else if (preview) { preview.removeAttribute('src'); }
                return true;
            }
            input.addEventListener('change', function () { pick(input.files[0]); });
            ['dragenter', 'dragover'].forEach(function (ev) {
                dz.addEventListener(ev, function (e) { e.preventDefault(); dz.classList.add('is-over'); });
            });
            ['dragleave', 'drop'].forEach(function (ev) {
                dz.addEventListener(ev, function () { dz.classList.remove('is-over'); });
            });
            dz.addEventListener('drop', function (e) {
                e.preventDefault();
                if (e.dataTransfer && e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    pick(input.files[0]);
                }
            });
        });
    }

    /* ---------------- image loading / fallback ---------------- */
    function markThumb(img, state) {
        var t = img.closest('.thumb');
        if (t) t.classList.add(state);
    }
    function bindImages(root) {
        Array.prototype.forEach.call((root || document).querySelectorAll('.thumb img'), function (img) {
            if (img.__ssBound) return;
            img.__ssBound = true;
            if (img.complete) { markThumb(img, img.naturalWidth ? 'is-loaded' : 'is-broken'); return; }
            img.addEventListener('load', function () { markThumb(img, 'is-loaded'); });
            img.addEventListener('error', function () { markThumb(img, 'is-broken'); });
        });
    }
    SS.bindImages = bindImages;

    /* avatars: <img class="avatar" data-fallback="images/avatar-default.png"> */
    document.addEventListener('error', function (e) {
        var img = e.target;
        if (!img || img.tagName !== 'IMG' || !img.getAttribute('data-fallback')) return;
        var fb = img.getAttribute('data-fallback');
        img.removeAttribute('data-fallback');
        img.src = fb;
    }, true);

    /* ---------------- relative time: <time datetime="2026-10-01 12:00:00" data-rel> ---------------- */
    function relTime(d) {
        var s = Math.round((Date.now() - d.getTime()) / 1000);
        var future = s < 0; s = Math.abs(s);
        var u = [[31536000, 'y'], [2592000, 'mo'], [604800, 'w'], [86400, 'd'], [3600, 'h'], [60, 'm']];
        for (var i = 0; i < u.length; i++) {
            if (s >= u[i][0]) { var n = Math.floor(s / u[i][0]); return future ? 'in ' + n + u[i][1] : n + u[i][1] + ' ago'; }
        }
        return 'just now';
    }
    function bindTimes() {
        Array.prototype.forEach.call(document.querySelectorAll('time[data-rel]'), function (t) {
            var raw = t.getAttribute('datetime');
            if (!raw) return;
            /* DB timestamps are server-time (UTC in the container); treat bare values as UTC */
            var d = new Date(/[zZ]|[+-]\d\d:?\d\d$/.test(raw) ? raw : raw.replace(' ', 'T') + 'Z');
            if (isNaN(d)) return;
            t.title = d.toLocaleString();
            t.textContent = relTime(d);
        });
    }

    /* ---------------- mobile nav ---------------- */
    function bindNav() {
        var hdr = document.querySelector('header');
        var tgl = document.querySelector('.nav-toggle');
        if (!hdr || !tgl) return;
        tgl.addEventListener('click', function () {
            var open = hdr.classList.toggle('nav-open');
            tgl.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && hdr.classList.contains('nav-open')) { hdr.classList.remove('nav-open'); tgl.setAttribute('aria-expanded', 'false'); tgl.focus(); }
        });
    }

    /* ---------------- ajax + clipboard helpers ---------------- */
    /* SS.post(url, {k:v}) -> Promise<json>. Sends the CSRF token (from <meta name="csrf-token">) as a header and form field. */
    SS.post = function (url, data) {
        var meta = document.querySelector('meta[name="csrf-token"]');
        var token = meta ? meta.content : '';
        var body = new URLSearchParams();
        Object.keys(data || {}).forEach(function (k) { body.append(k, data[k]); });
        body.append('csrf', token);
        return fetch(url, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'X-CSRF-Token': token, 'X-Requested-With': 'fetch', 'Accept': 'application/json' },
            body: body
        }).then(function (r) {
            return r.json().catch(function () { return { ok: false, error: 'Unexpected response from server.' }; });
        }).catch(function () { return { ok: false, error: 'Network error. Check your connection and try again.' }; });
    };

    /* SS.copy(text) -> Promise<boolean>; shows a toast. */
    SS.copy = function (text, okMsg) {
        function done(ok) { SS.toast(ok ? (okMsg || 'Link copied') : 'Could not copy. Select and copy manually.', { type: ok ? 'success' : 'error' }); return ok; }
        if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text).then(function () { return done(true); }, function () { return done(false); });
        var ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.select();
        var ok = false; try { ok = document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(ta);
        return Promise.resolve(done(ok));
    };

    /* ---------------- nav "MORE" dropdown ---------------- */
    function bindNavMore() {
        var wraps = document.querySelectorAll('.nav-more');
        if (!wraps.length) return;
        function closeAll(except) {
            wraps.forEach(function (w) { if (w !== except) { w.classList.remove('is-open'); w.querySelector('.nav-more-btn').setAttribute('aria-expanded', 'false'); } });
        }
        wraps.forEach(function (w) {
            var btn = w.querySelector('.nav-more-btn');
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = !w.classList.contains('is-open');
                closeAll();
                w.classList.toggle('is-open', open);
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });
        document.addEventListener('click', function (e) {
            if (!e.target.closest || !e.target.closest('.nav-more')) closeAll();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAll();
        });
    }


    /* <button data-copy="view.php?id=1"> copies an absolute link.
       The guide drawer has its own copy buttons carrying literal payloads,
       not links: they must not pass through here, or the text is resolved
       against the current URL and the player gets two toasts. */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest && e.target.closest('[data-copy]');
        if (!btn || btn.classList.contains('guide-copy')) return;
        e.preventDefault();
        var rel = btn.getAttribute('data-copy');
        SS.copy(new URL(rel, window.location.href).href, 'Link copied');
    });

    /* ---------------- keep your place across a form submit ----------------
       Searching and filtering are GET forms, so the browser loads a fresh
       page and lands at the top. In these labs that means every payload
       throws you back up and you scroll down again to read the result.

       The position is stored only when a form is submitted, and restored
       once, for the same path. Following an ordinary link still starts at
       the top, which is what you want there. */
    var SCROLL_KEY = 'ss_scroll';

    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!f || f.tagName !== 'FORM') return;
        try {
            sessionStorage.setItem(SCROLL_KEY, JSON.stringify({
                path: location.pathname,
                y: window.scrollY || window.pageYOffset || 0
            }));
        } catch (err) { /* storage blocked - page just starts at the top */ }
    }, true);

    function restoreScroll() {
        var saved = null;
        try { saved = JSON.parse(sessionStorage.getItem(SCROLL_KEY) || 'null'); } catch (err) { return; }
        if (!saved || saved.path !== location.pathname || !saved.y) return;
        try { sessionStorage.removeItem(SCROLL_KEY); } catch (err) {}

        /* The page is still growing while images arrive, so a single scroll
           can be clamped short. Re-apply until the document is tall enough
           or the attempts run out. */
        var tries = 0;
        (function settle() {
            window.scrollTo(0, saved.y);
            if (++tries < 12 && Math.abs((window.scrollY || 0) - saved.y) > 2) {
                setTimeout(settle, 50);
            }
        })();
    }
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';

    /* ---------------- boot ---------------- */
    function boot() {
        restoreScroll();
        var main = document.querySelector('.content');
        if (main && !main.id) main.id = 'main';
        bindNav();
        bindNavMore();
        enhancePasswords();
        bindStrength();
        bindMatch();
        bindCounters();
        bindDropzones();
        bindImages();
        bindTimes();
        var flash = document.getElementById('ss-flash');
        if (flash) {
            try {
                var list = JSON.parse(flash.textContent);
                list.forEach(function (m) { SS.toast(m.text, { type: m.type || 'info' }); });
            } catch (e) { /* malformed flash payload */ }
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();

    /* ---------------- guided walkthrough drawer ---------------- */
    function guideBoot() {
        var tab = document.getElementById('guideTab');
        if (!tab) return;
        var close = document.getElementById('guideClose');
        var KEY = 'ss_guide_open';

        function set(open) {
            document.body.classList.toggle('guide-open', open);
            tab.setAttribute('aria-expanded', open ? 'true' : 'false');
            try { localStorage.setItem(KEY, open ? '1' : '0'); } catch (e) {}
        }

        /* The open/shut class is applied by an inline script in header.php
           before the first paint, so a remembered-open drawer is simply
           already there instead of sliding in on every page load. Nothing
           to restore here - only the control to bring into line with it. */
        tab.setAttribute('aria-expanded',
            document.body.classList.contains('guide-open') ? 'true' : 'false');

        tab.addEventListener('click', function () { set(true); });
        close.addEventListener('click', function () { set(false); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && document.body.classList.contains('guide-open')) set(false);
        });

        document.addEventListener('click', function (e) {
            var btn = e.target.closest ? e.target.closest('.guide-copy') : null;
            if (!btn) return;
            e.preventDefault();
            var text = btn.getAttribute('data-copy');
            if (window.SS && SS.copy) { SS.copy(text, 'Copied'); return; }
            try { navigator.clipboard.writeText(text); } catch (err) {}
        });

        /* Burp or curl, chosen once and applied to every step that offers
           both. The starting state is set by the inline script in guide.php,
           before paint; this only handles the clicking. */
        var CLIENT_KEY = 'ss_guide_client';
        function applyClient(name) {
            var groups = document.querySelectorAll('.guide-send');
            [].forEach.call(groups, function (g) {
                if (!g.querySelector('.guide-send-pane[data-send="' + name + '"]')) return;
                [].forEach.call(g.querySelectorAll('[data-send]'), function (el) {
                    el.classList.toggle('is-on', el.getAttribute('data-send') === name);
                });
            });
        }
        document.addEventListener('click', function (e) {
            var tab = e.target.closest ? e.target.closest('.guide-send-tab') : null;
            if (!tab) return;
            e.preventDefault();
            var name = tab.getAttribute('data-send');
            applyClient(name);
            try { localStorage.setItem(CLIENT_KEY, name); } catch (err) {}
        });

        /* One step open at a time, the open one survives a reload, and both
           halves of the swap are animated.

           <details> has no transition of its own: setting open flips the
           content in and out in a single frame. So the summary click is
           taken over here and the body is animated to and from its own
           height instead. The name= attribute is dropped at the same time -
           it is there for browsers with JavaScript off, and while it is set
           the browser slams the other steps shut with no animation, which is
           exactly the jump being fixed. */
        var steps = [].slice.call(document.querySelectorAll('.guide-step'));
        if (!steps.length) return;
        var STEP_KEY = 'ss_guide_step';
        var DUR = 260;
        var EASE = 'cubic-bezier(.2, .7, .2, 1)';
        var reduce = false;
        try { reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}

        function remember(i) {
            try { localStorage.setItem(STEP_KEY, String(i)); } catch (e) {}
        }
        function bodyOf(d) { return d.querySelector('.guide-step-body'); }

        /* Height alone is not enough: with border-box sizing the padding
           holds the element open at height 0, so it travels with it. */
        function run(d, opening) {
            var b = bodyOf(d);
            if (!b || !b.animate) { d.open = opening; return; }
            if (b.ssAnim) { b.ssAnim.cancel(); b.ssAnim = null; }

            var cs = window.getComputedStyle(b);
            var pt = cs.paddingTop, pb = cs.paddingBottom;
            if (opening) d.open = true;
            var h = opening ? b.scrollHeight : b.getBoundingClientRect().height;

            var shut = { height: '0px', paddingTop: '0px', paddingBottom: '0px', opacity: 0 };
            var full = { height: h + 'px', paddingTop: pt, paddingBottom: pb, opacity: 1 };

            b.style.overflow = 'hidden';
            /* fill: forwards holds the last frame. Without it the keyframes
               stop applying the moment the animation ends and the body
               renders at its natural height for a frame before open = false
               hides it, which reads as a flash. */
            var anim = b.animate(opening ? [shut, full] : [full, shut],
                                 { duration: reduce ? 0 : DUR, easing: EASE, fill: 'forwards' });
            b.ssAnim = anim;
            anim.onfinish = function () {
                /* close first, then release the held frame */
                if (!opening) d.open = false;
                b.ssAnim = null;
                anim.cancel();
                b.style.overflow = '';
            };
        }

        function show(idx) {
            steps.forEach(function (d, i) {
                if (i === idx) { if (!d.open) run(d, true); }
                else if (d.open) { run(d, false); }
            });
            remember(idx);
        }

        /* Which step is open, and the name= removal, are both settled by the
           inline script at the end of guide.php - before the panel paints.
           Repeating it here is what caused the jump it was meant to avoid. */
        steps.forEach(function (d) { d.removeAttribute('name'); });

        steps.forEach(function (d, i) {
            var sum = d.querySelector('summary');
            if (!sum) return;
            sum.addEventListener('click', function (e) {
                /* take the toggle off the browser so it can be animated */
                e.preventDefault();
                if (d.open) { run(d, false); remember(-1); }
                else { show(i); }
            });
        });
    }
    /* app.js loads in <head>, so the panel does not exist yet. */
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', guideBoot);
    else guideBoot();

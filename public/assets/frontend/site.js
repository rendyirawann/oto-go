/*
 * OtoGo public site behaviour. No dependencies.
 * Every effect degrades to a static, readable page without JS and is
 * skipped under prefers-reduced-motion.
 */
(function () {
    'use strict';

    var doc = document.documentElement;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    doc.classList.remove('no-js');
    doc.classList.add('js');
    if (reduce) doc.classList.add('reduce-motion');

    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

    var rupiah = new Intl.NumberFormat('id-ID');
    function formatRp(n) { return 'Rp ' + rupiah.format(n); }

    /* ---------------------------------------------------------- theme */
    function initTheme() {
        var btn = $('[data-theme-toggle]');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var next = doc.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            doc.setAttribute('data-theme', next);
            try { localStorage.setItem('otogo:site-theme', next); } catch (e) { /* storage off */ }
            btn.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
        });
        btn.setAttribute('aria-pressed', doc.getAttribute('data-theme') === 'dark' ? 'true' : 'false');
    }

    /* ------------------------------------------------------------ nav */
    function initNav() {
        var nav = $('[data-nav]');
        if (!nav) return;

        var onScroll = function () { nav.classList.toggle('is-scrolled', window.scrollY > 8); };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        var toggle = $('[data-nav-toggle]', nav);
        if (toggle) {
            toggle.addEventListener('click', function () {
                var open = !nav.classList.contains('is-open');
                nav.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            $$('.nav__links a', nav).forEach(function (a) {
                a.addEventListener('click', function () {
                    nav.classList.remove('is-open');
                    toggle.setAttribute('aria-expanded', 'false');
                });
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && nav.classList.contains('is-open')) {
                    nav.classList.remove('is-open');
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.focus();
                }
            });
        }
    }

    /* --------------------------------------------------------- reveal */
    function initReveal() {
        var items = $$('[data-reveal]');
        if (!items.length) return;

        if (reduce || !('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('is-in'); });
            return;
        }

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in');
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

        items.forEach(function (el) { io.observe(el); });
    }

    /* -------------------------------------------------------- rotator */
    function initRotator() {
        var rot = $('[data-rotator]');
        if (!rot) return;
        var words = $$('span', rot);
        if (words.length < 2) return;

        var i = 0;
        words[0].classList.add('is-active');
        if (reduce) return;

        setInterval(function () {
            if (document.hidden) return;
            var current = words[i];
            i = (i + 1) % words.length;
            var next = words[i];
            current.classList.remove('is-active');
            current.classList.add('is-leaving');
            next.classList.remove('is-leaving');
            next.classList.add('is-active');
            setTimeout(function () { current.classList.remove('is-leaving'); }, 700);
        }, 2400);
    }

    /* -------------------------------------------------------- counters */
    function initCounters() {
        var els = $$('[data-count]');
        if (!els.length || reduce || !('IntersectionObserver' in window)) return;

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var el = entry.target;
                io.unobserve(el);
                var target = parseInt(el.getAttribute('data-count'), 10) || 0;
                var start = null;
                var dur = 1100;
                var step = function (ts) {
                    if (start === null) start = ts;
                    var p = Math.min(1, (ts - start) / dur);
                    var eased = 1 - Math.pow(1 - p, 3);
                    el.textContent = String(Math.round(target * eased));
                    if (p < 1) requestAnimationFrame(step);
                };
                el.textContent = '0';
                requestAnimationFrame(step);
            });
        }, { threshold: 0.6 });

        els.forEach(function (el) { io.observe(el); });
    }

    /* ------------------------------------------------------- estimator */
    function initEstimator() {
        var root = $('[data-estimator]');
        var dataEl = $('#estimator-data');
        if (!root || !dataEl) return;

        var data;
        try { data = JSON.parse(dataEl.textContent); } catch (e) { return; }

        var tbody = $('[data-estimator-rows]', root);
        var title = $('[data-estimator-title]', root);
        var count = $('[data-estimator-count]', root);
        var state = { cat: data.default, module: 'all' };

        var names = {};
        data.categories.forEach(function (c) { names[c.code] = c.name; });

        function render(animate) {
            var rows = (data.prices[state.cat] || []).filter(function (r) {
                return state.module === 'all' || r.module === state.module;
            });

            var html = '';
            if (!rows.length) {
                html = '<tr><td colspan="4" class="muted">Belum ada estimasi untuk kombinasi ini.</td></tr>';
            }
            rows.forEach(function (r, idx) {
                var tag = r.addon ? ' <span class="tag tag--orange">add-on</span>' : '';
                html += '<tr' + (animate ? ' class="is-new" style="--d:' + Math.min(idx * 35, 420) + 'ms"' : '') + '>' +
                    '<td class="svc">' + escapeHtml(r.service) + tag + '<small>' + escapeHtml(r.group) + '</small></td>' +
                    '<td class="col-module"><span class="tag">' + escapeHtml(r.module_label) + '</span></td>' +
                    '<td class="col-time">' + (r.duration ? '± ' + r.duration + ' mnt' : '-') + '</td>' +
                    '<td class="amount">' + formatRp(r.price) + '</td>' +
                    '</tr>';
            });
            tbody.innerHTML = html;
            if (title) title.textContent = names[state.cat] || '';
            if (count) count.textContent = rows.length + ' layanan';
        }

        $$('[data-cat]', root).forEach(function (chip) {
            chip.addEventListener('click', function () {
                state.cat = chip.getAttribute('data-cat');
                $$('[data-cat]', root).forEach(function (c) { c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
                render(!reduce);
            });
        });

        $$('[data-module]', root).forEach(function (tab) {
            tab.addEventListener('click', function () {
                state.module = tab.getAttribute('data-module');
                $$('[data-module]', root).forEach(function (t) { t.setAttribute('aria-selected', t === tab ? 'true' : 'false'); });
                render(!reduce);
            });
        });

        render(false);
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
        });
    }

    /* ----------------------------------------------- modes: route map */
    function initModes() {
        var map = $('[data-map]');
        var modes = $$('[data-mode-step]');
        if (!map || !modes.length) return;

        var trip = $('[data-trip]', map);
        var caption = $('[data-map-caption]', map);
        var sub = $('[data-map-sub]', map);
        var length = 0;

        if (trip && trip.getTotalLength) {
            length = trip.getTotalLength();
            trip.style.strokeDasharray = length + ' ' + length;
            trip.style.strokeDashoffset = reduce ? '0' : String(length);
        }

        function setMode(step) {
            modes.forEach(function (m) { m.classList.toggle('is-active', m === step); });
            map.setAttribute('data-mode', step.getAttribute('data-mode-step'));
            if (caption) caption.textContent = step.getAttribute('data-caption') || '';
            if (sub) sub.textContent = step.getAttribute('data-sub') || '';
        }
        setMode(modes[0]);

        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) setMode(entry.target);
                });
            }, { rootMargin: '-45% 0px -45% 0px', threshold: 0 });
            modes.forEach(function (m) { io.observe(m); });
        }

        if (reduce || !length) return;

        // Draw the route proportionally to how far the section has scrolled.
        var section = map.closest('section') || map;
        var ticking = false;
        var update = function () {
            ticking = false;
            var rect = section.getBoundingClientRect();
            var vh = window.innerHeight || 800;
            var total = rect.height + vh * 0.4;
            var progressed = vh * 0.8 - rect.top;
            var p = Math.max(0, Math.min(1, progressed / total * 1.6));
            trip.style.strokeDashoffset = String(length * (1 - p));
        };
        window.addEventListener('scroll', function () {
            if (!ticking) { ticking = true; requestAnimationFrame(update); }
        }, { passive: true });
        update();
    }

    /* ------------------------------------------------- demo switches */
    function initSwitches() {
        $$('[data-switch]').forEach(function (sw) {
            sw.addEventListener('click', function () {
                var on = sw.getAttribute('aria-checked') !== 'true';
                sw.setAttribute('aria-checked', on ? 'true' : 'false');
            });
        });
    }

    /* ---------------------------------------------------- toc + share */
    function initToc() {
        var links = $$('[data-toc] a');
        if (!links.length || !('IntersectionObserver' in window)) return;
        var map = {};
        links.forEach(function (a) { map[a.getAttribute('href').slice(1)] = a; });

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                links.forEach(function (a) { a.classList.remove('is-active'); });
                var link = map[entry.target.id];
                if (link) link.classList.add('is-active');
            });
        }, { rootMargin: '0px 0px -70% 0px', threshold: 0 });

        Object.keys(map).forEach(function (id) {
            var h = document.getElementById(id);
            if (h) io.observe(h);
        });
    }

    function initShare() {
        $$('[data-copy-link]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-copy-link') || window.location.href;
                var label = btn.querySelector('span') || btn;
                var original = label.textContent;
                var done = function (text) {
                    label.textContent = text;
                    setTimeout(function () { label.textContent = original; }, 1800);
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(function () { done('Tautan disalin'); }, function () { done('Gagal menyalin'); });
                } else {
                    done('Salin dari bilah alamat');
                }
            });
        });
    }

    function boot() {
        initTheme();
        initNav();
        initReveal();
        initRotator();
        initCounters();
        initEstimator();
        initModes();
        initSwitches();
        initToc();
        initShare();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();

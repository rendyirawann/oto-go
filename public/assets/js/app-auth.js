/* ==========================================================================
   Authentication screens: entry preloader, animated topology background,
   saved-account picker, sign-in submit flow and the hand-off into the
   dashboard. Vanilla JS, no build step.
   ========================================================================== */

(function (window, document) {
    'use strict';

    var PRELOAD_MS = 1200;

    /** localStorage key holding the identifiers used on this device. */
    var ACCOUNTS_KEY = 'app:accounts';
    var ACCOUNTS_MAX = 4;

    var P5_URL = 'https://cdn.jsdelivr.net/npm/p5@1.9.0/lib/p5.min.js';
    var VANTA_URL = 'https://cdn.jsdelivr.net/npm/vanta@0.5.24/dist/vanta.topology.min.js';

    var reduceMotion = false;

    try {
        reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (e) {
        reduceMotion = false;
    }

    function isDark() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark';
    }

    /* ----------------------------------------------------------------------
       Preloader — always visible for exactly PRELOAD_MS, then faded out.
       ---------------------------------------------------------------------- */

    function initPreloader() {
        var el = document.getElementById('auth-preloader');

        if (!el) {
            return;
        }

        var start = window.__authPreloadStart || Date.now();
        var remaining = Math.max(0, PRELOAD_MS - (Date.now() - start));

        window.setTimeout(function () {
            el.classList.add('is-done');
            window.setTimeout(function () {
                if (el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, reduceMotion ? 0 : 520);
        }, reduceMotion ? 0 : remaining);
    }

    /* ----------------------------------------------------------------------
       Animated topology background (Vanta + p5)

       Loaded from a CDN after the preloader, never blocking first paint. Any
       failure — offline, blocked CDN, CSP — leaves the still image background
       in place, so the page is never worse off for trying.
       ---------------------------------------------------------------------- */

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var tag = document.createElement('script');
            tag.src = src;
            tag.async = true;
            tag.crossOrigin = 'anonymous';
            tag.onload = resolve;
            tag.onerror = function () {
                reject(new Error('failed to load ' + src));
            };
            document.head.appendChild(tag);
        });
    }

    /**
     * Drift the background against the pointer.
     *
     * Vanta's topology effect ignores the cursor entirely — its draw loop
     * only walks particles through a flow field baked once at setup, and it
     * never reads mouseX/mouseY. So the response to the cursor is ours: the
     * canvas is rendered slightly larger than the viewport and nudged the
     * opposite way to the pointer, which reads as parallax depth.
     */
    function initParallax(host) {
        if (reduceMotion) {
            return;
        }

        var MAX = 26;   // px of travel at the very edge of the viewport
        var EASE = 0.08;

        var targetX = 0;
        var targetY = 0;
        var currentX = 0;
        var currentY = 0;
        var ticking = false;

        function step() {
            currentX += (targetX - currentX) * EASE;
            currentY += (targetY - currentY) * EASE;

            host.style.transform = 'translate3d(' +
                (currentX * -MAX).toFixed(2) + 'px,' +
                (currentY * -MAX).toFixed(2) + 'px,0)';

            if (Math.abs(targetX - currentX) > 0.001 || Math.abs(targetY - currentY) > 0.001) {
                window.requestAnimationFrame(step);
            } else {
                ticking = false;
            }
        }

        window.addEventListener('mousemove', function (event) {
            targetX = (event.clientX / Math.max(1, window.innerWidth)) * 2 - 1;
            targetY = (event.clientY / Math.max(1, window.innerHeight)) * 2 - 1;

            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(step);
            }
        }, { passive: true });
    }

    function initVanta() {
        var host = document.getElementById('auth-vanta');

        if (!host || reduceMotion) {
            return;
        }

        // A phone repainting a full-screen canvas costs battery for a
        // background people look at for seconds; the still image is enough.
        if (window.innerWidth < 768) {
            return;
        }

        var dark = isDark();
        var color = host.getAttribute(dark ? 'data-color-dark' : 'data-color-light') || '#6366f1';
        var background = host.getAttribute(dark ? 'data-bg-dark' : 'data-bg-light') || '#eef2f8';

        // Downloading starts immediately (p5 is ~1MB) but the canvas is only
        // revealed once the preloader has cleared, so the two never overlap.
        var revealAt = (window.__authPreloadStart || Date.now()) + PRELOAD_MS + 120;

        loadScript(P5_URL)
            .then(function () {
                return loadScript(VANTA_URL);
            })
            .then(function () {
                if (!window.VANTA || !window.VANTA.TOPOLOGY || !window.p5) {
                    return;
                }

                window.__vantaEffect = window.VANTA.TOPOLOGY({
                    el: host,
                    p5: window.p5,
                    mouseControls: false,
                    touchControls: false,
                    gyroControls: false,
                    minHeight: 200,
                    minWidth: 200,
                    scale: 1,
                    scaleMobile: 1,
                    color: color,
                    backgroundColor: background
                });

                window.setTimeout(function () {
                    host.classList.add('is-on');
                    initParallax(host);
                }, Math.max(0, revealAt - Date.now()));
            })
            .catch(function () {
                /* Still background stays — nothing to do. */
            });
    }

    /* ----------------------------------------------------------------------
       Saved accounts

       Only the identifier typed into the form is stored, and only on this
       device. Passwords are never written here — those belong to the
       browser's own credential store (see rememberCredentials below).
       ---------------------------------------------------------------------- */

    function readAccounts() {
        try {
            var raw = window.localStorage.getItem(ACCOUNTS_KEY);
            var list = raw ? JSON.parse(raw) : [];

            return Array.isArray(list) ? list.filter(function (item) {
                return item && typeof item.id === 'string' && item.id.length < 200;
            }) : [];
        } catch (e) {
            return [];
        }
    }

    function writeAccounts(list) {
        try {
            window.localStorage.setItem(ACCOUNTS_KEY, JSON.stringify(list.slice(0, ACCOUNTS_MAX)));
        } catch (e) {
            /* Private mode or storage full — the picker just stays empty. */
        }
    }

    function rememberAccount(identifier) {
        var id = String(identifier || '').trim();

        if (!id) {
            return;
        }

        var list = readAccounts().filter(function (item) {
            return item.id.toLowerCase() !== id.toLowerCase();
        });

        list.unshift({ id: id, at: Date.now() });
        writeAccounts(list);
    }

    function initialsFor(identifier) {
        var name = String(identifier).split('@')[0];
        var parts = name.split(/[.\-_\s]+/).filter(Boolean);

        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }

        return name.slice(0, 2).toUpperCase() || '?';
    }

    function initAccounts() {
        var wrap = document.getElementById('auth-accounts');
        var input = document.getElementById('auth-identifier');

        if (!wrap || !input) {
            return;
        }

        var list = wrap.querySelector('[data-accounts-list]');
        var clearBtn = wrap.querySelector('[data-accounts-clear]');

        function render() {
            var accounts = readAccounts();

            list.textContent = '';
            wrap.hidden = accounts.length === 0;

            accounts.forEach(function (account) {
                var li = document.createElement('li');

                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'auth-account';
                button.setAttribute('aria-label', 'Masuk sebagai ' + account.id);

                var mark = document.createElement('span');
                mark.className = 'auth-account__mark';
                mark.setAttribute('aria-hidden', 'true');
                mark.textContent = initialsFor(account.id);

                var name = document.createElement('span');
                name.className = 'auth-account__name';
                name.textContent = account.id;

                button.appendChild(mark);
                button.appendChild(name);

                button.addEventListener('click', function () {
                    input.value = account.id;
                    var password = document.getElementById('auth-password');
                    if (password) {
                        password.focus();
                    }
                });

                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'auth-account__remove';
                remove.setAttribute('aria-label', 'Lupakan akun ' + account.id);
                remove.textContent = '×';

                remove.addEventListener('click', function (event) {
                    event.stopPropagation();
                    writeAccounts(readAccounts().filter(function (item) {
                        return item.id !== account.id;
                    }));
                    render();
                });

                li.appendChild(button);
                li.appendChild(remove);
                list.appendChild(li);
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                writeAccounts([]);
                render();
            });
        }

        render();

        // Deliberately no navigator.credentials.get() here. It pops Chrome's
        // "Sign in as" mediation dialog over the page on load, which is far
        // more intrusive than the browser's own autofill dropdown. Saved
        // credentials are surfaced by that native dropdown instead, which the
        // autocomplete attributes on the form already drive.
    }

    /**
     * Hand the credentials to the browser's password manager so it offers to
     * save them. A fetch-based sign-in produces no navigation, which is what
     * browsers normally use as the "save password?" trigger.
     */
    function rememberCredentials(form) {
        if (!navigator.credentials || !window.PasswordCredential) {
            return Promise.resolve();
        }

        try {
            var credential = new window.PasswordCredential({
                id: form.querySelector('#auth-identifier').value,
                password: form.querySelector('#auth-password').value,
                name: form.querySelector('#auth-identifier').value
            });

            return navigator.credentials.store(credential).catch(function () {});
        } catch (e) {
            return Promise.resolve();
        }
    }

    /* ----------------------------------------------------------------------
       Password visibility
       ---------------------------------------------------------------------- */

    function initPasswordToggle() {
        var toggles = document.querySelectorAll('[data-auth-toggle-password]');

        for (var i = 0; i < toggles.length; i++) {
            (function (button) {
                button.addEventListener('click', function () {
                    var input = document.getElementById(button.getAttribute('data-auth-toggle-password'));

                    if (!input) {
                        return;
                    }

                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                    button.setAttribute('aria-pressed', show ? 'true' : 'false');

                    var eye = button.querySelector('[data-eye]');
                    var eyeOff = button.querySelector('[data-eye-off]');

                    if (eye && eyeOff) {
                        eye.style.display = show ? 'none' : '';
                        eyeOff.style.display = show ? '' : 'none';
                    }

                    input.focus();
                });
            })(toggles[i]);
        }
    }

    /* ----------------------------------------------------------------------
       Sign-in progress overlay
       ---------------------------------------------------------------------- */

    function progressOverlay() {
        var el = document.getElementById('auth-progress');

        if (!el) {
            return null;
        }

        var stepEl = el.querySelector('[data-progress-step]');
        var dots = el.querySelectorAll('[data-progress-dots] i');
        var timers = [];

        return {
            run: function (steps, onDone) {
                el.classList.add('is-on');

                var perStep = reduceMotion ? 60 : 420;

                steps.forEach(function (text, index) {
                    timers.push(window.setTimeout(function () {
                        if (stepEl) {
                            stepEl.textContent = text;
                        }

                        for (var d = 0; d < dots.length; d++) {
                            dots[d].classList.toggle('is-active', d <= index);
                        }
                    }, index * perStep));
                });

                timers.push(window.setTimeout(onDone, steps.length * perStep));
            },
            stop: function () {
                timers.forEach(window.clearTimeout);
                timers = [];
                el.classList.remove('is-on');
            }
        };
    }

    /* ----------------------------------------------------------------------
       Sign-in form
       ---------------------------------------------------------------------- */

    function initSignIn() {
        var form = document.getElementById('auth-signin-form');

        if (!form) {
            return;
        }

        var button = form.querySelector('[data-auth-submit]');
        var alertBox = document.getElementById('auth-alert');
        var overlay = progressOverlay();
        var busy = false;

        function setBusy(state) {
            busy = state;

            if (!button) {
                return;
            }

            button.disabled = state;
            button.classList.toggle('is-busy', state);

            var label = button.querySelector('[data-label]');

            if (label) {
                label.textContent = state ? 'Memverifikasi...' : button.getAttribute('data-label-idle');
            }
        }

        function showError(message) {
            if (!alertBox) {
                window.alert(message);

                return;
            }

            alertBox.querySelector('[data-alert-text]').textContent = message;
            alertBox.hidden = false;
            alertBox.scrollIntoView({ block: 'nearest', behavior: reduceMotion ? 'auto' : 'smooth' });
        }

        function clearError() {
            if (alertBox) {
                alertBox.hidden = true;
            }
        }

        function countdown(seconds) {
            var remaining = parseInt(seconds, 10);

            if (isNaN(remaining) || remaining < 1) {
                remaining = 60;
            }

            if (!button) {
                return;
            }

            var idle = button.getAttribute('data-label-idle');
            var label = button.querySelector('[data-label]');
            button.disabled = true;

            var tick = window.setInterval(function () {
                if (label) {
                    label.textContent = 'Coba lagi dalam ' + remaining + ' detik';
                }

                if (remaining <= 0) {
                    window.clearInterval(tick);
                    button.disabled = false;

                    if (label) {
                        label.textContent = idle;
                    }
                }

                remaining--;
            }, 1000);
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (busy) {
                return;
            }

            clearError();
            setBusy(true);

            var body = new FormData(form);
            var token = form.querySelector('input[name="_token"]');

            window.fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token ? token.value : ''
                },
                body: body
            })
                .then(function (response) {
                    return response.json()
                        .catch(function () {
                            return {};
                        })
                        .then(function (data) {
                            return { ok: response.ok, status: response.status, data: data };
                        });
                })
                .then(function (result) {
                    if (!result.ok) {
                        setBusy(false);

                        var errors = result.data.errors || {};

                        if (result.status === 429) {
                            var seconds = (errors.seconds && errors.seconds[0]) || 60;

                            if (errors.email && errors.email[0]) {
                                var match = String(errors.email[0]).match(/(\d+)/);

                                if (match) {
                                    seconds = match[1];
                                }
                            }

                            showError('Terlalu banyak percobaan login. Silakan tunggu sebentar.');
                            countdown(seconds);

                            return;
                        }

                        var message = (errors.email && errors.email[0])
                            || (errors.password && errors.password[0])
                            || result.data.message
                            || 'Login gagal. Periksa kembali kredensial Anda.';

                        showError(message);

                        return;
                    }

                    // Authenticated. Remember the identifier locally and let
                    // the browser offer to save the password, then play the
                    // hand-off and load the dashboard with ?welcome=1 so it
                    // renders the reveal animation.
                    var identifier = form.querySelector('#auth-identifier');
                    rememberAccount(identifier ? identifier.value : '');

                    var target = result.data.redirect || form.getAttribute('data-redirect') || '/';
                    var url = target + (target.indexOf('?') === -1 ? '?' : '&') + 'welcome=1';

                    var finish = function () {
                        window.location.assign(url);
                    };

                    rememberCredentials(form).then(function () {
                        if (overlay) {
                            overlay.run(
                                ['Kredensial terverifikasi', 'Menyiapkan sesi aman', 'Membuka dashboard'],
                                finish
                            );
                        } else {
                            finish();
                        }
                    });
                })
                .catch(function () {
                    setBusy(false);

                    if (overlay) {
                        overlay.stop();
                    }

                    showError('Tidak dapat terhubung ke server. Periksa koneksi Anda.');
                });
        });
    }

    function boot() {
        initPreloader();
        initPasswordToggle();
        initAccounts();
        initSignIn();

        // Kicked off straight away; it reveals itself after the preloader.
        initVanta();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(window, document);

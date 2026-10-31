/* ==========================================================================
   App shell behaviour: the post-login reveal, the sidebar drawer and the
   generic confirm-before-submit helper.

   Vanilla JS, no dependencies, safe to load in <head> (it defers its own
   DOM work until the elements it needs exist).
   ========================================================================== */

(function (window, document) {
    'use strict';

    var TAU = Math.PI * 2;

    var reduceMotion = false;

    try {
        reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (e) {
        reduceMotion = false;
    }

    /* ======================================================================
       Post-login reveal

       The overlay is rendered server-side so there is never a flash of the
       dashboard before it covers the screen. From there it runs in stages:

         1. tiles     the solid ground breaks apart and the pieces fly off
         2. swirl     each piece leaves behind a circle that spirals inward
         3. cube      the circles settle onto the edges of a rotating cube
         4. collapse  the cube spins up and winds down into a single point

       Everything after stage 1 is drawn on a canvas, which keeps ~90 moving
       objects on one composited layer instead of 90 animated DOM nodes.
       ====================================================================== */

    var TILE_DUR = 460;      // how long one tile takes to fly away
    var TILE_STAGGER = 34;   // ripple delay per ring out from the centre
    var SWIRL_DUR = 1250;    // circle spiralling in towards the cube
    var SWIRL_SPREAD = 260;  // extra per-particle delay, so the vortex builds
    var CUBE_HOLD = 620;     // assembled cube rotating in place
    var COLLAPSE_DUR = 780;  // spin up and shrink to nothing
    var CLEAR_DUR = 340;     // overlay fade that finally shows the dashboard
    var SWIRL_TURNS = 1.15;  // extra revolutions while spiralling in

    function easeInOutCubic(t) {
        return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }

    function easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    function easeInCubic(t) {
        return t * t * t;
    }

    function clamp01(value) {
        return value < 0 ? 0 : (value > 1 ? 1 : value);
    }

    /** Shortest signed angular distance from a to b. */
    function angleDelta(a, b) {
        var d = (b - a) % TAU;

        if (d > Math.PI) {
            d -= TAU;
        } else if (d < -Math.PI) {
            d += TAU;
        }

        return d;
    }

    /** Unit-cube corners and the 12 edges connecting them. */
    var CUBE_VERTICES = [
        [-1, -1, -1], [1, -1, -1], [1, 1, -1], [-1, 1, -1],
        [-1, -1, 1], [1, -1, 1], [1, 1, 1], [-1, 1, 1]
    ];

    var CUBE_EDGES = [
        [0, 1], [1, 2], [2, 3], [3, 0],
        [4, 5], [5, 6], [6, 7], [7, 4],
        [0, 4], [1, 5], [2, 6], [3, 7]
    ];

    /**
     * Points distributed over the cube's wireframe: every corner plus a few
     * evenly spaced points along each edge.
     */
    function buildCubePoints(perEdge) {
        var points = [];
        var i;

        for (i = 0; i < CUBE_VERTICES.length; i++) {
            points.push({
                x: CUBE_VERTICES[i][0],
                y: CUBE_VERTICES[i][1],
                z: CUBE_VERTICES[i][2],
                corner: true
            });
        }

        for (i = 0; i < CUBE_EDGES.length; i++) {
            var a = CUBE_VERTICES[CUBE_EDGES[i][0]];
            var b = CUBE_VERTICES[CUBE_EDGES[i][1]];

            for (var s = 1; s <= perEdge; s++) {
                var t = s / (perEdge + 1);
                points.push({
                    x: a[0] + (b[0] - a[0]) * t,
                    y: a[1] + (b[1] - a[1]) * t,
                    z: a[2] + (b[2] - a[2]) * t,
                    corner: false
                });
            }
        }

        return points;
    }

    /** Rotate around Y, tilt around X, then apply a light perspective. */
    function project(point, angle, scale, cx, cy) {
        var cosY = Math.cos(angle);
        var sinY = Math.sin(angle);

        var x = point.x * cosY - point.z * sinY;
        var z = point.x * sinY + point.z * cosY;

        var tilt = -0.52;
        var cosX = Math.cos(tilt);
        var sinX = Math.sin(tilt);

        var y2 = point.y * cosX - z * sinX;
        var z2 = point.y * sinX + z * cosX;

        var depth = 1 / (1 + z2 * 0.17);

        return {
            x: cx + x * scale * depth,
            y: cy + y2 * scale * depth,
            depth: depth
        };
    }

    function mixColor(a, b, t) {
        return [
            Math.round(a[0] + (b[0] - a[0]) * t),
            Math.round(a[1] + (b[1] - a[1]) * t),
            Math.round(a[2] + (b[2] - a[2]) * t)
        ];
    }

    function parseHex(hex, fallback) {
        var value = String(hex || '').trim().replace('#', '');

        if (value.length === 3) {
            value = value[0] + value[0] + value[1] + value[1] + value[2] + value[2];
        }

        if (!/^[0-9a-f]{6}$/i.test(value)) {
            return fallback;
        }

        return [
            parseInt(value.slice(0, 2), 16),
            parseInt(value.slice(2, 4), 16),
            parseInt(value.slice(4, 6), 16)
        ];
    }

    /** Stage 1: break the overlay into tiles and record where each one was. */
    function buildTiles(overlay, bg) {
        var vw = Math.max(document.documentElement.clientWidth, 320);
        var vh = Math.max(document.documentElement.clientHeight, 320);

        var cols = Math.max(4, Math.min(12, Math.round(vw / 150)));
        var rows = Math.max(3, Math.min(10, Math.round(vh / 150)));

        var grid = document.createElement('div');
        grid.className = 'app-reveal__grid';
        grid.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
        grid.style.gridTemplateRows = 'repeat(' + rows + ', 1fr)';

        var centreCol = (cols - 1) / 2;
        var centreRow = (rows - 1) / 2;
        var tiles = [];
        var maxDelay = 0;

        for (var i = 0; i < cols * rows; i++) {
            var col = i % cols;
            var row = Math.floor(i / cols);

            // Ripple out from the centre, with jitter so it reads as
            // shattering rather than a tidy sweep.
            var distance = Math.sqrt(
                Math.pow(col - centreCol, 2) + Math.pow(row - centreRow, 2)
            );
            var delay = Math.round(distance * TILE_STAGGER + Math.random() * TILE_STAGGER * 1.5);
            maxDelay = Math.max(maxDelay, delay);

            var dirX = (col - centreCol) / Math.max(1, centreCol);
            var dirY = (row - centreRow) / Math.max(1, centreRow);

            var tile = document.createElement('div');
            tile.className = 'app-reveal__tile';
            tile.style.background = bg;
            tile.style.animationDelay = delay + 'ms';
            tile.style.setProperty('--app-tile-dur', TILE_DUR + 'ms');
            tile.style.setProperty('--app-tile-x', Math.round(dirX * 90) + 'px');
            tile.style.setProperty('--app-tile-y', Math.round(dirY * 90 - 30) + 'px');
            tile.style.setProperty('--app-tile-rot', (Math.random() * 26 - 13).toFixed(1) + 'deg');

            grid.appendChild(tile);

            tiles.push({
                x: (col + 0.5) / cols * vw,
                y: (row + 0.5) / rows * vh,
                delay: delay
            });
        }

        overlay.appendChild(grid);

        return { grid: grid, tiles: tiles, maxDelay: maxDelay, vw: vw, vh: vh };
    }

    /** Stages 2-4, drawn on a canvas layered over the (now empty) overlay. */
    function runParticles(overlay, stage, colors) {
        var canvas = document.createElement('canvas');
        canvas.className = 'app-reveal__canvas';
        canvas.setAttribute('aria-hidden', 'true');
        overlay.appendChild(canvas);

        var context = canvas.getContext('2d');
        var dpr = Math.min(window.devicePixelRatio || 1, 2);

        canvas.width = Math.round(stage.vw * dpr);
        canvas.height = Math.round(stage.vh * dpr);
        context.scale(dpr, dpr);

        var cx = stage.vw / 2;
        var cy = stage.vh / 2;
        var cubeScale = Math.min(stage.vw, stage.vh) * 0.115;

        var points = buildCubePoints(6);
        var particles = [];
        var lastLanding = 0;

        for (var i = 0; i < points.length; i++) {
            // Each circle is born where a tile used to be, so the debris
            // visibly turns into the swarm.
            var source = stage.tiles[i % stage.tiles.length];
            var dx = source.x - cx;
            var dy = source.y - cy;

            // Spread the departures so the vortex forms gradually
            // instead of every circle moving in lockstep.
            var spawnAt = source.delay + TILE_DUR * 0.3 + Math.random() * SWIRL_SPREAD;
            lastLanding = Math.max(lastLanding, spawnAt + SWIRL_DUR);

            particles.push({
                cube: points[i],
                spawnAt: spawnAt,
                r0: Math.sqrt(dx * dx + dy * dy) || 1,
                a0: Math.atan2(dy, dx),
                size0: 2 + Math.random() * 2.5,
                size1: points[i].corner ? 3.4 : 2.2,
                tint: Math.random()
            });
        }

        var cubeStart = lastLanding;
        var cubeEnd = cubeStart + CUBE_HOLD;
        var collapseEnd = cubeEnd + COLLAPSE_DUR;

        var c1 = parseHex(colors.p1, [99, 102, 241]);
        var c2 = parseHex(colors.p2, [217, 70, 239]);

        var startAngle = 0.6;
        var start = null;
        var finished = false;

        function cubeAngle(t) {
            if (t <= cubeStart) {
                return startAngle;
            }

            if (t <= cubeEnd) {
                // Slow, steady turn while the cube holds together.
                return startAngle + (t - cubeStart) / 1000 * 1.5;
            }

            // Spin up as it winds down into the centre.
            var held = (cubeEnd - cubeStart) / 1000 * 1.5;
            var p = clamp01((t - cubeEnd) / COLLAPSE_DUR);

            return startAngle + held + easeInCubic(p) * 7.5;
        }

        function cubeSize(t) {
            if (t <= cubeEnd) {
                return cubeScale;
            }

            return cubeScale * (1 - easeInCubic(clamp01((t - cubeEnd) / COLLAPSE_DUR)));
        }

        function frame(now) {
            if (start === null) {
                start = now;
            }

            var t = now - start;
            var angle = cubeAngle(t);
            var scale = cubeSize(t);
            var globalFade = t <= cubeEnd
                ? 1
                : 1 - clamp01((t - cubeEnd - COLLAPSE_DUR * 0.45) / (COLLAPSE_DUR * 0.55));

            context.clearRect(0, 0, stage.vw, stage.vh);

            var projected = [];
            var p;
            var i;

            for (i = 0; i < particles.length; i++) {
                p = particles[i];

                if (t < p.spawnAt) {
                    projected.push(null);
                    continue;
                }

                var target = project(p.cube, angle, scale, cx, cy);
                var local = t - p.spawnAt;
                var pos;
                var radius;
                var alpha = globalFade;

                if (local < SWIRL_DUR) {
                    var e = easeInOutCubic(local / SWIRL_DUR);

                    // Polar interpolation with extra turns: a vortex rather
                    // than a straight line into the centre.
                    var tdx = target.x - cx;
                    var tdy = target.y - cy;
                    var r1 = Math.sqrt(tdx * tdx + tdy * tdy);
                    var a1 = Math.atan2(tdy, tdx);

                    var radiusNow = p.r0 + (r1 - p.r0) * e;
                    var angleNow = p.a0 + (angleDelta(p.a0, a1) + SWIRL_TURNS * TAU) * e;

                    pos = {
                        x: cx + Math.cos(angleNow) * radiusNow,
                        y: cy + Math.sin(angleNow) * radiusNow
                    };
                    radius = p.size0 + (p.size1 - p.size0) * e;
                    alpha *= easeOutCubic(clamp01(local / 160));
                } else {
                    pos = target;
                    radius = p.size1 * Math.max(0.35, target.depth);
                }

                projected.push({ pos: pos, alpha: alpha, corner: p.cube.corner });

                var rgb = mixColor(c1, c2, p.tint);

                context.beginPath();
                context.arc(pos.x, pos.y, Math.max(0.4, radius), 0, TAU);
                context.fillStyle = 'rgba(' + rgb[0] + ',' + rgb[1] + ',' + rgb[2] + ',' + alpha.toFixed(3) + ')';
                context.fill();
            }

            // Faint wireframe once the corners are in place, so the shape
            // reads as a cube and not just a cloud of dots.
            if (t > cubeStart - 160) {
                var edgeAlpha = clamp01((t - (cubeStart - 160)) / 240) * 0.7 * globalFade;

                if (edgeAlpha > 0.01) {
                    context.lineWidth = 1.2;
                    context.strokeStyle = 'rgba(' + c1[0] + ',' + c1[1] + ',' + c1[2] + ',' + edgeAlpha.toFixed(3) + ')';
                    context.beginPath();

                    for (i = 0; i < CUBE_EDGES.length; i++) {
                        var from = projected[CUBE_EDGES[i][0]];
                        var to = projected[CUBE_EDGES[i][1]];

                        if (from && to) {
                            context.moveTo(from.pos.x, from.pos.y);
                            context.lineTo(to.pos.x, to.pos.y);
                        }
                    }

                    context.stroke();
                }
            }

            if (t < collapseEnd) {
                window.requestAnimationFrame(frame);

                return;
            }

            if (!finished) {
                finished = true;
                clearOverlay(overlay);
            }
        }

        window.requestAnimationFrame(frame);

        // Safety net: if rAF is starved (background tab), clear up anyway.
        window.setTimeout(function () {
            if (!finished) {
                finished = true;
                clearOverlay(overlay);
            }
        }, collapseEnd + 1200);
    }

    function removeOverlay(overlay) {
        if (overlay && overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
        }
    }

    /** Fade the opaque backing away, which is what reveals the dashboard. */
    function clearOverlay(overlay) {
        overlay.classList.add('is-clearing');

        window.setTimeout(function () {
            removeOverlay(overlay);
        }, CLEAR_DUR + 60);
    }

    function playReveal(overlay) {
        var styles = window.getComputedStyle(overlay);
        var bg = styles.getPropertyValue('--app-reveal-bg').trim() || '#eef2f8';

        var colors = {
            p1: styles.getPropertyValue('--app-reveal-p1').trim() || '#6366f1',
            p2: styles.getPropertyValue('--app-reveal-p2').trim() || '#d946ef'
        };

        // Cancel the no-JS failsafe now that we are driving the animation.
        overlay.style.animation = 'none';

        // The backing stays opaque: the dashboard is not uncovered
        // until runParticles() finishes and fades the overlay out.
        var stage = buildTiles(overlay, bg);

        runParticles(overlay, stage, colors);

        // The empty grid would keep a full-screen layer alive for nothing.
        window.setTimeout(function () {
            if (stage.grid.parentNode) {
                stage.grid.parentNode.removeChild(stage.grid);
            }
        }, stage.maxDelay + TILE_DUR + 60);
    }

    function initReveal() {
        var overlay = document.getElementById('app-reveal');

        if (!overlay) {
            return;
        }

        // Drop ?welcome=1 so a refresh or a shared link does not replay it.
        try {
            var url = new URL(window.location.href);

            if (url.searchParams.has('welcome')) {
                url.searchParams.delete('welcome');
                window.history.replaceState({}, '', url.pathname + url.search + url.hash);
            }
        } catch (e) {
            /* URL API unavailable — the animation still runs. */
        }

        if (reduceMotion) {
            overlay.style.transition = 'opacity 200ms linear';
            overlay.style.opacity = '0';
            window.setTimeout(function () {
                removeOverlay(overlay);
            }, 240);

            return;
        }

        // Let the browser paint one frame of the solid overlay first.
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                playReveal(overlay);
            });
        });
    }

    /* ======================================================================
       Sidebar drawer
       ====================================================================== */

    function initSidebar() {
        var sidebar = document.getElementById('kt_app_sidebar');
        var overlay = document.getElementById('kt_sidebar_overlay');

        if (!sidebar) {
            return;
        }

        function setOpen(open) {
            sidebar.classList.toggle('active', open);

            if (overlay) {
                overlay.classList.toggle('active', open);
            }

            document.body.classList.toggle('app-sidebar-open', open);
            sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');

            var toggles = document.querySelectorAll('[data-app-sidebar-toggle]');

            for (var i = 0; i < toggles.length; i++) {
                toggles[i].setAttribute('aria-expanded', open ? 'true' : 'false');
            }
        }

        var toggles = document.querySelectorAll('[data-app-sidebar-toggle]');

        for (var i = 0; i < toggles.length; i++) {
            toggles[i].addEventListener('click', function (event) {
                event.preventDefault();
                setOpen(!sidebar.classList.contains('active'));
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function () {
                setOpen(false);
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && sidebar.classList.contains('active')) {
                setOpen(false);
            }
        });

        // Following a link inside the drawer should close it.
        sidebar.addEventListener('click', function (event) {
            var link = event.target.closest ? event.target.closest('a[href]') : null;

            if (link && link.getAttribute('href').charAt(0) !== '#') {
                setOpen(false);
            }
        });

        setOpen(false);
    }

    /* ======================================================================
       Confirm before submit

       Any control carrying data-confirm asks first and only then submits its
       form. SweetAlert is used when the theme bundle is present, with the
       browser's own dialog as a fallback.
       ====================================================================== */

    function initConfirm() {
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest ? event.target.closest('[data-confirm]') : null;

            if (!trigger || trigger.dataset.confirmBusy === '1') {
                return;
            }

            var form = trigger.closest('form');

            if (!form) {
                return;
            }

            event.preventDefault();

            var submit = function () {
                trigger.dataset.confirmBusy = '1';
                form.submit();
            };

            var title = trigger.getAttribute('data-confirm-title') || 'Konfirmasi';
            var text = trigger.getAttribute('data-confirm') || 'Lanjutkan tindakan ini?';
            var okLabel = trigger.getAttribute('data-confirm-ok') || 'Ya, lanjutkan';
            var cancelLabel = trigger.getAttribute('data-confirm-cancel') || 'Batal';
            var icon = trigger.getAttribute('data-confirm-icon') || 'question';

            if (!window.Swal) {
                if (window.confirm(text)) {
                    submit();
                }

                return;
            }

            window.Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonText: okLabel,
                cancelButtonText: cancelLabel,
                reverseButtons: true,
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-light'
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    submit();
                }
            });
        });
    }

    function boot() {
        initReveal();
        initSidebar();
        initConfirm();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(window, document);

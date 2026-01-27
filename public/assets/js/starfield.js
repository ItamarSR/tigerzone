/**
 * Starfield / aglomerado de estrelas (canvas) – leve e sem dependências.
 * Respeita prefers-reduced-motion.
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    function clamp(n, a, b) { return Math.max(a, Math.min(b, n)); }

    function rand(min, max) { return min + Math.random() * (max - min); }

    // Box–Muller transform para distribuição normal
    function randn() {
        var u = 0, v = 0;
        while (u === 0) u = Math.random();
        while (v === 0) v = Math.random();
        return Math.sqrt(-2.0 * Math.log(u)) * Math.cos(2.0 * Math.PI * v);
    }

    function pick(arr) { return arr[(Math.random() * arr.length) | 0]; }

    function createNebula(width, height, dpr) {
        var c = document.createElement('canvas');
        c.width = Math.max(1, Math.floor(width * dpr));
        c.height = Math.max(1, Math.floor(height * dpr));
        var ctx = c.getContext('2d');
        if (!ctx) return null;
        ctx.scale(dpr, dpr);

        // Base escura
        var bg = ctx.createLinearGradient(0, 0, 0, height);
        bg.addColorStop(0, 'rgba(10, 12, 24, 0.85)');
        bg.addColorStop(0.5, 'rgba(6, 8, 18, 0.88)');
        bg.addColorStop(1, 'rgba(3, 4, 10, 0.92)');
        ctx.fillStyle = bg;
        ctx.fillRect(0, 0, width, height);

        // “Névoa” com blobs suaves
        var blobs = clamp(Math.floor((width * height) / 180000), 6, 18);
        var colors = [
            'rgba(232,185,35,0.08)',  // dourado
            'rgba(196,30,58,0.07)',   // vermelho
            'rgba(109,40,217,0.07)',  // roxo
            'rgba(56,189,248,0.06)'   // azul
        ];

        for (var i = 0; i < blobs; i++) {
            var x = rand(0, width);
            var y = rand(0, height);
            var r = rand(Math.min(width, height) * 0.18, Math.min(width, height) * 0.42);
            var g = ctx.createRadialGradient(x, y, 0, x, y, r);
            var col = pick(colors);
            g.addColorStop(0, col);
            g.addColorStop(1, 'rgba(0,0,0,0)');
            ctx.fillStyle = g;
            ctx.fillRect(x - r, y - r, r * 2, r * 2);
        }

        return c;
    }

    function init(canvas, config) {
        var ctx = canvas.getContext('2d', { alpha: true });
        if (!ctx) return;

        var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
        var dpr = clamp(window.devicePixelRatio || 1, 1, 2);

        var width = 0;
        var height = 0;
        var stars = [];
        var nebula = null;
        var raf = 0;
        var last = 0;

        var centerBias = clamp(config.centerBias ?? 0.72, 0.0, 0.95); // % de estrelas no “aglomerado”
        var speed = clamp(config.speed ?? 10, 0, 40); // px/s base
        var twinkle = clamp(config.twinkle ?? 0.55, 0, 1);

        function resize() {
            width = canvas.clientWidth || window.innerWidth;
            height = canvas.clientHeight || window.innerHeight;

            canvas.width = Math.max(1, Math.floor(width * dpr));
            canvas.height = Math.max(1, Math.floor(height * dpr));
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

            nebula = createNebula(width, height, dpr);

            var area = width * height;
            var count = clamp(Math.floor(area / 9000), 120, 360);

            var cx = width * (config.centerX ?? 0.55);
            var cy = height * (config.centerY ?? 0.35);
            var spreadX = width * 0.22;
            var spreadY = height * 0.18;

            stars = [];
            for (var i = 0; i < count; i++) {
                var clustered = Math.random() < centerBias;
                var x = clustered ? (cx + randn() * spreadX) : rand(0, width);
                var y = clustered ? (cy + randn() * spreadY) : rand(0, height);
                // Wrap inicial
                x = (x % width + width) % width;
                y = (y % height + height) % height;

                var depth = rand(0.2, 1.0);
                var size = rand(0.6, 1.9) * depth;
                var vel = speed * (0.3 + depth * 1.4);
                var hue = clustered
                    ? pick([210, 215, 220, 240, 260, 45]) // azul/roxo + um pouco dourado
                    : pick([205, 215, 230, 250, 40]);

                stars.push({
                    x: x,
                    y: y,
                    vx: rand(-0.15, 0.15) * vel,
                    vy: (0.7 + rand(0, 0.6)) * vel,
                    r: size,
                    a: rand(0.25, 0.95) * (clustered ? 1.0 : 0.9),
                    hue: hue,
                    t: rand(0, Math.PI * 2),
                    tw: rand(0.5, 1.8) * (clustered ? 1.1 : 1.0)
                });
            }
        }

        function drawBackground() {
            // Fundo base (se nebula existir, desenha; senão, fill simples)
            if (nebula) {
                ctx.drawImage(nebula, 0, 0, width, height);
            } else {
                ctx.clearRect(0, 0, width, height);
                ctx.fillStyle = 'rgba(6, 8, 18, 0.9)';
                ctx.fillRect(0, 0, width, height);
            }
        }

        function drawStars(dt) {
            ctx.save();
            ctx.globalCompositeOperation = 'lighter';

            for (var i = 0; i < stars.length; i++) {
                var s = stars[i];
                s.x += s.vx * dt;
                s.y += s.vy * dt;
                if (s.x < -10) s.x = width + 10;
                if (s.x > width + 10) s.x = -10;
                if (s.y > height + 10) s.y = -10;

                var tw = twinkle ? (0.65 + 0.35 * Math.sin(s.t)) : 1.0;
                s.t += dt * s.tw;

                var alpha = clamp(s.a * tw, 0.05, 1.0);
                var glow = s.r * 3.2;

                // Glow
                var g = ctx.createRadialGradient(s.x, s.y, 0, s.x, s.y, glow);
                g.addColorStop(0, 'hsla(' + s.hue + ', 90%, 75%, ' + (alpha * 0.35) + ')');
                g.addColorStop(1, 'rgba(0,0,0,0)');
                ctx.fillStyle = g;
                ctx.beginPath();
                ctx.arc(s.x, s.y, glow, 0, Math.PI * 2);
                ctx.fill();

                // Núcleo
                ctx.fillStyle = 'hsla(' + s.hue + ', 95%, 88%, ' + alpha + ')';
                ctx.beginPath();
                ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
                ctx.fill();
            }

            ctx.restore();
        }

        function frame(ts) {
            if (!last) last = ts;
            var dt = clamp((ts - last) / 1000, 0, 0.05);
            last = ts;

            drawBackground();
            drawStars(dt);

            raf = window.requestAnimationFrame(frame);
        }

        function stop() {
            if (raf) window.cancelAnimationFrame(raf);
            raf = 0;
        }

        function start() {
            stop();
            last = 0;
            if (reduceMotion) {
                drawBackground();
                drawStars(0);
                return;
            }
            raf = window.requestAnimationFrame(frame);
        }

        var ro = null;
        if (window.ResizeObserver) {
            ro = new ResizeObserver(function () {
                resize();
                start();
            });
            ro.observe(canvas);
        } else {
            window.addEventListener('resize', function () {
                resize();
                start();
            });
        }

        resize();
        start();

        // Pausa se a aba perder foco
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) stop();
            else start();
        });
    }

    ready(function () {
        var canvas = document.getElementById('ft-stars');
        if (!canvas) return;
        init(canvas, window.FT_STARS_CONFIG || {});
    });
})();


/**
 * Fortune Tiger – Slot com valores R$ 1–20, colunas 3/4/5, pontos (1 R$ = 10 pts).
 * Rolos giram e param devagar; sons de spin, paragem, vitória e derrota.
 */
(function () {
    'use strict';

    const C = window.FT_CONFIG;
    if (!C || !C.values || !C.playUrl) return;

    const form = document.getElementById('ft-play-form');
    const spinBtn = document.getElementById('ft-spin');
    const balanceEl = document.getElementById('ft-balance');
    const pointsEl = document.getElementById('ft-points');
    const pointsSpinEl = document.getElementById('ft-points-spin');
    const winEl = document.getElementById('ft-win');
    const reelsEl = document.getElementById('ft-reels');
    const historyList = document.querySelector('.ft-history-list');
    const columnsInput = document.getElementById('ft-columns-input');
    const betInput = document.getElementById('ft-bet');
    if (!form || !spinBtn || !balanceEl || !winEl || !reelsEl) return;

    const POINTS_RATE = C.pointsRate || 10;
    const SPIN_INTERVAL_MS = 110;
    const STOP_DELAY_MS = 700;

    function getReelElements() {
        return Array.from(reelsEl.querySelectorAll('.ft-reel'));
    }

    function getSymbolElements(reelEl) {
        return Array.from(reelEl.querySelectorAll('.ft-symbol-wrap .ft-symbol'));
    }

    function formatMoney(n) {
        return (typeof n === 'number' ? n : parseFloat(n))
            .toFixed(2).replace('.', ',');
    }

    function setSymbol(el, value) {
        const v = typeof value === 'string' ? parseInt(value, 10) : value;
        el.setAttribute('data-value', String(v));
        el.textContent = C.currency + ' ' + formatMoney(v);
        el.className = 'ft-symbol ft-symbol-val';
    }

    function randomValue() {
        return C.values[Math.floor(Math.random() * C.values.length)];
    }

    function cycleReelSymbols(reelEl) {
        getSymbolElements(reelEl).forEach(function (sym) {
            setSymbol(sym, randomValue());
        });
    }

    function applyReelResult(reelEl, values) {
        const elems = getSymbolElements(reelEl);
        (values || []).forEach(function (val, i) {
            if (elems[i]) setSymbol(elems[i], val);
        });
    }

    function setColumns(n) {
        const cols = Math.max(3, Math.min(5, parseInt(n, 10) || 3));
        if (columnsInput) columnsInput.value = cols;
        reelsEl.classList.remove('ft-cols-3', 'ft-cols-4', 'ft-cols-5');
        reelsEl.classList.add('ft-cols-' + cols);
        const reels = getReelElements();
        reels.forEach(function (r, i) {
            r.classList.toggle('ft-reel-hidden', i >= cols);
        });
        var btns = form.querySelectorAll('.ft-col-btn');
        btns.forEach(function (b) {
            b.classList.toggle('active', parseInt(b.getAttribute('data-cols'), 10) === cols);
        });
        return cols;
    }

    function parseBalance() {
        const raw = (balanceEl && balanceEl.textContent) ? balanceEl.textContent.trim() : '';
        const normalized = raw.replace(/\./g, '').replace(',', '.');
        return parseFloat(normalized) || 0;
    }

    function updatePointsDisplay() {
        const balance = parseBalance();
        const bet = parseFloat(betInput?.value || 0) || 0;
        const pts = Math.round(balance * POINTS_RATE);
        if (pointsEl) pointsEl.textContent = pts + ' pts';
        if (pointsSpinEl) pointsSpinEl.textContent = Math.round(bet * POINTS_RATE) + ' pts';
    }

    /* Web Audio */
    let audioCtx = null;
    function initAudio() {
        if (audioCtx) return audioCtx;
        try { audioCtx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) {}
        return audioCtx;
    }
    function soundTick() {
        const ctx = initAudio();
        if (!ctx) return;
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.connect(g); g.connect(ctx.destination);
        o.frequency.value = 180; o.type = 'square';
        g.gain.setValueAtTime(0.04, ctx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.04);
        o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.04);
    }
    function soundStop() {
        const ctx = initAudio();
        if (!ctx) return;
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.connect(g); g.connect(ctx.destination);
        o.frequency.value = 90; o.type = 'sine';
        g.gain.setValueAtTime(0.12, ctx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.12);
        o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.12);
    }
    function soundWin() {
        const ctx = initAudio();
        if (!ctx) return;
        const base = 330;
        [0, 2, 4, 7].forEach(function (semi, i) {
            const o = ctx.createOscillator();
            const g = ctx.createGain();
            o.connect(g); g.connect(ctx.destination);
            o.frequency.value = base * Math.pow(2, semi / 12);
            o.type = 'sine';
            const t = ctx.currentTime + i * 0.08;
            g.gain.setValueAtTime(0, t);
            g.gain.linearRampToValueAtTime(0.1, t + 0.02);
            g.gain.exponentialRampToValueAtTime(0.0001, t + 0.2);
            o.start(t); o.stop(t + 0.2);
        });
    }
    function soundLose() {
        const ctx = initAudio();
        if (!ctx) return;
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.connect(g); g.connect(ctx.destination);
        o.frequency.value = 220; o.type = 'sine';
        g.gain.setValueAtTime(0.08, ctx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.15);
        o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.15);
    }

    function spinAnimation(intervalMs) {
        const reels = getReelElements().filter(function (r) { return !r.classList.contains('ft-reel-hidden'); });
        const timers = [];
        let tickCount = 0;
        reels.forEach(function (r) {
            r.classList.add('spinning');
            timers.push(setInterval(function () {
                cycleReelSymbols(r);
                tickCount++;
                if (tickCount % 3 === 0) soundTick();
            }, intervalMs));
        });
        return {
            stopReel: function (index) {
                if (timers[index]) {
                    clearInterval(timers[index]);
                    timers[index] = null;
                }
                if (reels[index]) reels[index].classList.remove('spinning');
            },
            stopAll: function () {
                timers.forEach(function (t, i) {
                    if (t) clearInterval(t);
                    if (reels[i]) reels[i].classList.remove('spinning');
                });
            }
        };
    }

    function stopReelsOneByOne(reelsData, spin, onComplete) {
        const reels = getReelElements().filter(function (r) { return !r.classList.contains('ft-reel-hidden'); });
        let idx = 0;
        function stopNext() {
            if (idx >= reels.length) {
                spin.stopAll();
                reels.forEach(function (r) { r.classList.remove('win'); });
                if (onComplete) onComplete();
                return;
            }
            const r = reels[idx];
            const vals = reelsData[idx];
            spin.stopReel(idx);
            applyReelResult(r, vals);
            r.classList.add('win');
            soundStop();
            idx++;
            setTimeout(stopNext, STOP_DELAY_MS);
        }
        stopNext();
    }

    function showWin(msg, isWin) {
        winEl.textContent = msg;
        winEl.className = 'ft-win show ' + (isWin ? 'win' : 'lose');
        winEl.style.display = 'block';
    }

    function showError(msg) {
        winEl.textContent = msg;
        winEl.className = 'ft-win show error';
        winEl.style.display = 'block';
    }

    function updateBalance(val) {
        const n = typeof val === 'number' ? val : parseFloat(val);
        balanceEl.textContent = (isNaN(n) ? 0 : n).toFixed(2).replace('.', ',');
        updatePointsDisplay();
    }

    function prependHistory(winAmount) {
        if (!historyList) return;
        const sp = document.createElement('span');
        sp.className = 'ft-history-item';
        sp.textContent = C.currency + ' ' + (typeof winAmount === 'number' ? formatMoney(winAmount) : winAmount);
        historyList.insertBefore(sp, historyList.firstChild);
        const items = historyList.querySelectorAll('.ft-history-item');
        for (let i = 5; i < items.length; i++) items[i].remove();
        const empty = historyList.querySelector('.ft-history-empty');
        if (empty) empty.remove();
    }

    setColumns(3);

    form.querySelectorAll('.ft-col-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setColumns(parseInt(btn.getAttribute('data-cols'), 10));
            updatePointsDisplay();
        });
    });
    if (betInput) {
        betInput.addEventListener('input', updatePointsDisplay);
        betInput.addEventListener('change', updatePointsDisplay);
    }
    updatePointsDisplay();

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const bet = parseFloat(betInput?.value || 0) || 0;
        const cols = parseInt(columnsInput?.value || 3, 10);
        if (bet < 1 || bet > 20) {
            showError('Aposta entre R$ 1 e R$ 20.');
            return;
        }

        spinBtn.disabled = true;
        winEl.className = 'ft-win';
        winEl.textContent = '';

        const spin = spinAnimation(SPIN_INTERVAL_MS);

        const fd = new FormData(form);
        fd.set('columns', String(Math.max(3, Math.min(5, cols))));
        let data;
        try {
            const res = await fetch(C.playUrl, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            data = await res.json();
        } catch (err) {
            spin.stopAll();
            spinBtn.disabled = false;
            showError('Erro de ligação. Tente novamente.');
            soundLose();
            return;
        }

        if (data.error) {
            spin.stopAll();
            showError(data.error);
            if (data.balance != null) updateBalance(data.balance);
            spinBtn.disabled = false;
            soundLose();
            return;
        }

        const win = parseFloat(data.win) || 0;
        const balance = parseFloat(data.balance) || 0;
        const reels = data.reels;

        if (reels && Array.isArray(reels) && reels.length >= 3) {
            stopReelsOneByOne(reels, spin, function () {
                updateBalance(balance);
                prependHistory(win);
                showWin(
                    win > 0 ? 'Ganhou ' + C.currency + ' ' + formatMoney(win) + '!' : 'Tente novamente!',
                    win > 0
                );
                if (win > 0) soundWin();
                else soundLose();
                spinBtn.disabled = false;
            });
        } else {
            spin.stopAll();
            updateBalance(balance);
            prependHistory(win);
            showWin(
                win > 0 ? 'Ganhou ' + C.currency + ' ' + formatMoney(win) + '!' : 'Tente novamente!',
                win > 0
            );
            if (win > 0) soundWin();
            else soundLose();
            spinBtn.disabled = false;
        }
    });
})();

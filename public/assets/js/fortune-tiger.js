/**
 * Fortune Tiger – Aposta em R$, ganhos em R$, pool.
 * Rolos centralizados; giro melhorado.
 */
(function () {
    'use strict';

    const C = window.FT_CONFIG;
    if (!C || !C.values || !C.playUrl) return;

    const form = document.getElementById('ft-play-form');
    const spinBtn = document.getElementById('ft-spin');
    const balanceEl = document.getElementById('ft-balance');
    const winEl = document.getElementById('ft-win');
    const reelsEl = document.getElementById('ft-reels');
    const historyList = document.querySelector('.ft-history-list');
    const columnsInput = document.getElementById('ft-columns-input');
    const betInput = document.getElementById('ft-bet');
    const prizePoolEl = document.getElementById('ft-prize-pool');
    if (!form || !spinBtn || !winEl || !reelsEl) return;

    const SPIN_INTERVAL_MS = 100;
    const STOP_DELAY_MS = 750;

    function getReelElements() {
        return Array.from(reelsEl.querySelectorAll('.ft-reel'));
    }

    function getVisibleReels() {
        return getReelElements().filter(function (r) { return !r.classList.contains('ft-reel-hidden'); });
    }

    function getSymbolElements(reelEl) {
        return Array.from(reelEl.querySelectorAll('.ft-symbol-wrap .ft-symbol'));
    }

    function formatMoney(n) {
        return (typeof n === 'number' ? n : parseFloat(n)).toFixed(2).replace('.', ',');
    }

    function parseMoneyBr(text) {
        if (!text) return 0;
        var s = String(text).replace(/[^\d.,-]/g, '').trim();
        // Remove separador de milhar e normaliza decimal
        s = s.replace(/\./g, '').replace(',', '.');
        var v = parseFloat(s);
        return isNaN(v) ? 0 : v;
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
        // também aplica na página (para CSS responsivo)
        var page = document.querySelector('.ft-page');
        if (page) {
            page.classList.remove('ft-cols-3', 'ft-cols-4', 'ft-cols-5');
            page.classList.add('ft-cols-' + cols);
        }
        const reels = getReelElements();
        reels.forEach(function (r, i) {
            r.classList.toggle('ft-reel-hidden', i >= cols);
        });
        var btns = form.querySelectorAll('.ft-col-btn');
        btns.forEach(function (b) {
            b.classList.toggle('active', parseInt(b.getAttribute('data-cols'), 10) === cols);
        });
        // Ajusta o tamanho do cabinet para melhor visualização
        var cabinet = document.querySelector('.ft-cabinet');
        if (cabinet) {
            cabinet.style.maxWidth = cols === 3 ? 'min(520px, 92vw)' : cols === 4 ? 'min(780px, 96vw)' : 'min(980px, 99vw)';
        }

        // Multiplicador: 3 colunas = editável (1–40); 4 = 50; 5 = 100
        if (betInput) {
            if (cols === 4) {
                betInput.value = '50';
                betInput.disabled = true;
            } else if (cols === 5) {
                betInput.value = '100';
                betInput.disabled = true;
            } else {
                betInput.disabled = false;
                var v = parseMoneyBr(betInput.value || '0');
                if (v < 1) v = 1;
                if (v > 40) v = 40;
                betInput.value = String(Math.round(v));
            }
        }
        return cols;
    }

    function updateBalanceDisplay(bal) {
        if (!balanceEl) return;
        balanceEl.textContent = (typeof bal === 'number' ? bal : parseFloat(bal) || 0).toFixed(2).replace('.', ',');
    }

    function updatePrizePool(val) {
        if (!prizePoolEl) return;
        prizePoolEl.textContent = (typeof val === 'number' ? val : parseFloat(val) || 0).toFixed(2).replace('.', ',');
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
        var base = 330;
        [0, 2, 4, 7].forEach(function (semi, i) {
            var o = ctx.createOscillator();
            var g = ctx.createGain();
            o.connect(g); g.connect(ctx.destination);
            o.frequency.value = base * Math.pow(2, semi / 12);
            o.type = 'sine';
            var t = ctx.currentTime + i * 0.08;
            g.gain.setValueAtTime(0, t);
            g.gain.linearRampToValueAtTime(0.1, t + 0.02);
            g.gain.exponentialRampToValueAtTime(0.0001, t + 0.2);
            o.start(t); o.stop(t + 0.2);
        });
    }
    function soundLose() {
        const ctx = initAudio();
        if (!ctx) return;
        var o = ctx.createOscillator();
        var g = ctx.createGain();
        o.connect(g); g.connect(ctx.destination);
        o.frequency.value = 220; o.type = 'sine';
        g.gain.setValueAtTime(0.08, ctx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.15);
        o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.15);
    }

    function spinAnimation(intervalMs) {
        const reels = getVisibleReels();
        const timers = [];
        var tickCount = 0;
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
                if (timers[index]) { clearInterval(timers[index]); timers[index] = null; }
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
        const reels = getVisibleReels();
        var idx = 0;
        function stopNext() {
            if (idx >= reels.length) {
                spin.stopAll();
                reels.forEach(function (r) { r.classList.remove('win'); });
                if (onComplete) onComplete();
                return;
            }
            var r = reels[idx];
            var vals = reelsData[idx];
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

    function prependHistory(winReais, poolBonus) {
        if (!historyList) return;
        var sp = document.createElement('span');
        sp.className = 'ft-history-item';
        sp.textContent = (winReais > 0 ? C.currency + ' ' + formatMoney(winReais) : '—') + (poolBonus > 0 ? ' +' + C.currency + ' ' + formatMoney(poolBonus) : '');
        historyList.insertBefore(sp, historyList.firstChild);
        var items = historyList.querySelectorAll('.ft-history-item');
        for (var i = 5; i < items.length; i++) items[i].remove();
        var empty = historyList.querySelector('.ft-history-empty');
        if (empty) empty.remove();
    }

    setColumns(3);

    form.querySelectorAll('.ft-col-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setColumns(parseInt(btn.getAttribute('data-cols'), 10));
        });
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        var bet = parseMoneyBr(betInput?.value || '0');
        var cols = parseInt(columnsInput?.value || 3, 10);
        // 3 colunas: multiplicador livre (1–40); 4: 50; 5: 100
        if (cols === 3) {
            if (bet < 1 || bet > 40) {
                showError('Multiplicador deve ser entre R$ 1,00 e R$ 40,00.');
                return;
            }
            bet = Math.round(bet);
        } else if (cols === 4) {
            bet = 50;
        } else if (cols === 5) {
            bet = 100;
        }
        var bal = parseMoneyBr(balanceEl?.textContent || '0');
        if (bal < bet) {
            showError('Saldo insuficiente.');
            return;
        }

        spinBtn.disabled = true;
        winEl.className = 'ft-win';
        winEl.textContent = '';

        var spin = spinAnimation(SPIN_INTERVAL_MS);
        var fd = new FormData(form);
        fd.set('columns', String(Math.max(3, Math.min(5, cols))));
        fd.set('bet', String(bet));

        var data;
        try {
            var res = await fetch(C.playUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
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
            if (data.balance != null) updateBalanceDisplay(data.balance);
            spinBtn.disabled = false;
            soundLose();
            return;
        }

        var winReais = parseFloat(data.win) || 0; // Ganho em R$
        var poolBonus = parseFloat(data.pool_bonus) || 0;
        var reels = data.reels;

        if (reels && Array.isArray(reels) && reels.length >= 3) {
            stopReelsOneByOne(reels, spin, function () {
                updateBalanceDisplay(data.balance);
                if (data.prize_pool != null) updatePrizePool(data.prize_pool);
                prependHistory(winReais, poolBonus);
                var msg = winReais > 0 ? 'Ganhou ' + C.currency + ' ' + formatMoney(winReais) + (poolBonus > 0 ? ' + ' + C.currency + ' ' + formatMoney(poolBonus) + ' bónus pool!' : '!') : 'Tente novamente!';
                showWin(msg, winReais > 0);
                if (winReais > 0) soundWin();
                else soundLose();
                spinBtn.disabled = false;
            });
        } else {
            spin.stopAll();
            updateBalanceDisplay(data.balance);
            if (data.prize_pool != null) updatePrizePool(data.prize_pool);
            prependHistory(winReais, poolBonus);
            var msg = winReais > 0 ? 'Ganhou ' + C.currency + ' ' + formatMoney(winReais) + (poolBonus > 0 ? ' + ' + C.currency + ' ' + formatMoney(poolBonus) + ' bónus!' : '!') : 'Tente novamente!';
            showWin(msg, winReais > 0);
            if (winReais > 0) soundWin();
            else soundLose();
            spinBtn.disabled = false;
        }
    });
})();

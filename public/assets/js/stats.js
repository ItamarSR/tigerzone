/**
 * TigerZone – Estatísticas em tempo real (dados reais)
 * Atualização automática via /api/stats
 */
(function () {
    'use strict';
    const bar = document.getElementById('stats-bar');
    if (!bar) return;

    const el = function (key) {
        return bar.querySelector('[data-stat="' + key + '"]');
    };

    function fetchStats() {
        fetch('/api/stats', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var o = el('online');
                if (o) o.textContent = data.online != null ? data.online : '—';
                var w = el('wins');
                if (w) w.textContent = data.recent_wins || '—';
                var d = el('deposits');
                if (d) d.textContent = data.total_deposits || '—';
            })
            .catch(function () {});
    }

    fetchStats();
    setInterval(fetchStats, 8000);
})();

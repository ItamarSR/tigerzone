/**
 * TigerZone – Scripts globais
 */
(function () {
    'use strict';
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (el) {
        setTimeout(function () {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.3s';
            setTimeout(function () { el.remove(); }, 300);
        }, 5000);
    });
})();

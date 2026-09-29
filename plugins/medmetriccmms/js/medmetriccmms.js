/* ---------------------------------------------------------------------
 * MedMetric CMMS - client helpers
 * ---------------------------------------------------------------------
 */

(function () {
    'use strict';

    /**
     * Resolve the plugin ajax base URL from this script's own src tag,
     * e.g. https://glpi.example/plugins/medmetriccmms/js/medmetriccmms.js
     */
    function resolveAjaxBase() {
        var scripts = document.getElementsByTagName('script');
        for (var i = scripts.length - 1; i >= 0; i--) {
            var src = scripts[i].src || '';
            var marker = '/plugins/medmetriccmms/js/';
            var at = src.indexOf(marker);
            if (at !== -1) {
                return src.substring(0, at + marker.length - 3) + 'ajax/';
            }
        }
        // Fallback: relative path
        return '/plugins/medmetriccmms/ajax/';
    }

    var AJAX_BASE = null;

    /**
     * Small JSON fetch helper for plugin ajax endpoints.
     */
    function medmetricFetch(action, params) {
        if (AJAX_BASE === null) {
            AJAX_BASE = resolveAjaxBase();
        }
        var endpointMap = {
            equipment_search: 'equipment.php',
            equipment_due: 'equipment.php',
            department_equipment: 'department.php',
            department_counts: 'department.php',
            workorder_by_status: 'workorder.php',
            workorder_for_equipment: 'workorder.php',
            notification_count: 'notification.php',
            notification_latest: 'notification.php',
            inventory_low: 'inventory.php',
            inventory_stock: 'inventory.php',
            analytics_summary: 'analytics.php',
            ai_status: 'ai.php',
            report_data: 'report.php'
        };
        var file = endpointMap[action] || 'analytics.php';
        var query = new URLSearchParams(Object.assign({ action: action }, params || {}));
        return fetch(AJAX_BASE + file + '?' + query.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            return response.json();
        });
    }

    window.medmetricFetch = medmetricFetch;

    // Notification badge polling (guarded: only when container exists)
    document.addEventListener('DOMContentLoaded', function () {
        var badge = document.getElementById('medmetric-notif-badge');
        if (!badge) {
            return;
        }
        var refresh = function () {
            medmetricFetch('notification_count').then(function (data) {
                var count = data && data.open ? parseInt(data.open, 10) : 0;
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.style.display = count > 0 ? 'inline-block' : 'none';
            }).catch(function () { /* silent */ });
        };
        refresh();
        window.setInterval(refresh, 60000);
    });
})();

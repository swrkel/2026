(function () {
    'use strict';
    window.RestaurantNewMultiBranch = {
        refreshDashboard: function () {
            document.dispatchEvent(new CustomEvent('restaurant-new:multi-branch-refresh'));
        },
        bindTransferActions: function () {
            document.querySelectorAll('[data-rn-transfer-action]').forEach(function (button) {
                button.addEventListener('click', function () {
                    button.classList.add('rn-loading');
                });
            });
        }
    };
    document.addEventListener('DOMContentLoaded', window.RestaurantNewMultiBranch.bindTransferActions);
})();

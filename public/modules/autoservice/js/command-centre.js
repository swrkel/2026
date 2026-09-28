(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.autoservice-quick-card, .autoservice-priority-list a, .autoservice-shortcut-list a').forEach(function (item) {
            item.addEventListener('mouseenter', function () {
                item.classList.add('autoservice-hovered');
            });
            item.addEventListener('mouseleave', function () {
                item.classList.remove('autoservice-hovered');
            });
        });
    });
})();

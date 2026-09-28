(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var roleSearch = document.getElementById('umn-role-search');
        if (roleSearch) {
            roleSearch.addEventListener('input', function () {
                var term = this.value.trim().toLowerCase();
                document.querySelectorAll('#umn-role-table tbody tr[data-role-name]').forEach(function (row) {
                    row.style.display = row.dataset.roleName.indexOf(term) !== -1 ? '' : 'none';
                });
            });
        }

        document.querySelectorAll('[data-confirm-role]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                if (!window.confirm('Delete role "' + button.dataset.confirmRole + '"?')) {
                    event.preventDefault();
                }
            });
        });

        document.querySelectorAll('.umn-module-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                var card = button.closest('.umn-module-card');
                var collapsed = card.classList.toggle('is-collapsed');
                button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                var icon = button.querySelector('.fa-chevron-up,.fa-chevron-down');
                if (icon) {
                    icon.classList.toggle('fa-chevron-up', !collapsed);
                    icon.classList.toggle('fa-chevron-down', collapsed);
                }
            });
        });

        document.querySelectorAll('[data-umn-module-all]').forEach(function (toggle) {
            var card = toggle.closest('.umn-module-card');
            var boxes = Array.from(card.querySelectorAll('input[name="permissions[]"]'));
            var refresh = function () {
                toggle.checked = boxes.length > 0 && boxes.every(function (box) { return box.checked; });
                toggle.indeterminate = boxes.some(function (box) { return box.checked; }) && !toggle.checked;
            };
            toggle.addEventListener('change', function () {
                boxes.forEach(function (box) { box.checked = toggle.checked; });
                refresh();
            });
            boxes.forEach(function (box) { box.addEventListener('change', refresh); });
            refresh();
        });

        var search = document.getElementById('umn-permission-search');
        if (search) {
            search.addEventListener('input', function () {
                var term = this.value.trim().toLowerCase();
                document.querySelectorAll('.umn-module-card[data-search]').forEach(function (card) {
                    card.classList.toggle('is-hidden', card.dataset.search.indexOf(term) === -1);
                });
            });
        }

        var setCollapsed = function (collapsed) {
            document.querySelectorAll('.umn-module-card[data-search]').forEach(function (card) {
                card.classList.toggle('is-collapsed', collapsed);
            });
        };
        document.querySelectorAll('[data-umn-expand]').forEach(function (button) {
            button.addEventListener('click', function () { setCollapsed(false); });
        });
        document.querySelectorAll('[data-umn-collapse]').forEach(function (button) {
            button.addEventListener('click', function () { setCollapsed(true); });
        });
    });
}());

/* User Management New - Add/Edit Role searchable dropdown */
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('umn-permission-search');
    var menu = document.getElementById('umn-permission-search-results');
    var clear = document.getElementById('umn-permission-search-clear');

    if (!input || !menu) return;

    var items = [];

    document.querySelectorAll('.umn-module-card[data-search]').forEach(function (card) {
        var title = card.querySelector('.umn-module-toggle strong');

        if (title) {
            items.push({
                label: title.textContent.trim(),
                type: 'Module',
                card: card,
                tile: null
            });
        }

        card.querySelectorAll('.umn-page-tile').forEach(function (tile) {
            var label = tile.textContent.replace(/\s+/g, ' ').trim();

            if (label) {
                items.push({
                    label: label,
                    type: 'Page / Tab',
                    card: card,
                    tile: tile
                });
            }
        });
    });

    function render() {
        var term = input.value.trim().toLowerCase();
        menu.innerHTML = '';

        var matches = items.filter(function (item) {
            return !term || item.label.toLowerCase().indexOf(term) !== -1;
        }).slice(0, 20);

        matches.forEach(function (item) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'umn-search-dropdown__item';
            button.innerHTML = '<span></span><small></small>';
            button.querySelector('span').textContent = item.label;
            button.querySelector('small').textContent = item.type;

            button.addEventListener('mousedown', function (e) {
                e.preventDefault();
                input.value = item.label;

                document.querySelectorAll('.umn-module-card[data-search]').forEach(function (card) {
                    card.classList.toggle('is-hidden', card !== item.card);

                    card.querySelectorAll('.umn-page-tile').forEach(function (tile) {
                        tile.style.display = (!item.tile || tile === item.tile) ? '' : 'none';
                    });
                });

                item.card.classList.remove('is-collapsed');

                var toggle = item.card.querySelector('.umn-module-toggle');
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'true');
                }

                menu.hidden = true;
                input.setAttribute('aria-expanded', 'false');
            });

            menu.appendChild(button);
        });

        if (!matches.length) {
            menu.innerHTML = '<div class="umn-search-dropdown__empty">No matching module, page or tab found.</div>';
        }

        menu.hidden = false;
        input.setAttribute('aria-expanded', 'true');

        if (clear) {
            clear.hidden = input.value.trim() === '';
        }
    }

    input.addEventListener('focus', render);
    input.addEventListener('input', function () {
        document.querySelectorAll('.umn-page-tile').forEach(function (tile) {
            tile.style.display = '';
        });
        render();
    });

    if (clear) {
        clear.addEventListener('click', function () {
            input.value = '';
            input.dispatchEvent(new Event('input', {bubbles: true}));
            input.focus();
        });
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.umn-search-wrap')) {
            menu.hidden = true;
            input.setAttribute('aria-expanded', 'false');
        }
    });
});

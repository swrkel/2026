(function () {
    'use strict';

    function activateTab(container, name) {
        container.querySelectorAll('[data-pdn-tab]').forEach(function (button) {
            var selected = button.getAttribute('data-pdn-tab') === name;
            button.classList.toggle('active', selected);
            button.setAttribute('aria-selected', selected ? 'true' : 'false');
            button.tabIndex = selected ? 0 : -1;
        });
        container.parentElement.querySelectorAll('[data-pdn-panel]').forEach(function (panel) {
            var selected = panel.getAttribute('data-pdn-panel') === name;
            panel.classList.toggle('active', selected);
            panel.hidden = !selected;
        });
        try { sessionStorage.setItem('pdnew.activeTab', name); } catch (e) {}
    }

    function bindSelectFilter(input) {
        var targetId = input.getAttribute('data-pdn-filter-select');
        var select = targetId ? document.getElementById(targetId) : null;
        if (!select) return;

        var options = Array.prototype.map.call(select.options, function (option) {
            return {
                value: option.value,
                text: option.text,
                disabled: option.disabled
            };
        });

        input.addEventListener('input', function () {
            var term = input.value.trim().toLocaleLowerCase();
            var selected = select.value;
            select.innerHTML = '';

            options.forEach(function (option) {
                var keep = option.value === ''
                    || option.value === selected
                    || term === ''
                    || option.text.toLocaleLowerCase().indexOf(term) !== -1;
                if (!keep) return;

                var created = new Option(option.text, option.value, false, option.value === selected);
                created.disabled = option.disabled;
                select.add(created);
            });

            if (selected && Array.prototype.some.call(select.options, function (option) { return option.value === selected; })) {
                select.value = selected;
            }
        });
    }

    function setColumnVisibility(toggle) {
        var tableId = toggle.getAttribute('data-pdn-column-toggle');
        var index = parseInt(toggle.getAttribute('data-column-index'), 10);
        var table = tableId ? document.getElementById(tableId) : null;
        if (!table || Number.isNaN(index)) return;

        table.querySelectorAll('tr').forEach(function (row) {
            var cell = row.children[index];
            if (cell) cell.hidden = !toggle.checked;
        });
    }

    document.addEventListener('keydown', function (event) {
        var tab = event.target.closest('[data-pdn-tab]');
        if (!tab || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;

        var tabs = Array.prototype.slice.call(tab.closest('.pdn-tabs').querySelectorAll('[data-pdn-tab]'));
        var index = tabs.indexOf(tab);
        if (event.key === 'Home') index = 0;
        if (event.key === 'End') index = tabs.length - 1;
        if (event.key === 'ArrowLeft') index = (index - 1 + tabs.length) % tabs.length;
        if (event.key === 'ArrowRight') index = (index + 1) % tabs.length;
        event.preventDefault();
        tabs[index].focus();
        activateTab(tab.closest('.pdn-tabs'), tabs[index].getAttribute('data-pdn-tab'));
    });

    document.addEventListener('click', function (event) {
        var tab = event.target.closest('[data-pdn-tab]');
        if (tab) {
            event.preventDefault();
            activateTab(tab.closest('.pdn-tabs'), tab.getAttribute('data-pdn-tab'));
            return;
        }

        var confirmTarget = event.target.closest('[data-confirm]');
        if (confirmTarget && !window.confirm(confirmTarget.getAttribute('data-confirm'))) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    });

    document.addEventListener('change', function (event) {
        var toggle = event.target.closest('[data-pdn-column-toggle]');
        if (toggle) setColumnVisibility(toggle);
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.pdn-tabs').forEach(function (tabs) {
            var requested = new URLSearchParams(window.location.search).get('tab');
            var remembered = null;
            try { remembered = sessionStorage.getItem('pdnew.activeTab'); } catch (e) {}
            var first = tabs.querySelector('[data-pdn-tab]');
            var candidate = tabs.querySelector('[data-pdn-tab="' + (requested || remembered || '') + '"]') || first;
            if (candidate) activateTab(tabs, candidate.getAttribute('data-pdn-tab'));
        });

        document.querySelectorAll('[data-pdn-filter-select]').forEach(bindSelectFilter);
        document.querySelectorAll('[data-pdn-column-toggle]').forEach(setColumnVisibility);

        document.querySelectorAll('form[data-prevent-double-submit]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var submit = form.querySelector('[type="submit"]');
                if (submit) {
                    submit.disabled = true;
                    submit.dataset.originalText = submit.textContent;
                    submit.textContent = 'Processing…';
                }
            });
        });
    });
})();

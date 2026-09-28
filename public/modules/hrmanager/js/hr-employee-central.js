document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-hr-column-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var wrapper = btn.closest('.hr-list-toolbar');
            if (!wrapper) return;
            var panel = wrapper.querySelector('[data-hr-column-panel]');
            if (panel) panel.classList.toggle('open');
        });
    });
});

(function () {
    'use strict';

    function all(selector, context) {
        return Array.prototype.slice.call((context || document).querySelectorAll(selector));
    }

    function updateSelectionCount() {
        var count = all('input[name="sections[]"]:checked').length;
        all('[data-mgmt-selected-count]').forEach(function (node) { node.textContent = count; });
    }

    function setAllSections(checked) {
        all('input[name="sections[]"]').forEach(function (input) { input.checked = checked; });
        updateSelectionCount();
    }

    function formPayload(form) {
        return new FormData(form);
    }

    function showPreviewError(host, message) {
        host.innerHTML = '<div class="alert alert-danger mgmt-alert"><strong>Unable to prepare preview.</strong><br>' + String(message || 'Please check the report scope and try again.') + '</div>';
    }

    function initPreview() {
        var button = document.getElementById('mgmt-preview-button');
        var form = document.getElementById('mgmt-report-form');
        var host = document.getElementById('mgmt-report-preview');
        var loading = document.getElementById('mgmt-preview-loading');
        if (!button || !form || !host) return;

        button.addEventListener('click', function () {
            if (!all('input[name="sections[]"]:checked').length) {
                showPreviewError(host, 'Select at least one report section.');
                return;
            }
            loading.hidden = false;
            button.disabled = true;
            fetch(button.getAttribute('data-preview-url'), {
                method: 'POST',
                body: formPayload(form),
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            })
            .then(function (response) {
                return response.json().then(function (body) {
                    if (!response.ok) throw new Error(body.message || Object.values(body.errors || {}).join(' '));
                    return body;
                });
            })
            .then(function (body) {
                host.innerHTML = body.html;
                host.scrollIntoView({behavior: 'smooth', block: 'start'});
            })
            .catch(function (error) { showPreviewError(host, error.message); })
            .finally(function () { loading.hidden = true; button.disabled = false; });
        });
    }

    function initShare() {
        var modal = document.getElementById('mgmt-share-modal');
        var form = document.getElementById('mgmt-share-form');
        if (!modal || !form) return;

        all('[data-mgmt-open-share]').forEach(function (button) {
            button.addEventListener('click', function () { modal.hidden = false; document.body.style.overflow = 'hidden'; });
        });
        all('[data-mgmt-close-share]', modal).forEach(function (button) {
            button.addEventListener('click', function () { modal.hidden = true; document.body.style.overflow = ''; });
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var raw = document.getElementById('mgmt-recipient-input').value || '';
            var recipients = raw.split(/\r?\n|,/).map(function (value) { return value.trim(); }).filter(Boolean);
            var result = document.getElementById('mgmt-share-result');
            if (!recipients.length) {
                result.innerHTML = '<div class="alert alert-danger">Please enter at least one recipient.</div>';
                return;
            }

            var data = new FormData(form);
            data.delete('recipients[]');
            recipients.forEach(function (recipient) { data.append('recipients[]', recipient); });
            var submit = form.querySelector('[type="submit"]');
            submit.disabled = true;
            result.innerHTML = '<div class="alert alert-info"><i class="fa fa-spinner fa-spin"></i> Preparing delivery...</div>';

            fetch(form.action, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            })
            .then(function (response) {
                return response.json().then(function (body) {
                    if (!response.ok) throw new Error(body.message || Object.values(body.errors || {}).join(' '));
                    return body;
                });
            })
            .then(function (body) {
                var links = '<a target="_blank" href="' + body.public_url + '">Open secure report link</a>';
                if (body.launch_url) links += ' &nbsp; <a target="_blank" href="' + body.launch_url + '">Open WhatsApp</a>';
                result.innerHTML = '<div class="alert alert-success">' + body.message + '<br>' + links + '</div>';
                if (body.launch_url) window.open(body.launch_url, '_blank', 'noopener');
            })
            .catch(function (error) { result.innerHTML = '<div class="alert alert-danger">' + error.message + '</div>'; })
            .finally(function () { submit.disabled = false; });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        all('input[name="sections[]"]').forEach(function (input) { input.addEventListener('change', updateSelectionCount); });
        all('[data-mgmt-select-all]').forEach(function (button) { button.addEventListener('click', function () { setAllSections(true); }); });
        all('[data-mgmt-clear-all]').forEach(function (button) { button.addEventListener('click', function () { setAllSections(false); }); });
        updateSelectionCount();
        initPreview();
        initShare();

        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery('.mgmt-searchable').select2({width: '100%'});
        }
    });
})();

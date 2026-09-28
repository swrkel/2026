(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, {once: true});
        } else {
            callback();
        }
    }

    function fieldNameTokens(name) {
        var tokens = [];
        String(name || '').replace(/([^\[\]]+)|\[([^\]]*)\]/g, function (_, plain, bracket) {
            tokens.push(typeof plain === 'string' ? plain : bracket);
            return '';
        });
        return tokens;
    }

    function nextNumericKey(object) {
        var highest = -1;
        Object.keys(object || {}).forEach(function (key) {
            if (/^\d+$/.test(key)) {
                highest = Math.max(highest, Number(key));
            }
        });
        return String(highest + 1);
    }

    function assignNestedValue(root, name, value) {
        var tokens = fieldNameTokens(name);
        if (!tokens.length) {
            return;
        }

        var cursor = root;
        tokens.forEach(function (rawToken, index) {
            var token = rawToken === '' ? nextNumericKey(cursor) : String(rawToken);
            var isLast = index === tokens.length - 1;

            if (isLast) {
                // A checked checkbox follows its hidden 0 field and must win.
                cursor[token] = value;
                return;
            }

            if (!cursor[token] || typeof cursor[token] !== 'object' || Array.isArray(cursor[token])) {
                cursor[token] = {};
            }
            cursor = cursor[token];
        });
    }

    function buildManagePayload(form) {
        var payload = {};
        var formData = new FormData(form);

        formData.forEach(function (value, name) {
            if (name === '_token' || name === '_method' || name === 'manage_form_payload' || name === 'manage_form_payload_json') {
                return;
            }
            if (typeof value !== 'string') {
                // Uploaded files remain ordinary multipart fields.
                return;
            }
            assignNestedValue(payload, name, value);
        });

        return payload;
    }

    function updateAutoPermissionPayload(form) {
        var permissions = {};
        form.querySelectorAll('[data-auto-permission-key]').forEach(function (input) {
            var key = String(input.getAttribute('data-auto-permission-key') || '').trim();
            if (key) {
                permissions[key] = input.checked ? 1 : 0;
            }
        });

        var hidden = document.getElementById('auto_manage_permissions_json');
        if (hidden) {
            hidden.value = JSON.stringify(permissions);
        }
    }

    function showStatus(message, state) {
        var old = document.getElementById('sa_manage_save_flash');
        if (old) {
            old.remove();
        }

        state = state === 'success' || state === 'error' ? state : 'pending';
        var className = state === 'success' ? 'alert-success' : (state === 'error' ? 'alert-danger' : 'alert-info');
        var icon = state === 'success' ? 'fa-check-circle' : (state === 'error' ? 'fa-exclamation-triangle' : 'fa-spinner fa-spin');

        var flash = document.createElement('div');
        flash.id = 'sa_manage_save_flash';
        flash.className = 'sa-manage-save-flash alert ' + className + ' alert-dismissible';
        flash.setAttribute('role', 'alert');
        flash.setAttribute('aria-live', 'assertive');
        flash.innerHTML = '<button type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
            '<i class="fa ' + icon + '" aria-hidden="true"></i> ' +
            '<span class="sa-manage-status-text"></span>';
        flash.querySelector('.sa-manage-status-text').textContent = String(message || '');
        flash.querySelector('.close').addEventListener('click', function () { flash.remove(); });
        document.body.appendChild(flash);

        if (state !== 'pending') {
            window.setTimeout(function () {
                if (flash.parentNode) {
                    flash.remove();
                }
            }, 10000);
        }
    }

    function showStatusFromUrl() {
        try {
            var params = new URLSearchParams(window.location.search || '');
            var state = params.get('manage_status');
            if (state !== 'success' && state !== 'error') {
                return;
            }
            var fallback = state === 'success'
                ? 'Manage permissions saved successfully.'
                : 'Manage permissions could not be saved. Please try again.';
            showStatus(params.get('manage_message') || fallback, state);
        } catch (ignore) {
            // Session-based Laravel status messages remain available as fallback.
        }
    }

    function setSaving(saving) {
        var main = document.getElementById('custom_permission_btn');
        var floating = document.getElementById('sa_floating_save_btn');

        [main, floating].forEach(function (button) {
            if (!button) {
                return;
            }
            button.disabled = !!saving;
            button.classList.toggle('is-saving', !!saving);
        });

        if (floating) {
            floating.innerHTML = saving
                ? '<i class="fa fa-spinner fa-spin"></i> Saving...'
                : '<i class="fa fa-save"></i> Save';
        }
    }

    function disableOriginalControls(form, payloadField) {
        form.querySelectorAll('input[name], select[name], textarea[name], button[name]').forEach(function (control) {
            var name = String(control.name || '');
            var type = String(control.type || '').toLowerCase();

            if (control === payloadField || type === 'file' || name === '_token' || name === '_method') {
                return;
            }

            control.disabled = true;
        });
    }

    ready(function () {
        var form = document.getElementById('custom_permission_form');
        if (!form) {
            return;
        }

        // Hidden/inactive legacy fields are marked required, so browser-native
        // constraint validation must not be allowed to cancel this form.
        form.setAttribute('novalidate', 'novalidate');
        window.SA_MANAGE_FAST_SAVE_ACTIVE = false;

        var scheduled = false;
        var saving = false;
        var nativeSubmit = HTMLFormElement.prototype.submit;

        function performFullSave() {
            scheduled = false;
            if (saving) {
                return;
            }
            saving = true;

            try {
                updateAutoPermissionPayload(form);

                var payloadField = document.getElementById('manage_form_payload');
                if (!payloadField) {
                    throw new Error('Manage form payload field is missing.');
                }

                payloadField.value = JSON.stringify(buildManagePayload(form));
                disableOriginalControls(form, payloadField);
                setSaving(true);
                showStatus('Saving Manage settings...', 'pending');

                // Submit directly after compacting. This bypasses invalid hidden
                // required controls and cannot re-enter a competing submit hook.
                nativeSubmit.call(form);
            } catch (error) {
                saving = false;
                setSaving(false);
                showStatus(error && error.message ? error.message : 'Manage settings could not be submitted.', 'error');
            }
        }

        function scheduleFullSave() {
            if (scheduled || saving) {
                return;
            }
            scheduled = true;
            // Let legacy click preparation (for example opt_vars) finish first.
            window.setTimeout(performFullSave, 0);
        }

        var mainButton = document.getElementById('custom_permission_btn');
        if (mainButton) {
            mainButton.addEventListener('click', function (event) {
                event.preventDefault();
                scheduleFullSave();
            }, true);
        }

        var floatingButton = document.getElementById('sa_floating_save_btn');
        if (floatingButton) {
            floatingButton.addEventListener('click', function (event) {
                event.preventDefault();
                if (saving) {
                    return;
                }
                // Route the floating button through the real Save button so all
                // page-specific click preparation executes before serialization.
                if (mainButton) {
                    mainButton.click();
                } else {
                    scheduleFullSave();
                }
            }, true);
        }

        // Covers Enter-key submission and legacy code calling jQuery .submit().
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
            scheduleFullSave();
        }, true);

        showStatusFromUrl();
    });
})();

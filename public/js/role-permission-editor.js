(function (window, document) {
    'use strict';

    var $ = window.jQuery;
    if (!$ || window.__rolePermissionEditorV59Loaded) {
        return;
    }
    window.__rolePermissionEditorV59Loaded = true;

    function escapeText(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function permissionLabel(input) {
        var $input = $(input);
        var $label = $input.closest('label');
        var text = '';

        if ($label.length) {
            var $clone = $label.clone();
            $clone.find('input,ins,.iCheck-helper').remove();
            text = $.trim($clone.text().replace(/\s+/g, ' '));
        }

        if (!text) {
            text = String($input.val() || '')
                .replace(/[._-]+/g, ' ')
                .replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
        }

        return text;
    }

    function checkedPermissionInputs($form) {
        return $form.find(
            'input[name="permissions[]"]:checked,' +
            'input[name="spg_permissions[]"]:checked'
        );
    }

    function initialAssignedPermissions() {
        var node = document.getElementById('role_initial_assigned_permissions');
        if (!node) {
            return [];
        }

        try {
            var decoded = JSON.parse(node.textContent || '[]');
            return Array.isArray(decoded) ? decoded.map(String).filter(Boolean) : [];
        } catch (error) {
            return [];
        }
    }

    function ensureAssignedPermissionControls($form) {
        var initial = initialAssignedPermissions();
        var $fallback = $('#role_unlisted_permissions_controls');
        if (!initial.length || !$fallback.length) {
            return;
        }

        var represented = Object.create(null);
        var assigned = Object.create(null);
        initial.forEach(function (value) { assigned[value] = true; });

        $form.find('input[name="permissions[]"],input[name="spg_permissions[]"]').each(function () {
            var value = String($(this).val() || '');
            if (!value) {
                return;
            }
            represented[value] = true;
            if (assigned[value] && !this.checked) {
                this.checked = true;
                $(this).prop('checked', true);
                if ($.fn.iCheck && $(this).data('iCheck')) {
                    $(this).iCheck('check');
                }
            }
        });

        var missing = initial.filter(function (value) {
            return !represented[value];
        });

        if (!missing.length) {
            $fallback.empty().hide();
            return;
        }

        missing.sort();
        var html = '<div class="role-unlisted-permissions-heading">' +
            '<strong>Assigned permissions from currently unavailable or legacy sections</strong>' +
            '<span>These permissions remain selected and can be removed here before saving.</span>' +
            '</div><div class="role-unlisted-permissions-grid">';

        missing.forEach(function (value) {
            var label = String(value)
                .replace(/[._-]+/g, ' ')
                .replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
            html += '<label class="role-unlisted-permission-item">' +
                '<input type="checkbox" name="permissions[]" value="' + escapeText(value) + '" checked> ' +
                '<span><strong>' + escapeText(label) + '</strong><small>' + escapeText(value) + '</small></span>' +
                '</label>';
        });

        html += '</div>';
        $fallback.html(html).show();
    }

    function refreshAssignedPermissions($form) {
        var $list = $('#role_assigned_permissions_list');
        var $count = $('#role_selected_permission_count');
        if (!$list.length || !$count.length) {
            return;
        }

        var items = [];
        var seen = Object.create(null);
        checkedPermissionInputs($form).each(function () {
            var value = String($(this).val() || '');
            if (!value || seen[value]) {
                return;
            }
            seen[value] = true;
            items.push({value: value, label: permissionLabel(this)});
        });

        items.sort(function (a, b) {
            return a.label.localeCompare(b.label);
        });

        $count.text(items.length);
        if (!items.length) {
            $list.html('<span class="role-no-permissions">No permissions selected yet.</span>');
            return;
        }

        var html = '';
        items.forEach(function (item) {
            html += '<span class="role-permission-chip" data-permission-name="' + escapeText(item.value) + '" title="' + escapeText(item.value) + '">' +
                '<i class="fa fa-check-circle"></i> ' + escapeText(item.label) + '</span>';
        });
        $list.html(html);
    }

    function buildPayload($form) {
        var permissions = [];
        var seen = Object.create(null);

        $form.find('input[name="permissions[]"]:checked').each(function () {
            var value = String($(this).val() || '');
            if (value && !seen[value]) {
                seen[value] = true;
                permissions.push(value);
            }
        });

        $form.find('#permissions_payload').val(JSON.stringify(permissions));
    }

    function forceEditorVisible($form) {
        var $box = $form.closest('.role-page-editor-box');
        $box.children('.box-body').show().css({visibility: 'visible', opacity: 1});
        $form.show().css({visibility: 'visible', opacity: 1});
        $('#role_stable_header, #role_permissions_workspace, #role_assigned_permissions_card')
            .show()
            .css({visibility: 'visible', opacity: 1});

        // Remove collapse state left by an older cached permission-search script.
        $box.removeClass('sa-permission-section-collapsed collapsed');
        $box.find('.sa-permission-section-collapsed').each(function () {
            $(this).removeClass('sa-permission-section-collapsed').addClass('sa-permission-section-expanded');
            $(this).children('.box-body,.card-body,.panel-body,.module-permission-body,.permission-section-body').show();
        });
    }

    function initEditor() {
        var $form = $('form[data-role-permission-editor]').first();
        if (!$form.length) {
            return;
        }

        forceEditorVisible($form);
        ensureAssignedPermissionControls($form);
        refreshAssignedPermissions($form);

        $form.on('submit.rolePermissionEditor', function () {
            buildPayload($form);
        });

        $(document).on(
            'change.rolePermissionEditor ifChanged.rolePermissionEditor',
            '#role_permissions_workspace input[name="permissions[]"], #role_permissions_workspace input[name="spg_permissions[]"]',
            function () {
                window.setTimeout(function () {
                    refreshAssignedPermissions($form);
                }, 0);
            }
        );

        // Defensive re-checks cover iCheck and legacy scripts that initialize after
        // this file. They do not collapse or filter any permission section.
        window.setTimeout(function () { forceEditorVisible($form); refreshAssignedPermissions($form); }, 100);
        window.setTimeout(function () { forceEditorVisible($form); refreshAssignedPermissions($form); }, 650);
        window.setTimeout(function () { forceEditorVisible($form); refreshAssignedPermissions($form); }, 1600);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEditor);
    } else {
        initEditor();
    }
})(window, document);

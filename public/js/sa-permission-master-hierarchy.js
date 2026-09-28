(function(window, document, $) {
    'use strict';

    function setCheckboxDisabled($cb, disabled) {
        if (!$cb || !$cb.length) return;
        $cb.prop('disabled', !!disabled);
        $cb.closest('label, .checkbox').toggleClass('sa-permission-disabled-child', !!disabled);
        if ($.fn && $.fn.iCheck) {
            try { $cb.iCheck(disabled ? 'disable' : 'enable'); } catch (e) {}
        }
    }

    function setCheckboxChecked($cb, checked) {
        if (!$cb || !$cb.length) return;
        $cb.prop('checked', !!checked);
        if ($.fn && $.fn.iCheck) {
            try { $cb.iCheck(checked ? 'check' : 'uncheck'); } catch (e) {}
        }
    }

    function candidateMasterCheckbox($section) {
        // Primary rule: module table header row followed by the module data row.
        var $header = $section.find('> .card-body > .row.bg-danger, > .box-body > .row.bg-danger, .card-body > .row.bg-danger, .box-body > .row.bg-danger').first();
        if ($header.length) {
            var $row = $header.nextAll('.row').first();
            var $cb = $row.find('input[type="checkbox"].ch_select, input[type="checkbox"]').not('.sa-section-select-all,.check_all,.check_all_daily_collection').first();
            if ($cb.length) return $cb;
        }

        // Fallback: explicit parent-looking names.
        var $named = $section.find('input[type="checkbox"]').filter(function() {
            var name = String($(this).attr('name') || $(this).attr('id') || '').toLowerCase();
            if (!name) return false;
            if ($(this).hasClass('sa-section-select-all') || $(this).hasClass('check_all')) return false;
            return /^enable_/.test(name) || /_module$/.test(name) || /module_enable$/.test(name);
        }).first();
        if ($named.length) return $named;

        return $();
    }

    function childCheckboxes($section, $master) {
        return $section.find('input[type="checkbox"]')
            .not($master)
            .not('.sa-section-select-all')
            .not('.check_all')
            .not('.check_all_daily_collection')
            .not('[data-always-disabled="1"]');
    }

    function sectionSelectAlls($section) {
        return $section.find('input[type="checkbox"].sa-section-select-all, input[type="checkbox"].check_all').not('.check_all_daily_collection');
    }

    function applySectionHierarchy($section) {
        if (!$section || !$section.length) return;
        var $master = candidateMasterCheckbox($section);
        if (!$master.length) return;

        $master.addClass('sa-master-module-checkbox');
        // Critical rule: master module checkbox must always remain clickable.
        setCheckboxDisabled($master, false);

        var parentOn = $master.is(':checked');
        var $children = childCheckboxes($section, $master);
        var $selectAlls = sectionSelectAlls($section);

        $children.each(function() { setCheckboxDisabled($(this), !parentOn); });
        $selectAlls.each(function() { setCheckboxDisabled($(this), !parentOn); });

        $section.toggleClass('sa-module-parent-off', !parentOn);

        var $notice = $section.find('.sa-module-parent-off-notice').first();
        if (!parentOn) {
            if (!$notice.length) {
                var title = $.trim($section.find('.card-header h4, .box-title, h4').first().text()).replace(/\s+/g, ' ') || 'Module';
                $notice = $('<div class="sa-module-parent-off-notice">Module disabled: enable the main module checkbox to edit child permissions and show this module in the sidebar.</div>');
                var $body = $section.children('.card-body, .box-body').first();
                if ($body.length) { $body.append($notice); } else { $section.append($notice); }
            }
            $notice.show();
        } else {
            $notice.hide();
        }
    }

    function applyAllHierarchy() {
        $('.card, .box').each(function() {
            var $section = $(this);
            if ($section.closest('.modal').length) return;
            if (!$section.find('input[type="checkbox"]').length) return;
            applySectionHierarchy($section);
        });
    }

    $(function() {
        // Remove stale disabled states left by older scripts before calculating the real state.
        $('.card input[type="checkbox"], .box input[type="checkbox"]').not('[data-always-disabled="1"]').each(function() {
            var $cb = $(this);
            if ($cb.hasClass('sa-section-select-all') || $cb.hasClass('check_all') || $cb.hasClass('ch_select') || $cb.closest('.card, .box').length) {
                setCheckboxDisabled($cb, false);
            }
        });

        applyAllHierarchy();

        // Support iCheck and native checkboxes.
        $(document).on('change ifChanged', '.sa-master-module-checkbox', function() {
            applySectionHierarchy($(this).closest('.card, .box'));
        });

        // If older scripts add Select All rows late, re-apply after a short delay.
        setTimeout(applyAllHierarchy, 300);
        setTimeout(applyAllHierarchy, 1000);

        // When a search result expands a section, make sure it is not visually locked by stale collapsed classes.
        $(document).on('click', '#sa_permission_search_results [data-target], #sa_permission_search_results .sa-search-result', function() {
            setTimeout(function() {
                $('.sa-permission-search-highlight, .sa-permission-section-match').closest('.card, .box').each(function() {
                    $(this).removeClass('sa-module-parent-off-legacy sa-permission-disabled-section');
                    applySectionHierarchy($(this));
                });
            }, 50);
        });
    });
})(window, document, window.jQuery);

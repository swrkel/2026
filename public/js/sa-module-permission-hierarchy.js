(function (window, document) {
    'use strict';

    if (window.__saModulePermissionHierarchyV60Loaded) { return; }

    // V060: final hierarchy controller for Super Admin > Manage.
    // It must never run inside Role Add/Edit, where every permitted and
    // non-permitted checkbox must remain independently editable.
    var explorer = document.getElementById('sa_permission_explorer');
    var isRoleEditor = (explorer && explorer.getAttribute('data-sa-context') === 'role')
        || !!document.querySelector('form[data-role-permission-editor]');
    if (isRoleEditor) {
        window.__saModulePermissionHierarchyV60Loaded = true;
        return;
    }

    // Parent/module checkbox is always clickable. Child permissions lock/unlock only from the parent state.
    // Also defeats legacy iCheck/Blade disabled states that were still locking enabled sections.
    window.__saModulePermissionHierarchyV60Loaded = true;

    var $ = window.jQuery;

    function txt(el) {
        return String((el && el.textContent) || '').replace(/\s+/g, ' ').trim();
    }

    function closest(el, selector) {
        return el && el.closest ? el.closest(selector) : null;
    }

    function lower(value) { return String(value || '').toLowerCase(); }

    function checkboxLabel(cb) {
        var wrap = closest(cb, 'label,.checkbox,.form-group,td,div') || cb.parentElement || cb;
        return txt(wrap);
    }

    function isSelectAll(cb) {
        var s = lower((cb.name || '') + ' ' + (cb.id || '') + ' ' + (cb.className || '') + ' ' + checkboxLabel(cb));
        return /select\s*all|select_all|check_all|check\s*all|sa-section-select-all/.test(s);
    }

    function isMasterByName(cb) {
        var s = lower((cb.name || '') + ' ' + (cb.id || '') + ' ' + checkboxLabel(cb));
        if (isSelectAll(cb)) { return false; }
        if (/location|module_permission\[|permissions\[|page_permission|sub_menu|submenu|tab|settings|dashboard|settlement|operator|payment|cash|card|report|status|sales|transfer/.test(s)) {
            // Allow exact module names even when label contains "module".
            return /(^|\s|_)enable_[a-z0-9_]*module($|\s|_)|(^|\s|_)[a-z0-9_]+_module($|\s|_)|\bmodule\b/.test(s)
                && !/(sub_menu|submenu|tab|settings|dashboard|settlement|operator|payment|cash|card|report|status|sales|transfer)/.test(s.replace(/module/g, ''));
        }
        return /(^|\s|_)enable_[a-z0-9_]*module($|\s|_)|(^|\s|_)[a-z0-9_]+_module($|\s|_)|\bmodule\b/.test(s);
    }

    function isModuleSection(node) {
        if (!node || !node.querySelector) { return false; }
        if (closest(node, '#sa_permission_explorer')) { return false; }
        var t = txt(node);
        return /Module Name/i.test(t) && /Enable/i.test(t) && /Status/i.test(t) && node.querySelector('input[type="checkbox"]');
    }

    function sections() {
        var root = document.querySelector('form') || document.querySelector('.content') || document.body;
        var candidates = Array.prototype.slice.call(root.querySelectorAll('.card,.box,.panel,.module-permission-section,.permission-section,.accordion-item,.sa-permission-section'));
        var out = [];
        candidates.forEach(function (n) { if (isModuleSection(n) && out.indexOf(n) === -1) { out.push(n); } });
        // Fallback for legacy custom green blocks.
        Array.prototype.slice.call(root.querySelectorAll('h3,h4,h5,.box-title,.card-title,.panel-title')).forEach(function (h) {
            var n = closest(h, '.card,.box,.panel,.module-permission-section,.permission-section,.accordion-item,.sa-permission-section') || h.parentElement;
            if (isModuleSection(n) && out.indexOf(n) === -1) { out.push(n); }
        });
        return out;
    }

    function statusActive(section) {
        var badges = section.querySelectorAll('.label,.badge,span');
        for (var i = 0; i < badges.length; i += 1) {
            var b = lower(txt(badges[i]));
            if (b === 'active') { return true; }
        }
        return false;
    }

    function rowOf(cb) {
        return closest(cb, 'tr,.row,.form-group') || cb.parentElement;
    }

    function findMaster(section) {
        var cbs = Array.prototype.slice.call(section.querySelectorAll('input[type="checkbox"]'));
        var exact = cbs.filter(function (cb) { return isMasterByName(cb); });
        if (exact.length === 1) { return exact[0]; }

        // Best legacy pattern: checkbox in the same row as status/interval/expiry/module price.
        for (var i = 0; i < cbs.length; i += 1) {
            if (isSelectAll(cbs[i])) { continue; }
            var rt = lower(txt(rowOf(cbs[i])));
            if (/\b(active|not set|disabled|expired|module price|activated on|expiry|interval)\b/.test(rt)) {
                return cbs[i];
            }
        }
        if (exact.length) { return exact[0]; }
        for (var j = 0; j < cbs.length; j += 1) {
            if (!isSelectAll(cbs[j])) { return cbs[j]; }
        }
        return null;
    }

    function iCheck(cb, action) {
        if (!$ || !$.fn || !$.fn.iCheck) { return; }
        try { $(cb).iCheck(action); } catch (e) {}
        try { $(cb).iCheck('update'); } catch (e2) {}
    }

    function wrapper(cb) {
        return closest(cb, '.icheckbox_square-blue,.icheckbox_minimal-blue,.icheckbox_square-red,.icheckbox_minimal-red,.icheckbox_square,.icheckbox_minimal');
    }

    function enableCheckbox(cb) {
        if (!cb) { return; }
        cb.disabled = false;
        cb.removeAttribute('disabled');
        cb.readOnly = false;
        cb.removeAttribute('readonly');
        cb.style.pointerEvents = 'auto';
        cb.style.opacity = '1';
        cb.classList.remove('disabled');
        iCheck(cb, 'enable');
        var w = wrapper(cb);
        if (w) {
            w.classList.remove('disabled');
            w.style.pointerEvents = 'auto';
            w.style.opacity = '1';
        }
        var p = closest(cb, 'label,.checkbox,.form-group,.col-md-1,.col-md-2,.col-md-3,.col-md-4,.col-sm-1,.col-sm-2,.col-sm-3,.col-sm-4,td') || cb.parentElement;
        if (p) {
            p.classList.remove('disabled', 'sa-child-permission-disabled');
            p.style.pointerEvents = 'auto';
            p.style.opacity = '1';
        }
    }

    function disableCheckbox(cb) {
        if (!cb) { return; }
        cb.disabled = true;
        cb.setAttribute('disabled', 'disabled');
        iCheck(cb, 'disable');
        var w = wrapper(cb);
        if (w) { w.classList.add('disabled'); }
        var p = closest(cb, 'label,.checkbox,.form-group,.col-md-1,.col-md-2,.col-md-3,.col-md-4,.col-sm-1,.col-sm-2,.col-sm-3,.col-sm-4,td') || cb.parentElement;
        if (p) { p.classList.add('sa-child-permission-disabled'); }
    }

    function setChecked(cb, checked) {
        cb.checked = !!checked;
        cb.defaultChecked = !!checked;
        if (checked) { cb.setAttribute('checked', 'checked'); iCheck(cb, 'check'); }
        else { cb.removeAttribute('checked'); iCheck(cb, 'uncheck'); }
    }

    function childCheckboxes(section, master) {
        return Array.prototype.slice.call(section.querySelectorAll('input[type="checkbox"]')).filter(function (cb) { return cb !== master; });
    }

    function applySection(section) {
        var master = findMaster(section);
        if (!master) { return; }

        master.setAttribute('data-sa-master-checkbox', '1');
        var mw = closest(master, 'label,.checkbox,.form-group,.col-md-1,.col-md-2,.col-md-3,.col-md-4,td') || master.parentElement;
        if (mw) { mw.setAttribute('data-sa-master-wrapper', '1'); }

        enableCheckbox(master);

        var enabled = !!master.checked || master.defaultChecked || master.getAttribute('checked') === 'checked' || statusActive(section);
        if (statusActive(section) && !master.checked) {
            setChecked(master, true);
            enabled = true;
        }

        section.classList.toggle('sa-module-master-disabled', !enabled);
        section.classList.toggle('sa-module-enabled-force', enabled);
        section.setAttribute('data-sa-master-enabled', enabled ? '1' : '0');

        childCheckboxes(section, master).forEach(function (cb) {
            if (enabled) { enableCheckbox(cb); }
            else { disableCheckbox(cb); }
        });

        enableCheckbox(master);
    }

    function applyAll() {
        sections().forEach(applySection);
    }

    function isMaster(cb) {
        var section = closest(cb, '.card,.box,.panel,.module-permission-section,.permission-section,.accordion-item,.sa-permission-section');
        return section && findMaster(section) === cb;
    }

    function installHandlers() {
        if ($) {
            $(document)
                .off('.saModulePermissionHierarchyV60')
                .on('ifChanged.saModulePermissionHierarchyV60 change.saModulePermissionHierarchyV60', 'input[type="checkbox"]', function () {
                    var section = closest(this, '.card,.box,.panel,.module-permission-section,.permission-section,.accordion-item,.sa-permission-section');
                    if (!section) { return; }
                    if (isMaster(this)) {
                        // User changed the parent: unlock/lock immediately.
                        applySection(section);
                    }
                })
                .on('submit.saModulePermissionHierarchyV60', 'form', function () {
                    sections().forEach(function (section) {
                        var master = findMaster(section);
                        if (!master) { return; }
                        enableCheckbox(master); // parent must post
                        var enabled = !!master.checked;
                        childCheckboxes(section, master).forEach(function (cb) {
                            if (!enabled) { setChecked(cb, false); }
                            enableCheckbox(cb); // enabled before submit so hidden 0 + actual value submit correctly
                        });
                    });
                });
        }

        document.addEventListener('click', function () { setTimeout(applyAll, 50); }, true);
        document.addEventListener('change', function () { setTimeout(applyAll, 40); }, true);

        if (window.MutationObserver) {
            var mo = new MutationObserver(function () { clearTimeout(window.__saPermHierarchyTimer); window.__saPermHierarchyTimer = setTimeout(applyAll, 60); });
            mo.observe(document.body, {childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'class', 'style', 'checked']});
        }

        [30, 120, 300, 700, 1500, 3000, 5000].forEach(function (ms) { setTimeout(applyAll, ms); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', installHandlers);
    } else {
        installHandlers();
    }

    window.SAModulePermissionHierarchy = { applyAll: applyAll, applySection: applySection, findMaster: findMaster, sections: sections };
})(window, document);

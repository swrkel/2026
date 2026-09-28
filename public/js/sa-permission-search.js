(function (window, document) {
    'use strict';

    // V059: Role Add/Edit uses a non-collapsing search context. Manage pages keep the section explorer.
    // Package and legacy screens retain permission-level search. Selecting a
    // heading opens the complete section so every permission remains editable.
    if (window.__saPermissionExplorerV59Loaded) { return; }
    window.__saPermissionExplorerV59Loaded = true;

    var $ = window.jQuery;
    if (!$) { return; }

    var state = {
        index: [],
        matches: [],
        active: 0,
        built: false,
        buildStarted: false,
        inputTimer: null,
        lastTerm: '',
        collapsedReady: false,
        panelMinimized: false,
        selectedSection: null,
        selectedHeading: null
    };

    function sectionHeadingMode() {
        var explorer = document.getElementById('sa_permission_explorer');
        return !!(explorer && explorer.getAttribute('data-search-mode') === 'sections');
    }

    function explorerContext() {
        var explorer = document.getElementById('sa_permission_explorer');
        return explorer ? String(explorer.getAttribute('data-sa-context') || '') : '';
    }

    function rolePageMode() {
        return explorerContext() === 'role';
    }

    function searchRoot() {
        if (rolePageMode()) {
            return document.getElementById('role_permissions_workspace')
                || document.querySelector('form[data-role-permission-editor]')
                || document.body;
        }
        return document.querySelector('.content, form') || document.body;
    }

    function ensureRoleEditorVisible() {
        if (!rolePageMode()) { return; }
        var form = document.querySelector('form[data-role-permission-editor]');
        var workspace = document.getElementById('role_permissions_workspace');
        var stableHeader = document.getElementById('role_stable_header');
        var assigned = document.getElementById('role_assigned_permissions_card');
        var nodes = [form, workspace, stableHeader, assigned];

        for (var i = 0; i < nodes.length; i += 1) {
            if (!nodes[i]) { continue; }
            nodes[i].style.display = '';
            nodes[i].style.visibility = 'visible';
            nodes[i].style.opacity = '1';
        }

        if (form) {
            var boxBody = closestAny(form, '.box-body');
            if (boxBody) {
                boxBody.style.display = '';
                boxBody.style.visibility = 'visible';
                boxBody.style.opacity = '1';
            }
            var outerBox = closestAny(form, '.box');
            if (outerBox) {
                outerBox.classList.remove('sa-permission-section-collapsed', 'collapsed');
                outerBox.classList.add('sa-permission-section-expanded');
            }
        }

        var collapsed = document.querySelectorAll('.role-page-editor-box .sa-permission-section-collapsed');
        for (var c = 0; c < collapsed.length; c += 1) {
            collapsed[c].classList.remove('sa-permission-section-collapsed');
            collapsed[c].classList.add('sa-permission-section-expanded');
            var body = sectionBody(collapsed[c]);
            if (body) { body.style.display = ''; }
        }
    }

    function indexedCounterText(count) {
        if (sectionHeadingMode()) { return count ? (count + ' sections indexed') : 'No sections indexed'; }
        return count ? (count + ' permissions indexed') : 'No permissions indexed';
    }

    function trimText(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function htmlEscape(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return {'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'}[char];
        });
    }

    function titleCase(value) {
        value = trimText(String(value || '')
            .replace(/[_\-]+/g, ' ')
            .replace(/([a-z])([A-Z])/g, '$1 $2'));
        if (!value) { return ''; }
        return value.replace(/\b\w+/g, function (word) {
            if (/^(pd|pos|sms|vat|otp|mpcs|api|url|id|nic|kot|cogs|sw|crm|hr|qc)$/i.test(word)) {
                return word.toUpperCase();
            }
            return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
        });
    }

    function cleanText(value) {
        var text = trimText(value);
        if (!text) { return ''; }

        // Convert accidental literal translation calls to clean labels.
        text = text.replace(/@lang\(\s*['\"]([^'\"]+)['\"](?:\s*,[^)]*)?\s*\)/g, function (match, key) {
            return titleCase(String(key || '').split('.').pop());
        });
        text = text.replace(/__\(\s*['\"]([^'\"]+)['\"](?:\s*,[^)]*)?\s*\)/g, function (match, key) {
            return titleCase(String(key || '').split('.').pop());
        });
        text = text.replace(/trans\(\s*['\"]([^'\"]+)['\"](?:\s*,[^)]*)?\s*\)/g, function (match, key) {
            return titleCase(String(key || '').split('.').pop());
        });
        text = text.replace(/\{\{\s*\$?[A-Za-z0-9_>\-\[\]'\"]+\s*\}\}/g, '');
        text = text.replace(/^Business\s*Name\s*:?.*?[\-–—]\s*/i, '');
        text = text.replace(/^Tick\/untick\s+all\s+options\s+in\s+Business\s+Name\s*:?.*$/i, '');
        text = text.replace(/^Tick\/untick\s+all\s+options\s+in\s+/i, '');
        text = text.replace(/^Select\s+All\s*/i, '');
        text = text.replace(/\b(index|create|show)\b$/ig, '');
        text = text.replace(/\s*:\s*$/g, '');
        return trimText(text);
    }

    function isNoise(text) {
        text = cleanText(text);
        if (!text || text.length < 2) { return true; }
        return /^(Business Name|Search Permissions|Annual Fee Package|Currency|Individual Package|Number of products|Number of Branches|VAT Effective Date|Post Dated Cheques Effective Date|Business Type|Select Currency|Please Select|0 = infinite|Ready|Help Guide|SMS Bal|POS\/POP)$/i.test(text);
    }

    function fixVisibleLanguageKeys() {
        var root = searchRoot();
        var nodes = root.querySelectorAll('label,.checkbox,.box-title,.card-title,.panel-title,h3,h4,h5,.permission-item,.search_label');
        for (var i = 0; i < nodes.length; i += 1) {
            if (nodes[i].closest && nodes[i].closest('#sa_permission_explorer')) { continue; }
            for (var j = 0; j < nodes[i].childNodes.length; j += 1) {
                var child = nodes[i].childNodes[j];
                if (child.nodeType === 3 && /(@lang\(|__\(|trans\()/.test(child.nodeValue || '')) {
                    child.nodeValue = cleanText(child.nodeValue);
                }
            }
        }
    }

    function closestAny(el, selector) {
        if (!el || !el.closest) { return null; }
        return el.closest(selector);
    }

    function getOwnText(el) {
        if (!el) { return ''; }
        // Fast text extraction. Do not clone large DOM trees.
        var text = el.getAttribute('data-permission-title') || el.getAttribute('title') || el.textContent || '';
        return cleanText(text);
    }

    function sectionOf(el) {
        if (rolePageMode()) {
            return closestAny(el, '.check_group,.module-permission-section,.permission-section,.role-permission-section,.row')
                || el.parentElement;
        }
        return closestAny(el, '.box,.card,.panel,.module-permission-section,.permission-section,.tab-pane,.pos-tab-content,.accordion-item') || el.parentElement;
    }


    function sectionTitle(section) {
        if (!section || !section.querySelector) { return ''; }

        var stored = cleanText(section.getAttribute('data-sa-section-title') || section.getAttribute('data-permission-title') || '');
        if (stored && !isNoise(stored)) { return titleCase(stored); }

        // Read only the section's own header. Using a broad descendant query can pick
        // an individual permission label from a nested card and create duplicate results.
        var directSelectors = [
            ':scope > .box-header .box-title',
            ':scope > .box-header .module-permission-title',
            ':scope > .box-header .permission-section-title',
            ':scope > .box-header h3', ':scope > .box-header h4', ':scope > .box-header h5',
            ':scope > .card-header .card-title',
            ':scope > .card-header .module-permission-title',
            ':scope > .card-header .permission-section-title',
            ':scope > .card-header h3', ':scope > .card-header h4', ':scope > .card-header h5',
            ':scope > .panel-heading .panel-title',
            ':scope > .panel-heading .module-permission-title',
            ':scope > .panel-heading .permission-section-title',
            ':scope > .panel-heading h3', ':scope > .panel-heading h4', ':scope > .panel-heading h5',
            ':scope > .module-permission-header .module-permission-title',
            ':scope > .permission-section-header .permission-section-title',
            ':scope > h3', ':scope > h4', ':scope > h5'
        ];
        for (var i = 0; i < directSelectors.length; i += 1) {
            var found = null;
            try { found = section.querySelector(directSelectors[i]); } catch (err) { found = null; }
            var text = found ? getOwnText(found) : '';
            if (text && !isNoise(text)) { return titleCase(text); }
        }

        // Fallback for browsers without :scope support: inspect direct children only.
        var children = section.children || [];
        for (var c = 0; c < children.length; c += 1) {
            var child = children[c];
            if (!/(^|\s)(box-header|card-header|panel-heading|module-permission-header|permission-section-header)(\s|$)/.test(child.className || '')) { continue; }
            var heading = child.querySelector('.box-title,.card-title,.panel-title,.module-permission-title,.permission-section-title,h3,h4,h5');
            var fallbackText = heading ? getOwnText(heading) : '';
            if (fallbackText && !isNoise(fallbackText)) { return titleCase(fallbackText); }
        }

        if (rolePageMode()) {
            var roleHeading = section.querySelector('h3 label,h4 label,h5 label,h3,h4,h5');
            var roleHeadingText = roleHeading ? getOwnText(roleHeading) : '';
            if (roleHeadingText && !isNoise(roleHeadingText)) { return titleCase(roleHeadingText); }
        }
        return '';
    }

    function sectionBody(section) {
        if (!section || !section.querySelector) { return null; }
        var body = section.querySelector(':scope > .box-body, :scope > .card-body, :scope > .panel-body, :scope > .module-permission-body, :scope > .permission-section-body, :scope > .accordion-collapse');
        if (body) { return body; }
        // Fallback for older browsers without :scope support.
        var candidates = section.children || [];
        for (var i = 0; i < candidates.length; i += 1) {
            if (/(^|\s)(box-body|card-body|panel-body|module-permission-body|permission-section-body|accordion-collapse)(\s|$)/.test(candidates[i].className || '')) {
                return candidates[i];
            }
        }
        return null;
    }


    function isTopControlSection(section) {
        if (!section || !section.querySelector) { return false; }
        var text = trimText(section.textContent || '');
        return /Annual Fee Package/i.test(text) && /Search Permissions/i.test(text) && /Individual Package/i.test(text);
    }

    function isInlineExplorer(explorer) {
        explorer = explorer || document.getElementById('sa_permission_explorer');
        return !!(explorer && explorer.getAttribute('data-sa-inline') === '1');
    }

    function detachFloatingExplorer() {
        var explorer = document.getElementById('sa_permission_explorer');
        if (!explorer || isInlineExplorer(explorer) || explorer.getAttribute('data-sa-floating') === '1') { return; }
        explorer.setAttribute('data-sa-floating', '1');
        document.body.appendChild(explorer);
    }

    function collapseTopControlSection() {
        if (rolePageMode()) { ensureRoleEditorVisible(); return; }
        var sections = document.querySelectorAll('.box,.card,.panel,.module-permission-section,.permission-section,.accordion-item');
        for (var i = 0; i < sections.length; i += 1) {
            if (isTopControlSection(sections[i])) {
                if (!sections[i].classList.contains('sa-permission-collapsible-section')) {
                    sections[i].classList.add('sa-permission-collapsible-section');
                }
                collapseSection(sections[i]);
            }
        }
    }

    function expandTopControlSection() {
        // Kept as a safe no-op wrapper for older calls. In v036 the floating search stays visible,
        // but the top business/details area remains collapsed by default.
        detachFloatingExplorer();
        collapseTopControlSection();
    }

    function discoverHeadingSections(root) {
        root = root || (document.querySelector('.content, form') || document.body);
        var candidates = root.querySelectorAll(
            '.sa-permission-search-section,.module-permission-section,.permission-section,' +
            '.box,.card,.panel,.accordion-item'
        );
        var seenSection = new Set();
        var seenTitle = Object.create(null);
        var sections = [];

        for (var i = 0; i < candidates.length; i += 1) {
            var section = candidates[i];
            if (!section || seenSection.has(section) || !sectionBody(section)) { continue; }
            if (closestAny(section, '#sa_permission_explorer,.sa-package-hidden-by-blueprint')) { continue; }
            if (isTopControlSection(section)) { continue; }

            var title = sectionTitle(section);
            if (!title || isNoise(title)) { continue; }
            var titleKey = title.toLowerCase();
            if (seenTitle[titleKey]) { continue; }

            seenSection.add(section);
            seenTitle[titleKey] = true;
            section.setAttribute('data-sa-section-title', title);
            sections.push({ section: section, title: title });
        }
        return sections;
    }

    function allPermissionSections() {
        if (rolePageMode()) { return []; }
        ensureIndexNow();
        var seen = new Set();
        var sections = [];
        for (var i = 0; i < state.index.length; i += 1) {
            var sec = state.index[i].section;
            if (!sec || seen.has(sec) || !sectionBody(sec)) { continue; }
            seen.add(sec);
            sections.push(sec);
        }

        // Preserve collapse/expand support for sections that contain no searchable
        // permission control (for example informational module settings blocks).
        var discovered = discoverHeadingSections(document.querySelector('.content, form') || document.body);
        for (var d = 0; d < discovered.length; d += 1) {
            var extra = discovered[d].section;
            if (!extra || seen.has(extra) || !sectionBody(extra)) { continue; }
            seen.add(extra);
            sections.push(extra);
        }
        return sections;
    }

    function prepareCollapsibleSections() {
        if (rolePageMode()) {
            state.collapsedReady = true;
            ensureRoleEditorVisible();
            return;
        }
        var sections = allPermissionSections();
        for (var i = 0; i < sections.length; i += 1) {
            var sec = sections[i];
            if (isTopControlSection(sec)) { sec.classList.add('sa-permission-collapsible-section'); }
            if (!sec.classList.contains('sa-permission-collapsible-section')) {
                sec.classList.add('sa-permission-collapsible-section');
                var title = sectionTitle(sec);
                if (title) { sec.setAttribute('data-sa-section-title', title); }
                var header = sec.querySelector(':scope > .box-header, :scope > .card-header, :scope > .panel-heading, :scope > .module-permission-header, :scope > .permission-section-header');
                if (header && !header.querySelector('.sa-section-collapse-indicator')) {
                    var indicator = document.createElement('span');
                    indicator.className = 'sa-section-collapse-indicator';
                    indicator.innerHTML = '<i class="fa fa-chevron-right"></i>';
                    header.insertBefore(indicator, header.firstChild);
                    header.style.cursor = 'pointer';
                    header.addEventListener('click', function (event) {
                        if (closestAny(event.target, 'input,button,a,select,textarea,label')) { return; }
                        toggleSection(this.closest('.sa-permission-collapsible-section'));
                    });
                }
            }
        }
        state.collapsedReady = true;
    }

    function collapseSection(section) {
        if (rolePageMode()) { ensureRoleEditorVisible(); return; }
        var body = sectionBody(section);
        if (!body) { return; }
        section.classList.add('sa-permission-section-collapsed');
        section.classList.remove('sa-permission-section-expanded');
        body.style.display = 'none';
        var icon = section.querySelector(':scope > .box-header .sa-section-collapse-indicator i, :scope > .card-header .sa-section-collapse-indicator i, :scope > .panel-heading .sa-section-collapse-indicator i, :scope > .module-permission-header .sa-section-collapse-indicator i, :scope > .permission-section-header .sa-section-collapse-indicator i');
        if (icon) { icon.className = 'fa fa-chevron-right'; }
    }

    function expandSection(section) {
        if (rolePageMode()) { ensureRoleEditorVisible(); return; }
        var body = sectionBody(section);
        if (!body) { return; }
        section.classList.remove('sa-permission-section-collapsed');
        section.classList.add('sa-permission-section-expanded');
        body.style.display = '';
        var icon = section.querySelector(':scope > .box-header .sa-section-collapse-indicator i, :scope > .card-header .sa-section-collapse-indicator i, :scope > .panel-heading .sa-section-collapse-indicator i, :scope > .module-permission-header .sa-section-collapse-indicator i, :scope > .permission-section-header .sa-section-collapse-indicator i');
        if (icon) { icon.className = 'fa fa-chevron-down'; }
    }

    function toggleSection(section) {
        if (!section) { return; }
        if (section.classList.contains('sa-permission-section-collapsed')) { expandSection(section); }
        else { collapseSection(section); }
    }

    function collapseAllSections() {
        if (rolePageMode()) {
            ensureRoleEditorVisible();
            $('#sa_permission_search_counter').text(indexedCounterText(state.index.length || 0));
            return 0;
        }
        prepareCollapsibleSections();
        var sections = allPermissionSections();
        for (var i = 0; i < sections.length; i += 1) { collapseSection(sections[i]); }
        $('#sa_permission_search_counter').text(indexedCounterText(state.index.length || 0));
        return sections.length;
    }

    function expandAllSections() {
        if (rolePageMode()) {
            ensureRoleEditorVisible();
            $('#sa_permission_search_counter').text(indexedCounterText(state.index.length || 0));
            return 0;
        }
        prepareCollapsibleSections();
        var sections = allPermissionSections();
        for (var i = 0; i < sections.length; i += 1) { expandSection(sections[i]); }
        $('#sa_permission_search_counter').text('Expanded ' + sections.length + ' sections');
        return sections.length;
    }

    function expandTargetPath(el) {
        if (!el) { return; }
        if (rolePageMode()) { ensureRoleEditorVisible(); return; }
        prepareCollapsibleSections();
        $(el).parents('.sa-permission-collapsible-section').each(function () { expandSection(this); });
        var sec = closestAny(el, '.sa-permission-collapsible-section');
        if (sec) { expandSection(sec); }
    }

    function moduleNameFor(el) {
        var section = sectionOf(el);
        var title = sectionTitle(section);
        if (title) { return title; }
        var id = (section && section.id) || (closestAny(el, '[id]') || {}).id || '';
        id = id.replace(/(_module|_section|_settings|_tab|_permission|module)/ig, ' ');
        return titleCase(id);
    }

    function displayLabel(el, label) {
        label = titleCase(cleanText(label));
        var moduleName = moduleNameFor(el);
        if (!moduleName || moduleName.toLowerCase() === label.toLowerCase()) { return label; }
        if (label.toLowerCase().indexOf(moduleName.toLowerCase()) === 0) { return label; }
        return moduleName + ' → ' + label;
    }

    function buildFastIndex() {
        if (state.built || state.buildStarted) { return; }
        state.buildStarted = true;

        fixVisibleLanguageKeys();
        var root = searchRoot();
        var index = [];

        if (sectionHeadingMode()) {
            // Manage page: show one result per module/section heading only.
            var discovered = discoverHeadingSections(root);
            for (var i = 0; i < discovered.length; i += 1) {
                var section = discovered[i].section;
                var title = discovered[i].title;
                var header = null;
                try {
                    header = section.querySelector(':scope > .box-header,:scope > .card-header,:scope > .panel-heading,:scope > .module-permission-header,:scope > .permission-section-header');
                } catch (err) { header = null; }
                var target = header || section;
                if (!target.id) { target.id = 'sa_section_target_' + i + '_' + Math.random().toString(36).slice(2, 7); }
                index.push({ text: title, search: title.toLowerCase(), element: target, section: section, id: target.id });
            }
        } else {
            // Package and legacy pages retain exact permission-level search.
            var nodes = [];
            var inputs = root.querySelectorAll('input[type="checkbox"],input[type="radio"]');
            for (var p = 0; p < inputs.length; p += 1) {
                var input = inputs[p];
                if (closestAny(input, '#sa_permission_explorer,.dataTables_length,.dataTables_filter,.sa-package-hidden-by-blueprint')) { continue; }
                var label = closestAny(input, 'label') || closestAny(input, '.checkbox,.permission-item,.form-group') || input.parentElement;
                if (label) { nodes.push(label); }
            }
            var extras = root.querySelectorAll('.search_label,.permission-item,.module-permission-title,.permission-section-title,.box-title,.card-title,.panel-title');
            for (var e = 0; e < extras.length; e += 1) {
                if (!closestAny(extras[e], '#sa_permission_explorer,.dataTables_length,.dataTables_filter,.sa-package-hidden-by-blueprint')) { nodes.push(extras[e]); }
            }
            var seenNode = new Set();
            var seenKey = Object.create(null);
            for (var n = 0; n < nodes.length; n += 1) {
                var el = nodes[n];
                if (!el || seenNode.has(el)) { continue; }
                seenNode.add(el);
                var raw = getOwnText(el);
                if (isNoise(raw)) { continue; }
                var text = displayLabel(el, raw);
                if (isNoise(text)) { continue; }
                var control = el.querySelector ? el.querySelector('input[type="checkbox"],input[type="radio"]') : null;
                var key = [text.toLowerCase(), control ? (control.name || '') : '', control ? (control.value || '') : '', (sectionOf(el) || {}).id || ''].join('|');
                if (seenKey[key]) { continue; }
                seenKey[key] = true;
                if (!el.id) { el.id = 'sa_perm_target_' + index.length + '_' + Math.random().toString(36).slice(2, 7); }
                index.push({ text: text, search: text.toLowerCase(), element: el, section: sectionOf(el), id: el.id });
            }
        }

        index.sort(function (a, b) { return a.text.localeCompare(b.text); });
        state.index = index;
        state.built = true;
        state.buildStarted = false;
        $('#sa_permission_search_counter').text(indexedCounterText(index.length));
        if (rolePageMode()) {
            ensureRoleEditorVisible();
        } else {
            prepareCollapsibleSections();
            collapseAllSections();
            collapseTopControlSection();
        }
        if (state.lastTerm) { renderResults(state.lastTerm); }
    }

    function ensureIndexNow() {
        if (!state.built) { buildFastIndex(); }
    }

    function firstSegment(text) {
        return trimText(String(text || '').split('→')[0] || '');
    }

    function isManageAccountsItem(item) {
        return /^manage\s+accounts$/i.test(firstSegment(item.text));
    }

    function termIncludesManageAccounts(term) {
        term = trimText(term).toLowerCase();
        return term.indexOf('manage accounts') !== -1 || term.indexOf('manage acc') !== -1;
    }

    function matchItems(term) {
        ensureIndexNow();
        term = trimText(term).toLowerCase();
        if (!term) { return []; }
        var words = term.split(' ').filter(Boolean);
        var allowManageAccounts = termIncludesManageAccounts(term);
        var matches = [];
        for (var i = 0; i < state.index.length; i += 1) {
            var item = state.index[i];
            if (!sectionHeadingMode() && isManageAccountsItem(item) && !allowManageAccounts) { continue; }
            var ok = true;
            for (var w = 0; w < words.length; w += 1) {
                if (item.search.indexOf(words[w]) === -1) { ok = false; break; }
            }
            if (ok) { matches.push(item); }
            if (!sectionHeadingMode() && matches.length >= 80) { break; }
        }
        return matches;
    }

    function highlight(text, term) {
        var safe = htmlEscape(text);
        term = trimText(term);
        if (!term) { return safe; }
        try {
            var parts = term.split(' ').filter(Boolean).map(function (p) { return p.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); });
            if (!parts.length) { return safe; }
            return safe.replace(new RegExp('(' + parts.join('|') + ')', 'ig'), '<mark>$1</mark>');
        } catch (err) { return safe; }
    }

    function openContainers(el) {
        if (!el) { return; }
        var $el = $(el);
        var $tabPane = $el.closest('.tab-pane');
        if ($tabPane.length && $tabPane.attr('id')) {
            $('a[href="#' + $tabPane.attr('id') + '"]').first().trigger('click');
            $tabPane.addClass('active in').show();
        }
        var $posTab = $el.closest('.pos-tab-content');
        if ($posTab.length) {
            $('.pos-tab-content').removeClass('active').hide();
            $posTab.addClass('active').show();
        }
        $el.parents('.collapse').each(function () {
            var $collapse = $(this);
            $collapse.addClass('in show').css('height', 'auto').show();
            var id = $collapse.attr('id');
            if (id) { $('a[href="#' + id + '"],button[data-target="#' + id + '"]').removeClass('collapsed').attr('aria-expanded', 'true'); }
        });
        $el.parents('.box,.card,.panel,.module-permission-section,.permission-section').each(function () {
            $(this).show().removeClass('collapsed');
            $(this).find('> .box-body,> .card-body,> .panel-body').show();
        });
    }

    function scrollParent(el) {
        var parent = el.parentElement;
        while (parent && parent !== document.body) {
            var style = window.getComputedStyle(parent);
            var overflow = style.overflowY || style.overflow;
            if (/(auto|scroll)/.test(overflow) && parent.scrollHeight > parent.clientHeight + 10) { return parent; }
            parent = parent.parentElement;
        }
        return window;
    }

    function scrollToElement(el) {
        if (!el) { return; }
        try { el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' }); } catch (err) { el.scrollIntoView(true); }
        window.setTimeout(function () {
            var sp = scrollParent(el);
            var explorer = document.getElementById('sa_permission_explorer');
            var offset = (explorer ? explorer.offsetHeight : 90) + 30;
            if (sp === window) {
                var top = window.pageYOffset + el.getBoundingClientRect().top - offset;
                window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
                $('html, body').stop(true, true).animate({ scrollTop: Math.max(0, top) }, 0);
            } else {
                var rect = el.getBoundingClientRect();
                var parentRect = sp.getBoundingClientRect();
                sp.scrollTop += rect.top - parentRect.top - offset;
            }
        }, 60);
    }

    function clearSelectedSectionHighlight() {
        if (state.selectedHeading) { $(state.selectedHeading).removeClass('sa-permission-search-highlight'); }
        if (state.selectedSection) { $(state.selectedSection).removeClass('sa-permission-section-match'); }
        state.selectedHeading = null;
        state.selectedSection = null;
    }

    function selectItem(item) {
        if (!item) { return; }
        var el = document.getElementById(item.id) || item.element;
        if (!el) { return; }
        if (!rolePageMode()) { collapseAllSections(); }
        else { ensureRoleEditorVisible(); }

        if (sectionHeadingMode()) {
            var section = item.section;
            if (!section) { return; }
            openContainers(section);
            expandTargetPath(section);
            expandSection(section);
            clearSelectedSectionHighlight();
            $('.sa-permission-search-highlight,.sa-permission-section-match').removeClass('sa-permission-search-highlight sa-permission-section-match');
            $(section).addClass('sa-permission-section-match');
            $(el).addClass('sa-permission-search-highlight');
            state.selectedSection = section;
            state.selectedHeading = el;
            scrollToElement(section);
            return;
        }

        openContainers(el);
        expandTargetPath(el);
        clearSelectedSectionHighlight();
        $('.sa-permission-search-highlight,.sa-permission-section-match').removeClass('sa-permission-search-highlight sa-permission-section-match');
        var permissionSection = item.section;
        if (permissionSection) { $(permissionSection).addClass('sa-permission-section-match'); }
        $(el).addClass('sa-permission-search-highlight');
        scrollToElement(el);
        window.setTimeout(function () {
            var input = el.querySelector ? el.querySelector('input[type="checkbox"],input[type="radio"]') : null;
            if (!input && el.matches && el.matches('input[type="checkbox"],input[type="radio"]')) { input = el; }
            if (input) { try { input.focus({ preventScroll: true }); } catch (err) { input.focus(); } }
        }, 350);
        window.setTimeout(function () {
            $(el).removeClass('sa-permission-search-highlight');
            if (permissionSection) { $(permissionSection).removeClass('sa-permission-section-match'); }
        }, 3500);
    }

    function renderResults(term) {
        if (state.panelMinimized) { return; }
        var $results = $('#sa_permission_search_results');
        term = trimText(term);
        if (sectionHeadingMode() && term !== state.lastTerm) {
            clearSelectedSectionHighlight();
        }
        state.lastTerm = term;
        $results.empty();
        if (!term) {
            state.matches = [];
            state.active = 0;
            $results.hide();
            $('#sa_permission_search_counter').text(state.index.length ? indexedCounterText(state.index.length) : 'Ready');
            return;
        }
        state.matches = matchItems(term);
        state.active = 0;
        if (!state.matches.length) {
            $('#sa_permission_search_counter').text('0 matches');
            $results.append('<div class="sa-permission-search-empty">' + (sectionHeadingMode() ? 'No matching section heading found' : 'No matching permission found') + '</div>').show();
            return;
        }
        $('#sa_permission_search_counter').text(sectionHeadingMode() ? (state.matches.length + (state.matches.length === 1 ? ' section' : ' sections')) : (state.matches.length + ' matches'));
        var html = '';
        for (var i = 0; i < state.matches.length; i += 1) {
            html += '<div class="sa-permission-search-result' + (i === 0 ? ' active' : '') + '" data-index="' + i + '">' + highlight(state.matches[i].text, term) + '</div>';
        }
        $results.html(html).show();
    }

    function moveActive(delta) {
        if (!state.matches.length) { return; }
        state.active += delta;
        if (state.active < 0) { state.active = state.matches.length - 1; }
        if (state.active >= state.matches.length) { state.active = 0; }
        var $items = $('#sa_permission_search_results .sa-permission-search-result');
        $items.removeClass('active').eq(state.active).addClass('active');
        var activeEl = $items.get(state.active);
        if (activeEl && activeEl.scrollIntoView) { activeEl.scrollIntoView({ block: 'nearest', inline: 'nearest' }); }
    }


    function setPanelMinimized(minimized) {
        var explorer = document.getElementById('sa_permission_explorer');
        var button = document.getElementById('sa_permission_toggle_panel');
        var results = $('#sa_permission_search_results');
        state.panelMinimized = !!minimized;
        if (!explorer) { return; }
        explorer.classList.toggle('sa-permission-panel-minimized', state.panelMinimized);
        if (button) {
            button.textContent = state.panelMinimized ? 'Show' : 'Minimize';
            button.setAttribute('aria-expanded', state.panelMinimized ? 'false' : 'true');
            button.setAttribute('title', state.panelMinimized ? 'Show search details' : 'Minimize search details');
        }
        if (state.panelMinimized) { results.hide(); }
    }

    function togglePanelMinimized() {
        setPanelMinimized(!state.panelMinimized);
        if (!state.panelMinimized) {
            window.setTimeout(function () {
                var input = document.getElementById('sa_permission_search_input');
                if (input) { try { input.focus({ preventScroll: true }); } catch (err) { input.focus(); } }
                if (input && input.value) { renderResults(input.value); }
            }, 60);
        }
    }

    function init() {
        $('select#search_settings').each(function () { $(this).next('.select2,.select2-container').remove(); $(this).remove(); });
        $('.sa-permission-explorer').not('#sa_permission_explorer').remove();

        var $input = $('#sa_permission_search_input');
        var $clear = $('#sa_permission_search_clear');
        var $results = $('#sa_permission_search_results');
        var $expandAll = $('#sa_permission_expand_all');
        var $collapseAll = $('#sa_permission_collapse_all');
        var $togglePanel = $('#sa_permission_toggle_panel');
        if (!$input.length) { return; }

        $input.prop('disabled', false).prop('readonly', false).removeAttr('aria-hidden tabindex disabled readonly')
            .css({ display: 'block', visibility: 'visible', opacity: 1, pointerEvents: 'auto', color: '#111827' });

        // Build once after paint. Inline role-page search stays in normal flow and
        // leaves initial focus on the Role Name field. Other pages retain floating behavior.
        window.setTimeout(function () {
            var explorer = document.getElementById('sa_permission_explorer');
            var inlineExplorer = isInlineExplorer(explorer);
            if (!inlineExplorer) {
                detachFloatingExplorer();
                try { window.scrollTo({ top: 0, behavior: 'auto' }); } catch (err0) { window.scrollTo(0, 0); }
            }
            buildFastIndex();
            if (rolePageMode()) { ensureRoleEditorVisible(); }
            else { collapseTopControlSection(); }
            if (!inlineExplorer) {
                try { window.scrollTo({ top: 0, behavior: 'auto' }); } catch (err) { window.scrollTo(0, 0); }
                window.setTimeout(function () { try { $input.focus(); } catch (err2) {} }, 120);
            }
        }, 20);

        $input.off('.saPermissionSearch').on('input.saPermissionSearch', function () {
            var value = this.value;
            if (state.inputTimer) { window.cancelAnimationFrame(state.inputTimer); }
            state.inputTimer = window.requestAnimationFrame(function () { renderResults(value); });
        }).on('keydown.saPermissionSearch', function (event) {
            if (event.key === 'ArrowDown') { event.preventDefault(); moveActive(1); }
            else if (event.key === 'ArrowUp') { event.preventDefault(); moveActive(-1); }
            else if (event.key === 'Enter') { event.preventDefault(); var selected = state.matches[state.active] || state.matches[0]; $results.hide(); selectItem(selected); }
            else if (event.key === 'Escape') { event.preventDefault(); $input.val(''); renderResults(''); $results.hide(); }
        }).on('focus.saPermissionSearch click.saPermissionSearch', function () {
            if (state.panelMinimized) { return; }
            if (this.value) { renderResults(this.value); }
        });

        $clear.off('.saPermissionSearch').on('click.saPermissionSearch', function () {
            $input.val('').focus();
            renderResults('');
            if (rolePageMode()) {
                ensureRoleEditorVisible();
                clearSelectedSectionHighlight();
            } else {
                collapseAllSections();
                collapseTopControlSection();
                try { window.scrollTo({ top: 0, behavior: 'smooth' }); } catch (err) { window.scrollTo(0, 0); }
            }
        });
        $togglePanel.off('.saPermissionSearch').on('click.saPermissionSearch', function (event) {
            event.preventDefault();
            event.stopPropagation();
            togglePanelMinimized();
            return false;
        });
        $expandAll.off('.saPermissionSearch').on('click.saPermissionSearch', function (event) {
            event.preventDefault();
            event.stopPropagation();
            expandAllSections();
            return false;
        });
        $collapseAll.off('.saPermissionSearch').on('click.saPermissionSearch', function (event) {
            event.preventDefault();
            event.stopPropagation();
            collapseAllSections();
            return false;
        });

        // Hard fallback for pages with other scripts intercepting button clicks.
        document.removeEventListener('click', window.__saPermissionExplorerV41CaptureClick || function(){}, true);
        window.__saPermissionExplorerV41CaptureClick = function (event) {
            var btn = event.target && event.target.closest ? event.target.closest('#sa_permission_expand_all,#sa_permission_collapse_all,#sa_permission_toggle_panel') : null;
            if (!btn) { return; }
            event.preventDefault();
            event.stopPropagation();
            if (btn.id === 'sa_permission_expand_all') { expandAllSections(); }
            else if (btn.id === 'sa_permission_collapse_all') { collapseAllSections(); }
            else if (btn.id === 'sa_permission_toggle_panel') { togglePanelMinimized(); }
            if (rolePageMode()) { ensureRoleEditorVisible(); }
        };
        document.addEventListener('click', window.__saPermissionExplorerV41CaptureClick, true);

        $results.off('.saPermissionSearch').on('mouseenter.saPermissionSearch', '.sa-permission-search-result', function () {
            state.active = parseInt($(this).attr('data-index'), 10) || 0;
            $results.find('.active').removeClass('active');
            $(this).addClass('active');
        }).on('mousedown.saPermissionSearch click.saPermissionSearch', '.sa-permission-search-result', function (event) {
            event.preventDefault();
            event.stopPropagation();
            state.active = parseInt($(this).attr('data-index'), 10) || 0;
            var selected = state.matches[state.active];
            $results.hide();
            selectItem(selected);
            $input.focus();
        });

        $(document).off('click.saPermissionSearch').on('click.saPermissionSearch', function (event) {
            if (!$(event.target).closest('#sa_permission_explorer').length) { $results.hide(); }
        });
        $(document).off('shown.bs.tab.saPermissionSearch').on('shown.bs.tab.saPermissionSearch', function () {
            state.built = false;
            state.buildStarted = false;
            state.index = [];
            buildFastIndex();
            if (rolePageMode()) { ensureRoleEditorVisible(); }
            else { collapseAllSections(); }
        });
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); }
    else { init(); }
})(window, document);

/* V054: permission search is navigation only; it must never lock permission controls.
 *
 * Parent module switches still control runtime visibility through the backend,
 * but Super Admin must be able to search, open and change every permission.
 * Older versions disabled children when a guessed "master" checkbox was off,
 * which made the searched Permissions section look disabled and unclickable.
 */
(function (window, document) {
    'use strict';
    var $ = window.jQuery;
    if (!$ || window.__saPermissionControlsUnlockedV54Loaded) { return; }
    window.__saPermissionControlsUnlockedV54Loaded = true;

    function unlock(root) {
        root = root || document;
        var controls = root.querySelectorAll(
            '.card input[type="checkbox"],.box input[type="checkbox"],.panel input[type="checkbox"],' +
            '.module-permission-section input[type="checkbox"],.permission-section input[type="checkbox"],' +
            '.accordion-item input[type="checkbox"]'
        );

        for (var i = 0; i < controls.length; i += 1) {
            controls[i].disabled = false;
            controls[i].removeAttribute('disabled');
            var wrap = controls[i].closest('.checkbox,label,.form-group,.col-sm-3,.col-md-3,.row');
            if (wrap) { wrap.classList.remove('sa-child-permission-disabled'); }
        }

        var sections = root.querySelectorAll('.sa-module-master-disabled');
        for (var s = 0; s < sections.length; s += 1) {
            sections[s].classList.remove('sa-module-master-disabled');
        }
    }

    $(document).on('click focus change', '#sa_permission_search_results,.sa-permission-search-result,input[type="checkbox"]', function () {
        unlock(document);
    });

    $(document).on('submit', 'form', function () {
        // Disabled checkboxes are omitted by the browser. Ensure every visible
        // Super Admin choice is posted without changing its checked state.
        unlock(this);
    });

    $(function () {
        unlock(document);
        window.setTimeout(function () { unlock(document); }, 250);
        window.setTimeout(function () { unlock(document); }, 1200);
    });
})(window, document);


@php
    $blueprintSections = isset($package_permission_blueprint_sections) && is_array($package_permission_blueprint_sections)
        ? $package_permission_blueprint_sections
        : [];
    $blueprint = isset($package_permission_blueprint) && is_array($package_permission_blueprint)
        ? $package_permission_blueprint
        : ['enabled' => 0, 'permissions' => []];
    $blueprintPermissions = isset($blueprint['permissions']) && is_array($blueprint['permissions'])
        ? $blueprint['permissions']
        : [];
    $blueprintEnabled = !empty($blueprint['enabled']);
@endphp

<div class="box box-primary" id="package_permission_blueprint_box" style="margin-top:22px;">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-cubes"></i>
            Package Module, Feature &amp; Permission Blueprint
        </h3>
    </div>
    <div class="box-body">
        <div class="alert alert-info" style="margin-bottom:15px;">
            <strong>Create once and reuse:</strong>
            the selected standalone modules and page permissions are copied automatically
            to every subscription assigned to this package. When enabled, the business
            Manage page shows only permissions included in its package.
        </div>

        <div class="row" style="margin-bottom:14px;">
            <div class="col-md-4">
                <div class="checkbox">
                    <label style="font-weight:700;">
                        <input type="checkbox"
                               id="package_permission_blueprint_enabled"
                               name="package_permission_blueprint_enabled"
                               value="1"
                               {{ $blueprintEnabled ? 'checked' : '' }}>
                        Enable package-controlled permissions
                    </label>
                </div>
            </div>
            <div class="col-md-5">
                <input type="text"
                       id="package_permission_blueprint_search"
                       class="form-control"
                       autocomplete="off"
                       placeholder="Search module, page or permission">
            </div>
            <div class="col-md-3 text-right" style="padding-top:2px;">
                <button type="button" class="btn btn-default btn-sm" id="package_blueprint_select_all">
                    Select All
                </button>
                <button type="button" class="btn btn-default btn-sm" id="package_blueprint_clear_all">
                    Clear All
                </button>
            </div>
        </div>

        <input type="hidden"
               name="package_permission_blueprint_json"
               id="package_permission_blueprint_json"
               value="{{ e(json_encode($blueprintPermissions)) }}">

        @if(empty($blueprintSections))
            <div class="alert alert-warning">
                No additional standalone module permissions were discovered. Legacy/core
                package options above will continue to control the subscription.
            </div>
        @else
            <div id="package_permission_blueprint_sections" class="row">
                @foreach($blueprintSections as $sectionIndex => $section)
                    @php
                        $sectionItems = collect($section['items'] ?? [])->filter(function ($item) {
                            return !empty($item['key']);
                        })->values();
                        $parentItem = $sectionItems->firstWhere('type', 'module');
                        $parentKey = $parentItem['key'] ?? (($section['module_key'] ?? 'module') . '_module');
                    @endphp
                    <div class="col-md-6 package-blueprint-section"
                         data-search="{{ strtolower(($section['title'] ?? '') . ' ' . $sectionItems->pluck('label')->implode(' ') . ' ' . $sectionItems->pluck('key')->implode(' ')) }}">
                        <div class="panel panel-default" style="min-height:180px;">
                            <div class="panel-heading" style="display:flex;align-items:center;justify-content:space-between;">
                                <strong>{{ $section['title'] ?? 'Module' }}</strong>
                                <label style="margin:0;font-weight:600;">
                                    <input type="checkbox"
                                           class="js-package-blueprint-permission js-package-blueprint-parent"
                                           data-key="{{ $parentKey }}"
                                           data-section="{{ $sectionIndex }}"
                                           {{ !empty($blueprintPermissions[$parentKey]) ? 'checked' : '' }}>
                                    Enable Module
                                </label>
                            </div>
                            <div class="panel-body" style="max-height:260px;overflow:auto;">
                                @foreach($sectionItems as $item)
                                    @php
                                        $itemKey = $item['key'];
                                        $isParent = ($item['type'] ?? '') === 'module';
                                    @endphp
                                    @if(!$isParent || $itemKey !== $parentKey)
                                        <div class="checkbox package-blueprint-item"
                                             data-search="{{ strtolower(($item['label'] ?? '') . ' ' . $itemKey) }}">
                                            <label>
                                                <input type="checkbox"
                                                       class="js-package-blueprint-permission js-package-blueprint-child"
                                                       data-key="{{ $itemKey }}"
                                                       data-section="{{ $sectionIndex }}"
                                                       {{ !empty($blueprintPermissions[$itemKey]) ? 'checked' : '' }}>
                                                {{ $item['label'] ?? ucwords(str_replace('_', ' ', $itemKey)) }}
                                            </label>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<script>
(function (window, document) {
    'use strict';

    var root = document.getElementById('package_permission_blueprint_box');
    if (!root || root.getAttribute('data-initialized') === '1') {
        return;
    }
    root.setAttribute('data-initialized', '1');

    var hidden = document.getElementById('package_permission_blueprint_json');
    var enableToggle = document.getElementById('package_permission_blueprint_enabled');
    var controls = root.querySelectorAll('.js-package-blueprint-permission');

    function permissionMap() {
        var map = {};
        for (var i = 0; i < controls.length; i += 1) {
            var key = controls[i].getAttribute('data-key');
            if (key) {
                map[key] = controls[i].checked ? 1 : 0;
            }
        }
        return map;
    }

    function serialize() {
        hidden.value = JSON.stringify(permissionMap());
    }

    function setCheckbox(control, checked) {
        control.checked = !!checked;
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.iCheck) {
            window.jQuery(control).iCheck(checked ? 'check' : 'uncheck');
        }
    }

    function parentFor(section) {
        return root.querySelector('.js-package-blueprint-parent[data-section="' + section + '"]');
    }

    function childrenFor(section) {
        return root.querySelectorAll('.js-package-blueprint-child[data-section="' + section + '"]');
    }

    root.addEventListener('change', function (event) {
        var control = event.target.closest ? event.target.closest('.js-package-blueprint-permission') : null;
        if (!control) {
            return;
        }

        var section = control.getAttribute('data-section');
        if (control.classList.contains('js-package-blueprint-parent')) {
            var children = childrenFor(section);
            for (var i = 0; i < children.length; i += 1) {
                setCheckbox(children[i], control.checked);
            }
        } else if (control.checked) {
            var parent = parentFor(section);
            if (parent) {
                setCheckbox(parent, true);
            }
        }

        serialize();
    });

    var search = document.getElementById('package_permission_blueprint_search');
    if (search) {
        search.addEventListener('input', function () {
            var term = String(this.value || '').toLowerCase().trim();
            var sections = root.querySelectorAll('.package-blueprint-section');
            for (var i = 0; i < sections.length; i += 1) {
                var sectionText = sections[i].getAttribute('data-search') || '';
                var itemMatch = false;
                var items = sections[i].querySelectorAll('.package-blueprint-item');
                for (var j = 0; j < items.length; j += 1) {
                    var matches = !term || (items[j].getAttribute('data-search') || '').indexOf(term) !== -1;
                    items[j].style.display = matches ? '' : 'none';
                    itemMatch = itemMatch || matches;
                }
                sections[i].style.display = (!term || sectionText.indexOf(term) !== -1 || itemMatch) ? '' : 'none';
            }
        });
    }

    var selectAll = document.getElementById('package_blueprint_select_all');
    var clearAll = document.getElementById('package_blueprint_clear_all');
    if (selectAll) {
        selectAll.addEventListener('click', function () {
            for (var i = 0; i < controls.length; i += 1) {
                setCheckbox(controls[i], true);
            }
            if (enableToggle) {
                setCheckbox(enableToggle, true);
            }
            serialize();
        });
    }
    if (clearAll) {
        clearAll.addEventListener('click', function () {
            for (var i = 0; i < controls.length; i += 1) {
                setCheckbox(controls[i], false);
            }
            serialize();
        });
    }

    var form = root.closest('form');
    if (form) {
        form.addEventListener('submit', serialize);
    }
    serialize();
})(window, document);
</script>

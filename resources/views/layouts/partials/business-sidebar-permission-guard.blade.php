@php
    // S591 R2: Manage Side Bar guards sidebar navigation only; page tabs are
    // controlled by their server-side Manage Page permissions.
    $sidebar_guard_disabled_modules = [];
    $sidebar_guard_disabled_permissions = [];
    $sidebar_guard_disabled_role_permissions = [];
    try {
        if (auth()->check()
            && session()->has('user.business_id')
            && class_exists('App\\Utils\\SidebarPermissionUtil')) {
            /*
             * Sidebar visibility is business-specific even when the genuine Super
             * Admin used Login As Business. Functional Super Admin access remains
             * unrestricted by the server middleware.
             */
            $sidebar_guard_disabled_modules = App\Utils\SidebarPermissionUtil::disabledSidebarDescriptorsForBusiness(
                (int) session('user.business_id')
            );
            $sidebar_guard_disabled_permissions = App\Utils\SidebarPermissionUtil::disabledAutomaticPermissionDescriptorsForBusiness(
                (int) session('user.business_id')
            );
            $sidebar_guard_disabled_role_permissions = App\Utils\SidebarPermissionUtil::disabledManagedRolePermissionDescriptorsForCurrentUser(
                (int) session('user.business_id')
            );
            $sidebar_guard_disabled_permissions = array_merge(
                (array) $sidebar_guard_disabled_permissions,
                (array) $sidebar_guard_disabled_role_permissions
            );

            /*
             * IS2353 - curated Purchase page permissions created by Manage Page
             * New. These keys intentionally stay stable even if the Purchase
             * module's route discovery changes. Enforce them in the shared
             * sidebar guard so unticking one page hides only that page.
             */
            $__is2353BusinessId = (int) session('user.business_id');
            $__is2353PurchasePages = [
                ['purchase_dashboard', 'Dashboard', ['purchase-module-live', 'purchase-module-live/dashboard']],
                ['purchase_entries_list', 'List Purchase Entries', ['purchase-module-live/entries', 'purchase/entries']],
                ['purchase_entries_add', 'Add Purchase Entry', ['purchase-module-live/entries/create', 'purchase/entries/create']],
                ['purchase_orders_list', 'List Purchase Orders', ['purchase-module-live/orders', 'purchase/orders']],
                ['purchase_orders_add', 'Add Purchase Order', ['purchase-module-live/orders/create', 'purchase/orders/create']],
                ['purchase_returns_list', 'List Purchase Returns', ['purchase-module-live/returns', 'purchase/returns']],
                ['purchase_returns_add', 'Add Purchase Return', ['purchase-module-live/returns/create', 'purchase/returns/create']],
                ['purchase_bills_list', 'List Purchase Bills', ['purchase-module-live/bills', 'purchase/bills']],
                ['purchase_bills_add', 'Add Purchase Bill', ['purchase-module-live/bills/create', 'purchase/bills/create']],
                ['purchase_supplier_payments_list', 'List Supplier Payments', ['purchase-module-live/supplier-payments', 'purchase/supplier-payments']],
                ['purchase_supplier_payments_add', 'Add Supplier Payment', ['purchase-module-live/supplier-payments/create', 'purchase/supplier-payments/create']],
                ['purchase_reports_dashboard', 'Reports Dashboard', ['purchase-module-live/reports', 'purchase/reports']],
                ['purchase_report_register', 'Purchase Register', ['purchase-module-live/reports/purchase-register', 'purchase/reports/purchase-register']],
                ['purchase_report_payment', 'Purchase Payment Report', ['purchase-module-live/reports/purchase-payment', 'purchase/reports/purchase-payment']],
                ['purchase_report_product_purchase', 'Product Purchase Report', ['purchase-module-live/reports/product-purchase', 'purchase/reports/product-purchase']],
                ['purchase_report_purchase_sale', 'Purchase & Sale Report', ['purchase-module-live/reports/purchase-sale', 'purchase/reports/purchase-sale']],
                ['purchase_report_stock_purchase_sale', 'Stock Purchase/Sale Report', ['purchase-module-live/reports/stock-purchase-sale', 'purchase/reports/stock-purchase-sale']],
                ['purchase_report_supplier_outstanding', 'Supplier Outstanding', ['purchase-module-live/reports/supplier-outstanding', 'purchase/reports/supplier-outstanding']],
                ['purchase_settings_general', 'General Settings', ['purchase-module-live/settings', 'purchase-module-live/settings/general', 'purchase/settings']],
                ['purchase_settings_numbering', 'Numbering Settings', ['purchase-module-live/settings/numbering', 'purchase/settings/numbering']],
                ['purchase_settings_approval', 'Approval Settings', ['purchase-module-live/settings/approval', 'purchase/settings/approval']],
                ['purchase_settings_tax', 'Tax Settings', ['purchase-module-live/settings/tax', 'purchase/settings/tax']],
                ['purchase_settings_supplier', 'Supplier Settings', ['purchase-module-live/settings/supplier', 'purchase/settings/supplier']],
            ];

            if (App\Utils\SidebarPermissionUtil::isManageSidebarEnabled('purchase', $__is2353BusinessId)) {
                foreach ($__is2353PurchasePages as [$__is2353PurchaseKey, $__is2353PurchaseLabel, $__is2353PurchasePaths]) {
                    if (App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled(
                        $__is2353PurchaseKey,
                        $__is2353BusinessId
                    )) {
                        continue;
                    }
                    $sidebar_guard_disabled_permissions[] = [
                        'key' => $__is2353PurchaseKey,
                        'module_key' => 'purchase',
                        'module_labels' => ['Purchase Module', 'Purchase'],
                        'type' => 'page',
                        'match_labels' => [$__is2353PurchaseLabel],
                        'route_paths' => $__is2353PurchasePaths,
                        'selectors' => [],
                    ];
                }
            }

            /*
             * IS2353 - managed-role sidebar reconciliation.
             *
             * Older SidebarPermissionUtil builds returned no descriptor at all
             * when a managed role did NOT have umn.module.<module>.view. That
             * left parent menus such as Accounting visible even though the role
             * page had the module unticked. It also meant a role containing just
             * one page could lose the parent menu completely.
             *
             * Reconcile from the role's own umn.* permissions here. A selected
             * page is sufficient to keep its parent visible; every unselected
             * sibling receives a scoped descriptor. This is generic and works
             * for every module published by AutomaticModuleRegistry.
             */
            $__is2353User = auth()->user();
            $__is2353BusinessId = (int) session('user.business_id');
            $__is2353Privileged = false;
            try {
                $__is2353Privileged = $__is2353User
                    && ($__is2353User->can('superadmin')
                        || $__is2353User->hasRole('Admin#' . $__is2353BusinessId));
            } catch (\Throwable $e) {
                $__is2353Privileged = false;
            }

            $__is2353Allows = static function ($user, string $permission, int $businessId): bool {
                if (!$user || $permission === '') {
                    return false;
                }
                try {
                    if (method_exists($user, 'roleAllowsPermission')) {
                        return (bool) $user->roleAllowsPermission($permission, $businessId);
                    }
                    return (bool) $user->can($permission);
                } catch (\Throwable $e) {
                    return false;
                }
            };

            $__is2353ManagedRole = !$__is2353Privileged
                && $__is2353Allows($__is2353User, 'umn.managed', $__is2353BusinessId);

            if ($__is2353ManagedRole && class_exists('App\Services\AutomaticModuleRegistry')) {
                foreach ((array) App\Services\AutomaticModuleRegistry::manageSections() as $__is2353Section) {
                    $__is2353ModuleKey = App\Services\AutomaticModuleRegistry::normalizeKey(
                        (string) ($__is2353Section['module_key'] ?? '')
                    );
                    if ($__is2353ModuleKey === '') {
                        continue;
                    }

                    $__is2353Items = [];
                    $__is2353AnyPageAllowed = false;
                    foreach ((array) ($__is2353Section['items'] ?? []) as $__is2353Item) {
                        $__is2353Type = strtolower((string) ($__is2353Item['type'] ?? ''));
                        if (!in_array($__is2353Type, ['page', 'tab'], true)) {
                            continue;
                        }
                        $__is2353PageKey = App\Services\AutomaticModuleRegistry::normalizeKey(
                            (string) ($__is2353Item['key'] ?? '')
                        );
                        if ($__is2353PageKey === '') {
                            continue;
                        }
                        $__is2353PageAllowed = $__is2353Allows(
                            $__is2353User,
                            'umn.page.' . $__is2353PageKey . '.view',
                            $__is2353BusinessId
                        );
                        $__is2353AnyPageAllowed = $__is2353AnyPageAllowed || $__is2353PageAllowed;
                        $__is2353Items[] = [$__is2353Item, $__is2353PageKey, $__is2353PageAllowed];
                    }

                    $__is2353ModuleAllowed = $__is2353Allows(
                        $__is2353User,
                        'umn.module.' . $__is2353ModuleKey . '.view',
                        $__is2353BusinessId
                    ) || $__is2353AnyPageAllowed;

                    if (!$__is2353ModuleAllowed) {
                        $__is2353ModuleMeta = App\Services\AutomaticModuleRegistry::findSidebar($__is2353ModuleKey);
                        $__is2353Labels = [
                            (string) ($__is2353Section['title'] ?? $__is2353ModuleKey),
                            str_replace('_', ' ', $__is2353ModuleKey),
                        ];
                        if (is_array($__is2353ModuleMeta)) {
                            $__is2353Labels[] = (string) ($__is2353ModuleMeta['title'] ?? '');
                            $__is2353Labels = array_merge($__is2353Labels, (array) ($__is2353ModuleMeta['aliases'] ?? []));
                        }
                        $sidebar_guard_disabled_modules[] = [
                            'key' => $__is2353ModuleKey,
                            'title' => (string) ($__is2353Section['title'] ?? $__is2353ModuleKey),
                            'match_labels' => array_values(array_unique(array_filter($__is2353Labels))),
                            'route_prefixes' => [],
                        ];
                        continue;
                    }

                    foreach ($__is2353Items as [$__is2353Item, $__is2353PageKey, $__is2353PageAllowed]) {
                        if ($__is2353PageAllowed) {
                            continue;
                        }
                        $sidebar_guard_disabled_permissions[] = [
                            'key' => $__is2353PageKey,
                            'module_key' => $__is2353ModuleKey,
                            'module_labels' => [
                                (string) ($__is2353Section['title'] ?? $__is2353ModuleKey),
                                str_replace('_', ' ', $__is2353ModuleKey),
                            ],
                            'type' => strtolower((string) ($__is2353Item['type'] ?? 'page')),
                            'match_labels' => [
                                (string) ($__is2353Item['label'] ?? $__is2353PageKey),
                            ],
                            'route_paths' => array_values(array_unique(array_filter(array_map(
                                static fn ($path): string => trim((string) $path, '/ '),
                                (array) ($__is2353Item['route_paths'] ?? [])
                            )))),
                            'selectors' => array_values(array_unique(array_filter(array_map(
                                'strval',
                                (array) ($__is2353Item['selectors'] ?? [])
                            )))),
                        ];
                    }
                }
            }

            /*
             * Stock Reports compatibility bridge.
             *
             * Some established business roles use the functional permissions
             * `stock_report.view` / `stock_transaction_report`, while newer
             * UserManagementNew roles use `umn.module.stock_reports.view`.
             * SidebarPermissionUtil::disabledSidebarDescriptorsForBusiness()
             * also applies the newer managed-role module gate and can therefore
             * classify Stock Reports as disabled for an older/compatible role
             * even though Manage Side Bar is ON and the user has a valid Stock
             * Reports permission. The browser guard then hides the menu after
             * Blade has rendered it, which is why only some users lose it.
             *
             * Keep Manage Side Bar authoritative: remove only the Stock Reports
             * *parent* descriptor when its business switch is ON and the current
             * user's assigned role allows one of the supported Stock Reports
             * permissions. Child/page descriptors remain untouched, so Manage
             * Page and role page permissions continue to control child links.
             */
            $__stockReportsBusinessId = (int) session('user.business_id');
            $__stockReportsParentEnabled = App\Utils\SidebarPermissionUtil::isManageSidebarEnabled(
                'stock_reports',
                $__stockReportsBusinessId
            );
            $__stockReportsRoleAllowed = false;

            if ($__stockReportsParentEnabled && auth()->check()) {
                $__stockReportsUser = auth()->user();
                try {
                    $__stockReportsRoleAllowed = $__stockReportsUser->can('superadmin')
                        || $__stockReportsUser->hasRole('Admin#' . $__stockReportsBusinessId);
                } catch (\Throwable $e) {
                    $__stockReportsRoleAllowed = false;
                }

                if (!$__stockReportsRoleAllowed) {
                    foreach ([
                        'stock_report.view',
                        'stock_transaction_report',
                        'umn.module.stock_reports.view',
                    ] as $__stockReportsPermission) {
                        try {
                            if (method_exists($__stockReportsUser, 'roleAllowsPermission')) {
                                $__stockReportsRoleAllowed = $__stockReportsUser->roleAllowsPermission(
                                    $__stockReportsPermission,
                                    $__stockReportsBusinessId
                                );
                            } else {
                                $__stockReportsRoleAllowed = $__stockReportsUser->can($__stockReportsPermission);
                            }
                        } catch (\Throwable $e) {
                            $__stockReportsRoleAllowed = false;
                        }

                        if ($__stockReportsRoleAllowed) {
                            break;
                        }
                    }
                }
            }

            if ($__stockReportsParentEnabled && $__stockReportsRoleAllowed) {
                $sidebar_guard_disabled_modules = array_values(array_filter(
                    (array) $sidebar_guard_disabled_modules,
                    static function ($descriptor): bool {
                        $key = is_array($descriptor) ? ($descriptor['key'] ?? '') : '';
                        try {
                            $key = App\Services\AutomaticModuleRegistry::normalizeKey((string) $key);
                        } catch (\Throwable $e) {
                            $key = strtolower(trim((string) $key));
                            $key = trim((string) preg_replace('/[^a-z0-9]+/', '_', $key), '_');
                        }

                        return !in_array($key, ['stock_reports', 'stockreports', 'stock_report'], true);
                    }
                ));
            }
        }
    } catch (\Throwable $e) {
        $sidebar_guard_disabled_modules = [];
        $sidebar_guard_disabled_permissions = [];
        $sidebar_guard_disabled_role_permissions = [];
    }
@endphp

@if(!empty($sidebar_guard_disabled_modules) || !empty($sidebar_guard_disabled_permissions))
<style>
    .business-sidebar-disabled-link,
    .business-sidebar-disabled-parent,
    .business-page-permission-disabled {
        display: none !important;
    }
</style>
<script>
(function () {
    'use strict';

    var disabledModules = @json($sidebar_guard_disabled_modules);
    var disabledPermissions = @json($sidebar_guard_disabled_permissions);
    disabledModules = Array.isArray(disabledModules) ? disabledModules : [];
    disabledPermissions = Array.isArray(disabledPermissions) ? disabledPermissions : [];
    if (!disabledModules.length && !disabledPermissions.length) {
        return;
    }

    var disabledPrefixes = [];
    var disabledSelectors = [];
    var disabledKeys = Object.create(null);
    var disabledLabels = Object.create(null);
    var disabledPermissionLabels = Object.create(null);
    var disabledPermissionScopes = [];
    var blockedMessage = 'This module/page is disabled by Manage Side Bar, Manage Page New, or the assigned role.';

    function normalizeLabel(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function addDisabledLabel(value) {
        value = normalizeLabel(value);
        if (value) {
            disabledLabels[value] = true;
        }
    }

    disabledModules.forEach(function (module) {
        var canonicalKey = normalizeLabel(module.key);
        if (canonicalKey) {
            disabledKeys[canonicalKey] = true;
        }

        /*
         * Manage Side Bar is the authoritative parent switch. Always register
         * its canonical key/title for parent-menu matching, even when shared
         * aliases or route prefixes are intentionally removed as ambiguous.
         */
        addDisabledLabel(module.key);
        addDisabledLabel(module.title);

        (module.route_prefixes || []).forEach(function (prefix) {
            prefix = String(prefix || '').toLowerCase().replace(/^\/+|\/+$/g, '');
            if (prefix && disabledPrefixes.indexOf(prefix) === -1) {
                disabledPrefixes.push(prefix);
            }
        });

        (module.match_labels || []).forEach(addDisabledLabel);
    });

    disabledPermissions.forEach(function (permission) {
        (permission.route_paths || []).forEach(function (path) {
            path = String(path || '').toLowerCase().replace(/^\/+|\/+$/g, '');
            if (path && disabledPrefixes.indexOf(path) === -1) {
                disabledPrefixes.push(path);
            }
        });
        (permission.selectors || []).forEach(function (selector) {
            selector = String(selector || '').trim();
            if (selector && disabledSelectors.indexOf(selector) === -1) {
                disabledSelectors.push(selector);
            }
        });

        var scopeModule = normalizeLabel(permission.module_key || '');
        var scopeModuleLabels = (permission.module_labels || []).map(normalizeLabel).filter(Boolean);
        var scopeLabels = (permission.match_labels || []).map(normalizeLabel).filter(Boolean);

        if (scopeModule || scopeModuleLabels.length) {
            if (scopeLabels.length) {
                disabledPermissionScopes.push({
                    moduleKey: scopeModule,
                    moduleLabels: scopeModuleLabels,
                    labels: scopeLabels
                });
            }
        } else {
            scopeLabels.forEach(function (label) {
                disabledPermissionLabels[label] = true;
            });
        }
    });

    function normalizePath(url) {
        if (!url || url === '#' || /^javascript:|^mailto:|^tel:/i.test(url)) {
            return '';
        }

        try {
            var parsed = new URL(url, window.location.origin);
            if (parsed.origin !== window.location.origin) {
                return '';
            }
            return String(parsed.pathname || '')
                .replace(/^\/+|\/+$/g, '')
                .toLowerCase();
        } catch (e) {
            return String(url)
                .split('?')[0]
                .split('#')[0]
                .replace(/^\/+|\/+$/g, '')
                .toLowerCase();
        }
    }

    function normalizeRoutePath(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/_/g, '-')
            .replace(/-+/g, '-')
            .replace(/^\/+|\/+$/g, '');
    }

    function pathMatchesPrefix(path, prefix) {
        path = normalizeRoutePath(path);
        prefix = normalizeRoutePath(prefix);

        if (prefix.indexOf('{') !== -1) {
            var pattern = prefix
                .replace(/[.+?^$()|[\]\\]/g, '\\$&')
                .replace(/\{[^}]+\}/g, '[^/]+');
            return !!path && new RegExp('^' + pattern + '(?:/|$)').test(path);
        }

        return !!path && !!prefix
            && (path === prefix || path.indexOf(prefix + '/') === 0);
    }

    function isBlockedUrl(url) {
        var path = normalizePath(url);
        if (!path) {
            return false;
        }

        for (var i = 0; i < disabledPrefixes.length; i++) {
            if (pathMatchesPrefix(path, disabledPrefixes[i])) {
                return true;
            }
        }

        return false;
    }

    function elementUrl(element) {
        return element.getAttribute('href')
            || element.getAttribute('data-href')
            || element.getAttribute('data-url')
            || element.getAttribute('data-action')
            || '';
    }

    function directAnchor(item) {
        var children = item && item.children ? item.children : [];
        for (var i = 0; i < children.length; i++) {
            if (String(children[i].tagName || '').toLowerCase() === 'a') {
                return children[i];
            }
        }
        return null;
    }

    function directMenuLabel(item) {
        var anchor = directAnchor(item);
        if (!anchor) {
            return '';
        }

        var preferred = anchor.querySelector('[data-sidebar-title], .menu-title, .title, span, p');
        return normalizeLabel(preferred ? preferred.textContent : anchor.textContent);
    }

    function declaredModuleKeys(item) {
        return [
            item.getAttribute('data-sidebar-module'),
            item.getAttribute('data-auto-module'),
            item.getAttribute('data-module'),
            item.getAttribute('data-module-key'),
            item.getAttribute('data-sidebar-key')
        ].map(normalizeLabel).filter(Boolean);
    }

    function isExplicitlyDisabled(item) {
        var keys = declaredModuleKeys(item);
        for (var i = 0; i < keys.length; i++) {
            if (disabledKeys[keys[i]]) {
                return true;
            }
        }

        /*
         * Text matching is only a compatibility fallback for a real parent menu
         * with child links. Single links such as the core POS page must never be
         * mistaken for a similarly named standalone module.
         */
        if (item.querySelector('.collapse, .treeview-menu, ul')) {
            return !!disabledLabels[directMenuLabel(item)];
        }

        return false;
    }

    function closestMenuParent(element) {
        var node = element && element.parentElement ? element.parentElement : null;
        while (node) {
            if (node.matches && node.matches('li, [data-sidebar-module], [data-auto-module], [data-module], [data-module-key], [data-sidebar-key]')) {
                if (node.querySelector && node.querySelector('.collapse, .treeview-menu, ul')) {
                    return node;
                }
            }
            node = node.parentElement;
        }
        return null;
    }

    function isScopedPermissionLabelBlocked(element, label) {
        if (!label || !disabledPermissionScopes.length) {
            return false;
        }

        var parent = closestMenuParent(element);
        if (!parent) {
            return false;
        }

        var parentKeys = declaredModuleKeys(parent);
        var parentLabel = directMenuLabel(parent);

        for (var i = 0; i < disabledPermissionScopes.length; i++) {
            var scope = disabledPermissionScopes[i];
            if (scope.labels.indexOf(label) === -1) {
                continue;
            }

            var moduleMatches = false;
            if (scope.moduleKey && parentKeys.indexOf(scope.moduleKey) !== -1) {
                moduleMatches = true;
            }
            if (!moduleMatches && parentLabel) {
                if (scope.moduleKey && parentLabel === scope.moduleKey) {
                    moduleMatches = true;
                }
                if (!moduleMatches && scope.moduleLabels.indexOf(parentLabel) !== -1) {
                    moduleMatches = true;
                }
            }

            if (moduleMatches) {
                return true;
            }
        }

        return false;
    }

    function updateLink(element) {
        var blocked = isBlockedUrl(elementUrl(element));
        if (!blocked) {
            var label = normalizeLabel(element.textContent || '');
            blocked = !!label && (
                !!disabledPermissionLabels[label]
                || isScopedPermissionLabelBlocked(element, label)
            );
        }
        element.classList.toggle('business-sidebar-disabled-link', blocked);

        if (blocked) {
            element.setAttribute('data-sidebar-permission-blocked', '1');
            element.setAttribute('aria-hidden', 'true');
            element.setAttribute('title', blockedMessage);
        } else {
            element.removeAttribute('data-sidebar-permission-blocked');
            element.removeAttribute('aria-hidden');
            if (element.getAttribute('title') === blockedMessage) {
                element.removeAttribute('title');
            }
        }
    }

    function updateMenuParent(parent) {
        var explicitlyDisabled = isExplicitlyDisabled(parent);
        var direct = directAnchor(parent);
        var directBlocked = direct ? isBlockedUrl(elementUrl(direct)) : false;
        var links = parent.querySelectorAll('a[href], a[data-href], a[data-url], a[data-action]');
        var actionable = 0;
        var blocked = 0;

        for (var i = 0; i < links.length; i++) {
            var url = elementUrl(links[i]);
            if (!url || url === '#' || /^javascript:/i.test(url)) {
                continue;
            }
            actionable++;
            if (links[i].getAttribute('data-sidebar-permission-blocked') === '1') {
                blocked++;
            }
        }

        var allLinksBlocked = actionable > 0 && blocked === actionable;
        parent.classList.toggle(
            'business-sidebar-disabled-parent',
            explicitlyDisabled || directBlocked || allLinksBlocked
        );

        if (explicitlyDisabled || directBlocked || allLinksBlocked) {
            parent.setAttribute('aria-hidden', 'true');
        } else {
            parent.removeAttribute('aria-hidden');
        }
    }

    function protectPageSelectors() {
        for (var s = 0; s < disabledSelectors.length; s++) {
            try {
                var matches = document.querySelectorAll(disabledSelectors[s]);
                for (var i = 0; i < matches.length; i++) {
                    matches[i].classList.add('business-page-permission-disabled');
                    matches[i].setAttribute('aria-hidden', 'true');
                }
            } catch (ignore) {
                // A module may publish a selector unsupported by an older browser.
                // Server-side middleware still enforces the same permission.
            }
        }
    }

    function protectSidebar() {
        var roots = document.querySelectorAll('#accordionSidebar, aside.main-sidebar, .main-sidebar, ul.sidebar-menu');
        for (var r = 0; r < roots.length; r++) {
            var links = roots[r].querySelectorAll('a[href], a[data-href], a[data-url], a[data-action], [data-sidebar-module-link]');
            for (var i = 0; i < links.length; i++) {
                updateLink(links[i]);
            }

            var parents = roots[r].querySelectorAll('li, [data-sidebar-module]');
            for (var j = 0; j < parents.length; j++) {
                updateMenuParent(parents[j]);
            }
        }
        protectPageSelectors();
    }

    document.addEventListener('click', function (event) {
        var target = event.target.closest('[data-sidebar-permission-blocked="1"]');
        if (!target) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
    }, true);

    function protectAddedSidebarNode(node, root) {
        if (!node || node.nodeType !== 1 || !root) {
            return;
        }

        var links = [];
        if (node.matches && node.matches('a[href], a[data-href], a[data-url], a[data-action], [data-sidebar-module-link]')) {
            links.push(node);
        }
        if (node.querySelectorAll) {
            Array.prototype.push.apply(
                links,
                node.querySelectorAll('a[href], a[data-href], a[data-url], a[data-action], [data-sidebar-module-link]')
            );
        }
        for (var i = 0; i < links.length; i++) {
            updateLink(links[i]);
        }

        var parents = [];
        if (node.matches && node.matches('li, [data-sidebar-module]')) {
            parents.push(node);
        }
        if (node.querySelectorAll) {
            Array.prototype.push.apply(parents, node.querySelectorAll('li, [data-sidebar-module]'));
        }
        for (var j = 0; j < parents.length; j++) {
            updateMenuParent(parents[j]);
        }

        /* Re-evaluate only the nearest existing parent, not the whole sidebar. */
        var nearestParent = node.parentElement && node.parentElement.closest
            ? node.parentElement.closest('li, [data-sidebar-module]')
            : null;
        if (nearestParent && root.contains(nearestParent)) {
            updateMenuParent(nearestParent);
        }
    }

    function protectAddedPageNode(node) {
        if (!node || node.nodeType !== 1 || !disabledSelectors.length) {
            return;
        }
        for (var s = 0; s < disabledSelectors.length; s++) {
            try {
                if (node.matches && node.matches(disabledSelectors[s])) {
                    node.classList.add('business-page-permission-disabled');
                    node.setAttribute('aria-hidden', 'true');
                }
                if (node.querySelectorAll) {
                    var matches = node.querySelectorAll(disabledSelectors[s]);
                    for (var i = 0; i < matches.length; i++) {
                        matches[i].classList.add('business-page-permission-disabled');
                        matches[i].setAttribute('aria-hidden', 'true');
                    }
                }
            } catch (ignore) {}
        }
    }

    // Initial pass once only. Later changes are handled incrementally below.
    protectSidebar();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', protectSidebar, {once: true});
    }

    if (window.MutationObserver) {
        /*
         * Observe each sidebar itself instead of document.documentElement.
         * Busy DataTables/pages can mutate thousands of unrelated nodes; the old
         * full-page observer rescanned every sidebar and made module clicks feel
         * delayed.  This observer touches only newly inserted sidebar nodes.
         */
        var sidebarRoots = document.querySelectorAll('#accordionSidebar, aside.main-sidebar, .main-sidebar, ul.sidebar-menu');
        for (var r = 0; r < sidebarRoots.length; r++) {
            (function (root) {
                var sidebarObserver = new MutationObserver(function (mutations) {
                    for (var m = 0; m < mutations.length; m++) {
                        var added = mutations[m].addedNodes || [];
                        for (var a = 0; a < added.length; a++) {
                            protectAddedSidebarNode(added[a], root);
                        }
                    }
                });
                sidebarObserver.observe(root, {childList: true, subtree: true});
            })(sidebarRoots[r]);
        }

        /* Dynamic page controls still get hidden, but without rescanning sidebars. */
        if (disabledSelectors.length && document.body) {
            var pageObserver = new MutationObserver(function (mutations) {
                for (var m = 0; m < mutations.length; m++) {
                    var added = mutations[m].addedNodes || [];
                    for (var a = 0; a < added.length; a++) {
                        protectAddedPageNode(added[a]);
                    }
                }
            });
            pageObserver.observe(document.body, {childList: true, subtree: true});
        }
    }
})();
</script>
@endif

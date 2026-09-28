<?php

namespace Modules\UserManagementNew\Services;

use App\Services\AutomaticModuleRegistry;
use App\Utils\SidebarPermissionUtil;
use Symfony\Component\HttpFoundation\Response;

/**
 * S754 - managed-role sidebar visibility guard.
 *
 * UserManagementNew's umn.* contract is authoritative for roles carrying the
 * umn.managed marker. Route access was already denied by
 * EnforceManagedRolePermissions, but a number of legacy sidebar partials only
 * honour their own historical permission checks / Manage Side Bar state. That
 * allowed links such as Finance, Help Guide and User Management New to remain
 * visible even when the managed role only allowed Petro PD.
 *
 * This service applies the managed-role contract to the rendered sidebar too,
 * without modifying any other module. Modern sidebar partials are hidden
 * immediately through their data-sidebar-module / data-sidebar-page keys. A
 * small title-based fallback handles older parent menu items that have not yet
 * been upgraded to the standard data attribute. Security never depends on this
 * display filter: direct requests are still blocked server-side by the route
 * middleware.
 */
class ManagedSidebarVisibilityService
{
    public function __construct(private RolePermissionService $permissions)
    {
    }

    public function apply(Response $response, $user, int $businessId): Response
    {
        if ($businessId <= 0 || !$this->isHtmlResponse($response)) {
            return $response;
        }

        if (SidebarPermissionUtil::hasSuperAdminBypass()
            || !$this->permissions->userUsesManagedRole($user, $businessId)) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || stripos($html, '</body>') === false) {
            return $response;
        }

        $catalogue = $this->catalogue($businessId);
        if ($catalogue['modules'] === []) {
            return $response;
        }

        $deniedModuleKeys = [];
        $deniedModuleLabels = [];
        $allowedModuleKeys = [];
        $deniedPages = [];
        $deniedPageRoutes = [];
        $deniedPageSelectors = [];

        foreach ($catalogue['modules'] as $moduleKey => $module) {
            $moduleViewAllowed = $this->permissions->managedUserAllows(
                $user,
                $businessId,
                $this->permissions->modulePermission($moduleKey, 'view')
            );

            /*
             * IS2347 hierarchy rule:
             * - selected child page => show the module parent + that page;
             * - unselected child pages remain hidden;
             * - no selected module View and no selected child => hide parent.
             *
             * New saves derive module View server-side. The child check below is
             * also kept as a compatibility safety net for roles saved by older
             * builds, so users do not have to recreate those roles.
             */
            $pageAllowances = [];
            $hasAllowedChild = false;
            foreach ((array) ($module['pages'] ?? []) as $pageKey => $pageMeta) {
                $pageAllowed = $this->permissions->managedUserAllows(
                    $user,
                    $businessId,
                    $this->permissions->pagePermission((string) $pageKey)
                );
                $pageAllowances[(string) $pageKey] = $pageAllowed;
                $hasAllowedChild = $hasAllowedChild || $pageAllowed;
            }

            $allowed = $moduleViewAllowed || $hasAllowedChild;

            if ($allowed) {
                $allowedModuleKeys[] = $moduleKey;
            } else {
                $deniedModuleKeys[] = $moduleKey;
                foreach ((array) ($module['labels'] ?? []) as $label) {
                    $label = $this->normaliseLabel((string) $label);
                    if ($label !== '') {
                        $deniedModuleLabels[$label] = true;
                    }
                }
            }

            foreach ((array) ($module['pages'] ?? []) as $pageKey => $pageMeta) {
                if (!($pageAllowances[(string) $pageKey] ?? false)) {
                    $deniedPages[(string) $pageKey] = true;
                    foreach ((array) ($pageMeta['route_paths'] ?? []) as $routePath) {
                        $routePath = trim((string) $routePath, '/ ');
                        if ($routePath !== '') {
                            $deniedPageRoutes[$routePath] = true;
                        }
                    }
                    foreach ((array) ($pageMeta['selectors'] ?? []) as $selector) {
                        $selector = trim((string) $selector);
                        if ($selector !== '') {
                            $deniedPageSelectors[$selector] = true;
                        }
                    }
                }
            }
        }

        $deniedModuleKeys = array_values(array_unique(array_filter($deniedModuleKeys)));
        $allowedModuleKeys = array_values(array_unique(array_filter($allowedModuleKeys)));

        $style = $this->styleBlock($deniedModuleKeys, array_keys($deniedPages));
        $script = $this->scriptBlock(
            $allowedModuleKeys,
            array_keys($deniedModuleLabels),
            array_keys($deniedPages),
            array_keys($deniedPageRoutes),
            array_keys($deniedPageSelectors)
        );

        if ($style !== '') {
            if (stripos($html, '</head>') !== false) {
                $html = preg_replace('/<\/head>/i', $style . "\n</head>", $html, 1) ?? $html;
            } else {
                $html = $style . "\n" . $html;
            }
        }

        $html = preg_replace('/<\/body>/i', $script . "\n</body>", $html, 1) ?? $html;
        $response->setContent($html);

        return $response;
    }

    /**
     * Build one lightweight catalogue of sidebar parents and enabled child pages.
     * Only Manage Side Bar / Manage Page-enabled entries participate, matching
     * the Role screen hierarchy.
     *
     * @return array{modules: array<string,array{labels:array<int,string>,pages:array<string,string>}>}
     */
    private function catalogue(int $businessId): array
    {
        $modules = [];

        try {
            foreach ((array) AutomaticModuleRegistry::sidebarModules() as $catalogKey => $module) {
                $key = AutomaticModuleRegistry::normalizeKey(
                    (string) ($module['key'] ?? $catalogKey)
                );
                if ($key === '' || !SidebarPermissionUtil::isManageSidebarEnabled($key, $businessId)) {
                    continue;
                }

                $modules[$key] = [
                    'labels' => array_values(array_unique(array_filter([
                        (string) ($module['title'] ?? ''),
                        (string) ($module['label'] ?? ''),
                        str_replace('_', ' ', $key),
                    ]))),
                    'pages' => [],
                ];
            }

            foreach ((array) AutomaticModuleRegistry::manageSections() as $section) {
                $key = AutomaticModuleRegistry::normalizeKey(
                    (string) ($section['module_key'] ?? '')
                );
                if ($key === '' || !SidebarPermissionUtil::isManageSidebarEnabled($key, $businessId)) {
                    continue;
                }

                if (!isset($modules[$key])) {
                    $modules[$key] = [
                        'labels' => [],
                        'pages' => [],
                    ];
                }

                $title = trim((string) ($section['title'] ?? ''));
                if ($title !== '') {
                    $modules[$key]['labels'][] = $title;
                }

                foreach ((array) ($section['items'] ?? []) as $item) {
                    $type = strtolower((string) ($item['type'] ?? ''));
                    if (!in_array($type, ['page', 'tab'], true)) {
                        continue;
                    }

                    $pageKey = AutomaticModuleRegistry::normalizeKey((string) ($item['key'] ?? ''));
                    if ($pageKey === ''
                        || !SidebarPermissionUtil::isAutomaticPermissionEnabled($pageKey, $businessId)) {
                        continue;
                    }

                    $modules[$key]['pages'][$pageKey] = [
                        'label' => (string) ($item['label'] ?? $pageKey),
                        'route_paths' => array_values(array_unique(array_filter(array_map(
                            static fn ($path): string => trim((string) $path, '/ '),
                            (array) ($item['route_paths'] ?? [])
                        )))),
                        'selectors' => array_values(array_unique(array_filter(array_map(
                            'strval',
                            (array) ($item['selectors'] ?? [])
                        )))),
                    ];
                }
            }

            /*
             * v7: keep this post-response role guard on the same effective
             * page catalogue as Manage Page, Role and the automatic sidebar.
             * Without this, runtime-discovered pages (Airline New / Tea Estate)
             * are absent here even though the server-side menu knows about them.
             */
            foreach ($modules as $key => &$moduleMeta) {
                $registryModule = AutomaticModuleRegistry::findSidebar($key)
                    ?: AutomaticModuleRegistry::find($key)
                    ?: [];

                try {
                    $effectiveItems = AutomaticModuleRegistry::effectivePermissionItems(
                        $key,
                        is_array($registryModule) ? $registryModule : []
                    );
                } catch (\Throwable $effectiveException) {
                    $effectiveItems = [];
                }

                foreach ((array) $effectiveItems as $item) {
                    $type = strtolower((string) ($item['type'] ?? ''));
                    if (!in_array($type, ['page', 'tab'], true)) {
                        continue;
                    }

                    $pageKey = AutomaticModuleRegistry::normalizeKey(
                        (string) ($item['key'] ?? '')
                    );
                    if ($pageKey === ''
                        || !SidebarPermissionUtil::isAutomaticPermissionEnabled($pageKey, $businessId)) {
                        continue;
                    }

                    $moduleMeta['pages'][$pageKey] = [
                        'label' => (string) ($item['label'] ?? $pageKey),
                        'route_paths' => array_values(array_unique(array_filter(array_map(
                            static fn ($path): string => trim((string) $path, '/ '),
                            (array) ($item['route_paths'] ?? [])
                        )))),
                        'selectors' => array_values(array_unique(array_filter(array_map(
                            'strval',
                            (array) ($item['selectors'] ?? [])
                        )))),
                    ];
                }
            }
            unset($moduleMeta);
        } catch (\Throwable $e) {
            // Sidebar protection must never turn a valid application page into 500.
            report($e);
        }

        // The module itself can be rendered from its local partial even on an
        // older registry cache. Keep its parent in the catalogue explicitly so
        // a Petro-PD-only role cannot see User Management New by accident.
        if (!isset($modules['user_management_new'])
            && SidebarPermissionUtil::isManageSidebarEnabled('user_management_new', $businessId)) {
            $modules['user_management_new'] = [
                'labels' => ['User Management New', 'User Management'],
                'pages' => [
                    'user_management_new_roles' => [
                        'label' => 'Roles & Permissions',
                        'route_paths' => ['user-management-new/roles'],
                        'selectors' => [],
                    ],
                    'user_management_new_users' => [
                        'label' => 'Users',
                        'route_paths' => ['user-management-new/users'],
                        'selectors' => [],
                    ],
                ],
            ];
        }

        foreach ($modules as &$module) {
            $module['labels'] = array_values(array_unique(array_filter(array_map(
                static fn ($label): string => trim((string) $label),
                (array) ($module['labels'] ?? [])
            ))));
        }
        unset($module);

        return ['modules' => $modules];
    }

    private function isHtmlResponse(Response $response): bool
    {
        if ($response->isRedirection()
            || $response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse
            || $response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        if ($contentType !== '' && !str_contains($contentType, 'text/html')) {
            return false;
        }

        return true;
    }

    private function styleBlock(array $deniedModuleKeys, array $deniedPageKeys): string
    {
        $selectors = [];

        foreach ($deniedModuleKeys as $key) {
            $escaped = $this->cssAttributeValue($key);
            $selectors[] = '[data-sidebar-module="' . $escaped . '"]';
        }

        foreach ($deniedPageKeys as $key) {
            $escaped = $this->cssAttributeValue($key);
            $selectors[] = '[data-sidebar-page="' . $escaped . '"]';
            $selectors[] = '[data-sidebar-permission="' . $escaped . '"]';
            $selectors[] = '[data-permission-key="' . $escaped . '"]';
        }

        $selectors = array_values(array_unique($selectors));
        if ($selectors === []) {
            return '';
        }

        return '<style id="umn-s754-sidebar-guard">' . implode(',', $selectors)
            . '{display:none!important;visibility:hidden!important;}</style>';
    }

    private function scriptBlock(
        array $allowedModuleKeys,
        array $deniedModuleLabels,
        array $deniedPageKeys,
        array $deniedPageRoutes,
        array $deniedPageSelectors
    ): string {
        $payload = json_encode([
            'allowedModules' => array_values($allowedModuleKeys),
            'deniedLabels' => array_values($deniedModuleLabels),
            'deniedPages' => array_values($deniedPageKeys),
            'deniedRoutes' => array_values($deniedPageRoutes),
            'deniedSelectors' => array_values($deniedPageSelectors),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        if ($payload === false) {
            return '';
        }

        $script = <<<'HTML'
<script id="umn-s754-sidebar-guard-script">
(function () {
    'use strict';
    var acl = __UMN_ACL__;
    var allowed = Object.create(null);
    var deniedLabels = Object.create(null);
    var deniedPages = Object.create(null);

    function normalise(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/&/g, ' and ')
            .replace(/[^a-z0-9]+/g, ' ')
            .replace(/^\s+|\s+$/g, '')
            .replace(/\s+/g, ' ');
    }

    function hide(node) {
        if (!node) return;
        node.style.setProperty('display', 'none', 'important');
        node.setAttribute('aria-hidden', 'true');
        node.setAttribute('data-umn-managed-hidden', '1');
    }

    (acl.allowedModules || []).forEach(function (key) {
        allowed[normalise(String(key).replace(/_/g, ' '))] = true;
    });
    (acl.deniedLabels || []).forEach(function (label) {
        deniedLabels[normalise(label)] = true;
    });
    (acl.deniedPages || []).forEach(function (key) {
        deniedPages[normalise(String(key).replace(/_/g, ' '))] = true;
    });

    // Current sidebar standard: authoritative parent key on the menu wrapper.
    document.querySelectorAll('[data-sidebar-module]').forEach(function (node) {
        var key = normalise((node.getAttribute('data-sidebar-module') || '').replace(/_/g, ' '));
        if (key && !allowed[key]) {
            hide(node);
        }
    });

    // Current page/tab standard. Some modules use one of these historical
    // attribute names, so support all three without requiring changes there.
    document.querySelectorAll('[data-sidebar-page],[data-sidebar-permission],[data-permission-key]').forEach(function (node) {
        var key = node.getAttribute('data-sidebar-page')
            || node.getAttribute('data-sidebar-permission')
            || node.getAttribute('data-permission-key')
            || '';
        key = normalise(String(key).replace(/_/g, ' '));
        if (key && deniedPages[key]) {
            hide(node.closest('li') || node);
        }
    });

    // IS2347: legacy child links may have no data-sidebar-page attribute.
    // Hide them by the exact registered route path/selectors supplied by Manage
    // Page. This makes sidebar display match the selected Role permissions even
    // for older standalone module sidebars.
    (acl.deniedSelectors || []).forEach(function (selector) {
        if (!selector) return;
        try {
            document.querySelectorAll(selector).forEach(function (node) {
                hide(node.closest('li') || node);
            });
        } catch (e) {}
    });

    var deniedRoutes = (acl.deniedRoutes || []).map(function (path) {
        return String(path || '').replace(/^\/+|\/+$/g, '').toLowerCase();
    }).filter(Boolean);
    if (deniedRoutes.length) {
        document.querySelectorAll('.main-sidebar a[href], #accordionSidebar a[href], .sidebar-menu a[href], aside a[href]').forEach(function (anchor) {
            var href = anchor.getAttribute('href') || '';
            if (!href || href.charAt(0) === '#') return;
            var path = href;
            try { path = new URL(href, window.location.origin).pathname; } catch (e) {}
            path = String(path || '').replace(/^\/+|\/+$/g, '').toLowerCase();
            if (!path) return;
            var denied = deniedRoutes.some(function (route) {
                return path === route || path.indexOf(route + '/') === 0;
            });
            if (denied) hide(anchor.closest('li') || anchor);
        });
    }

    // Legacy fallback: a few old parent sidebars still do not publish a key.
    // Match only exact parent labels known to be denied. No broad substring
    // matching is used, so unrelated child links cannot disappear accidentally.
    Object.keys(deniedLabels).forEach(function (label) {
        if (!label) return;
        document.querySelectorAll('.main-sidebar a, #accordionSidebar a, .sidebar-menu a, aside a').forEach(function (anchor) {
            if (normalise(anchor.textContent) !== label) return;
            var item = anchor.closest('li.nav-item, li.treeview, li');
            if (item) hide(item);
        });
    });
})();
</script>
HTML;

        return str_replace('__UMN_ACL__', $payload, $script);
    }

    private function cssAttributeValue(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }

    private function normaliseLabel(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/&/', ' and ', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}

@php
    /*
     * Automatic sidebar reconciliation — system-standard renderer. SIDEBAR-AUTO-V7
     *
     * IMPORTANT:
     * `AutomaticModuleRegistry::all()` is the detailed Manage Page registry. It
     * intentionally ignores folders that it cannot fully parse for routes/pages.
     * That makes it the wrong source for the final sidebar safety net: a newly
     * installed standalone module can be valid and enabled in Manage Side Bar
     * while not yet qualifying for the detailed registry.
     *
     * The lightweight `sidebarModules()` catalogue is specifically designed to
     * enumerate installed sidebar-capable modules. Use it as the authoritative
     * catalogue for the fallback, while still using `sidebarEntries()` for rich
     * module-owned sidebar views whenever the detailed registry can provide one.
     *
     * This prevents a newer module (for example a module installed after a
     * shared sidebar package was built) from disappearing just because the
     * shared sidebar Blade no longer contains its old manual include.
     */
    $__automaticSidebarEntries = [];
    $__automaticSidebarCatalog = [];
    $__automaticSidebarDetailed = [];
    $__automaticSidebarFallbacks = [];
    $__automaticSidebarEntryKeys = [];
    $__automaticSidebarManualKeys = ['sw'];
    // MSB-STRICT-20260914: these parents must obey Manage Side Bar even for Super Admin.
    $__automaticSidebarStrictManageMap = [
        'development' => 'development',
        'dashboard_logistics' => 'dashboard_logistics',
        'subscription' => 'subscription',
        'subscription_management' => 'subscription',
        'chequer' => 'chequer_module',
        'chequer_module' => 'chequer_module',
        'cheque' => 'chequer_module',
        'ezylaw' => 'ezylaw',
        'graphs' => 'graphs',
        'rice_mill' => 'rice_mill',
        'ricemill' => 'rice_mill',
    ];

    try {
        // Lightweight catalogue: installed/sidebar-capable modules. This is the
        // broad source used by Manage Side Bar itself and must therefore also be
        // the last-resort source for sidebar visibility.
        $__automaticSidebarCatalog = \App\Services\AutomaticModuleRegistry::sidebarModules();

        // Detailed registry remains useful for rich route metadata/sidebar views,
        // but it is NOT allowed to decide whether an installed module exists.
        try {
            $__automaticSidebarDetailed = \App\Services\AutomaticModuleRegistry::all();
        } catch (\Throwable $__automaticSidebarDetailedException) {
            $__automaticSidebarDetailed = [];
        }

        try {
            $__automaticSidebarEntries = \App\Services\AutomaticModuleRegistry::sidebarEntries([
                resource_path('views/layouts/partials/sidebar.blade.php'),
                resource_path('views/layouts_v2/partials/sidebar.blade.php'),
            ]);
        } catch (\Throwable $__automaticSidebarEntriesException) {
            $__automaticSidebarEntries = [];
        }

        $__automaticSidebarEntries = array_values(array_filter(
            $__automaticSidebarEntries,
            function ($__entry) use ($__automaticSidebarManualKeys) {
                $__entryKey = \App\Services\AutomaticModuleRegistry::normalizeKey($__entry['key'] ?? '');
                return !in_array($__entryKey, $__automaticSidebarManualKeys, true);
            }
        ));

        foreach ($__automaticSidebarEntries as $__entry) {
            $__entryKey = \App\Services\AutomaticModuleRegistry::normalizeKey($__entry['key'] ?? '');
            if ($__entryKey !== '') {
                $__automaticSidebarEntryKeys[$__entryKey] = true;
            }
        }

        $__automaticSidebarOwnSuperAdmin = \App\Utils\SidebarPermissionUtil::isGenuineSuperAdmin();

        foreach ($__automaticSidebarCatalog as $__catalogKey => $__catalogCandidate) {
            $__candidateKey = \App\Services\AutomaticModuleRegistry::normalizeKey(
                $__catalogCandidate['key'] ?? $__catalogKey
            );
            if ($__candidateKey === ''
                || in_array($__candidateKey, $__automaticSidebarManualKeys, true)) {
                continue;
            }

            /*
             * Keep a hidden safety copy when there is no automatic entry (the
             * historical use-case), and also when an automatic entry delegates
             * to a module-owned sidebar view. A view can exist yet render an
             * empty string/empty parent when its old permission names no longer
             * match the current Manage Page/User Management contract.
             *
             * Automatic entries without a sidebar view already render their own
             * generic parent and need no second hidden copy; skipping those keeps
             * the DOM small and sidebar rendering fast.
             */
            $__candidateHasAutomaticEntry = isset($__automaticSidebarEntryKeys[$__candidateKey]);
            if ($__candidateHasAutomaticEntry) {
                // v4: normal automatic entries now perform their own
                // server-side sidebar-view validation and generic fallback.
                // A second hidden copy is therefore unnecessary.
                continue;
            }

            // Prefer richer metadata when available, but never require it.
            $__candidate = array_merge(
                (array) $__catalogCandidate,
                (array) ($__automaticSidebarDetailed[$__candidateKey] ?? [])
            );
            $__candidate['key'] = $__candidateKey;

            // Normal tenant sidebars keep known core ERP sections on their
            // hand-written menus. Genuine Super Admin retains the safety fallback
            // so a stale legacy condition cannot hide an installed/core parent.
            if (!empty($__candidate['core']) && !$__automaticSidebarOwnSuperAdmin) {
                continue;
            }

            $__candidateStrictManageKey = $__automaticSidebarStrictManageMap[$__candidateKey] ?? null;
            $__candidateEnabled = \App\Utils\SidebarPermissionUtil::isVisibleInSidebar(
                $__candidateStrictManageKey !== null ? $__candidateStrictManageKey : $__candidateKey
            );

            if (!$__candidateEnabled) {
                continue;
            }

            $__primaryUrl = trim((string) ($__candidate['primary_url'] ?? ''));
            if ($__primaryUrl === '' || $__primaryUrl === '#') {
                $__prefixes = array_values(array_filter((array) ($__candidate['route_prefixes'] ?? [])));
                $__primaryUrl = $__prefixes !== []
                    ? '/' . ltrim((string) $__prefixes[0], '/')
                    : '/' . str_replace('_', '-', $__candidateKey);
            }
            $__candidate['primary_url'] = $__primaryUrl;

            $__automaticSidebarFallbacks[] = $__candidate;
        }
    } catch (\Throwable $__automaticSidebarDiscoveryException) {
        // Rich auto-discovery must never break the complete sidebar. Existing
        // hand-written module menus remain untouched if discovery is unavailable.
        $__automaticSidebarEntries = [];
        $__automaticSidebarFallbacks = [];
    }

    /*
     * v4 generic page builder.
     *
     * This is the server-side source of truth for an automatic module menu.
     * It uses the same effective page registry as Manage Page, then applies the
     * current business page state and UserManagementNew role page permission.
     * Old module-specific `@can(...)` checks are therefore not allowed to make
     * an otherwise enabled standard module disappear.
     */
    $__automaticBuildPages = function (array $__module) {
        $__pages = [];
        $__seenUrls = [];
        $__key = \App\Services\AutomaticModuleRegistry::normalizeKey($__module['key'] ?? '');
        if ($__key === '') {
            return $__pages;
        }

        $__primaryPath = '/' . ltrim((string) ($__module['primary_url'] ?? '/'), '/');
        $__canonicalPrefix = trim(str_replace('_', '-', $__key), '/ ');
        $__prefixes = array_values(array_unique(array_filter(array_map(
            static fn ($value) => strtolower(trim((string) $value, '/ ')),
            array_merge(
                [$__canonicalPrefix, trim($__primaryPath, '/ ')],
                (array) ($__module['route_prefixes'] ?? [])
            )
        ))));

        $__usesManagedRole = false;
        try {
            $__usesManagedRole = \App\Utils\SidebarPermissionUtil::usesManagedRoleForCurrentUser();
        } catch (\Throwable $__managedRoleException) {
            $__usesManagedRole = false;
        }

        try {
            $__permissionItems = \App\Services\AutomaticModuleRegistry::effectivePermissionItems(
                $__key,
                $__module
            );
        } catch (\Throwable $__permissionItemsException) {
            $__permissionItems = (array) ($__module['permission_items'] ?? []);
        }

        foreach ((array) $__permissionItems as $__item) {
            if (strtolower((string) ($__item['type'] ?? '')) !== 'page') {
                continue;
            }

            $__permissionKey = \App\Services\AutomaticModuleRegistry::normalizeKey(
                (string) ($__item['key'] ?? '')
            );
            if ($__permissionKey === '') {
                continue;
            }

            $__pageEnabled = true;
            try {
                $__pageEnabled = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled(
                    $__permissionKey
                );
                if ($__pageEnabled
                    && $__usesManagedRole
                    && !\App\Utils\SidebarPermissionUtil::managedRoleAllowsPermission(
                        'umn.page.' . $__permissionKey . '.view'
                    )) {
                    $__pageEnabled = false;
                }
            } catch (\Throwable $__pagePermissionException) {
                $__pageEnabled = true;
            }
            if (!$__pageEnabled) {
                continue;
            }

            $__candidatePaths = [];
            foreach ((array) ($__item['route_paths'] ?? []) as $__pathCandidate) {
                $__pathCandidate = strtolower(trim((string) $__pathCandidate, '/ '));
                if ($__pathCandidate === ''
                    || str_contains($__pathCandidate, '{')
                    || str_starts_with($__pathCandidate, 'api/')) {
                    continue;
                }
                $__candidatePaths[$__pathCandidate] = true;
            }
            $__candidatePaths = array_keys($__candidatePaths);

            /*
             * Prefer the path that semantically matches the Manage Page key.
             * This avoids choosing generated aliases such as
             * restaurant-new/admin/list when the real page is
             * restaurant-new/reservations/list.
             */
            $__pageTail = $__permissionKey;
            if (str_starts_with($__pageTail, $__key . '_')) {
                $__pageTail = substr($__pageTail, strlen($__key) + 1);
            }
            $__pageTail = preg_replace(
                '/_(?:view|index|show|list|page)$/',
                '',
                (string) $__pageTail
            );
            $__pageTailNormalized = \App\Services\AutomaticModuleRegistry::normalizeKey($__pageTail);

            $__bestPath = '';
            $__bestScore = PHP_INT_MAX;
            foreach ($__candidatePaths as $__pathCandidate) {
                $__matchedPrefix = '';
                foreach ($__prefixes as $__prefix) {
                    if ($__pathCandidate === $__prefix
                        || str_starts_with($__pathCandidate, $__prefix . '/')) {
                        $__matchedPrefix = $__prefix;
                        break;
                    }
                }
                if ($__matchedPrefix === '') {
                    continue;
                }

                $__local = $__pathCandidate === $__matchedPrefix
                    ? ''
                    : substr($__pathCandidate, strlen($__matchedPrefix) + 1);
                $__localNormalized = \App\Services\AutomaticModuleRegistry::normalizeKey($__local);
                $__localSegments = array_values(array_filter(explode('/', $__local)));

                $__score = 1000 + (count($__localSegments) * 10) + strlen($__pathCandidate);

                if (in_array($__pageTailNormalized, ['dashboard', 'home'], true)
                    && trim($__pathCandidate, '/') === trim($__primaryPath, '/')) {
                    $__score = 0;
                } elseif ($__pageTailNormalized !== '') {
                    if ($__localNormalized === $__pageTailNormalized) {
                        $__score = 10 + count($__localSegments);
                    } elseif (str_starts_with($__localNormalized, $__pageTailNormalized . '_')
                        || str_contains('_' . $__localNormalized . '_', '_' . $__pageTailNormalized . '_')) {
                        $__score = 30 + count($__localSegments);
                    }
                }

                if ($__canonicalPrefix !== ''
                    && ($__pathCandidate === $__canonicalPrefix
                        || str_starts_with($__pathCandidate, $__canonicalPrefix . '/'))) {
                    $__score -= 5;
                }

                if ($__score < $__bestScore) {
                    $__bestScore = $__score;
                    $__bestPath = $__pathCandidate;
                }
            }

            if ($__bestPath === ''
                && in_array($__pageTailNormalized, ['dashboard', 'home'], true)) {
                $__bestPath = trim($__primaryPath, '/ ');
            }
            if ($__bestPath === '' && $__candidatePaths !== []) {
                $__bestPath = $__candidatePaths[0];
            }
            if ($__bestPath === '') {
                continue;
            }

            $__pageUrl = url('/' . ltrim($__bestPath, '/'));
            if (isset($__seenUrls[$__pageUrl])) {
                continue;
            }
            $__seenUrls[$__pageUrl] = true;

            $__label = trim((string) ($__item['label'] ?? ''));
            if ($__label === '') {
                $__label = ucwords(str_replace('_', ' ', $__pageTailNormalized ?: $__permissionKey));
            }

            $__pages[] = [
                'key' => $__permissionKey,
                'label' => $__label,
                'url' => $__pageUrl,
                'active' => request()->is(trim($__bestPath, '/'))
                    || request()->is(trim($__bestPath, '/') . '/*'),
            ];
        }

        return $__pages;
    };

    /*
     * Validate a module-owned sidebar after Blade has rendered it for the
     * current user. The old implementation trusted the existence of a view
     * file. RestaurantNew demonstrated why that is not enough: its partial can
     * legally render an empty string when retired permission names are absent.
     * Tea Estate can render an expandable parent with zero usable children.
     */
    $__automaticSidebarHtmlUsable = function (string $__html, int $__expectedPages = 0): bool {
        if (trim(strip_tags($__html)) === '') {
            return false;
        }

        preg_match_all(
            '/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1/is',
            $__html,
            $__hrefMatches,
            PREG_SET_ORDER
        );

        $__realLinks = [];
        foreach ($__hrefMatches as $__hrefMatch) {
            $__href = trim(html_entity_decode((string) ($__hrefMatch[2] ?? ''), ENT_QUOTES));
            if ($__href === ''
                || str_starts_with($__href, '#')
                || str_starts_with(strtolower($__href), 'javascript:')) {
                continue;
            }
            $__realLinks[strtolower($__href)] = true;
        }

        $__realLinkCount = count($__realLinks);
        if ($__expectedPages > 0) {
            // A legacy partial containing only a parent/dashboard link is not a
            // valid replacement for an expandable module with several managed
            // pages. This was the Tea Estate empty-expanded-menu failure.
            $__minimum = min(2, $__expectedPages);
            return $__realLinkCount >= $__minimum;
        }

        return $__realLinkCount > 0 || trim(strip_tags($__html)) !== '';
    };
@endphp

{{-- Normal automatic entries.
     v6 uses the shared system renderer for modules marked standard and validates other module-owned sidebars server-side and immediately substitutes
     the standard generic menu when the old partial is empty/unusable. --}}
@foreach($__automaticSidebarEntries as $__automaticSidebarModule)
    @php
        $__automaticSidebarEnabled = false;
        $__automaticSidebarView = null;
        $__automaticSidebarRenderedHtml = '';
        $__automaticSidebarUseRenderedHtml = false;
        $__automaticSidebarEntryKey = '';
        $__automaticSidebarPages = [];
        $__automaticSidebarForceStandard = false;

        try {
            $__automaticSidebarEntryKey = \App\Services\AutomaticModuleRegistry::normalizeKey(
                $__automaticSidebarModule['key'] ?? ''
            );
            $__automaticSidebarForceStandard = \App\Services\AutomaticModuleRegistry::prefersStandardSidebar(
                $__automaticSidebarEntryKey
            );
            $__automaticSidebarStrictManageKey = $__automaticSidebarStrictManageMap[$__automaticSidebarEntryKey] ?? null;
            $__automaticSidebarEnabled = \App\Utils\SidebarPermissionUtil::isVisibleInSidebar(
                $__automaticSidebarStrictManageKey !== null
                    ? $__automaticSidebarStrictManageKey
                    : ($__automaticSidebarModule['key'] ?? null)
            );

            if ($__automaticSidebarEnabled) {
                $__automaticSidebarPages = $__automaticBuildPages($__automaticSidebarModule);

                if (!$__automaticSidebarForceStandard) {
                    foreach (($__automaticSidebarModule['sidebar_views'] ?? []) as $__candidateSidebarView) {
                    if (!view()->exists($__candidateSidebarView)) {
                        continue;
                    }

                    $__automaticSidebarView = $__candidateSidebarView;
                    try {
                        $__automaticSidebarRenderedHtml = (string) view($__candidateSidebarView)->render();
                        $__automaticSidebarUseRenderedHtml = $__automaticSidebarHtmlUsable(
                            $__automaticSidebarRenderedHtml,
                            count($__automaticSidebarPages)
                        );
                    } catch (\Throwable $__sidebarViewRenderException) {
                        $__automaticSidebarRenderedHtml = '';
                        $__automaticSidebarUseRenderedHtml = false;
                    }

                    /*
                     * First existing sidebar view remains the module's declared
                     * preference. If it is unusable, do not try content-tab
                     * navigation files as a replacement; use the standard
                     * automatic menu instead.
                     */
                        break;
                    }
                }
            }
        } catch (\Throwable $__automaticSidebarModuleException) {
            $__automaticSidebarEnabled = false;
            $__automaticSidebarUseRenderedHtml = false;
            $__automaticSidebarRenderedHtml = '';
            $__automaticSidebarPages = [];
            $__automaticSidebarForceStandard = false;
        }

        $__automaticSidebarTitle = $__automaticSidebarModule['title']
            ?? ucwords(str_replace('_', ' ', $__automaticSidebarEntryKey));
        $__automaticSidebarPrimaryPath = '/' . ltrim(
            (string) ($__automaticSidebarModule['primary_url'] ?? '/'),
            '/'
        );
        $__automaticSidebarUrl = url($__automaticSidebarPrimaryPath);
        $__automaticSidebarActive = false;
        foreach ((array) ($__automaticSidebarModule['route_prefixes'] ?? []) as $__automaticSidebarPrefix) {
            $__automaticSidebarPrefix = trim((string) $__automaticSidebarPrefix, '/ ');
            if ($__automaticSidebarPrefix !== ''
                && (request()->is($__automaticSidebarPrefix)
                    || request()->is($__automaticSidebarPrefix . '/*'))) {
                $__automaticSidebarActive = true;
                break;
            }
        }
        $__automaticSidebarMenuId = 'automatic-sidebar-' . preg_replace(
            '/[^a-z0-9_-]+/i',
            '-',
            $__automaticSidebarEntryKey
        );
    @endphp

    @if($__automaticSidebarEnabled && auth()->check())
        @if($__automaticSidebarUseRenderedHtml)
            {!! $__automaticSidebarRenderedHtml !!}
        @else
            <li class="nav-item automatic-sidebar-standard {{ $__automaticSidebarActive ? 'active active-sub' : '' }}"
                data-auto-module="{{ $__automaticSidebarEntryKey }}"
                data-sidebar-module="{{ $__automaticSidebarEntryKey }}"
                data-sidebar-title="{{ $__automaticSidebarTitle }}">
                @if(count($__automaticSidebarPages) > 0)
                    <a class="nav-link collapsed"
                       href="#"
                       data-toggle="collapse"
                       data-target="#{{ $__automaticSidebarMenuId }}"
                       aria-expanded="{{ $__automaticSidebarActive ? 'true' : 'false' }}"
                       aria-controls="{{ $__automaticSidebarMenuId }}">
                        <i class="fa fa-cubes"></i>
                        <span>{{ $__automaticSidebarTitle }}</span>
                    </a>
                    <div id="{{ $__automaticSidebarMenuId }}"
                         class="collapse {{ $__automaticSidebarActive ? 'show' : '' }}"
                         data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <h6 class="collapse-header">{{ $__automaticSidebarTitle }}:</h6>
                            @foreach($__automaticSidebarPages as $__automaticSidebarPage)
                                <a class="collapse-item {{ $__automaticSidebarPage['active'] ? 'active' : '' }}"
                                   data-manage-page-key="{{ $__automaticSidebarPage['key'] }}"
                                   data-sidebar-page="{{ $__automaticSidebarPage['key'] }}"
                                   href="{{ $__automaticSidebarPage['url'] }}">{{ $__automaticSidebarPage['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a class="nav-link"
                       href="{{ $__automaticSidebarUrl }}">
                        <i class="fa fa-cubes"></i>
                        <span>{{ $__automaticSidebarTitle }}</span>
                    </a>
                @endif
            </li>
        @endif
    @endif
@endforeach

{{--
    Automatic safety net for catalogue modules that are already integrated
    elsewhere in the host sidebar and therefore are not normal automatic
    entries. Normal automatic entries are now validated/fallback-rendered on
    the server above; this legacy DOM reconciliation remains only as a final
    duplicate-safe bridge for hand-integrated modules.
--}}
@foreach($__automaticSidebarFallbacks as $__fallbackModule)
    @php
        $__fallbackKey = \App\Services\AutomaticModuleRegistry::normalizeKey($__fallbackModule['key'] ?? '');
        $__fallbackTitle = $__fallbackModule['title'] ?? ucwords(str_replace('_', ' ', $__fallbackKey));
        $__fallbackPrimaryPath = '/' . ltrim((string) ($__fallbackModule['primary_url'] ?? '/'), '/');
        $__fallbackUrl = url($__fallbackPrimaryPath);
        $__fallbackAliases = array_values(array_unique(array_filter(array_map(
            function ($value) {
                try {
                    return \App\Services\AutomaticModuleRegistry::normalizeKey($value);
                } catch (\Throwable $e) {
                    return '';
                }
            },
            array_merge([$__fallbackKey, $__fallbackKey . '_module'], $__fallbackModule['aliases'] ?? [])
        ))));
        $__fallbackPrefixes = array_values(array_unique(array_filter(array_map(
            static fn ($prefix) => trim((string) $prefix, '/ '),
            $__fallbackModule['route_prefixes'] ?? []
        ))));
        $__fallbackActive = false;
        foreach ($__fallbackPrefixes as $__fallbackPrefix) {
            if (request()->is($__fallbackPrefix) || request()->is($__fallbackPrefix . '/*')) {
                $__fallbackActive = true;
                break;
            }
        }

        /*
         * Build child links from the same automatic page registry used by
         * Manage Page.  Therefore this menu cannot invent a page that Manage
         * Page does not know about, and an explicitly disabled page stays out.
         */
        $__fallbackPages = [];
        $__fallbackSeenUrls = [];
        $__fallbackUsesManagedRole = false;
        try {
            $__fallbackUsesManagedRole = \App\Utils\SidebarPermissionUtil::usesManagedRoleForCurrentUser();
        } catch (\Throwable $e) {
            $__fallbackUsesManagedRole = false;
        }

        $__fallbackPermissionItems = [];
        try {
            $__fallbackPermissionItems = \App\Services\AutomaticModuleRegistry::effectivePermissionItems(
                $__fallbackKey,
                $__fallbackModule
            );
        } catch (\Throwable $e) {
            $__fallbackPermissionItems = (array) ($__fallbackModule['permission_items'] ?? []);
        }

        foreach ((array) $__fallbackPermissionItems as $__fallbackPermissionItem) {
            if (strtolower((string) ($__fallbackPermissionItem['type'] ?? '')) !== 'page') {
                continue;
            }

            $__fallbackPermissionKey = \App\Services\AutomaticModuleRegistry::normalizeKey(
                (string) ($__fallbackPermissionItem['key'] ?? '')
            );
            if ($__fallbackPermissionKey === '') {
                continue;
            }

            $__fallbackPageEnabled = true;
            try {
                $__fallbackPageEnabled = \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled(
                    $__fallbackPermissionKey
                );
                if ($__fallbackPageEnabled
                    && $__fallbackUsesManagedRole
                    && !\App\Utils\SidebarPermissionUtil::managedRoleAllowsPermission(
                        'umn.page.' . $__fallbackPermissionKey . '.view'
                    )) {
                    $__fallbackPageEnabled = false;
                }
            } catch (\Throwable $e) {
                $__fallbackPageEnabled = true;
            }
            if (!$__fallbackPageEnabled) {
                continue;
            }

            $__fallbackStaticPaths = [];
            foreach ((array) ($__fallbackPermissionItem['route_paths'] ?? []) as $__fallbackPathCandidate) {
                $__fallbackPathCandidate = trim((string) $__fallbackPathCandidate, '/ ');
                if ($__fallbackPathCandidate === '' || str_contains($__fallbackPathCandidate, '{')) {
                    continue;
                }
                $__fallbackStaticPaths[] = $__fallbackPathCandidate;
            }
            $__fallbackStaticPaths = array_values(array_unique($__fallbackStaticPaths));

            /* Prefer a fully module-qualified route over a local route fragment. */
            $__fallbackPagePath = '';
            foreach ($__fallbackStaticPaths as $__fallbackPathCandidate) {
                foreach ($__fallbackPrefixes as $__fallbackPrefix) {
                    if ($__fallbackPathCandidate === $__fallbackPrefix
                        || str_starts_with($__fallbackPathCandidate, $__fallbackPrefix . '/')) {
                        $__fallbackPagePath = $__fallbackPathCandidate;
                        break 2;
                    }
                }
            }
            if ($__fallbackPagePath === '' && $__fallbackStaticPaths !== []) {
                $__fallbackPagePath = $__fallbackStaticPaths[0];
            }

            /* Dashboard/home may legitimately have an empty route path. */
            if ($__fallbackPagePath === ''
                && (str_ends_with($__fallbackPermissionKey, '_dashboard')
                    || str_ends_with($__fallbackPermissionKey, '_home'))) {
                $__fallbackPagePath = trim($__fallbackPrimaryPath, '/ ');
            }

            if ($__fallbackPagePath === '') {
                continue;
            }

            $__fallbackPageUrl = url('/' . ltrim($__fallbackPagePath, '/'));
            if (isset($__fallbackSeenUrls[$__fallbackPageUrl])) {
                continue;
            }
            $__fallbackSeenUrls[$__fallbackPageUrl] = true;

            $__fallbackPageLabel = trim((string) ($__fallbackPermissionItem['label'] ?? ''));
            if ($__fallbackPageLabel === '') {
                $__fallbackPageLabel = ucwords(str_replace('_', ' ', $__fallbackPermissionKey));
            }
            $__fallbackPageActive = request()->is(trim($__fallbackPagePath, '/'))
                || request()->is(trim($__fallbackPagePath, '/') . '/*');

            $__fallbackPages[] = [
                'key' => $__fallbackPermissionKey,
                'label' => $__fallbackPageLabel,
                'url' => $__fallbackPageUrl,
                'active' => $__fallbackPageActive,
            ];
        }

        $__fallbackMenuId = 'automatic-sidebar-' . preg_replace('/[^a-z0-9_-]+/i', '-', $__fallbackKey);
    @endphp

    <li class="nav-item automatic-sidebar-managed-fallback {{ $__fallbackActive ? 'active active-sub' : '' }}"
        data-auto-fallback="{{ $__fallbackKey }}"
        data-auto-module="{{ $__fallbackKey }}"
        data-sidebar-module="{{ $__fallbackKey }}"
        data-auto-title="{{ $__fallbackTitle }}"
        data-auto-has-pages="{{ count($__fallbackPages) > 0 ? '1' : '0' }}"
        data-auto-aliases="{{ base64_encode(json_encode($__fallbackAliases)) }}"
        data-auto-prefixes="{{ base64_encode(json_encode($__fallbackPrefixes)) }}"
        style="display:block">
        @if(count($__fallbackPages) > 0)
            <a class="nav-link collapsed" href="#"
               data-toggle="collapse"
               data-target="#{{ $__fallbackMenuId }}"
               aria-expanded="{{ $__fallbackActive ? 'true' : 'false' }}"
               aria-controls="{{ $__fallbackMenuId }}"
               data-sidebar-title="{{ $__fallbackTitle }}">
                <i class="fa fa-cubes"></i>
                <span>{{ $__fallbackTitle }}</span>
            </a>
            <div id="{{ $__fallbackMenuId }}"
                 class="collapse {{ $__fallbackActive ? 'show' : '' }}"
                 data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <h6 class="collapse-header">{{ $__fallbackTitle }}:</h6>
                    @foreach($__fallbackPages as $__fallbackPage)
                        <a class="collapse-item {{ $__fallbackPage['active'] ? 'active' : '' }}"
                           data-manage-page-key="{{ $__fallbackPage['key'] }}"
                           data-sidebar-page="{{ $__fallbackPage['key'] }}"
                           href="{{ $__fallbackPage['url'] }}">{{ $__fallbackPage['label'] }}</a>
                    @endforeach
                </div>
            </div>
        @else
            <a class="nav-link" href="{{ $__fallbackUrl }}" data-sidebar-title="{{ $__fallbackTitle }}">
                <i class="fa fa-cubes"></i>
                <span>{{ $__fallbackTitle }}</span>
            </a>
        @endif
    </li>
@endforeach


<style>
/*
 * Automatic-module parents sit on the dark system sidebar. Force only these
 * generated parent labels/icons to inherit the normal light sidebar colour.
 * Child pages remain dark on the white collapse panel.
 */
#accordionSidebar .automatic-sidebar-standard > .nav-link,
#accordionSidebar .automatic-sidebar-managed-fallback > .nav-link,
.sidebar .automatic-sidebar-standard > .nav-link,
.sidebar .automatic-sidebar-managed-fallback > .nav-link {
    color: #ffffff !important;
}
#accordionSidebar .automatic-sidebar-standard > .nav-link > i,
#accordionSidebar .automatic-sidebar-standard > .nav-link > span,
#accordionSidebar .automatic-sidebar-managed-fallback > .nav-link > i,
#accordionSidebar .automatic-sidebar-managed-fallback > .nav-link > span,
.sidebar .automatic-sidebar-standard > .nav-link > i,
.sidebar .automatic-sidebar-standard > .nav-link > span,
.sidebar .automatic-sidebar-managed-fallback > .nav-link > i,
.sidebar .automatic-sidebar-managed-fallback > .nav-link > span {
    color: #ffffff !important;
}
#accordionSidebar .automatic-sidebar-standard .collapse-inner .collapse-item,
#accordionSidebar .automatic-sidebar-managed-fallback .collapse-inner .collapse-item,
.sidebar .automatic-sidebar-standard .collapse-inner .collapse-item,
.sidebar .automatic-sidebar-managed-fallback .collapse-inner .collapse-item {
    color: #3a3b45 !important;
}
</style>

@if(!empty($__automaticSidebarFallbacks))
<script>
(function () {
    'use strict';

    function normalize(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function decodeList(value) {
        if (!value) return [];
        try {
            return JSON.parse(atob(value)) || [];
        } catch (e) {
            return [];
        }
    }

    function pathOf(url) {
        if (!url || String(url).trim().charAt(0) === '#') return '';
        try {
            var parsed = new URL(url, window.location.origin);
            if (parsed.origin !== window.location.origin) return '';
            return String(parsed.pathname || '').replace(/^\/+|\/+$/g, '').toLowerCase();
        } catch (e) {
            return String(url).split('?')[0].split('#')[0].replace(/^\/+|\/+$/g, '').toLowerCase();
        }
    }

    function prefixMatches(path, prefix) {
        path = String(path || '').replace(/^\/+|\/+$/g, '').toLowerCase();
        prefix = String(prefix || '').replace(/^\/+|\/+$/g, '').toLowerCase();
        return !!path && !!prefix && (path === prefix || path.indexOf(prefix + '/') === 0);
    }

    function directLabel(item) {
        if (!item) return '';
        var anchor = null;
        for (var i = 0; i < item.children.length; i++) {
            if (String(item.children[i].tagName || '').toLowerCase() === 'a') {
                anchor = item.children[i];
                break;
            }
        }
        if (!anchor) return '';
        var title = anchor.querySelector('[data-sidebar-title], .menu-title, .title, span, p');
        return normalize(title ? title.textContent : anchor.textContent);
    }

    function declaredKeys(item) {
        return [
            item.getAttribute('data-sidebar-module'),
            item.getAttribute('data-auto-module'),
            item.getAttribute('data-module'),
            item.getAttribute('data-module-key'),
            item.getAttribute('data-sidebar-key')
        ].map(normalize).filter(Boolean);
    }

    function hasUsableChildren(item) {
        if (!item) return false;
        var links = item.querySelectorAll('.collapse-item[href], .treeview-menu a[href], .collapse a[href]');
        for (var i = 0; i < links.length; i++) {
            var href = String(links[i].getAttribute('href') || '').trim();
            if (href && href.charAt(0) !== '#' && href.toLowerCase().indexOf('javascript:') !== 0) return true;
        }
        return false;
    }

    function isUsableExisting(item, fallback) {
        if (!item) return false;
        if (item.classList.contains('automatic-sidebar-managed-fallback')) return false;
        if (item.classList.contains('business-sidebar-disabled-parent')) return false;
        if (item.getAttribute('aria-hidden') === 'true') return false;

        try {
            var style = window.getComputedStyle(item);
            if (!style || style.display === 'none' || style.visibility === 'hidden') return false;
        } catch (e) {}

        var directAnchor = null;
        for (var ai = 0; ai < item.children.length; ai++) {
            if (String(item.children[ai].tagName || '').toLowerCase() === 'a') {
                directAnchor = item.children[ai];
                break;
            }
        }
        if (!directAnchor || !String(directAnchor.textContent || '').trim()) return false;

        /*
         * If the automatic registry has real pages, an old parent with zero
         * child links is not a usable implementation.  This is the exact state
         * produced by module sidebars whose historical permission checks filter
         * every child out (Tea Estate / Restaurant New symptom).
         */
        if (fallback && fallback.getAttribute('data-auto-has-pages') === '1') {
            return hasUsableChildren(item);
        }

        return true;
    }

    function matchesFallback(item, keySet, title, prefixes) {
        if (!item) return false;

        var itemKeys = declaredKeys(item);
        for (var k = 0; k < itemKeys.length; k++) {
            if (keySet[itemKeys[k]]) return true;
        }

        var label = directLabel(item);
        if (label && (label === title || keySet[label])) return true;

        if (prefixes.length) {
            var links = item.querySelectorAll('a[href]');
            for (var a = 0; a < links.length; a++) {
                var path = pathOf(links[a].getAttribute('href'));
                for (var r = 0; r < prefixes.length; r++) {
                    if (prefixMatches(path, prefixes[r])) return true;
                }
            }
        }

        return false;
    }

    function reconcile() {
        var sidebar = document.getElementById('accordionSidebar') || document.querySelector('.sidebar');
        if (!sidebar) return;

        var fallbacks = sidebar.querySelectorAll('.automatic-sidebar-managed-fallback');
        var parents = sidebar.querySelectorAll('li.nav-item, li.treeview');

        fallbacks.forEach(function (fallback) {
            var key = normalize(fallback.getAttribute('data-auto-fallback'));
            var title = normalize(fallback.getAttribute('data-auto-title'));
            var aliases = decodeList(fallback.getAttribute('data-auto-aliases')).map(normalize).filter(Boolean);
            var prefixes = decodeList(fallback.getAttribute('data-auto-prefixes'));
            var keySet = Object.create(null);
            [key].concat(aliases).forEach(function (value) {
                value = normalize(value);
                if (value) keySet[value] = true;
            });

            var existing = false;
            for (var i = 0; i < parents.length && !existing; i++) {
                var item = parents[i];
                if (!item || !item.isConnected) continue;
                if (item === fallback || item.classList.contains('automatic-sidebar-managed-fallback')) continue;
                if (!matchesFallback(item, keySet, title, prefixes)) continue;

                if (isUsableExisting(item, fallback)) {
                    item.setAttribute('data-sidebar-module', key);
                    item.setAttribute('data-auto-module', key);
                    existing = true;
                    break;
                }

                /* Remove the empty/invalid copy before revealing the standard fallback. */
                if (item.parentNode) item.parentNode.removeChild(item);
            }

            if (existing) {
                fallback.parentNode && fallback.parentNode.removeChild(fallback);
            } else {
                // V5 fail-open visibility: the standard fallback is already visible.
                // Keep it visible even if a legacy sidebar script fails later.
                fallback.style.display = '';
            }
        });
    }

    // This partial is rendered after the hand-written sidebar parents, so all
    // possible duplicates already exist. Reconcile synchronously: a newly
    // discovered module becomes visible before the user can interact with the
    // sidebar and no timeout/observer is needed for initial visibility.
    reconcile();
})();
</script>
@endif

<?php

namespace Modules\UserManagementNew\Services;

use App\Services\AutomaticModuleRegistry;
use App\Utils\SidebarPermissionUtil;

/**
 * The module's only bridge to the host permission catalogue.
 *
 * This keeps discovery details out of controllers/views and makes the module
 * portable while preserving Manage Side Bar > Manage Page New > Role precedence.
 */
class BusinessPermissionBridge
{
    public function sections(int $businessId): array
    {
        $sections = [];

        $seenModules = [];

        foreach (AutomaticModuleRegistry::manageSections() as $section) {
            $moduleKey = AutomaticModuleRegistry::normalizeKey(
                (string) ($section['module_key'] ?? '')
            );
            if ($moduleKey === ''
                || !SidebarPermissionUtil::isManageSidebarEnabled($moduleKey, $businessId)) {
                continue;
            }

            $items = [];
            foreach ((array) ($section['items'] ?? []) as $item) {
                $type = strtolower((string) ($item['type'] ?? ''));
                if (!in_array($type, ['page', 'tab'], true)) {
                    continue;
                }

                $itemKey = AutomaticModuleRegistry::normalizeKey(
                    (string) ($item['key'] ?? '')
                );
                if ($itemKey === ''
                    || !SidebarPermissionUtil::isAutomaticPermissionEnabled($itemKey, $businessId)) {
                    continue;
                }

                $items[] = [
                    'key' => $itemKey,
                    'label' => (string) ($item['label'] ?? $itemKey),
                    'type' => $type,
                ];
            }

            $module = AutomaticModuleRegistry::findSidebar($moduleKey)
                ?: AutomaticModuleRegistry::find($moduleKey)
                ?: [];

            $sections[] = [
                'module_key' => $moduleKey,
                'title' => (string) ($section['title'] ?? ($module['title'] ?? $moduleKey)),
                'items' => $items,
            ];
            $seenModules[$moduleKey] = true;
        }

        /*
         * Installed-module safety net.
         *
         * Manage Page's detailed registry can legitimately have no section for
         * a newly installed module while its lightweight Manage Side Bar entry
         * already exists. UserManagementNew must still expose the module-level
         * View/Edit/... rights; otherwise a managed role has no way to grant the
         * new module and the sidebar hides it despite Level 1 being enabled.
         *
         * v7 enriches page/tab rights below from the shared effective Manage Page
         * catalogue, including its conservative runtime route fallback.
         */
        foreach (AutomaticModuleRegistry::sidebarModules() as $catalogKey => $module) {
            $moduleKey = AutomaticModuleRegistry::normalizeKey(
                (string) ($module['key'] ?? $catalogKey)
            );
            if ($moduleKey === '' || isset($seenModules[$moduleKey])) {
                continue;
            }
            if (!SidebarPermissionUtil::isManageSidebarEnabled($moduleKey, $businessId)) {
                continue;
            }

            $sections[] = [
                'module_key' => $moduleKey,
                'title' => (string) ($module['title'] ?? $moduleKey),
                'items' => [],
            ];
            $seenModules[$moduleKey] = true;
        }


        /*
         * v7: enrich every enabled Role section with the SAME effective
         * page/tab catalogue used by Manage Page and the automatic sidebar.
         *
         * Older standalone modules can expose zero static Config/pages.php
         * entries while still having safe, top-level GET pages discoverable
         * from Laravel's live route collection. AirlineTicketingNew and
         * TeaEstateManagement are examples. Before this bridge, those pages
         * appeared in the automatic sidebar registry but never appeared on the
         * User Management New Role screen, so a managed role could not grant
         * them. The sidebar then correctly hid every child page, creating the
         * misleading "empty module" symptom.
         *
         * Static declarations remain authoritative when present because
         * effectivePermissionItems() returns them first and only uses the
         * conservative runtime fallback when static page metadata is absent.
         */
        foreach ($sections as &$section) {
            $moduleKey = AutomaticModuleRegistry::normalizeKey(
                (string) ($section['module_key'] ?? '')
            );
            if ($moduleKey === '') {
                continue;
            }

            $existingItems = [];
            foreach ((array) ($section['items'] ?? []) as $existingItem) {
                $existingKey = AutomaticModuleRegistry::normalizeKey(
                    (string) ($existingItem['key'] ?? '')
                );
                if ($existingKey !== '') {
                    $existingItems[$existingKey] = true;
                }
            }

            $module = AutomaticModuleRegistry::findSidebar($moduleKey)
                ?: AutomaticModuleRegistry::find($moduleKey)
                ?: [];

            try {
                $effectiveItems = AutomaticModuleRegistry::effectivePermissionItems(
                    $moduleKey,
                    is_array($module) ? $module : []
                );
            } catch (\Throwable $effectiveException) {
                $effectiveItems = [];
            }

            foreach ((array) $effectiveItems as $item) {
                $type = strtolower((string) ($item['type'] ?? ''));
                if (!in_array($type, ['page', 'tab'], true)) {
                    continue;
                }

                $itemKey = AutomaticModuleRegistry::normalizeKey(
                    (string) ($item['key'] ?? '')
                );
                if ($itemKey === ''
                    || isset($existingItems[$itemKey])
                    || !SidebarPermissionUtil::isAutomaticPermissionEnabled($itemKey, $businessId)) {
                    continue;
                }

                $section['items'][] = [
                    'key' => $itemKey,
                    'label' => (string) ($item['label'] ?? $itemKey),
                    'type' => $type,
                ];
                $existingItems[$itemKey] = true;
            }
        }
        unset($section);

        /*
         * Stock Reports compatibility safety net.
         *
         * Some older deployments expose StockReports in the sidebar but do not
         * publish a detailed Manage Page section. Manage Side Bar is still the
         * parent authority, so the Role screen must always offer the module View
         * right when that parent is enabled. No child pages are invented here.
         */
        $stockReportsKey = 'stock_reports';
        if (!isset($seenModules[$stockReportsKey])
            && SidebarPermissionUtil::isManageSidebarEnabled($stockReportsKey, $businessId)) {
            $sections[] = [
                'module_key' => $stockReportsKey,
                'title' => 'Stock Reports',
                'items' => [],
            ];
            $seenModules[$stockReportsKey] = true;
        }

        usort($sections, static fn (array $a, array $b): int =>
            strnatcasecmp($a['title'], $b['title'])
        );

        return $sections;
    }

    public function moduleKeysForRequest($request): array
    {
        return SidebarPermissionUtil::routeModuleKeysForRequest($request);
    }

    public function permissionKeysForRequest($request): array
    {
        return SidebarPermissionUtil::permissionKeysForRequest($request);
    }
}

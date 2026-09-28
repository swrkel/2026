<?php

namespace Modules\BankingUI\Services;

class BankingNavigationService
{
    public function groupsForUser($user = null): array
    {
        $groups = config('bankingui.navigation.groups', []);

        foreach ($groups as $groupKey => &$group) {
            $group['key'] = $groupKey;
            $group['items'] = collect($group['items'] ?? [])->filter(function ($item) use ($user) {
                if (!$user || empty($item['permission']) || !method_exists($user, 'can')) {
                    return true;
                }
                return $user->can($item['permission']);
            })->values()->all();
        }

        return $groups;
    }

    public function statusSummary(): array
    {
        return [
            ['label' => 'Completed foundations', 'value' => 13],
            ['label' => 'Pending enterprise modules', 'value' => 18],
            ['label' => 'Tester menu groups', 'value' => count(config('bankingui.navigation.groups', []))],
            ['label' => 'UI integration phase', 'value' => 'RC3'],
        ];
    }

    public function checkpoints(): array
    {
        return [
            'Menu visible in sidebar',
            'Page opens without 404/500',
            'Permission works for assigned role',
            'Toolbar position and style are consistent',
            'Date range/search/export placeholders are visible where applicable',
            'Branch/location filter is visible where applicable',
            'No correctly working non-Banking module is affected',
            'Audit event is recorded when tester opens the page',
        ];
    }
}

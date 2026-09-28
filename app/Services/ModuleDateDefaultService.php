<?php

namespace App\Services;

use App\BusinessModuleDateSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ModuleDateDefaultService
{
    const SOURCE_COMPUTER = 'computer';
    const SOURCE_GLOBAL = 'global';

    /**
     * Return the date-default configuration for the page being rendered.
     * The safe fallback is always Computer Date so this feature can never
     * stop an application page if the tenant migration has not run yet.
     */
    public function configurationForRequest(Request $request)
    {
        $businessId = $this->businessId($request);
        $moduleKey = $this->resolveCurrentModuleKey($request);
        $dateFormat = $this->businessDateFormat($request);

        $config = [
            'enabled' => $businessId > 0,
            'businessId' => $businessId,
            'moduleKey' => $moduleKey,
            'source' => self::SOURCE_COMPUTER,
            'globalDate' => null,
            'businessDateFormat' => $dateFormat,
        ];

        if ($businessId < 1 || !$this->settingsTableExists()) {
            return $config;
        }

        try {
            $setting = BusinessModuleDateSetting::where('business_id', $businessId)->first();
            if (!$setting) {
                return $config;
            }

            $moduleSettings = is_array($setting->module_settings) ? $setting->module_settings : [];
            $source = isset($moduleSettings[$moduleKey]) ? strtolower((string) $moduleSettings[$moduleKey]) : self::SOURCE_COMPUTER;
            if (!in_array($source, [self::SOURCE_COMPUTER, self::SOURCE_GLOBAL], true)) {
                $source = self::SOURCE_COMPUTER;
            }

            $config['source'] = $source;
            $config['globalDate'] = !empty($setting->global_date)
                ? $setting->global_date->format('Y-m-d')
                : null;
        } catch (\Throwable $e) {
            // Never make a normal business page fail because of a default-date preference.
            report($e);
        }

        return $config;
    }

    public function availableModules()
    {
        $modules = [
            'Core' => 'Core / Common Forms',
            'Sales' => 'Sales / POS',
            'Purchase' => 'Purchase',
            'Expenses' => 'Expenses',
            'Contacts' => 'Contacts / Legacy Customers & Suppliers',
        ];

        $statuses = $this->moduleStatuses();
        $moduleRoot = base_path('Modules');
        $moduleFiles = is_dir($moduleRoot) ? glob($moduleRoot . '/*/module.json') : [];

        foreach ((array) $moduleFiles as $moduleFile) {
            $folder = basename(dirname($moduleFile));
            if ($folder === '' || stripos($folder, 'backup') !== false) {
                continue;
            }

            $metadata = [];
            try {
                $metadata = json_decode((string) file_get_contents($moduleFile), true) ?: [];
            } catch (\Throwable $e) {
                $metadata = [];
            }

            $key = !empty($metadata['name']) ? (string) $metadata['name'] : $folder;
            if ($key === '') {
                continue;
            }

            if (array_key_exists($key, $statuses) && !$this->statusEnabled($statuses[$key])) {
                continue;
            }

            $label = !empty($metadata['description']) && strlen((string) $metadata['description']) <= 70
                ? (string) $metadata['description']
                : $this->humanize($key);

            $modules[$key] = $label;
        }

        natcasesort($modules);

        return $modules;
    }

    public function resolveCurrentModuleKey(Request $request)
    {
        $action = '';
        try {
            $route = $request->route();
            if ($route) {
                $action = (string) $route->getActionName();
            }
        } catch (\Throwable $e) {
            $action = '';
        }

        if (preg_match('/^Modules\\\\([^\\\\]+)\\\\/i', $action, $matches)) {
            return (string) $matches[1];
        }

        $first = strtolower((string) $request->segment(1));
        $map = [
            'pos' => 'Sales',
            'tpos' => 'Sales',
            'fpos' => 'Sales',
            'sell' => 'Sales',
            'sells' => 'Sales',
            'sales' => 'Sales',
            'sale' => 'Sales',
            'quotation' => 'Sales',
            'quotations' => 'Sales',
            'draft' => 'Sales',
            'drafts' => 'Sales',
            'purchase' => 'Purchase',
            'purchases' => 'Purchase',
            'purchase-pos' => 'Purchase',
            'purchase-return' => 'Purchase',
            'import-purchases' => 'Purchase',
            'expense' => 'Expenses',
            'expenses' => 'Expenses',
            'contacts' => 'Contacts',
            'contact' => 'Contacts',
            'customer-group' => 'Contacts',
            'supplier-group' => 'Contacts',
        ];

        return isset($map[$first]) ? $map[$first] : 'Core';
    }

    public function settingsTableExists()
    {
        try {
            return Schema::hasTable('business_module_date_settings');
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function businessId(Request $request)
    {
        $user = $request->user();

        return (int) (
            ($user && !empty($user->business_id) ? $user->business_id : 0)
            ?: $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
        );
    }

    protected function businessDateFormat(Request $request)
    {
        $business = $request->session()->get('business');
        if (is_object($business) && !empty($business->date_format)) {
            return (string) $business->date_format;
        }
        if (is_array($business) && !empty($business['date_format'])) {
            return (string) $business['date_format'];
        }

        return 'm/d/Y';
    }

    protected function moduleStatuses()
    {
        $path = base_path('modules_statuses.json');
        if (!is_file($path)) {
            return [];
        }

        try {
            return json_decode((string) file_get_contents($path), true) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function statusEnabled($value)
    {
        return in_array($value, [1, '1', true, 'true', 'yes', 'on'], true);
    }

    protected function humanize($key)
    {
        $label = preg_replace('/(?<!^)([A-Z])/', ' $1', (string) $key);
        $label = str_replace(['_', '-'], ' ', $label);

        return ucwords(trim((string) $label));
    }
}

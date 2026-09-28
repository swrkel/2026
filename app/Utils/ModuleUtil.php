<?php

namespace App\Utils;

use \Module;
use App\Account;
use App\BusinessLocation;
use App\Contact;
use App\Product;
use App\System;
use App\Transaction;
use App\User;

use Modules\HR\Entities\Employee;
use Illuminate\Support\Facades\Cache;
use App\Services\GlobalModuleRegistryCache;

class ModuleUtil extends Util
{
    private static array $installedRequestCache = [];
    private static array $subscriptionRequestCache = [];

    /**
     * This function check if a module is installed or not.
     *
     * @param string $module_name (Exact module name, with first letter capital)
     * @return boolean
     */
    public function isModuleInstalled($module_name)
    {
        $cacheKey = strtolower((string) $module_name);
        if (array_key_exists($cacheKey, self::$installedRequestCache)) {
            return self::$installedRequestCache[$cacheKey];
        }

        if (! GlobalModuleRegistryCache::has($module_name)) {
            return self::$installedRequestCache[$cacheKey] = false;
        }

        try {
            $moduleVersion = System::getProperty($cacheKey . '_version');
            return self::$installedRequestCache[$cacheKey] = ! empty($moduleVersion);
        } catch (\Throwable $e) {
            return self::$installedRequestCache[$cacheKey] = false;
        }
    }

    /**
     * This function check if superadmin module is installed or not.
     * @return boolean
     */
    public function isSuperadminInstalled()
    {
        return $this->isModuleInstalled('Superadmin');
    }

    /**
     * This function check if a function provided exist in all modules
     * DataController, merges the data and returned it.
     *
     * @param string $function_name
     *
     * @return array
     */
    public function getModuleData($function_name, $arguments = null)
    {
        $modules = GlobalModuleRegistryCache::modules();
        $installed_modules = [];
        foreach ($modules as $module => $details) {
            if ($this->isModuleInstalled($details['name'])) {
                $installed_modules[] = $details;
            }
        }
        
        // logger(json_encode($installed_modules));
        
        $data = [];
        if (!empty($modules)) {
            foreach ($modules as $module) {
                $class = 'Modules\\' . $module['name'] . '\Http\Controllers\DataController';

                if (class_exists($class)) {
                    $class_object = new $class();
                    if (method_exists($class_object, $function_name)) {
                        if (!empty($arguments)) {
                            $data[$module['name']] = call_user_func([$class_object, $function_name], $arguments);
                        } else {
                            $data[$module['name']] = call_user_func([$class_object, $function_name]);
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Checks if a module is defined
     *
     * @param string $module_name
     * @return bool
     */
    public function isModuleDefined($module_name)
    {
        $is_installed = $this->isModuleInstalled($module_name);

        $check_for_enable = [];

        $output = !empty($is_installed) ? true : false;

        if (
            in_array($module_name, $check_for_enable) &&
            !$this->isModuleEnabled(strtolower($module_name))
        ) {
            $output = false;
        }

        return $output;
    }

    /**
     * This function check if a business has active subscription packages
     *
     * @param int $business_id
     *
     * @return boolean
     */
    public static function isSubscribed($business_id)
    {
        if (SidebarPermissionUtil::hasSuperAdminBypass()) {
            return true;
        }

        $is_available = GlobalModuleRegistryCache::has('Superadmin');

        if ($is_available) {
            $package = self::currentSubscription((int) $business_id);

            if (empty($package)) {
                return false;
            }
        }

        return true;
    }

    /**
     * This function checks if a business has
     *
     * @param int $business_id
     * @param string $permission
     * @param string $callback_function = null
     *
     * @return boolean
     */
    public static function hasThePermissionInSubscription($business_id, $permission, $callback_function = null)
    {
        if (SidebarPermissionUtil::hasSuperAdminBypass() && ! SidebarPermissionUtil::isSuperAdminInsideBusiness()) {
            return true;
        }

        /*
         | S717: parent modules are no longer authorised by the retiring
         | Super Admin > Manage page/package flag.  Older modules can keep
         | calling this legacy helper, but true parent keys are transparently
         | resolved through Manage Side Bar instead. Child/page/feature keys owned
         | by Manage New are resolved immediately below through the new central
         | global-uid permission reader rather than the retiring Manage path.
         */
        $sidebarParent = SidebarPermissionUtil::manageSidebarParentKeyForPermission(
            (string) $permission,
            (int) $business_id
        );
        if ($sidebarParent !== null) {
            return SidebarPermissionUtil::isManageSidebarEnabled($sidebarParent, (int) $business_id);
        }

        // If Manage New owns this page/tab/feature, read its authoritative
        // central/global-uid state through SidebarPermissionUtil. Do not fall
        // back to Subscription::current_subscription(business_id), because
        // tenant business ids are reused across databases and that legacy read
        // can return another company's old Manage-page values.
        if (SidebarPermissionUtil::isManageNewPermissionKey((string) $permission)) {
            return SidebarPermissionUtil::isAutomaticPermissionEnabled(
                (string) $permission,
                (int) $business_id
            );
        }

        $is_available = GlobalModuleRegistryCache::has('Superadmin');

        if ($is_available) {
            $package = self::currentSubscription((int) $business_id);

            if (!empty($package) && isset($package->package_details) && isset($package->package_details[$permission])) {
                if (!is_null($callback_function)) {
                    $obj = new ModuleUtil();
                    $permissions = $obj->getModuleData($callback_function);

                    $permission_formatted = [];
                    foreach ($permissions as $per) {
                        foreach ($per as $details) {
                            $permission_formatted[$details['name']] = $details['label'];
                        }
                    }

                    if (isset($permission_formatted[$permission])) {
                        return (bool) $package->package_details[$permission];
                    } else {
                        return false;
                    }
                } else {
                    return (bool) $package->package_details[$permission];
                }
            }
        }

        if (auth()->user()) {
            if (auth()->user()->can('superadmin')
                && ! SidebarPermissionUtil::isSuperAdminInsideBusiness()) {
                return true;
            }
        }

        if ($is_available) {
            if (empty($package)) {
                return false;
            } else {
                if ($permission === 'select_pump_operator_in_settlement') {
                    return true;
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Read a business setting saved by Super Admin > All Businesses > Manage
     * without applying the Super Admin functional-access bypass.
     */
    public static function hasConfiguredPermissionInSubscription($business_id, $permission): bool
    {
        if (! GlobalModuleRegistryCache::has('Superadmin')) {
            return false;
        }

        $package = self::currentSubscription((int) $business_id);
        $packageDetails = ! empty($package) && isset($package->package_details)
            ? (array) $package->package_details
            : [];

        return ! empty($packageDetails[$permission]);
    }

    private static function currentSubscription(int $businessId)
    {
        if (array_key_exists($businessId, self::$subscriptionRequestCache)) {
            return self::$subscriptionRequestCache[$businessId];
        }

        try {
            return self::$subscriptionRequestCache[$businessId] =
                \Modules\Superadmin\Entities\Subscription::current_subscription($businessId);
        } catch (\Throwable $e) {
            return self::$subscriptionRequestCache[$businessId] = null;
        }
    }

    /**
     * Returns the name of view used to display for subscription expired.
     *
     * @return string
     */
    public static function expiredResponse($redirect_url = null)
    {
        $response_array = [
            'success' => 0,
            'msg' => __(
                "superadmin::lang.subscription_expired_toastr",
                [
                    'app_name' => env('app.name'),
                    'subscribe_url' => action('\Modules\Superadmin\Http\Controllers\SubscriptionController@index')
                ]
            )
        ];

        if (request()->ajax()) {
            if (request()->wantsJson()) {
                return $response_array;
            } else {
                return view('superadmin::subscription.subscription_expired_modal');
            }
        } else {
            if (is_null($redirect_url)) {
                return back()
                    ->with('status', $response_array);
            } else {
                return redirect($redirect_url)
                    ->with('status', $response_array);
            }
        }
    }

    /**
     * This function check if a business has available quota for various types.
     *
     * @param string $type
     * @param int $business_id
     * @param int $total_rows default 0
     *
     * @return boolean
     */
    public static function isQuotaAvailable($type, $business_id, $total_rows = 0)
    {
        $is_available = GlobalModuleRegistryCache::has('Superadmin');

        if ($is_available) {
            $package = self::currentSubscription((int) $business_id);

            if (empty($package)) {
                return false;
            }

            //Start
            $start_dt = \Carbon\Carbon::parse($package->start_date)->toDateTimeString();
            $end_dt = \Carbon\Carbon::parse($package->end_date)->endOfDay()->toDateTimeString();

            if ($type == 'locations') {
                //Check for available location and max number allowed.
                $max_allowed = isset($package->package_details['location_count']) ? $package->package_details['location_count'] : 0;
                if ($max_allowed == 0) {
                    return true;
                } else {
                    $count = BusinessLocation::where('business_id', $business_id)
                        ->count();
                    if ($count >= $max_allowed) {
                        return false;
                    }
                }
            } elseif ($type == 'users') {
                //Check for available location and max number allowed.
                $max_allowed = isset($package->package_details['user_count']) ? $package->package_details['user_count'] : 0;

                if ($max_allowed == 0) {
                    return true;
                } else {
                    $count = User::where('business_id', $business_id)->where('is_customer', 0)
                        ->count();

                    if ($count >= $max_allowed) {
                        return false;
                    }
                }
            } elseif ($type == 'products') {
                $max_allowed = isset($package->package_details['product_count']) ? $package->package_details['product_count'] : 0;
                if ($max_allowed == 0) {
                    return true;
                } else {
                    $count = Product::where('business_id', $business_id)
                        ->whereBetween('created_at', [$start_dt, $end_dt])
                        ->count();

                    $total_products = $count + $total_rows;
                    if ($total_products >= $max_allowed) {
                        return false;
                    }
                }
            } elseif ($type == 'invoices') {
                $max_allowed = isset($package->package_details['invoice_count']) ? $package->package_details['invoice_count'] : 0;

                if ($max_allowed == 0) {
                    return true;
                } else {
                    $count = Transaction::where('business_id', $business_id)
                        ->where('type', 'purchase')
                        ->whereBetween('created_at', [$start_dt, $end_dt])
                        ->count();
                    if ($count >= $max_allowed) {
                        return false;
                    }
                }
            } elseif ($type == 'customers') {
                $max_allowed = isset($package->package_details['customer_count']) ? $package->package_details['customer_count'] : 0;

                if ($max_allowed == 0) {
                    return true;
                } else {
                    $count = Contact::where('business_id', $business_id)
                        ->where('type', 'customer')
                        ->whereBetween('created_at', [$start_dt, $end_dt])
                        ->count();
                    if ($count >= $max_allowed) {
                        return false;
                    }
                }
            } elseif ($type == 'employees') {
                $max_allowed = isset($package->package_details['employee_count']) ? $package->package_details['employee_count'] : 0;

                if ($max_allowed == 0) {
                    return true;
                } else {
                    $count = Employee::where('business_id', $business_id)
                        ->whereBetween('created_at', [$start_dt, $end_dt])
                        ->count();
                    if ($count >= $max_allowed) {
                        return false;
                    }
                }
            } elseif ($type == 'monthly_total_sales_limit') {
                $max_allowed = isset($package->package_details['monthly_total_sales_limit']) ? $package->package_details['monthly_total_sales_limit'] : 0;

                if ($max_allowed == 0) {
                    return true;
                } else {
                    $start = \Carbon::now()->firstOfMonth()->format('Y-m-d');
                    $end = \Carbon::now()->lastOfMonth()->format('Y-m-d');
                    $total_sales_value = Transaction::where('business_id', $business_id)->where('type', 'sell')
                        ->whereBetween('created_at', [$start, $end])
                        ->sum('final_total');
                    if ($total_sales_value >= $max_allowed) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * This function returns the response for expired quota
     *
     * @param string $type
     * @param int $business_id
     * @param string $redirect_url = null
     *
     * @return \Illuminate\Http\Response
     */
    public static function quotaExpiredResponse($type, $business_id, $redirect_url = null)
    {
        if ($type == 'locations') {
            if (request()->ajax()) {
                $count = BusinessLocation::where('business_id', $business_id)
                    ->count();

                if (request()->wantsJson()) {
                    $response_array = [
                        'success' => 0,
                        'msg' => __("superadmin::lang.max_locations", ['count' => $count])
                    ];
                    return $response_array;
                } else {
                    return view('superadmin::subscription.max_location_modal')
                        ->with('count', $count);
                }
            }
        } elseif ($type == 'users') {
            $count = User::where('business_id', $business_id)
                ->count();

            $response_array = [
                'success' => 0,
                'msg' => __("superadmin::lang.max_users", ['count' => $count])
            ];
            return redirect($redirect_url)
                ->with('status', $response_array);
        } elseif ($type == 'customers') {
            $count = Contact::where('business_id', $business_id)
                ->where('type', 'customer')
                ->count();

            $response_array = [
                'success' => 0,
                'msg' => __("superadmin::lang.max_customers", ['count' => $count])
            ];
            return $response_array;
        } elseif ($type == 'products') {
            $count = Product::where('business_id', $business_id)
                ->count();

            $response_array = [
                'success' => 0,
                'msg' => __("superadmin::lang.max_products", ['count' => $count])
            ];

            return redirect($redirect_url)
                ->with('status', $response_array);
        } elseif ($type == 'invoices') {
            $count = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase')
                ->count();

            $response_array = [
                'success' => 0,
                'msg' => __("superadmin::lang.max_invoices", ['count' => $count])
            ];

            if (request()->wantsJson()) {
                return $response_array;
            } else {
                return redirect($redirect_url)
                    ->with('status', $response_array);
            }
        } elseif ($type == 'employees') {
            $count = Employee::where('business_id', $business_id)->count();

            $response_array = [
                'success' => 0,
                'msg' => __("superadmin::lang.max_employees", ['count' => $count])
            ];
            return $response_array;
        } elseif ($type == 'monthly_total_sales_limit') {
            $response_array = [
                'success' => 0,
                'msg' => __("superadmin::lang.max_monthly_sales")
            ];
            return $response_array;
        }
    }

    public function accountsDropdown($business_id, $prepend_none = false, $closed = false)
    {
        $dropdown = [];
      
        $dropdown = Account::forDropdown($business_id, $prepend_none, $closed);

        return $dropdown;
    }

    /**
     * This function returns the extra form fields in array format
     * required by any module which will be included during adding
     * or updating a resource
     *
     * @param string $function_name function name to be called to get data from
     *
     * @return array
     */
    public function getModuleFormField($function_name)
    {
        $form_fields = [];
        $module_form_fields = $this->getModuleData($function_name);
        if (!empty($module_form_fields)) {
            foreach ($module_form_fields as $key => $value) {
                if (!empty($value) && is_array($value)) {
                    $form_fields = array_merge($form_fields, $value);
                }
            }
        }

        return $form_fields;
    }

    public function getApiSettings($api_token)
    {
        $settings = \Modules\Ecommerce\Entities\EcomApiSetting::where('api_token', $api_token)
            ->first();

        return $settings;
    }

    public function availableModules()
    {
        return [
            'purchase' => ['name' => __('purchase.purchases')],
            'add_sale' => ['name' => __('sale.add_sale')],
            'pos_sale' => ['name' => __('sale.pos_sale')],
            'stock_transfer' => ['name' => __('lang_v1.stock_transfers')],
            'stock_adjustment' => ['name' => __('stock_adjustment.stock_adjustment')],
            'expenses' => ['name' => __('expense.expenses')],
            'account' => ['name' => __('account.accounting_module')],
            'banking_module' => ['name' => __('account.banking_module')],
            'tables' => [
                'name' => __('restaurant.tables'),
                'tooltip' => __('restaurant.tooltip_tables')
            ],
            'modifiers' => [
                'name' => __('restaurant.modifiers'),
                'tooltip' => __('restaurant.tooltip_modifiers')
            ],
            'service_staff' => [
                'name' => __('restaurant.service_staff'),
                'tooltip' => __('restaurant.tooltip_service_staff')
            ],
            'booking' => ['name' => __('lang_v1.enable_booking')],
            'kitchen' => [
                'name' => __('restaurant.kitchen_for_restaurant')
            ],
            'enable_subscription' => ['name' => __('lang_v1.enable_subscription')],
            'type_of_service' => [
                'name' => __('lang_v1.types_of_service'),
                'tooltip' => __('lang_v1.types_of_service_help_long')
            ]
        ];
    }

    /**
     * Validate module category types and
     * return module category data if validates
     *
     * @param  string  $category_type
     * @return array
     */
    public function getTaxonomyData($category_type)
    {
        $category_types = ['product'];

        $modules_data = $this->getModuleData('addTaxonomies');
        
        
        $module_data = [];
        foreach ($modules_data as $module => $data) {
            foreach ($data  as $key => $value) {
                //key is category type
                //check if category type is duplicate
                if (!in_array($key, $category_types)) {
                    $category_types[] = $key;
                }
                // else {
                //     echo __('lang_v1.duplicate_taxonomy_type_found');
                //     exit;
                // }

                if ($category_type == $key) {
                    $module_data = $value;
                }
            }
        }

        if (!in_array($category_type, $category_types)) {
            echo __('lang_v1.taxonomy_type_not_found');
            exit;
        }
        
        return $module_data;
    }
}

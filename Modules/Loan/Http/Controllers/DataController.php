<?php

namespace Modules\Loan\Http\Controllers;

use Modules\Accounting\Services\PermissionService;
use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    // use PermissionService;

    /**
     * Defines user permissions for the module.
     * @return array
     */
    // public function user_permissions()
    // {
    //     return $this->getPermissions('loan');
    // }

    public function superadmin_package()
    {
        return [
            [
                'name' => 'loan_module',
                'label' => __('loan::lang.loan'),
                'default' => false
            ]
        ];
    }

    /**
     * Adds Repair menus
     * @return null
     */
    public function modifyAdminMenu()
    {
        $is_loan_enabled = true;

        // if ($is_loan_enabled && (auth()->user()->can('superadmin') || auth()->user()->can('repair.view') || auth()->user()->can('job_sheet.view_assigned') || auth()->user()->can('job_sheet.view_all'))) {
        if ($is_loan_enabled) {
            Menu::modify('admin-sidebar-menu', function ($menu) {
                $menu->url(
                    action('\Modules\Loan\Http\Controllers\DashboardController@index'),
                    __('loan::lang.loan'),
                    ['icon' => 'fa fas fa-money-check-alt', 'id' => 'tour_step9', 'active' => request()->segment(1) == 'contact_loan', 'background-color: #80ff04 !important;']
                )
                    ->order(24);
            });
        }
    }
}

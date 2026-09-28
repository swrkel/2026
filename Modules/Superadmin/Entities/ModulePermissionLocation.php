<?php

namespace Modules\Superadmin\Entities;

use Illuminate\Database\Eloquent\Model;

class ModulePermissionLocation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'locations' => 'array'
    ];

    public static function getModulePermissionLocations($business_id, $module_name){
       return  ModulePermissionLocation::where('business_id', $business_id)->where('module_name', $module_name)->select('locations')->first();
    }

    public static function getModulePermissionList(){
       return  ['mf_module', 'agent_module', 'sales_agent_module', 'sales_agents_module', 'hr_module', 'accounting_module', 'restaurant_module', 'number_of_pumps', 'petro_pd_module', 'loan_module', 'pump_operator_module', 'ev_charging_module', 'patient_module', 'patient_test_module'];
    }
}

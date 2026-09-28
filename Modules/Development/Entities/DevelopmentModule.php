<?php

namespace Modules\Development\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DevelopmentModule extends Model
{
    use HasFactory;

    protected $table = 'development_modules';
    protected $fillable = ['name'];
    
    public static function hasDevelopmentModuleAccess(){
        $business_id = request()->session()->get('user.business_id');
        if(!auth()->user()->can('superadmin') && !empty($business_id)){
            $subscription = \Modules\Superadmin\Entities\Subscription::current_subscription($business_id);
            if(!empty($subscription)){
                $pacakge_details = $subscription->package_details;
                if (array_key_exists("development", $pacakge_details)) {
                    return $pacakge_details['development'];
                }
            }
        } 
        return true;
    }
}

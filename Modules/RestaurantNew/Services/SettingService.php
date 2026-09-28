<?php
namespace Modules\RestaurantNew\Services;
use Modules\RestaurantNew\Entities\Setting;
class SettingService
{
 public function __construct(private TenantScopeService $scope){}
 public function all(): array
 {
  $businessId=$this->scope->businessId();return Setting::withoutGlobalScopes()->where('business_id',$businessId)->pluck('setting_value','setting_key')->all();
 }
 public function setMany(array $values): void
 {
  $businessId=$this->scope->businessId();foreach($values as $key=>$value)Setting::withoutGlobalScopes()->updateOrCreate(['business_id'=>$businessId,'setting_key'=>$key],['setting_value'=>is_bool($value)?($value?'1':'0'):(string)$value,'updated_by'=>auth()->id()]);
 }
 public function get(string $key,$default=null){$businessId=$this->scope->businessId();return Setting::withoutGlobalScopes()->where('business_id',$businessId)->where('setting_key',$key)->value('setting_value')??$default;}
}

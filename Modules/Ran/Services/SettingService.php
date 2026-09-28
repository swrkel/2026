<?php
namespace Modules\Ran\Services;use Modules\Ran\Entities\Setting;use Modules\Ran\Support\RanContext;
class SettingService{public function all():array{return Setting::query()->pluck('value','key')->all();}public function save(array $values):void{foreach($values as $key=>$value)Setting::query()->updateOrCreate(['business_id'=>RanContext::businessId(),'key'=>$key],['value'=>$value,'value_type'=>is_bool($value)?'boolean':'string']);}}

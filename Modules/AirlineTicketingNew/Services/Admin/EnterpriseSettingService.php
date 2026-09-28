<?php
namespace Modules\AirlineTicketingNew\Services\Admin;

use Modules\AirlineTicketingNew\Entities\EnterpriseSetting;

class EnterpriseSettingService
{
    public function get(int $businessId,string $key,mixed $default=null): mixed
    {
        return EnterpriseSetting::query()
            ->where('business_id',$businessId)
            ->where('setting_key',$key)
            ->value('setting_value_json') ?? $default;
    }

    public function put(int $businessId,string $key,mixed $value,string $group='general'): EnterpriseSetting
    {
        return EnterpriseSetting::query()->updateOrCreate(
            ['business_id'=>$businessId,'setting_key'=>$key],
            ['setting_group'=>$group,'setting_value_json'=>$value]
        );
    }
}

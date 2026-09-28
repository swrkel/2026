<?php
namespace Modules\EzyLaw\Services;
use Modules\EzyLaw\Entities\LawSetting;
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class SettingsService
{
    public function get(string $key, $default=null, ?int $locationId=null){
        $q=LawSetting::query()->where('setting_key',$key);
        $locationId ? $q->where('location_id',$locationId) : $q->whereNull('location_id');
        $row=$q->first(); return $row ? $row->setting_value : $default;
    }
    public function put(string $key, $value, ?int $locationId=null): void{
        LawSetting::updateOrCreate(['business_id'=>EzyLawTenantGuard::businessId(),'location_id'=>$locationId,'setting_key'=>$key],['setting_value'=>(string)$value]);
    }
    public function nextNumber(string $type): string{
        $prefix=(string)$this->get($type.'_prefix', strtoupper(substr($type,0,3)).'-');
        $next=(int)$this->get($type.'_next',1);
        $this->put($type.'_next',$next+1);
        return $prefix.str_pad((string)$next,6,'0',STR_PAD_LEFT);
    }
}

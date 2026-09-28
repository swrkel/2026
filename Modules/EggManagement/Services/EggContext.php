<?php
namespace Modules\EggManagement\Services;

use Illuminate\Support\Facades\Auth;
use RuntimeException;

class EggContext
{
    protected function firstSession(array $keys)
    {
        foreach ($keys as $key) {
            $value = session($key);
            if ($value !== null && $value !== '') return $value;
        }
        return null;
    }

    public function userId()
    {
        return Auth::id();
    }

    public function businessId($required = true)
    {
        $id = $this->firstSession(config('egg.context.business_session_keys', []));
        if (!$id && Auth::check() && isset(Auth::user()->business_id)) $id = Auth::user()->business_id;
        if ($required && !$id) throw new RuntimeException('Egg Management could not resolve the active business.');
        return $id ? (int) $id : null;
    }

    public function locationId($required = false)
    {
        $id = $this->firstSession(config('egg.context.location_session_keys', []));
        return $id ? (int) $id : null;
    }

    public function storeId($required = false)
    {
        $id = $this->firstSession(config('egg.context.store_session_keys', []));
        return $id ? (int) $id : null;
    }

    public function scopePayload(array $override = [])
    {
        return array_merge([
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'store_id' => $this->storeId(),
        ], $override);
    }
}

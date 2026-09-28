<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;

class ClearSettingsCache
{
    public function handle($event)
    {
        $business_id = null;
        
        // Try to get business_id from model first
        if (isset($event->business_id)) {
            $business_id = $event->business_id;
        } elseif (request()->hasSession()) {
            $business_id = request()->session()->get('user.business_id');
        }
        
        if ($business_id) {
            // Clear all settings cache for this business
            $keys = [
                "settings_provinces_{$business_id}",
                "settings_units_{$business_id}",
                "settings_districts_{$business_id}",
                "settings_areas_{$business_id}",
                "settings_products_{$business_id}",
                "settings_categories_{$business_id}",
                "settings_subcategories_{$business_id}",
                "settings_business_{$business_id}",
            ];
            
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }
    }
}
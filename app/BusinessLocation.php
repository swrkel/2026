<?php

namespace App;

use App\Services\BusinessLocationAccessService;
use DB;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class BusinessLocation extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;

    
    protected static $logName = 'Business Location'; 

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected static function booted()
    {
        static::addGlobalScope('business_location_visibility', function ($query) {
            if (app()->runningInConsole() || ! auth()->check()) {
                return;
            }

            app(BusinessLocationAccessService::class)->applyScope(
                $query,
                'business_locations.business_id',
                'business_locations.id',
                null,
                true
            );
        });
    }

    /**
     * Return list of locations for a business
     *
     * @param int $business_id
     * @param boolean $show_all = false
     * @param array $receipt_printer_type_attribute =
     *
     * @return array
     */
    public static function forDropdown($business_id, $show_all = false, $receipt_printer_type_attribute = false, $append_id = true, $check_super_admin = false)
    {
        $query = BusinessLocation::Active();
        $locationAccess = app(BusinessLocationAccessService::class);
        $allowCentralAll = $check_super_admin && $locationAccess->isCentralSuperAdmin();

        if (! $allowCentralAll) {
            $contextBusinessId = $locationAccess->businessId();
            if (! $contextBusinessId
                || ($business_id && (int) $business_id !== $contextBusinessId)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('business_id', $contextBusinessId);
            }
        }

        $permitted_locations = $locationAccess->permittedLocationIds();
        if (! $allowCentralAll && $permitted_locations != 'all') {
            if (! auth()->user()->is_customer) {
                $query->whereIn('id', $permitted_locations);
            }
        }

        static::applyDropdownOrder($query, $permitted_locations);

        if ($append_id) {
            $query->select(
                DB::raw("IF(location_id IS NULL OR location_id='', name, CONCAT(name, ' (', location_id, ')')) AS name"),
                'id',
                'receipt_printer_type',
                'selling_price_group_id',
                'default_payment_accounts'
            );
        }

        $result = $query->get();

        $locations = $result->pluck('name', 'id');

        if ($show_all) {
            $locations->prepend(__('report.all_locations'), '');
        }

        if ($receipt_printer_type_attribute) {
            $attributes = collect($result)->mapWithKeys(function ($item) {
                return [$item->id => [
                            'data-receipt_printer_type' => $item->receipt_printer_type,
                            'data-default_price_group' => $item->selling_price_group_id,
                            'data-default_payment_accounts' => $item->default_payment_accounts
                        ]
                    ];
            })->all();

            return ['locations' => $locations, 'attributes' => $attributes];
        } else {
            return $locations;
        }
    }
    
    public static function getDropdownCollection($business_id, $show_all = false, $receipt_printer_type_attribute = false, $append_id = true)
    {
        $locationAccess = app(BusinessLocationAccessService::class);
        $contextBusinessId = $locationAccess->businessId();
        $query = BusinessLocation::Active();

        if (! $contextBusinessId
            || ($business_id && (int) $business_id !== $contextBusinessId)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->where('business_id', $contextBusinessId);
        }

        $permitted_locations = $locationAccess->permittedLocationIds();
        if ($permitted_locations != 'all') {
            if(!auth()->user()->is_customer){
                $query->whereIn('id', $permitted_locations);
            }
        }

        static::applyDropdownOrder($query, $permitted_locations);

        if ($append_id) {
            $query->select(
                DB::raw("IF(location_id IS NULL OR location_id='', name, CONCAT(name, ' (', location_id, ')')) AS name"),
                'id',
                'receipt_printer_type',
                'selling_price_group_id',
                'default_payment_accounts'
            );
        }

        $result = $query->get();

        $locations = $result/*->pluck('name', 'id')*/;

        if ($show_all) {
            $locations->prepend(__('report.all_locations'), '');
        }

        if ($receipt_printer_type_attribute) {
            $attributes = collect($result)->mapWithKeys(function ($item) {
                return [$item->id => [
                            'data-receipt_printer_type' => $item->receipt_printer_type,
                            'data-default_price_group' => $item->selling_price_group_id,
                            'data-default_payment_accounts' => $item->default_payment_accounts
                        ]
                    ];
            })->all();

            return ['locations' => $locations, 'attributes' => $attributes];
        } else {
            return $locations;
        }
    }

    public function price_group()
    {
        return $this->belongsTo(\App\SellingPriceGroup::class, 'selling_price_group_id');
    }

    /**
     * Scope a query to only include active location.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Keep permitted locations in the same order in which they were assigned
     * to the user. The first returned location is therefore the global default.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array|string  $permitted_locations
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function applyDropdownOrder($query, $permitted_locations)
    {
        if (is_array($permitted_locations)) {
            $location_ids = [];

            foreach ($permitted_locations as $location_id) {
                if (is_numeric($location_id) && (int) $location_id > 0) {
                    $location_ids[] = (int) $location_id;
                }
            }

            $location_ids = array_values(array_unique($location_ids));

            if (! empty($location_ids)) {
                $placeholders = implode(', ', array_fill(0, count($location_ids), '?'));

                return $query->orderByRaw("FIELD(id, {$placeholders})", $location_ids);
            }
        }

        return $query->orderBy('id');
    }


    
    public function stores()
    {
        return $this->hasMany(\App\Store::class, 'location_id');
    }


    public static function getDefaultAccountIdForMethod($method_name, $location_id)
    {
        $business_id = request()->session()->get('business.id');
        $account_id = null;
        $defualt_accounts = BusinessLocation::where('business_id', $business_id)->where('id',  $location_id)->first();
        if (!empty($defualt_accounts)) {
            $default_payment_accounts = (array) json_decode($defualt_accounts->default_payment_accounts);

            $account_id = $default_payment_accounts[$method_name]->account;
        }

        return $account_id;
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }

    /**
     * Get the currency associated with the BusinessLocation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }


}

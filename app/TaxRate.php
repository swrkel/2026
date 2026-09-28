<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Services\GlobalLookupCache;

class TaxRate extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;


    protected static $logName = 'Tax Rate'; 

    use SoftDeletes;
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }

    /**
     * Return list of tax rate dropdown for a business
     *
     * @param $business_id int
     * @param $prepend_none = true (boolean)
     * @param $include_attributes = false (boolean)
     *
     * @return array['tax_rates', 'attributes']
     */
    public static function forBusinessDropdown(
        $business_id,
        $prepend_none = true,
        $include_attributes = false
    ) {
        $payload = GlobalLookupCache::remember('tax-rates', ['business_id' => (int) $business_id], function () use ($business_id) {
            return TaxRate::where('business_id', $business_id)
                ->select(['id', 'name', 'amount'])
                ->orderBy('name')
                ->get()
                ->map(fn ($tax) => ['id' => $tax->id, 'name' => $tax->name, 'amount' => $tax->amount])
                ->all();
        });

        $result = collect($payload);
        $tax_rates = $result->pluck('name', 'id');
        if ($prepend_none) {
            $tax_rates->prepend(__('lang_v1.none'), '');
        }

        $attributes = null;
        if ($include_attributes) {
            $attributes = $result->mapWithKeys(fn ($item) => [$item['id'] => ['data-rate' => $item['amount']]])->all();
        }

        return ['tax_rates' => $tax_rates, 'attributes' => $attributes];
    }

    /**
     * Return list of tax rate for a business
     *
     * @return array
     */
    public static function forBusiness($business_id)
    {
        return GlobalLookupCache::remember('tax-rates-array', ['business_id' => (int) $business_id], function () use ($business_id) {
            return TaxRate::where('business_id', $business_id)
                ->select(['id', 'name', 'amount'])
                ->orderBy('name')
                ->get()
                ->toArray();
        });
    }

    /**
     * Return list of tax rates associated with the group_tax
     *
     * @return object
     */
    public function sub_taxes()
    {
        return $this->belongsToMany(\App\TaxRate::class, 'group_sub_taxes', 'group_tax_id', 'tax_id');
    }
}

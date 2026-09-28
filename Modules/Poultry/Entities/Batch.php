<?php

namespace Modules\Poultry\Entities;

use Carbon\Carbon;
use Modules\Poultry\Entities\Shared\Contact;

/**
 * A batch (flock) - a cohort of birds placed on a date into a house and
 * tracked as a unit until depletion. The spine of the module.
 *
 * COSTING NOTE
 *   A broiler batch and a layer batch are financially different animals.
 *   A broiler batch behaves like a work in progress job: doc + feed + meds
 *   accumulate and release to cost of sales at harvest. A layer batch behaves
 *   like a depreciating asset: the rearing cost through to point of lay is
 *   capitalised and amortised across the laying cycle, with the spent hen sale
 *   as residual value. See Services\CostingService - posting both the same way
 *   makes cost per egg meaningless.
 */
class Batch extends PoultryModel
{
    protected $table = 'poultry_batches';

    protected $dates = ['placement_date', 'expected_depletion_date', 'closed_on'];

    public const BIRD_TYPES = [
        'broiler' => 'Broiler',
        'layer'   => 'Layer',
        'pullet'  => 'Pullet (rearing)',
        'breeder' => 'Breeder',
    ];

    public const STATUSES = [
        'draft'       => 'Draft',
        'active'      => 'Active',
        'transferred' => 'Transferred',
        'closed'      => 'Closed',
    ];

    /* ------------------------------------------------------------------ */
    /* Relationships                                                       */
    /* ------------------------------------------------------------------ */

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id');
    }

    public function house()
    {
        return $this->belongsTo(House::class, 'house_id');
    }

    public function breed()
    {
        return $this->belongsTo(Breed::class, 'breed_id');
    }

    /** Hatchery or trader the day old chicks came from - a shared contact. */
    public function supplier()
    {
        return $this->belongsTo(Contact::class, 'supplier_contact_id');
    }

    public function dailyRecords()
    {
        return $this->hasMany(DailyRecord::class, 'batch_id');
    }

    public function weightSamples()
    {
        return $this->hasMany(WeightSample::class, 'batch_id');
    }

    public function feedConsumptions()
    {
        return $this->hasMany(FeedConsumption::class, 'batch_id');
    }

    public function eggCollections()
    {
        return $this->hasMany(EggCollection::class, 'batch_id');
    }

    public function vaccinations()
    {
        return $this->hasMany(VaccinationRecord::class, 'batch_id');
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class, 'batch_id');
    }

    public function harvests()
    {
        return $this->hasMany(Harvest::class, 'batch_id');
    }

    public function costs()
    {
        return $this->hasMany(BatchCost::class, 'batch_id');
    }

    public function transfersOut()
    {
        return $this->hasMany(BatchTransfer::class, 'batch_id');
    }

    /* ------------------------------------------------------------------ */
    /* Scopes                                                              */
    /* ------------------------------------------------------------------ */

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['draft', 'active']);
    }

    public function scopeOfType($query, $birdType)
    {
        return $query->where('bird_type', $birdType);
    }

    /** Batches that produce eggs - layers and breeders. */
    public function scopeLaying($query)
    {
        return $query->whereIn('bird_type', ['layer', 'breeder']);
    }

    /* ------------------------------------------------------------------ */
    /* Derived attributes                                                  */
    /* ------------------------------------------------------------------ */

    /** Age of the flock in days, as at today or a supplied date. */
    public function ageInDays($asAt = null)
    {
        $asAt = $asAt ? Carbon::parse($asAt) : Carbon::today();

        return max(0, Carbon::parse($this->placement_date)->diffInDays($asAt));
    }

    public function getAgeDaysAttribute()
    {
        return $this->ageInDays();
    }

    public function getAgeWeeksAttribute()
    {
        return (int) floor($this->ageInDays() / 7);
    }

    /**
     * Week of lay, counted from point of lay rather than from placement.
     * Layer performance is read against this, not against flock age.
     */
    public function getWeekOfLayAttribute()
    {
        $pointOfLay = (int) Setting::get('point_of_lay_week', config('poultry.defaults.point_of_lay_week'));
        $weekOfLay  = $this->age_weeks - $pointOfLay;

        return $weekOfLay > 0 ? $weekOfLay : 0;
    }

    public function getTotalMortalityAttribute()
    {
        return (int) $this->dailyRecords()->sum('mortality');
    }

    public function getTotalCullsAttribute()
    {
        return (int) $this->dailyRecords()->sum('culls');
    }

    /** Cumulative mortality as a percentage of birds placed. */
    public function getMortalityPctAttribute()
    {
        if ($this->initial_qty <= 0) {
            return 0.0;
        }

        return round((($this->total_mortality + $this->total_culls) / $this->initial_qty) * 100, 2);
    }

    public function getTotalFeedKgAttribute()
    {
        return (float) $this->feedConsumptions()->sum('qty');
    }

    public function getIsOpenAttribute()
    {
        return in_array($this->status, ['draft', 'active'], true);
    }

    /**
     * The active drug withdrawal, if any. While this is not null, eggs and
     * meat from this batch must not be sold. Enforced by Services\WithdrawalGuard.
     */
    public function activeWithdrawal()
    {
        return $this->treatments()
            ->whereNotNull('withdrawal_until')
            ->whereDate('withdrawal_until', '>=', Carbon::today()->toDateString())
            ->orderByDesc('withdrawal_until')
            ->first();
    }

    public function getIsUnderWithdrawalAttribute()
    {
        return $this->activeWithdrawal() !== null;
    }

    public function getLabelAttribute()
    {
        return $this->batch_code.' ('.(self::BIRD_TYPES[$this->bird_type] ?? $this->bird_type).')';
    }

    public static function dropdown($birdType = null, $onlyOpen = true, $businessId = null)
    {
        $query = static::query()->forBusiness($businessId);

        if ($onlyOpen) {
            $query->open();
        }

        if ($birdType) {
            $query->where('bird_type', $birdType);
        }

        return $query->orderByDesc('placement_date')
            ->pluck('batch_code', 'id')
            ->toArray();
    }
}

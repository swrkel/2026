<?php

namespace Modules\SW\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class Settlement extends SWModel
{
    use SoftDeletes;

    protected $table = 'sw_settlements';

    public const STATUS_DRAFT   = 0;
    public const STATUS_OPEN    = 1;
    public const STATUS_SETTLED = 2;
    public const STATUS_VOID    = 3;

    protected $casts = [
        'cash_denomination' => 'array',
        'total_meter_sales' => 'decimal:4',
        'total_other_sales' => 'decimal:4',
        'total_other_income' => 'decimal:4',
        'total_credit_sales' => 'decimal:4',
        'transaction_date' => 'date',
        'finish_date' => 'date',
        'total_sales' => 'decimal:4',
        'total_collected' => 'decimal:4',
        'variance' => 'decimal:4',
        'is_edited' => 'boolean',
    ];

    public function lines()
    {
        return $this->hasMany(SettlementLine::class, 'settlement_id');
    }

    public function collections()
    {
        return $this->hasMany(Collection::class, 'settlement_id');
    }

    /** 8044 */
    public function otherSales()
    {
        return $this->hasMany(OtherSale::class, 'settlement_id');
    }

    /** 8045 */
    public function otherIncome()
    {
        return $this->hasMany(OtherIncome::class, 'settlement_id');
    }

    public function creditSales()
    {
        return $this->hasMany(SettlementCreditSale::class, 'settlement_id');
    }

    /**
     * Recompute the section totals from the lines.
     *
     * The totals on this row are a CACHE of the lines, so the list page need
     * not re-sum every line of every settlement. They are refreshed on every
     * save; the lines are always the source.
     */
    public function recalculateTotals(): void
    {
        $meter = (float) $this->lines()->sum('amount');
        $otherSales = (float) $this->otherSales()->sum('amount');
        $otherIncome = (float) $this->otherIncome()->sum('amount');
        $creditSales = (float) $this->creditSales()->sum('amount');
        $collected = (float) $this->collections()->sum('amount');

        $this->total_meter_sales = $meter;
        $this->total_other_sales = $otherSales;
        $this->total_other_income = $otherIncome;
        $this->total_credit_sales = $creditSales;

        /*
         | IS2240: Credit Sales are a METHOD OF SETTLEMENT (Accounts Receivable),
         | not an additional sale. The underlying fuel/product sale is already
         | represented by Meter Sales / Other Sales / Other Income. Therefore
         | creditSales remains cached in total_credit_sales for reporting, but
         | it must NOT be added to total_sales a second time.
        */
        $this->total_sales = round($meter + $otherSales + $otherIncome, 4);
        $this->total_collected = $collected;
        $this->variance = round($collected - (float) $this->total_sales, 4);
    }

    /*
     | 8043: several SW Shifts can be settled together, so this is a join.
     | Every shift must be CLOSED and must belong to this settlement's
     | operator - both enforced when saving.
    */
    public function shifts()
    {
        return $this->belongsToMany(
            Shift::class,
            'sw_settlement_shifts',
            'settlement_id',
            'sw_shift_id'
        )->withTimestamps();
    }

    /**
     * Settled records are the ones that have posted to Finance. Drafts have not,
     * which is why a draft never updates a pump's meter reading either - an
     * abandoned draft would otherwise leave a pump showing a reading that never
     * happened.
     */
    public function isSettled(): bool
    {
        return (int) $this->status === self::STATUS_SETTLED;
    }

    public function statusLabel(): string
    {
        return [
            self::STATUS_DRAFT   => 'Draft',
            self::STATUS_OPEN    => 'Open',
            self::STATUS_SETTLED => 'Settled',
            self::STATUS_VOID    => 'Void',
        ][(int) $this->status] ?? 'Unknown';
    }

    public function scopeForLocation($query, int $businessId, int $locationId)
    {
        return $query->where('business_id', $businessId)
                     ->where('location_id', $locationId);
    }
}

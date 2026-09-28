<?php

namespace Modules\Poultry\Services;

use Illuminate\Support\Facades\DB;
use Modules\Poultry\Entities\HatchSet;
use Modules\Poultry\Support\BusinessContext;

/**
 * Incubation: setting hatching eggs, candling, transfer to hatcher, hatch.
 * Day old chicks produced can be posted into shared stock so they are sellable
 * or transferable to another farm through the existing modules.
 */
class HatcheryService
{
    protected $stock;

    public function __construct(StockGateway $stock)
    {
        $this->stock = $stock;
    }

    public function set(array $data)
    {
        $data['business_id'] = BusinessContext::id();
        $data['status']      = 'set';

        if (empty($data['set_code'])) {
            $data['set_code'] = $this->generateSetCode($data['set_date']);
        }

        return HatchSet::create($data);
    }

    /** Candling result - fertile versus clear. */
    public function candle(HatchSet $set, array $data)
    {
        $set->candling_date = $data['candling_date'];
        $set->fertile_eggs  = $data['fertile_eggs'];
        $set->clear_eggs    = $data['clear_eggs'] ?? ($set->eggs_set - $data['fertile_eggs']);
        $set->status        = 'candled';

        $set->recalculate()->save();

        return $set;
    }

    /**
     * Record the hatch and, where a variation is mapped, produce the saleable
     * chicks into shared stock.
     */
    public function hatch(HatchSet $set, array $data)
    {
        return DB::transaction(function () use ($set, $data) {
            $set->hatch_date      = $data['hatch_date'];
            $set->chicks_hatched  = $data['chicks_hatched'];
            $set->saleable_chicks = $data['saleable_chicks'] ?? $data['chicks_hatched'];
            $set->culled_chicks   = $data['culled_chicks'] ?? 0;
            $set->dead_in_shell   = $data['dead_in_shell'] ?? max(0, $set->fertile_eggs - $data['chicks_hatched']);
            $set->status          = 'hatched';

            $set->recalculate()->save();

            if (! empty($set->variation_id) && ! empty($data['location_id']) && $set->saleable_chicks > 0) {
                $transactionId = $this->stock->produce([
                    'business_id'      => $set->business_id,
                    'product_id'       => $set->product_id,
                    'variation_id'     => $set->variation_id,
                    'location_id'      => $data['location_id'],
                    'qty'              => $set->saleable_chicks,
                    'date'             => $data['hatch_date'],
                    'transaction_type' => config('poultry.transaction_types.production'),
                    'notes'            => 'Day old chicks - hatch '.$set->set_code,
                ]);

                if ($transactionId) {
                    $set->stock_transaction_id = $transactionId;
                    $set->is_posted            = true;
                    $set->save();
                }
            }

            return $set;
        });
    }

    protected function generateSetCode($setDate)
    {
        $stem = 'HS-'.\Carbon\Carbon::parse($setDate)->format('Ym').'-';

        $last = HatchSet::query()->forBusiness()
            ->where('set_code', 'like', $stem.'%')
            ->orderByDesc('set_code')
            ->value('set_code');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $stem.str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}

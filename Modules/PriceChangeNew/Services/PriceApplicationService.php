<?php

namespace Modules\PriceChangeNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PriceChangeNew\Entities\PriceChange;
use Modules\PriceChangeNew\Entities\PriceChangeApplication;
use Modules\PriceChangeNew\Entities\PriceChangeApplicationLine;
use Modules\PriceChangeNew\Entities\PriceChangeLine;
use Modules\PriceChangeNew\Entities\PriceChangeScopePrice;
use RuntimeException;
use Throwable;

class PriceApplicationService
{
    public function __construct(
        private PriceChangeSettingsService $settings,
        private PriceChangeAuditService $audits,
        private PriceCalculator $calculator
    ) {
    }

    public function apply(PriceChange $change, ?int $userId, string $triggerType = 'manual'): PriceChangeApplication
    {
        $application = PriceChangeApplication::query()->create([
            'price_change_id' => $change->id,
            'business_id' => $change->business_id,
            'triggered_by' => $userId,
            'trigger_type' => $triggerType,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            DB::transaction(function () use ($change, $application, $userId): void {
                $locked = PriceChange::query()
                    ->forBusiness((int) $change->business_id)
                    ->whereKey($change->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array($locked->status, ['approved', 'scheduled', 'failed', 'partial'], true)) {
                    throw new RuntimeException('Only approved, due scheduled, failed, or partially applied price changes may be applied.');
                }
                if ($locked->effective_at && $locked->effective_at->isFuture()) {
                    throw new RuntimeException('The effective date and time has not been reached.');
                }
                if ($locked->stock_price_mode !== 'all_stock') {
                    throw new RuntimeException('Old-stock/new-stock price layering is not enabled in this parcel.');
                }

                $locked->application_attempts = (int) $locked->application_attempts + 1;
                $locked->last_attempt_at = now();
                $locked->failure_message = null;
                $locked->save();

                $policy = (string) $this->settings->get($locked->business_id, 'conflict_policy', 'stop_all');
                $result = $locked->application_scope === 'location_price_groups'
                    ? $this->applyLocationPriceGroups($locked, $application, $policy)
                    : $this->applyBusinessBasePrices($locked, $application, $policy);

                $status = $result['failed'] > 0
                    ? ($result['success'] > 0 ? 'partial' : 'failed')
                    : 'applied';

                $locked->status = $status;
                $locked->applied_by = $userId;
                $locked->applied_at = $result['success'] > 0 ? now() : null;
                $locked->failure_message = $result['failed'] > 0 ? $result['message'] : null;
                $locked->save();

                $application->status = $status === 'applied' ? 'success' : $status;
                $application->success_count = $result['success'];
                $application->failed_count = $result['failed'];
                $application->message = $result['message'];
                $application->completed_at = now();
                $application->payload = ['application_scope' => $locked->application_scope, 'conflict_policy' => $policy];
                $application->save();

                $this->audits->record(
                    $locked,
                    $status === 'applied' ? 'prices_applied' : ($status === 'partial' ? 'prices_partially_applied' : 'price_application_failed'),
                    $change->status,
                    $status,
                    ['application_id' => $application->id, 'success_count' => $result['success'], 'failed_count' => $result['failed'], 'message' => $result['message']],
                    $userId
                );
            }, 3);
        } catch (Throwable $e) {
            $application->status = 'failed';
            $application->failed_count = max(1, (int) $application->failed_count);
            $application->message = $e->getMessage();
            $application->completed_at = now();
            $application->save();

            $fresh = PriceChange::query()->whereKey($change->id)->first();
            $isDue = $fresh && (! $fresh->effective_at || ! $fresh->effective_at->isFuture());
            $shouldMarkFailed = $fresh
                && $isDue
                && in_array($fresh->status, ['approved', 'failed', 'partial'], true);

            if ($shouldMarkFailed) {
                $from = $fresh->status;
                $fresh->status = 'failed';
                $fresh->failure_message = $e->getMessage();
                $fresh->last_attempt_at = now();
                $fresh->application_attempts = (int) $fresh->application_attempts + 1;
                $fresh->save();
                $this->audits->record($fresh, 'price_application_failed', $from, 'failed', [
                    'application_id' => $application->id,
                    'message' => $e->getMessage(),
                ], $userId);
            }
        }

        return $application->fresh(['lines']);
    }

    /** @return array{processed:int,applied:int,partial:int,failed:int} */
    public function applyDue(?int $businessId = null, int $limit = 100, bool $respectAutoSetting = true): array
    {
        $summary = ['processed' => 0, 'applied' => 0, 'partial' => 0, 'failed' => 0];
        $query = PriceChange::query()
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->whereIn('status', ['approved', 'scheduled', 'failed', 'partial'])
            ->where(function ($q): void {
                $q->whereNull('effective_at')->orWhere('effective_at', '<=', now());
            })
            ->orderBy('effective_at')
            ->orderBy('id')
            ->limit(max(1, $limit));

        foreach ($query->get() as $change) {
            if ($respectAutoSetting && ! (bool) $this->settings->get($change->business_id, 'auto_apply_due', false)) {
                continue;
            }
            $summary['processed']++;
            $application = $this->apply($change, null, 'scheduled');
            $bucket = match ($application->status) {
                'success' => 'applied',
                'partial' => 'partial',
                default => 'failed',
            };
            $summary[$bucket]++;
        }

        return $summary;
    }

    /** @return array{success:int,failed:int,message:?string} */
    private function applyBusinessBasePrices(PriceChange $change, PriceChangeApplication $application, string $policy): array
    {
        $lines = $change->lines()->where(function ($q): void {
            $q->whereNull('apply_status')->orWhere('apply_status', '!=', 'applied');
        })->get();
        $operations = [];
        $conflicts = [];

        foreach ($lines as $line) {
            $variation = DB::table('variations as v')
                ->join('products as p', 'p.id', '=', 'v.product_id')
                ->where('p.business_id', $change->business_id)
                ->where('v.id', $line->variation_id)
                ->whereNull('v.deleted_at')
                ->select([
                    'v.id', 'v.product_id', 'v.default_purchase_price', 'v.dpp_inc_tax',
                    'v.default_sell_price', 'v.sell_price_inc_tax', 'v.profit_percent',
                ])
                ->lockForUpdate()
                ->first();

            if (! $variation) {
                $conflicts[$line->id] = 'The product variation no longer exists for this business.';
                continue;
            }

            $matches = $this->samePrice((float) $variation->default_purchase_price, (float) $line->current_purchase_price_ex_tax)
                && $this->samePrice((float) $variation->dpp_inc_tax, (float) $line->current_purchase_price_inc_tax)
                && $this->samePrice((float) $variation->default_sell_price, (float) $line->current_sell_price_ex_tax)
                && $this->samePrice((float) $variation->sell_price_inc_tax, (float) $line->current_sell_price_inc_tax);

            if (! $matches) {
                $conflicts[$line->id] = 'Live product prices changed after this draft was prepared.';
                continue;
            }

            $operations[] = ['line' => $line, 'variation' => $variation];
        }

        if ($conflicts !== [] && $policy === 'stop_all') {
            throw new RuntimeException('Price conflict detected. No prices were changed. ' . implode(' ', array_values($conflicts)));
        }
        if ($lines->isEmpty()) {
            throw new RuntimeException('No unapplied price lines were found.');
        }
        if ($operations === [] && $conflicts === []) {
            throw new RuntimeException('No valid price operations were prepared.');
        }

        $success = 0;
        $failed = 0;
        $messages = [];

        foreach ($lines as $line) {
            if (isset($conflicts[$line->id])) {
                $failed++;
                $message = $conflicts[$line->id];
                $messages[] = $line->sku . ': ' . $message;
                $this->recordLineFailure($application, $change, $line, $message, 'business_base');
                continue;
            }

            $operation = collect($operations)->first(fn ($item) => (int) $item['line']->id === (int) $line->id);
            if (! $operation) {
                continue;
            }
            $variation = $operation['variation'];
            $purchaseEx = $line->new_purchase_price_ex_tax !== null ? (float) $line->new_purchase_price_ex_tax : (float) $variation->default_purchase_price;
            $purchaseInc = $line->new_purchase_price_inc_tax !== null ? (float) $line->new_purchase_price_inc_tax : (float) $variation->dpp_inc_tax;
            $sellEx = (float) $line->new_sell_price_ex_tax;
            $sellInc = (float) $line->new_sell_price_inc_tax;
            $profit = $this->calculator->profitPercent($purchaseEx, $sellEx);

            DB::table('variations')->where('id', $variation->id)->update([
                'default_purchase_price' => $purchaseEx,
                'dpp_inc_tax' => $purchaseInc,
                'default_sell_price' => $sellEx,
                'sell_price_inc_tax' => $sellInc,
                'profit_percent' => $profit,
                'updated_at' => now(),
            ]);
            DB::table('products')->where('id', $variation->product_id)->update(['updated_at' => now()]);

            $line->fill([
                'apply_status' => 'applied',
                'apply_message' => null,
                'actual_before_purchase_price_ex_tax' => $variation->default_purchase_price,
                'actual_before_purchase_price_inc_tax' => $variation->dpp_inc_tax,
                'actual_before_sell_price_ex_tax' => $variation->default_sell_price,
                'actual_before_sell_price_inc_tax' => $variation->sell_price_inc_tax,
                'actual_after_purchase_price_ex_tax' => $purchaseEx,
                'actual_after_purchase_price_inc_tax' => $purchaseInc,
                'actual_after_sell_price_ex_tax' => $sellEx,
                'actual_after_sell_price_inc_tax' => $sellInc,
                'applied_at' => now(),
            ])->save();

            PriceChangeApplicationLine::query()->create([
                'application_id' => $application->id,
                'price_change_id' => $change->id,
                'price_change_line_id' => $line->id,
                'business_id' => $change->business_id,
                'product_id' => $line->product_id,
                'variation_id' => $line->variation_id,
                'scope_type' => 'business_base',
                'status' => 'applied',
                'old_purchase_price_ex_tax' => $variation->default_purchase_price,
                'old_purchase_price_inc_tax' => $variation->dpp_inc_tax,
                'old_sell_price_ex_tax' => $variation->default_sell_price,
                'old_sell_price_inc_tax' => $variation->sell_price_inc_tax,
                'new_purchase_price_ex_tax' => $purchaseEx,
                'new_purchase_price_inc_tax' => $purchaseInc,
                'new_sell_price_ex_tax' => $sellEx,
                'new_sell_price_inc_tax' => $sellInc,
                'applied_at' => now(),
            ]);
            $success++;
        }

        return ['success' => $success, 'failed' => $failed, 'message' => $messages ? implode(' | ', $messages) : null];
    }

    /** @return array{success:int,failed:int,message:?string} */
    private function applyLocationPriceGroups(PriceChange $change, PriceChangeApplication $application, string $policy): array
    {
        if ($change->lines()->whereNotNull('new_purchase_price_inc_tax')->exists()) {
            throw new RuntimeException('Purchase prices cannot be location-specific. Remove new purchase prices or use Business base price scope.');
        }

        $rows = PriceChangeScopePrice::query()
            ->where('price_change_id', $change->id)
            ->where(function ($q): void {
                $q->whereNull('apply_status')->orWhere('apply_status', '!=', 'applied');
            })
            ->with('line')
            ->get()
            ->groupBy(fn ($row) => $row->price_change_line_id . ':' . $row->price_group_id);

        $operations = [];
        $conflicts = [];
        foreach ($rows as $key => $groupRows) {
            $first = $groupRows->first();
            if (! $first || ! $first->price_group_id || ! $first->line) {
                $conflicts[$key] = 'A selected location has no selling price group.';
                continue;
            }

            $variation = DB::table('variations as v')
                ->join('products as p', 'p.id', '=', 'v.product_id')
                ->where('p.business_id', $change->business_id)
                ->where('v.id', $first->line->variation_id)
                ->whereNull('v.deleted_at')
                ->select('v.id', 'v.product_id', 'v.sell_price_inc_tax')
                ->lockForUpdate()
                ->first();
            if (! $variation) {
                $conflicts[$key] = 'The product variation no longer exists.';
                continue;
            }

            $groupPrice = DB::table('variation_group_prices')
                ->where('variation_id', $first->line->variation_id)
                ->where('price_group_id', $first->price_group_id)
                ->lockForUpdate()
                ->first();
            $actual = $groupPrice ? (float) $groupPrice->price_inc_tax : (float) $variation->sell_price_inc_tax;
            if (! $this->samePrice($actual, (float) $first->current_group_price_inc_tax)) {
                $conflicts[$key] = 'The selling price group changed after this draft was prepared.';
                continue;
            }

            $operations[$key] = ['rows' => $groupRows, 'line' => $first->line, 'variation' => $variation, 'actual' => $actual];
        }

        if ($conflicts !== [] && $policy === 'stop_all') {
            throw new RuntimeException('Price-group conflict detected. No prices were changed. ' . implode(' ', array_values($conflicts)));
        }
        if ($rows->isEmpty()) {
            throw new RuntimeException('No unapplied location price-group rows were found.');
        }
        if ($operations === [] && $conflicts === []) {
            throw new RuntimeException('No valid location price-group operations were prepared.');
        }

        $success = 0;
        $failed = 0;
        $messages = [];
        foreach ($rows as $key => $groupRows) {
            $first = $groupRows->first();
            if (! $first || ! $first->line) {
                continue;
            }
            if (isset($conflicts[$key])) {
                $failed++;
                $message = $conflicts[$key];
                $messages[] = $first->line->sku . ': ' . $message;
                foreach ($groupRows as $scopePrice) {
                    $scopePrice->apply_status = 'conflict';
                    $scopePrice->apply_message = $message;
                    $scopePrice->save();
                }
                $this->recordLineFailure($application, $change, $first->line, $message, 'location_price_groups', $first->location_id, $first->price_group_id);
                continue;
            }

            $operation = $operations[$key] ?? null;
            if (! $operation) {
                continue;
            }
            $newPrice = (float) $first->new_group_price_inc_tax;
            $values = [
                'variation_id' => $first->line->variation_id,
                'price_group_id' => $first->price_group_id,
                'price_inc_tax' => $newPrice,
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('variation_group_prices', 'price_type')) {
                $values['price_type'] = 'fixed';
            }
            $existing = DB::table('variation_group_prices')
                ->where('variation_id', $first->line->variation_id)
                ->where('price_group_id', $first->price_group_id)
                ->first();
            if ($existing) {
                DB::table('variation_group_prices')->where('id', $existing->id)->update($values);
            } else {
                $values['created_at'] = now();
                DB::table('variation_group_prices')->insert($values);
            }
            DB::table('products')->where('id', $operation['variation']->product_id)->update(['updated_at' => now()]);

            foreach ($groupRows as $scopePrice) {
                $scopePrice->apply_status = 'applied';
                $scopePrice->apply_message = null;
                $scopePrice->applied_from_price_inc_tax = $operation['actual'];
                $scopePrice->applied_to_price_inc_tax = $newPrice;
                $scopePrice->applied_at = now();
                $scopePrice->save();
            }

            PriceChangeApplicationLine::query()->create([
                'application_id' => $application->id,
                'price_change_id' => $change->id,
                'price_change_line_id' => $first->line->id,
                'business_id' => $change->business_id,
                'product_id' => $first->line->product_id,
                'variation_id' => $first->line->variation_id,
                'scope_type' => 'location_price_groups',
                'location_id' => $first->location_id,
                'price_group_id' => $first->price_group_id,
                'status' => 'applied',
                'old_sell_price_inc_tax' => $operation['actual'],
                'new_sell_price_inc_tax' => $newPrice,
                'payload' => ['location_ids' => $groupRows->pluck('location_id')->values()->all()],
                'applied_at' => now(),
            ]);
            $success++;
        }

        foreach ($change->lines as $line) {
            $scopeRows = PriceChangeScopePrice::query()->where('price_change_line_id', $line->id)->get();
            if ($scopeRows->isEmpty()) {
                continue;
            }
            $applied = $scopeRows->where('apply_status', 'applied')->count();
            $line->apply_status = $applied === $scopeRows->count() ? 'applied' : ($applied > 0 ? 'partial' : 'conflict');
            $line->apply_message = $line->apply_status === 'applied' ? null : 'One or more location price groups could not be applied.';
            $line->applied_at = $applied > 0 ? now() : null;
            $line->save();
        }

        return ['success' => $success, 'failed' => $failed, 'message' => $messages ? implode(' | ', $messages) : null];
    }

    private function recordLineFailure(
        PriceChangeApplication $application,
        PriceChange $change,
        PriceChangeLine $line,
        string $message,
        string $scopeType,
        ?int $locationId = null,
        ?int $priceGroupId = null
    ): void {
        $line->apply_status = 'conflict';
        $line->apply_message = $message;
        $line->save();

        PriceChangeApplicationLine::query()->create([
            'application_id' => $application->id,
            'price_change_id' => $change->id,
            'price_change_line_id' => $line->id,
            'business_id' => $change->business_id,
            'product_id' => $line->product_id,
            'variation_id' => $line->variation_id,
            'scope_type' => $scopeType,
            'location_id' => $locationId,
            'price_group_id' => $priceGroupId,
            'status' => 'conflict',
            'message' => $message,
        ]);
    }

    private function samePrice(float $actual, float $expected): bool
    {
        return abs($actual - $expected) <= (float) config('pricechangenew.price_compare_tolerance', 0.00000001);
    }
}

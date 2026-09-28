<?php

namespace App\Console\Commands;

use App\Business;
use App\Product;
use App\Transaction;
use App\VariationLocationDetails;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Superadmin\Entities\Subscription;

class CleanupExpiredGraceBusinesses extends Command
{
    protected $signature = 'subscription:cleanup-expired-grace
        {--months=3 : Number of grace months after subscription expiry}
        {--delete-data : Delete products, transactions, and the business account instead of deactivating it}
        {--dry-run : Show affected businesses without changing data}';

    protected $description = 'Deactivate or remove businesses whose subscription grace period has ended.';

    public function handle()
    {
        $months = max(1, (int) $this->option('months'));
        $delete_data = (bool) $this->option('delete-data');
        $dry_run = (bool) $this->option('dry-run');
        $cutoff = Carbon::today()->subMonths($months)->toDateString();

        $expired_subscriptions = Subscription::query()
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $cutoff)
            ->approved()
            ->orderBy('business_id')
            ->orderByDesc('end_date')
            ->get()
            ->unique('business_id');

        $processed = 0;

        foreach ($expired_subscriptions as $subscription) {
            $business_id = (int) $subscription->business_id;

            if (Subscription::active_subscription($business_id)) {
                continue;
            }

            $business = Business::find($business_id);
            if (empty($business)) {
                continue;
            }

            $processed++;

            $this->line(sprintf(
                '%s business #%d (%s), expired on %s',
                $delete_data ? 'Removing' : 'Deactivating',
                $business->id,
                $business->name,
                Carbon::parse($subscription->end_date)->toDateString()
            ));

            if ($dry_run) {
                continue;
            }

            if ($delete_data) {
                $this->deleteBusinessData($business);
            } else {
                $business->is_active = 0;
                $business->save();
            }
        }

        $this->info($dry_run
            ? "Dry run complete. {$processed} business(es) would be processed."
            : "Cleanup complete. {$processed} business(es) processed.");

        return self::SUCCESS;
    }

    private function deleteBusinessData(Business $business): void
    {
        DB::transaction(function () use ($business) {
            $product_ids = Product::where('business_id', $business->id)->pluck('id')->toArray();

            if (! empty($product_ids)) {
                VariationLocationDetails::whereIn('product_id', $product_ids)->delete();
                Product::whereIn('id', $product_ids)->delete();
            }

            Transaction::where('business_id', $business->id)->delete();
            Subscription::where('business_id', $business->id)->delete();
            $business->delete();
        });
    }
}

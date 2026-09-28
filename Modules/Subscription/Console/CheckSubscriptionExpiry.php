<?php

namespace Modules\Subscription\Console;

use Illuminate\Console\Command;
use Modules\Subscription\Entities\SubscriptionList;
use Modules\Subscription\Entities\SubscriptionPayment;
use Modules\Subscription\Notifications\SubscriptionExpiredNotification;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionExpiry extends Command
{
    protected $signature = 'subscription:check-expiry';

    protected $description = 'Check for expired customer subscriptions and send notifications';

    public function handle()
    {
        $today = Carbon::today();

        // Get all subscriptions to check for expiry
        $subscriptions = SubscriptionList::leftJoin('contacts', 'contacts.id', '=', 'subscription_lists.contact_id')
            ->select('subscription_lists.*', 'contacts.name as customer_name')
            ->get();

        $this->info("Found {$subscriptions->count()} subscriptions to check.");

        foreach ($subscriptions as $subscription) {
            // Check latest payment expiry date first, fallback to subscription expiry_date
            $latestPayment = SubscriptionPayment::where('list_id', $subscription->id)
                ->latest()
                ->first();

            $expiry_date = !empty($latestPayment) ? $latestPayment->expiry_date : $subscription->expiry_date;
            $is_expired = !empty($expiry_date) && !Carbon::parse($expiry_date)->startOfDay()->gt($today);

            $this->info("Sub #{$subscription->id} - {$subscription->customer_name} - expiry: {$expiry_date}");

            $new_status = $is_expired ? 0 : 1;
            if ((int) $subscription->status !== $new_status) {
                SubscriptionList::where('id', $subscription->id)->update(['status' => $new_status]);
            }

            // Send notification on expiry date or after (not before)
            if (! $is_expired) {
                $this->info("  -> Skipped: not expired yet");
                continue;
            }

            // Find the business owner to notify
            $business = \App\Business::find($subscription->business_id);

            if (empty($business)) {
                $this->info("  -> Skipped: business not found");
                continue;
            }

            if (empty($business->owner_id)) {
                $this->info("  -> Skipped: business has no owner_id");
                continue;
            }

            $owner = User::find($business->owner_id);

            if (empty($owner)) {
                $this->info("  -> Skipped: owner user not found (owner_id={$business->owner_id})");
                continue;
            }

            // Check if we already sent this notification (avoid duplicates)
            $alreadyNotified = $owner->notifications()
                ->where('type', SubscriptionExpiredNotification::class)
                ->where('data->subscription_list_id', $subscription->id)
                ->exists();

            if ($alreadyNotified) {
                $this->info("  -> Skipped: notification already sent");
                continue;
            }

            $dateFormat = !empty($business->date_format) ? $business->date_format : 'm/d/Y';

            $owner->notify(new SubscriptionExpiredNotification([
                'subscription_list_id' => $subscription->id,
                'customer_name' => $subscription->customer_name ?? 'N/A',
                'expiry_date' => Carbon::parse($expiry_date)->format($dateFormat),
            ]));

            $this->info("  -> Notification SENT!");
        }

        $this->info('Subscription expiry check completed.');
    }
}

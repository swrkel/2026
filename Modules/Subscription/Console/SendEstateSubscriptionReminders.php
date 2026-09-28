<?php

namespace Modules\Subscription\Console;

use App\Utils\BusinessUtil;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Subscription\Services\CentralRegistryService;

class SendEstateSubscriptionReminders extends Command
{
    protected $signature = 'subscription:send-estate-reminders';
    protected $description = 'Send due central subscription reminder SMS messages once per reminder/date.';

    public function handle(CentralRegistryService $registry, BusinessUtil $businessUtil)
    {
        $connection = $registry->centralConnectionName();
        $schema = Schema::connection($connection);
        if (!$schema->hasTable('subs_business_subscriptions') || !$schema->hasTable('subs_subscription_reminders')) {
            $this->warn('Subscription central tables are not installed.');
            return 0;
        }
        $today = Carbon::today();
        $subscriptions = DB::connection($connection)->table('subs_business_subscriptions')->where('status', 1)->get();
        foreach ($subscriptions as $subscription) {
            $expiry = Carbon::parse($subscription->expiry_date)->startOfDay();
            $daysLeft = $today->diffInDays($expiry, false);
            $reminders = DB::connection($connection)->table('subs_subscription_reminders')
                ->where('subscription_id', $subscription->id)->where('is_enabled', 1)->get();
            foreach ($reminders as $reminder) {
                if ((int) $reminder->days_before !== (int) $daysLeft) continue;
                if ($this->alreadySent($connection, $subscription->id, $reminder->id, $today->toDateString())) continue;
                $message = str_replace(
                    ['{subscription_amount}', '{system_expiry_date}'],
                    [number_format((float) $subscription->subscription_amount, 2, '.', ','), $expiry->format('Y-m-d')],
                    $reminder->message_body
                );
                $numbers = array_filter(array_map('trim', explode(',', (string) $subscription->business_mobile_numbers)));
                try {
                    list($tenantConnection, $database, $tenantBusiness) = $registry->tenantBusiness(
                        $subscription->tenant_id,
                        $subscription->business_global_uid,
                        $subscription->business_registry_id,
                        $subscription->business_name
                    );
                    $smsSettings = empty($tenantBusiness->sms_settings)
                        ? $businessUtil->defaultSmsSettings()
                        : (is_string($tenantBusiness->sms_settings) ? json_decode($tenantBusiness->sms_settings, true) : (array) $tenantBusiness->sms_settings);
                    foreach ($numbers as $number) {
                        $businessUtil->superadminSendSms(['sms_settings' => $smsSettings, 'mobile_number' => $number, 'sms_body' => $message]);
                    }
                    $this->logSend($connection, $subscription->id, $reminder->id, $today->toDateString(), implode(',', $numbers), $message, 'sent', null);
                    $this->info('Sent reminder #' . $reminder->reminder_no . ' for ' . $subscription->business_name);
                } catch (\Throwable $e) {
                    $this->logSend($connection, $subscription->id, $reminder->id, $today->toDateString(), implode(',', $numbers), $message, 'failed', $e->getMessage());
                    Log::error('Subscription reminder SMS failed', ['subscription_id' => $subscription->id, 'message' => $e->getMessage()]);
                }
            }
            if ($daysLeft === 0) $this->sendMasterExpiryNotice($connection, $registry, $businessUtil, $subscription, $expiry, $today);
        }
        return 0;
    }

    protected function sendMasterExpiryNotice($connection, $registry, $businessUtil, $subscription, $expiry, $today)
    {
        $setting = DB::connection($connection)->table('subs_master_settings')->where('setting_key', 'notify_subscription_expiry_numbers')->first();
        if (!$setting || trim((string) $setting->setting_value) === '') return;
        $exists = DB::connection($connection)->table('subs_reminder_logs')->where('subscription_id', $subscription->id)
            ->whereNull('reminder_id')->where('due_date', $today->toDateString())->where('status', 'sent')->exists();
        if ($exists) return;
        $message = 'Subscription expired: ' . $subscription->business_name . ' | Amount: ' . number_format((float) $subscription->subscription_amount, 2, '.', ',') . ' | Expiry: ' . $expiry->format('Y-m-d');
        $numbers = array_filter(array_map('trim', explode(',', (string) $setting->setting_value)));
        try {
            list($tenantConnection, $database, $tenantBusiness) = $registry->tenantBusiness($subscription->tenant_id, $subscription->business_global_uid, $subscription->business_registry_id, $subscription->business_name);
            $smsSettings = empty($tenantBusiness->sms_settings) ? $businessUtil->defaultSmsSettings() : (is_string($tenantBusiness->sms_settings) ? json_decode($tenantBusiness->sms_settings, true) : (array) $tenantBusiness->sms_settings);
            foreach ($numbers as $number) $businessUtil->superadminSendSms(['sms_settings' => $smsSettings, 'mobile_number' => $number, 'sms_body' => $message]);
            $this->logSend($connection, $subscription->id, null, $today->toDateString(), implode(',', $numbers), $message, 'sent', null);
        } catch (\Throwable $e) {
            $this->logSend($connection, $subscription->id, null, $today->toDateString(), implode(',', $numbers), $message, 'failed', $e->getMessage());
        }
    }

    protected function alreadySent($connection, $subscriptionId, $reminderId, $dueDate)
    {
        return DB::connection($connection)->table('subs_reminder_logs')->where('subscription_id', $subscriptionId)->where('reminder_id', $reminderId)->where('due_date', $dueDate)->where('status', 'sent')->exists();
    }
    protected function logSend($connection, $subscriptionId, $reminderId, $dueDate, $numbers, $message, $status, $error)
    {
        DB::connection($connection)->table('subs_reminder_logs')->insert([
            'subscription_id' => $subscriptionId, 'reminder_id' => $reminderId, 'due_date' => $dueDate,
            'mobile_numbers' => $numbers, 'message_body' => $message, 'status' => $status, 'error_message' => $error,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}

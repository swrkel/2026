<?php
namespace Modules\ManagementReport\Services\Delivery;
use Modules\ManagementReport\Support\TenantConnection;

use Illuminate\Support\Str;
use Modules\ManagementReport\Entities\ReportRun;
use Modules\ManagementReport\Entities\ReportShare;
use Modules\ManagementReport\Entities\ReportShareRecipient;

class ShareService
{
    protected $sms;
    protected $email;
    protected $whatsapp;

    public function __construct(SmsDeliveryService $sms, EmailDeliveryService $email, WhatsAppDeliveryService $whatsapp)
    {
        $this->sms = $sms;
        $this->email = $email;
        $this->whatsapp = $whatsapp;
    }

    public function share(ReportRun $run, $channel, array $recipients, $message = null, $expiryHours = null, $attachPdf = true)
    {
        $expiryHours = $expiryHours ?: $this->setting($run->business_id, 'link_expiry_hours', config('managementreport.default_link_expiry_hours', 72));
        $message = trim($message ?: $this->setting($run->business_id, $channel . '_message', $run->report_title . ' for ' . $run->period_start . ' is ready: {link}'));
        $token = Str::random(64);

        return TenantConnection::db()->transaction(function () use ($run, $channel, $recipients, $message, $expiryHours, $attachPdf, $token) {
            $share = ReportShare::create([
                'report_run_id' => $run->id,
                'business_id' => $run->business_id,
                'channel' => $channel,
                'token' => hash('sha256', $token),
                'status' => 'pending',
                'message_body' => $message,
                'expires_at' => now()->addHours((int) $expiryHours),
                'created_by' => auth()->id(),
            ]);

            foreach ($recipients as $recipient) {
                ReportShareRecipient::create(['report_share_id' => $share->id, 'recipient' => $recipient, 'status' => 'pending']);
            }

            $url = route('managementreport.public.show', ['token' => $token]);
            $fullMessage = strpos($message, '{link}') !== false
                ? str_replace('{link}', $url, $message)
                : rtrim($message) . ' ' . $url;

            try {
                if ($channel === 'sms') {
                    $result = $this->sms->send($run->business_id, $recipients, $fullMessage);
                } elseif ($channel === 'email') {
                    $result = $this->email->send($run, $recipients, $fullMessage, $attachPdf);
                } else {
                    $result = $this->whatsapp->send($run->business_id, $recipients, $fullMessage);
                }

                $share->update(['status' => $result['status'], 'provider' => $result['provider'] ?? null, 'provider_response' => $result, 'delivered_at' => now()]);
                $share->recipients()->update(['status' => $result['status'], 'delivered_at' => now()]);
                return ['share' => $share->fresh('recipients'), 'public_url' => $url, 'launch_url' => $result['launch_url'] ?? null];
            } catch (\Throwable $e) {
                $share->update(['status' => 'failed', 'failure_reason' => $e->getMessage(), 'failed_at' => now()]);
                $share->recipients()->update(['status' => 'failed', 'failure_reason' => $e->getMessage(), 'failed_at' => now()]);
                throw $e;
            }
        });
    }

    protected function setting($businessId, $key, $default = null)
    {
        if (!TenantConnection::schema()->hasTable('mgmt_report_settings')) {
            return $default;
        }
        $stored = TenantConnection::db()->table('mgmt_report_settings')
            ->where('business_id', $businessId)
            ->whereNull('location_id')
            ->whereNull('store_id')
            ->where('setting_key', $key)
            ->value('setting_value');
        if ($stored === null) {
            return $default;
        }
        $decoded = json_decode($stored, true);
        return $decoded === null ? $stored : $decoded;
    }
}

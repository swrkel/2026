<?php

namespace Modules\BeautySaloons\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\BeautySaloons\Entities\BeautyNotificationLog;
use Modules\BeautySaloons\Entities\BeautyNotificationTemplate;

class BeautyNotificationService
{
    public function queueFromTemplate(string $code, string $channel, string $recipient, array $data = [], array $meta = []): BeautyNotificationLog
    {
        $businessId = $meta['business_id'] ?? session('business.id');

        $template = BeautyNotificationTemplate::query()
            ->where('business_id', $businessId)
            ->where('code', $code)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            $template = BeautyNotificationTemplate::query()
                ->whereNull('business_id')
                ->where('code', $code)
                ->where('channel', $channel)
                ->where('is_active', true)
                ->first();
        }

        $rendered = $template
            ? app(BeautyNotificationTemplateService::class)->render($template, $data)
            : ['subject' => $meta['subject'] ?? null, 'body' => $meta['message'] ?? ''];

        return BeautyNotificationLog::create([
            'business_id' => $businessId,
            'location_id' => $meta['location_id'] ?? null,
            'template_id' => $template->id ?? null,
            'related_type' => $meta['related_type'] ?? null,
            'related_id' => $meta['related_id'] ?? null,
            'customer_id' => $meta['customer_id'] ?? null,
            'staff_id' => $meta['staff_id'] ?? null,
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $rendered['subject'],
            'message' => $rendered['body'],
            'status' => 'pending',
            'scheduled_at' => $meta['scheduled_at'] ?? Carbon::now(),
            'created_by' => Auth::id(),
        ]);
    }

    public function markSent(BeautyNotificationLog $log, ?string $gatewayResponse = null): BeautyNotificationLog
    {
        $log->update(['status' => 'sent', 'sent_at' => now(), 'gateway_response' => $gatewayResponse]);
        return $log->fresh();
    }

    public function markFailed(BeautyNotificationLog $log, string $error): BeautyNotificationLog
    {
        $status = $log->retry_count >= 3 ? 'failed' : 'retry';
        $log->update(['status' => $status, 'retry_count' => $log->retry_count + 1, 'error_message' => $error]);
        return $log->fresh();
    }
}

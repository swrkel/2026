<?php
namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServiceNotificationLog;
use Modules\AutoService\Entities\AutoServiceJob;

class AutoServiceNotificationService
{
    public function queueJobNotification(AutoServiceJob $job, string $event, string $message, ?string $recipient = null, string $channel = 'sms')
    {
        return AutoServiceNotificationLog::create([
            'business_id' => $job->business_id ?? request()->session()->get('user.business_id'),
            'job_id' => $job->id,
            'contact_id' => $job->contact_id ?? null,
            'channel' => $channel,
            'event' => $event,
            'recipient' => $recipient ?: $this->resolveMobile($job),
            'message' => $message,
            'status' => 'pending',
        ]);
    }

    protected function resolveMobile(AutoServiceJob $job): ?string
    {
        if (empty($job->contact_id) || !DB::getSchemaBuilder()->hasTable('contacts')) return null;
        $contact = DB::table('contacts')->where('id', $job->contact_id)->first();
        return $contact->mobile ?? $contact->alternate_number ?? $contact->landline ?? null;
    }
}

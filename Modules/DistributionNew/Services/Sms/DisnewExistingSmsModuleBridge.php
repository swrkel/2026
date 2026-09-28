<?php

namespace Modules\DistributionNew\Services\Sms;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DisnewExistingSmsModuleBridge
{
    /**
     * Bridge only. Distribution New does not duplicate the existing SMS module.
     * If an SMS module queue table/class is available in the host system, this method pushes to it.
     * Otherwise, the log remains queued for the existing SMS module worker/adapter to consume.
     */
    public function pushQueuedLog(object $log): void
    {
        $attemptId = DB::table('disnew_sms_bridge_attempts')->insertGetId([
            'business_id' => $log->business_id,
            'disnew_sms_log_id' => $log->id,
            'bridge_driver' => 'existing_sms_module',
            'status' => 'pending',
            'request_payload' => json_encode([
                'mobile' => $log->mobile,
                'message' => $log->message,
                'event' => $log->event,
                'module' => 'DistributionNew',
            ]),
            'attempted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            if (class_exists('Modules\\SMS\\Services\\SmsQueueService')) {
                app('Modules\\SMS\\Services\\SmsQueueService')->queue([
                    'business_id' => $log->business_id,
                    'mobile' => $log->mobile,
                    'message' => $log->message,
                    'source_module' => 'DistributionNew',
                    'source_reference' => 'disnew_sms_logs:'.$log->id,
                ]);
                $this->mark($attemptId, $log->id, 'pushed', null, 'sms_service');
                return;
            }

            if (DB::getSchemaBuilder()->hasTable('sms_queues')) {
                $id = DB::table('sms_queues')->insertGetId([
                    'business_id' => $log->business_id,
                    'mobile' => $log->mobile,
                    'message' => $log->message,
                    'source_module' => 'DistributionNew',
                    'source_reference' => 'disnew_sms_logs:'.$log->id,
                    'status' => 'queued',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->mark($attemptId, $log->id, 'pushed', null, (string)$id);
                return;
            }

            $this->mark($attemptId, $log->id, 'skipped', 'Existing SMS queue adapter not detected yet.', null);
        } catch (\Throwable $e) {
            Log::error('DistributionNew SMS bridge failed', ['log_id' => $log->id, 'error' => $e->getMessage()]);
            $this->mark($attemptId, $log->id, 'failed', $e->getMessage(), null);
        }
    }

    private function mark(int $attemptId, int $logId, string $status, ?string $error, ?string $externalReference): void
    {
        DB::table('disnew_sms_bridge_attempts')->where('id', $attemptId)->update([
            'status' => $status,
            'external_reference' => $externalReference,
            'error_message' => $error,
            'updated_at' => now(),
        ]);
        DB::table('disnew_sms_logs')->where('id', $logId)->update([
            'bridge_status' => $status,
            'external_reference' => $externalReference,
            'error_message' => $error,
            'updated_at' => now(),
        ]);
    }
}

<?php

namespace Modules\ExpensesNew\Services\Notifications;

use Illuminate\Support\Facades\DB;

class ExpenseNotificationService
{
    public function queue(string $event, array $payload, array $channels = ['system']): void
    {
        DB::table('expnew_notification_queue')->insert([
            'event_key' => $event,
            'channels_json' => json_encode($channels),
            'payload_json' => json_encode($payload),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

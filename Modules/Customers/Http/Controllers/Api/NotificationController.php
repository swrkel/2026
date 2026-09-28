<?php

namespace Modules\Customers\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationController extends BaseDealerApiController
{
    public function notifications(Request $request)
    {
        return $this->genericRows($request, 'customer_portal_notifications', 'notifications');
    }

    public function messages(Request $request)
    {
        return $this->genericRows($request, 'customer_portal_messages', 'messages');
    }

    public function markRead(Request $request, $id)
    {
        if (!Schema::hasTable('customer_portal_notifications')) {
            return $this->fail('Notification table is not available.', 404);
        }

        DB::table('customer_portal_notifications')
            ->where('business_id', $this->businessId($request))
            ->where('contact_id', $this->customerId($request))
            ->where('id', (int) $id)
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->success([], 'Notification marked as read.');
    }

    protected function genericRows(Request $request, string $table, string $key)
    {
        if (!Schema::hasTable($table)) {
            return $this->success([$key => []]);
        }

        $query = DB::table($table)
            ->where('business_id', $this->businessId($request))
            ->where('contact_id', $this->customerId($request));

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $rows = $query->orderByDesc('created_at')
            ->limit($this->paginateLimit($request, 100, 500))
            ->get();

        return $this->success([$key => $rows]);
    }
}

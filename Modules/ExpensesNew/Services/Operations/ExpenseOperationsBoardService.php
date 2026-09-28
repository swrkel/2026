<?php

namespace Modules\ExpensesNew\Services\Operations;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseOperationsBoardService
{
    public function payload(Request $request): array
    {
        return ['title' => 'Live Operations Board', 'events' => $this->feed($request)['data']];
    }

    public function feed(Request $request): array
    {
        $events = DB::table('expnew_operation_events')
            ->when($request->get('business_id'), fn($q, $v) => $q->where('business_id', $v))
            ->orderByDesc('event_time')
            ->limit(50)
            ->get();
        return ['data' => $events];
    }
}

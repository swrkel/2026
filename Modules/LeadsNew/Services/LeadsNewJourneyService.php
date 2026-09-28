<?php

namespace Modules\LeadsNew\Services;

use Illuminate\Support\Facades\DB;

class LeadsNewJourneyService
{
    public function timeline(int $leadId): array
    {
        $items = [];
        foreach (['leads_new_activities', 'leads_new_followups', 'leads_new_documents', 'leads_new_conversions'] as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }
            $rows = DB::table($table)->where('lead_id', $leadId)->orderByDesc('created_at')->limit(100)->get();
            foreach ($rows as $row) {
                $items[] = [
                    'source' => $table,
                    'title' => $row->title ?? $row->subject ?? $row->status ?? $table,
                    'details' => $row->description ?? $row->notes ?? null,
                    'created_at' => $row->created_at ?? null,
                ];
            }
        }
        usort($items, fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
        return $items;
    }
}

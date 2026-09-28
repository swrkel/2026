<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * SW Logs - its own page, covering changes to both shifts and settlements.
 *
 * READ ONLY. There is no edit, no delete, and no route to either. A log that
 * can be altered answers nothing.
 */
class LogController extends Controller
{
    protected function businessId(): int
    {
        return (int) (session('business.id') ?: session('user.business_id') ?: 0);
    }

    public function index()
    {
        $businessId = $this->businessId();

        return view('sw::logs.index', [
            'business_locations' => DB::table('business_locations')
                ->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id'),
            'users' => DB::table('users')
                ->where('business_id', $businessId)
                ->orderBy('username')
                ->pluck('username', 'id'),
        ]);
    }

    public function data(Request $request)
    {
        $businessId = $this->businessId();

        $rows = DB::table('sw_logs as l')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 'l.location_id')
            ->where('l.business_id', $businessId)
            ->when($request->filled('location_id'),
                fn ($q) => $q->where('l.location_id', (int) $request->location_id))
            ->when($request->filled('document_type'),
                fn ($q) => $q->where('l.document_type', $request->document_type))
            ->when($request->boolean('after_closure_only'),
                fn ($q) => $q->where('l.after_closure', 1))
            ->when($request->filled('date_from'),
                fn ($q) => $q->whereDate('l.created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'),
                fn ($q) => $q->whereDate('l.created_at', '<=', $request->date_to))
            ->orderByDesc('l.id')
            ->limit(5000)
            ->get(['l.*', 'bl.name as location_name']);

        $data = $rows->map(function ($r) {
            return [
                'when' => $r->created_at
                    ? \Carbon\Carbon::parse($r->created_at)->format('d/m/Y H:i')
                    : '—',
                'document' => $this->documentLabel($r),
                'record' => e($this->recordLabel($r)),
                'action' => $this->actionLabel($r->action),
                'changes' => $this->changesHtml($r->changes),
                'reason' => e($r->reason ?? ''),
                'user' => e($r->username ?? '—'),
                'location' => e($r->location_name ?? '—'),
            ];
        });

        return response()->json(['data' => $data]);
    }

    // ------------------------------------------------------------------

    protected function documentLabel($r): string
    {
        $type = $r->document_type === 'settlement'
            ? __('sw::lang.settlement')
            : __('sw::lang.shift');

        $label = '<strong>' . e($r->document_no ?: '—') . '</strong>'
            . '<br><small class="text-muted">' . e($type) . '</small>';

        /*
         | An entry made after closure is marked.
         |
         | It is the whole point of this page: an edit to an open shift is
         | ordinary work, the same edit to a closed one is what someone came
         | here to find.
        */
        if ($r->after_closure) {
            $label .= ' <span class="label label-warning" style="font-size:10px">'
                . __('sw::lang.after_closure') . '</span>';
        }

        return $label;
    }

    protected function recordLabel($r): string
    {
        if (empty($r->record_type)) {
            return '—';
        }

        $names = [
            'daily_cash' => __('sw::lang.daily_cash'),
            'daily_credit_sale' => __('sw::lang.daily_credit_sales'),
            'daily_card' => __('sw::lang.daily_cards'),
            'daily_cheque' => __('sw::lang.daily_cheques'),
            'shortage_excess' => __('sw::lang.daily_shortage_excess'),
            'shift_operator' => __('sw::lang.pump_operator'),
            'settlement_line' => __('sw::lang.meter_sales'),
        ];

        return $names[$r->record_type] ?? $r->record_type;
    }

    protected function actionLabel(string $action): string
    {
        $labels = [
            'created' => ['success', __('sw::lang.created')],
            'updated' => ['info', __('sw::lang.updated')],
            'deleted' => ['danger', __('sw::lang.deleted')],
            'closed' => ['primary', __('sw::lang.closed_action')],
            'reopened' => ['warning', __('sw::lang.reopened')],
            'settled' => ['default', __('sw::lang.settled_action')],
        ];

        [$class, $text] = $labels[$action] ?? ['default', $action];

        return '<span class="label label-' . $class . '">' . e($text) . '</span>';
    }

    /**
     * The change list, field by field.
     *
     * Shown as "field: old → new" rather than as raw JSON. A log nobody can
     * read at a glance is a log nobody reads.
     */
    protected function changesHtml(?string $json): string
    {
        if (empty($json)) {
            return '<span class="text-muted">—</span>';
        }

        $changes = json_decode($json, true);

        if (! is_array($changes) || empty($changes)) {
            return '<span class="text-muted">—</span>';
        }

        $lines = [];

        foreach (array_slice($changes, 0, 8) as $change) {
            $field = e($this->fieldLabel($change['field'] ?? ''));
            $from = $change['from'] === null || $change['from'] === ''
                ? '<em class="text-muted">' . __('sw::lang.empty') . '</em>'
                : e($change['from']);
            $to = $change['to'] === null || $change['to'] === ''
                ? '<em class="text-muted">' . __('sw::lang.empty') . '</em>'
                : '<strong>' . e($change['to']) . '</strong>';

            $lines[] = $field . ': ' . $from . ' &rarr; ' . $to;
        }

        if (count($changes) > 8) {
            $lines[] = '<em class="text-muted">'
                . __('sw::lang.and_more', ['count' => count($changes) - 8]) . '</em>';
        }

        return '<small>' . implode('<br>', $lines) . '</small>';
    }

    protected function fieldLabel(string $field): string
    {
        return ucwords(str_replace(['_id', '_'], ['', ' '], $field));
    }
}

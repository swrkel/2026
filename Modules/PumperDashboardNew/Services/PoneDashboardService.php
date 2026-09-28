<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\PumperDashboardNew\Entities\PoneDailyCollection;
use Modules\PumperDashboardNew\Entities\PoneDayEntry;
use Modules\PumperDashboardNew\Entities\PoneOperatorDocument;
use Modules\PumperDashboardNew\Entities\PoneOperatorLedgerEntry;
use Modules\PumperDashboardNew\Entities\PoneOperatorNote;
use Modules\PumperDashboardNew\Entities\PoneOtherSale;
use Modules\PumperDashboardNew\Entities\PonePayment;
use Modules\PumperDashboardNew\Entities\PoneShift;
use Modules\PumperDashboardNew\Entities\PoneUnloadStock;

class PoneDashboardService
{
    public function __construct(
        private PoneContextService $context,
        private PoneShiftImportService $importer,
        private PoneShiftTotalsService $totals,
        private PoneSharedMasterDataService $masterData
    ) {}

    public function data(): array
    {
        $profile = $this->context->profile();
        $shift = $this->importer->importForOperator($profile);
        $this->context->setShift($shift);
        if ($shift) $shift = $this->totals->refresh($shift);

        $activeLocationId = $shift?->location_id ?: $profile->location_id;
        $assignments = $shift ? $shift->assignments()->orderBy('status')->orderBy('pump_id')->get() : collect();
        $pumpMap = $this->masterData->pumps($profile->business_id, $activeLocationId)->keyBy('id');
        foreach ($assignments as $assignment) $assignment->setAttribute('pump_master', $pumpMap->get($assignment->pump_id));

        $ledgerBalance = (float) PoneOperatorLedgerEntry::query()
            ->where('business_id', $profile->business_id)->where('operator_profile_id', $profile->id)
            ->where('status', 'active')->selectRaw('COALESCE(SUM(debit-credit),0) AS balance')->value('balance');

        $assignedPumps = $assignments->where('status', 'assigned')->count();
        $unconfirmedPumps = $assignments->filter(static function ($assignment): bool {
            return $assignment->status === 'assigned'
                || ($assignment->status === 'open' && empty($assignment->confirmed_at));
        })->count();
        $openConfirmedPumps = $assignments->filter(static function ($assignment): bool {
            return $assignment->status === 'open' && ! empty($assignment->confirmed_at);
        })->count();
        $openPumps = $assignments->whereIn('status', ['assigned', 'open'])->count();
        $canCloseShift = $shift !== null
            && $shift->isOpen()
            && $assignments->isNotEmpty()
            && $openPumps === 0;
        $dashboardNow = now();
        $hour = (int) $dashboardNow->format('G');
        $timeGreeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
        $displayName = trim((string) $profile->display_name);
        $operatorFirstName = preg_split('/\s+/', $displayName, -1, PREG_SPLIT_NO_EMPTY)[0] ?? 'Operator';

        return [
            'profile' => $profile,
            'shift' => $shift,
            'business' => $this->masterData->business($profile->business_id),
            'location' => $this->masterData->location($activeLocationId, $profile->business_id),
            'assignments' => $assignments,
            'open_pumps' => $openPumps,
            'closed_pumps' => $assignments->where('status', 'closed')->count(),
            'assigned_pumps' => $assignedPumps,
            'unconfirmed_pumps' => $unconfirmedPumps,
            'open_confirmed_pumps' => $openConfirmedPumps,
            'can_close_shift' => $canCloseShift,
            'shift_number' => $shift?->shift_number,
            'dashboard_now' => $dashboardNow,
            'time_greeting' => $timeGreeting,
            'operator_first_name' => $operatorFirstName,
            'pending_sync' => $shift ? $this->pendingSyncCount($shift) : 0,
            'latest_collection' => $shift ? PoneDailyCollection::query()->where('shift_id', $shift->id)->where('status', 'confirmed')->latest('collection_at')->first() : null,
            'collection_count' => $shift ? PoneDailyCollection::query()->where('shift_id', $shift->id)->where('status', 'confirmed')->count() : 0,
            'unload_count' => $shift ? PoneUnloadStock::query()->where('shift_id', $shift->id)->where('status', 'confirmed')->count() : 0,
            'ledger_balance' => round($ledgerBalance, 4),
            'document_count' => PoneOperatorDocument::query()->where('business_id', $profile->business_id)->where('operator_profile_id', $profile->id)->count(),
            'note_count' => PoneOperatorNote::query()->where('business_id', $profile->business_id)->where('operator_profile_id', $profile->id)->where('status', 'active')->count(),
            'recent_payments' => $shift ? PonePayment::query()->where('shift_id', $shift->id)->latest('transaction_at')->limit(5)->get() : collect(),
            'recent_other_sales' => $shift ? PoneOtherSale::query()->where('shift_id', $shift->id)->latest('sale_at')->limit(5)->get() : collect(),
            'recent_day_entries' => $shift ? PoneDayEntry::query()->where('shift_id', $shift->id)->latest('entry_at')->limit(5)->get() : collect(),
        ];
    }

    private function pendingSyncCount(PoneShift $shift): int
    {
        $count = in_array($shift->integration_status, ['failed', 'pending'], true) ? 1 : 0;
        foreach (['pone_pump_assignments', 'pone_payments', 'pone_other_sales', 'pone_unload_stocks', 'pone_day_entries'] as $table) {
            $count += DB::table($table)->where('shift_id', $shift->id)->whereIn('integration_status', ['pending', 'failed'])->count();
        }
        return $count;
    }
}

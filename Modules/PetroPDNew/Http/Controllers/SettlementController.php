<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Http\Request;
use Modules\PetroPDNew\Http\Requests\ReasonRequest;
use Modules\PetroPDNew\Http\Requests\SettlementCreateRequest;
use Modules\PetroPDNew\Http\Requests\SettlementUpdateRequest;
use Modules\PetroPDNew\Services\PdnewBusinessFeatureService;
use Modules\PetroPDNew\Services\PdnewMasterDataService;
use Modules\PetroPDNew\Services\Settlement\PdnewReconciliationService;
use Modules\PetroPDNew\Services\Settlement\PdnewSettlementService;
use Modules\PetroPDNew\Services\Source\PoneSourceReader;

class SettlementController extends PdnewController
{
    public function index(Request $request)
    {
        $query = \Modules\PetroPDNew\Entities\PdnewSettlement::query()
            ->forBusiness($this->context->businessId())
            ->forLocation($this->context->locationId());

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('date_from')) $query->whereDate('settlement_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('settlement_date', '<=', $request->date_to);
        if ($request->filled('operator')) $query->where('operator_name', 'like', '%' . $request->operator . '%');
        if ($request->filled('search')) {
            $term = trim((string) $request->search);
            $query->where(function ($q) use ($term): void {
                $q->where('settlement_number', 'like', '%' . $term . '%')
                    ->orWhere('pone_shift_number', 'like', '%' . $term . '%')
                    ->orWhere('operator_name', 'like', '%' . $term . '%');
            });
        }

        $settlements = $query->orderByDesc('settlement_date')->orderByDesc('id')->paginate(50)->withQueryString();
        return view('petropdnew::settlements.index', compact('settlements'));
    }

    public function create(PoneSourceReader $reader)
    {
        $shifts = $reader->closedShiftQuery($this->context->businessId(), $this->context->locationId())
            ->whereNull('i.settlement_id')
            ->whereNull('r.settlement_no')
            ->orderBy('s.closed_at')
            ->limit(200)
            ->get();

        return view('petropdnew::settlements.create', compact('shifts'));
    }

    public function store(SettlementCreateRequest $request, PdnewSettlementService $service, PoneSourceReader $reader)
    {
        try {
            $shiftId = (int) $request->validated('shift_id');
            $snapshot = $reader->snapshot($this->context->businessId(), $shiftId);
            $this->context->authorizeLocation((int) ($snapshot['shift']['location_id'] ?? 0) ?: null);
            $settlement = $service->createFromShift(
                $this->context->businessId(),
                $shiftId,
                $this->context->userId(),
                $request->validated('settlement_date'),
                $request->validated('notes')
            );

            return redirect()->route('petro-pd-new.settlements.show', $settlement->id)
                ->with('success', 'Petro PD-New settlement created successfully.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function show(
        int $settlement,
        PdnewReconciliationService $reconciliation,
        PdnewMasterDataService $masterData,
        PdnewBusinessFeatureService $features
    )
    {
        $model = $this->settlement($settlement);
        $result = $reconciliation->evaluate($model);
        $model = $result['settlement']->load([
            'sourceImport', 'sources', 'pumps', 'meterSales',
            'payments.details', 'creditSales.lines', 'otherSales.lines',
            'unloadStocks.lines', 'dayEntries', 'collections', 'ledgerEntries',
            'recoveries', 'commissions', 'adjustments', 'approvals', 'history', 'issues', 'documents', 'printLogs',
        ]);

        $tabAccess = $features->settlementTabs($this->context->businessId());
        $user = request()->user();
        $tabAccess['payments'] = ($tabAccess['payments'] ?? false)
            && ($user->can('petro_pd_new.payments.view') || $user->can('petro_pd_new.payments.manage'));
        $tabAccess['adjustments'] = ($tabAccess['adjustments'] ?? false)
            && ($user->can('petro_pd_new.adjustments.view')
                || $user->can('petro_pd_new.adjustments.request')
                || $user->can('petro_pd_new.adjustments.approve'));
        $tabAccess['reconciliation'] = ($tabAccess['reconciliation'] ?? false)
            && ($user->can('petro_pd_new.reconciliation.view')
                || $user->can('petro_pd_new.reconciliation.manage'));

        return view('petropdnew::settlements.show', [
            'settlement' => $model,
            'sourceMatches' => $result['source_matches'],
            'issues' => $result['issues'],
            'customers' => $masterData->customers($this->context->businessId()),
            'tabAccess' => $tabAccess,
        ]);
    }

    public function edit(int $settlement)
    {
        $model = $this->settlement($settlement);
        return view('petropdnew::settlements.edit', ['settlement' => $model]);
    }

    public function update(SettlementUpdateRequest $request, int $settlement, PdnewSettlementService $service)
    {
        try {
            $model = $service->update($this->settlement($settlement), $request->validated());
            return redirect()->route('petro-pd-new.settlements.show', $model->id)
                ->with('success', 'Settlement details updated.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function refresh(int $settlement, PdnewSettlementService $service)
    {
        try {
            $model = $service->refreshFromSource($this->settlement($settlement), $this->context->userId());
            return redirect()->route('petro-pd-new.settlements.show', $model->id)
                ->with('success', 'Settlement refreshed from the latest closed Pumper Dashboard-New records.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function cancel(ReasonRequest $request, int $settlement, PdnewSettlementService $service)
    {
        try {
            $model = $service->cancel(
                $this->settlement($settlement),
                $this->context->userId(),
                (string) $request->validated('reason')
            );
            return redirect()->route('petro-pd-new.settlements.show', $model->id)
                ->with('success', 'Settlement cancelled.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }
}

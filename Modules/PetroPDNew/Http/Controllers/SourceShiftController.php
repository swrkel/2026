<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Http\Request;
use Modules\PetroPDNew\Entities\PdnewSourceImport;
use Modules\PetroPDNew\Http\Requests\SettlementCreateRequest;
use Modules\PetroPDNew\Services\Settlement\PdnewSettlementService;
use Modules\PetroPDNew\Services\Source\PoneSourceImportService;
use Modules\PetroPDNew\Services\Source\PoneSourceReader;

class SourceShiftController extends PdnewController
{
    public function index(Request $request, PoneSourceReader $reader)
    {
        $query = $reader->closedShiftQuery($this->context->businessId(), $this->context->locationId());

        if ($request->filled('status')) {
            if ($request->string('status')->toString() === 'available') $query->whereNull('i.settlement_id')->whereNull('r.settlement_no');
            if ($request->string('status')->toString() === 'imported') $query->whereNotNull('i.id')->whereNull('i.settlement_id')->whereNull('r.settlement_no');
            if ($request->string('status')->toString() === 'settled') $query->where(function ($q): void { $q->whereNotNull('i.settlement_id')->orWhereNotNull('r.settlement_no'); });
        }
        if ($request->filled('date_from')) $query->whereDate('s.closed_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('s.closed_at', '<=', $request->date_to);
        if ($request->filled('operator_profile_id')) $query->where('s.operator_profile_id', (int) $request->operator_profile_id);

        $shifts = $query->orderBy('s.closed_at')->paginate(50)->withQueryString();
        return view('petropdnew::sources.index', compact('shifts'));
    }

    public function show(int $shift, PoneSourceReader $reader)
    {
        $snapshot = $reader->snapshot($this->context->businessId(), $shift);
        $this->context->authorizeLocation((int) ($snapshot['shift']['location_id'] ?? 0) ?: null);
        $sourceImport = PdnewSourceImport::query()
            ->forBusiness($this->context->businessId())
            ->where('pone_shift_id', $shift)
            ->first();
        $settlementReference = $reader->settlementReference(
            $this->context->businessId(),
            $shift
        );

        return view('petropdnew::sources.show', compact(
            'snapshot',
            'sourceImport',
            'settlementReference'
        ));
    }

    public function import(int $shift, PoneSourceImportService $imports, PoneSourceReader $reader)
    {
        try {
            $snapshot = $reader->snapshot($this->context->businessId(), $shift);
            $this->context->authorizeLocation((int) ($snapshot['shift']['location_id'] ?? 0) ?: null);
            $source = $imports->import($this->context->businessId(), $shift, $this->context->userId());
            return back()->with('success', 'Pumper Dashboard-New shift imported as snapshot version ' . $source->snapshot_version . '.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function verify(int $source, PoneSourceImportService $imports)
    {
        try {
            $model = PdnewSourceImport::query()
                ->forBusiness($this->context->businessId())
                ->findOrFail($source);
            $this->context->authorizeLocation(
                $model->location_id ? (int) $model->location_id : null
            );
            $result = $imports->verify($model);
            return back()->with($result['matches'] ? 'success' : 'error',
                $result['matches'] ? 'The Pumper Dashboard-New source is unchanged.' : 'The Pumper Dashboard-New source has changed.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function createSettlement(SettlementCreateRequest $request, int $shift, PdnewSettlementService $service, PoneSourceReader $reader)
    {
        try {
            $snapshot = $reader->snapshot($this->context->businessId(), $shift);
            $this->context->authorizeLocation((int) ($snapshot['shift']['location_id'] ?? 0) ?: null);
            $settlement = $service->createFromShift(
                $this->context->businessId(),
                $shift,
                $this->context->userId(),
                $request->validated('settlement_date'),
                $request->validated('notes')
            );

            return redirect()->route('petro-pd-new.settlements.show', $settlement->id)
                ->with('success', 'Petro PD-New settlement created from the closed Pumper Dashboard-New shift.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }
}

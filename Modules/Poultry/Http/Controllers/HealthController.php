<?php

namespace Modules\Poultry\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\Shared\BusinessLocation;
use Modules\Poultry\Entities\Shared\Product;
use Modules\Poultry\Entities\Setting;
use Modules\Poultry\Entities\VaccinationSchedule;
use Modules\Poultry\Services\HealthService;
use Modules\Poultry\Services\WithdrawalGuard;

class HealthController extends PoultryBaseController
{
    protected $health;
    protected $withdrawal;

    public function __construct(HealthService $health, WithdrawalGuard $withdrawal)
    {
        $this->health     = $health;
        $this->withdrawal = $withdrawal;
    }

    public function index()
    {
        $this->authorizePermission('poultry.health.view');

        $businessId = $this->businessId();

        $batches = Batch::query()->forBusiness($businessId)->open()
            ->with(['house', 'breed'])
            ->orderBy('batch_code')
            ->get();

        $due = $batches->flatMap(function ($batch) {
            return $this->health->schedule($batch)
                ->where('is_done', false)
                ->map(function ($row) use ($batch) {
                    return $row + ['batch' => $batch];
                });
        })->sortBy('due_date')->values();

        return view('poultry::health.index', [
            'batches'     => $batches,
            'due'         => $due,
            'withdrawals' => $this->withdrawal->activeBatches($businessId),
        ]);
    }

    public function batch($id)
    {
        $this->authorizePermission('poultry.health.view');

        $businessId = $this->businessId();
        $batch = Batch::query()->forBusiness($businessId)->with('breed')->findOrFail($id);

        $medCategories = (array) Setting::get('medication_category_ids', []);

        return view('poultry::health.batch', [
            'batch'        => $batch,
            'schedule'     => $this->health->schedule($batch),
            'vaccinations' => $batch->vaccinations()->orderByDesc('administered_on')->get(),
            'treatments'   => $batch->treatments()->orderByDesc('started_on')->get(),
            'withdrawal'   => $batch->activeWithdrawal(),
            'items'        => Product::variationDropdown($medCategories, $businessId),
            'locations'    => BusinessLocation::dropdown($businessId),
            'routes'       => VaccinationSchedule::ROUTES,
        ]);
    }

    public function storeVaccination(Request $request)
    {
        $this->authorizePermission('poultry.health.create');

        $data = $request->validate([
            'batch_id'         => 'required|integer',
            'schedule_id'      => 'nullable|integer',
            'name'             => 'required|string|max:255',
            'product_id'       => 'nullable|integer',
            'variation_id'     => 'nullable|integer',
            'location_id'      => 'nullable|integer',
            'administered_on'  => 'required|date|before_or_equal:today',
            'birds_covered'    => 'nullable|integer|min:0',
            'dose'             => 'nullable|string|max:100',
            'route'            => 'nullable|string|max:50',
            'vaccine_batch_no' => 'nullable|string|max:100',
            'administered_by'  => 'nullable|string|max:255',
            'qty_used'         => 'nullable|numeric|min:0',
            'total_cost'       => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        $batch = Batch::query()->forBusiness($this->businessId())->findOrFail($data['batch_id']);

        try {
            $record = $this->health->recordVaccination($batch, $data);
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('Could not record the vaccination: '.$e->getMessage());
        }

        return $this->ok('Vaccination recorded.', ['record' => $record]);
    }

    public function storeTreatment(Request $request)
    {
        $this->authorizePermission('poultry.health.create');

        $data = $request->validate([
            'batch_id'        => 'required|integer',
            'name'            => 'required|string|max:255',
            'product_id'      => 'nullable|integer',
            'variation_id'    => 'nullable|integer',
            'location_id'     => 'nullable|integer',
            'diagnosis'       => 'nullable|string|max:255',
            'started_on'      => 'required|date',
            'ended_on'        => 'nullable|date|after_or_equal:started_on',
            'dosage'          => 'nullable|string|max:100',
            'route'           => 'nullable|string|max:50',
            'withdrawal_days' => 'nullable|integer|min:0|max:365',
            'vet_name'        => 'nullable|string|max:255',
            'qty_used'        => 'nullable|numeric|min:0',
            'total_cost'      => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string',
        ]);

        $batch = Batch::query()->forBusiness($this->businessId())->findOrFail($data['batch_id']);

        try {
            $treatment = $this->health->recordTreatment($batch, $data);
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('Could not record the treatment: '.$e->getMessage());
        }

        /*
         * The withdrawal date is the consequential output here, so it is
         * returned explicitly rather than left for the operator to look up.
         * From now until that date, produce from this batch will be recorded
         * but not posted to saleable stock.
         */
        return $this->ok(
            $treatment->withdrawal_until
                ? 'Treatment recorded. Produce from this batch must not be sold until '
                  .$treatment->withdrawal_until->toDateString().'.'
                : 'Treatment recorded.',
            [
                'treatment'        => $treatment,
                'withdrawal_until' => $treatment->withdrawal_until,
            ]
        );
    }

    public function withdrawals()
    {
        $this->authorizePermission('poultry.health.view');

        return view('poultry::health.withdrawals', [
            'withdrawals' => $this->withdrawal->activeBatches($this->businessId()),
        ]);
    }
}

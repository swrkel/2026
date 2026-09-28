<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Business;
use App\BusinessLocation;
use Modules\Petro\Entities\Pump;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\PetroShift;
use Modules\Petro\Entities\PumpOperatorAssignment;

class SeedPumpOperatorShiftData extends Command
{
    /**
     * php artisan petro:seed-pump-operator-shifts
     *   --business_id=3
     *   --month=2026-03        (seeds one shift per day for the whole month)
     *   --date=2026-03-30      (single day; ignored when --month is set)
     *   --operators=2          (number of pump operators to create if none exist)
     */
    protected $signature = 'petro:seed-pump-operator-shifts
                            {--business_id=1 : Business ID to seed data for}
                            {--month= : Seed every day of this month (Y-m format, e.g. 2026-03)}
                            {--date= : Single date (Y-m-d). Ignored when --month is provided. Defaults to today}
                            {--operators=2 : Number of pump operators to create if none exist}';

    protected $description = 'Seed pump operators, assign them to pumps, and create daily shifts';

    private int   $locationId;
    private array $operators = [];   // PumpOperator models
    private array $pumps     = [];   // Pump models

    public function handle(): int
    {
        $businessId    = (int) $this->option('business_id');
        $operatorCount = (int) $this->option('operators');

        // ── Validate business & location ─────────────────────────────────────
        $business = Business::find($businessId);
        if (!$business) {
            $this->error("Business with ID {$businessId} not found.");
            return 1;
        }

        $location = BusinessLocation::where('business_id', $businessId)->first();
        if (!$location) {
            $this->error("No business location found for business ID {$businessId}.");
            return 1;
        }

        $this->locationId = $location->id;
        $this->info("Business : {$business->name}");
        $this->info("Location : {$location->name} (ID: {$location->id})");

        // ── Ensure pumps exist ────────────────────────────────────────────────
        $this->resolvePumps($businessId);
        if (empty($this->pumps)) {
            $this->error("No pumps found for business {$businessId}. Please add pumps first.");
            return 1;
        }

        // ── Ensure pump operators exist ───────────────────────────────────────
        $this->resolveOperators($businessId, $operatorCount);
        if (empty($this->operators)) {
            $this->error("Could not create or find pump operators for business {$businessId}.");
            return 1;
        }

        // ── Build list of dates ───────────────────────────────────────────────
        $dates = $this->resolveDates();
        $this->info("Seeding " . count($dates) . " day(s) ...");
        $this->newLine();

        foreach ($dates as $date) {
            $this->line("── {$date} ──────────────────────────────────────");
            $this->seedDay($businessId, $date);
        }

        $this->newLine();
        $this->info('Done. Open Pump Operators → Shift Assignment to see the data.');

        return 0;
    }

    // ── Core seeder ───────────────────────────────────────────────────────────

    private function seedDay(int $businessId, string $date): void
    {
        $shiftNumber = $this->nextShiftNumber();

        foreach ($this->operators as $idx => $operator) {
            // Pick a pump for this operator (round-robin)
            $pump = $this->pumps[$idx % count($this->pumps)];

            // Skip if this operator already has an open assignment on this date
            $alreadyOpen = PumpOperatorAssignment::where('business_id', $businessId)
                ->where('pump_operator_id', $operator->id)
                ->where('status', 'open')
                ->whereDate('date_and_time', $date)
                ->exists();

            if ($alreadyOpen) {
                $this->warn("  Operator [{$operator->name}] already has a shift on {$date}, skipping.");
                continue;
            }

            // ── 1. Create petro_shift (status=0 = open) ──────────────────────
            $shift = PetroShift::create([
                'business_id'      => $businessId,
                'pump_operator_id' => $operator->id,
                'status'           => 0,          // 0 = open (required by get_shifts query)
                'shift_date'       => $date . ' 06:00:00',
                'closed_time'      => null,        // null = still open
                'work_shift_id'    => null,
            ]);

            // ── 2. Create pump_operator_assignment (status=open) ──────────────
            $startingMeter = (float) ($pump->last_meter_reading ?? 0) + rand(0, 100);
            $closingMeter  = $startingMeter + rand(50, 300);

            PumpOperatorAssignment::create([
                'business_id'              => $businessId,
                'pump_id'                  => $pump->id,
                'pump_operator_id'         => $operator->id,
                'shift_id'                 => $shift->id,
                'shift_number'             => $shiftNumber,
                'starting_meter'           => $startingMeter,
                'closing_meter'            => $closingMeter,
                'date_and_time'            => $date . ' 06:00:00',
                'close_date_and_time'      => null,   // open assignment
                'status'                   => 'open', // must be 'open' for the shift dropdown
                'assigned_by'              => 1,
                'is_confirmed'             => 1,
                'confirmed_at'             => $date . ' 06:05:00',
                'is_manually_closed'       => 0,
                'closed_in_settlement'     => 0,
                'settlement_id'            => null,
            ]);

            $this->info("  Operator [{$operator->name}] → Pump [{$pump->pump_name}] | shift_id={$shift->id} | meters={$startingMeter}→{$closingMeter}");

            $shiftNumber++;
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolvePumps(int $businessId): void
    {
        $pumps = Pump::where('business_id', $businessId)->get();

        if ($pumps->isEmpty()) {
            $this->warn("No pumps found for business {$businessId}.");
            return;
        }

        $this->pumps = $pumps->all();
        $names = implode(', ', array_map(fn($p) => $p->pump_name, $this->pumps));
        $this->info("Pumps    : {$names}");
    }

    private function resolveOperators(int $businessId, int $createCount): void
    {
        // PumpOperator has a global scope filtering active=1, use withoutGlobalScope
        $existing = PumpOperator::withoutGlobalScope('active')
            ->where('business_id', $businessId)
            ->get();

        if ($existing->isNotEmpty()) {
            $this->operators = $existing->all();
            $names = implode(', ', array_map(fn($o) => $o->name, $this->operators));
            $this->info("Operators: {$names} (existing)");
            return;
        }

        // Create new operators
        $this->info("No operators found — creating {$createCount} operator(s)...");

        $pump = $this->pumps[0] ?? null;

        for ($i = 1; $i <= $createCount; $i++) {
            $operator = PumpOperator::create([
                'business_id'     => $businessId,
                'location_id'     => $this->locationId,
                'pump_id'         => $pump ? $pump->id : 0,
                'assigned_pump_id'=> $pump ? $pump->id : 0,
                'name'            => "Pump Operator {$i}",
                'cnic'            => str_pad($i, 13, '0', STR_PAD_LEFT),
                'address'         => 'Seeded Address',
                'dob'             => '1990-01-01',
                'mobile'          => '0300000000' . $i,
                'landline'        => null,
                'status'          => 1,
                'commission_type' => 'none',
                'commission_ap'   => 0,
                'short_amount'    => 0,
                'excess_amount'   => 0,
                'settlement_no'   => null,
                'active'          => 1,
                'is_default'      => 0,
                'can_fullscreen'  => 0,
            ]);

            $this->operators[] = $operator;
            $this->info("  Created operator: {$operator->name} (ID: {$operator->id})");
        }
    }

    private function nextShiftNumber(): int
    {
        return (int) (PumpOperatorAssignment::max('shift_number') ?? 0) + 1;
    }

    private function resolveDates(): array
    {
        $month = $this->option('month');

        if ($month) {
            try {
                $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            } catch (\Exception $e) {
                $this->error("Invalid --month format. Use Y-m, e.g. 2026-03");
                exit(1);
            }

            $days = [];
            $end  = $start->copy()->endOfMonth();
            $day  = $start->copy();

            while ($day->lte($end)) {
                $days[] = $day->toDateString();
                $day->addDay();
            }

            return $days;
        }

        $date = $this->option('date') ?: Carbon::today()->toDateString();
        return [$date];
    }
}

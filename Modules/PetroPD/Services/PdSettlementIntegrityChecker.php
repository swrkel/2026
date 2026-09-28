<?php

namespace Modules\PetroPD\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Check a PD settlement's records agree with each other.
 *
 * WHY THIS EXISTS
 * ---------------
 * On 22 August three settlements were corrected by hand - PDST13, PDST16 and
 * PDST17 - and the day was spent finding out what was wrong. Almost none of it
 * was a fault in the code. The DATA disagreed with itself, and nothing was
 * looking:
 *
 *   LP922's meter reading was filed under shift 18 while the settlement for
 *   shift 17 expected it. The settlement showed two pumps that were not the
 *   operator's and none that were.
 *
 *   A meter sale carried operator 9 while its settlement was operator 8, so
 *   the query that reads by operator found nothing.
 *
 *   LP952's closing meter was BELOW its opening. The system stored the
 *   difference as a positive quantity and sold fuel that had not moved.
 *
 *   38 card payments were recorded against the wrong operator, and their
 *   daily_cards slip records against a third.
 *
 *   A credit sale said PDST6 instead of PDST16 - one missing character - and
 *   19,864 landed in a settlement that had already been finalised.
 *
 *   A meter sale detail kept the old shift after its header was moved, because
 *   the same fact is stored in both.
 *
 * Every one of those was visible in the data. None was reported until someone
 * noticed a figure looked wrong, by which time two of the settlements had been
 * finalised and posted.
 *
 * WHAT THIS DOES, AND DOES NOT DO
 * -------------------------------
 * It reports. It changes nothing, corrects nothing and blocks nothing.
 *
 * Deliberately: a check that refuses to let a settlement save would, on the
 * evidence of 22 August, have stopped work for a whole day on findings that
 * needed a human decision anyway. Several of the "wrong" records turned out to
 * be right, and the office had to be asked. So this hands the findings to a
 * person rather than deciding for them.
 *
 * HOW TO USE IT
 *   $issues = app(PdSettlementIntegrityChecker::class)->check($settlement);
 *
 * Each issue carries a severity, a plain description, and the ids involved, so
 * it can be shown on screen, logged, or run across every settlement to find
 * which need attention.
 */
class PdSettlementIntegrityChecker
{
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_WARNING = 'warning';

    /**
     * @return array<int, array{severity:string, check:string, message:string, ids:array}>
     */
    public function check($settlement): array
    {
        $issues = [];

        if (empty($settlement)) {
            return $issues;
        }

        $businessId = (int) $settlement->business_id;
        $settlementNo = (string) $settlement->settlement_no;
        $operatorId = (int) $settlement->pump_operator_id;
        $shiftIds = $this->shiftIdsOf($settlement);

        foreach ([
            'checkPumpsMatchAssignments',
            'checkOperatorAgreement',
            'checkMeterDirection',
            'checkHeaderDetailAgreement',
            'checkSupportingRecords',
        ] as $method) {
            try {
                $issues = array_merge(
                    $issues,
                    $this->{$method}($businessId, $settlementNo, $operatorId, $shiftIds)
                );
            } catch (\Throwable $e) {
                // A check must never break the page it is reporting on.
                Log::warning('PD integrity check failed', [
                    'check' => $method,
                    'settlement_no' => $settlementNo,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $issues;
    }

    /**
     * PDST17: the settlement showed LP921 and LP952; the operator had worked
     * LP922. The assignments said LP921 and LP952 too - both were wrong
     * together - but on PDST13 the assignments were right and the meter sales
     * were not, and this would have caught it immediately.
     */
    private function checkPumpsMatchAssignments(
        int $businessId,
        string $settlementNo,
        int $operatorId,
        array $shiftIds
    ): array {
        if (empty($shiftIds)) {
            return [];
        }

        $assignedPumps = DB::table('pump_operator_assignments')
            ->where('business_id', $businessId)
            ->whereIn('shift_id', $shiftIds)
            ->where('pump_operator_id', $operatorId)
            ->distinct()
            ->pluck('pump_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->toArray();

        $settledPumps = DB::table('pump_operator_meter_sales as s')
            ->join('pump_operator_meter_sale_details as d', 'd.sale_id', '=', 's.id')
            ->where('s.settlement_no', $settlementNo)
            ->distinct()
            ->pluck('d.pump_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->toArray();

        if ($assignedPumps === $settledPumps) {
            return [];
        }

        $issues = [];

        $onlySettled = array_values(array_diff($settledPumps, $assignedPumps));
        $onlyAssigned = array_values(array_diff($assignedPumps, $settledPumps));

        if (! empty($onlySettled)) {
            $issues[] = [
                'severity' => self::SEVERITY_ERROR,
                'check' => 'pumps_match_assignments',
                'message' => 'This settlement contains pumps the operator was not assigned for this shift: '
                    . $this->pumpNames($onlySettled) . '.',
                'ids' => ['pump_ids' => $onlySettled],
            ];
        }

        if (! empty($onlyAssigned)) {
            $issues[] = [
                'severity' => self::SEVERITY_ERROR,
                'check' => 'pumps_match_assignments',
                'message' => 'The operator was assigned pumps that do not appear in this settlement: '
                    . $this->pumpNames($onlyAssigned) . '.',
                'ids' => ['pump_ids' => $onlyAssigned],
            ];
        }

        return $issues;
    }

    /**
     * PDST17: a meter sale carried operator 9 while the settlement was operator
     * 8. Every query here reads by operator, so the row was invisible - the
     * page showed no pumps at all and gave no reason.
     */
    private function checkOperatorAgreement(
        int $businessId,
        string $settlementNo,
        int $operatorId,
        array $shiftIds
    ): array {
        $wrong = DB::table('pump_operator_meter_sales')
            ->where('settlement_no', $settlementNo)
            ->where('pump_operator_id', '<>', $operatorId)
            ->pluck('id')
            ->toArray();

        if (empty($wrong)) {
            return [];
        }

        return [[
            'severity' => self::SEVERITY_ERROR,
            'check' => 'operator_agreement',
            'message' => 'Meter sales on this settlement belong to a different pump operator. '
                . 'They will not appear on any screen that reads by operator.',
            'ids' => ['meter_sale_ids' => $wrong],
        ]];
    }

    /**
     * PDST17: LP952's closing meter was 101,331.25 against an opening of
     * 101,596.79 - lower. The system took the difference as 255.54 litres and
     * sold fuel that had not been pumped.
     *
     * A meter cannot run backwards. If it is replaced or reset, that is a
     * meter reset and is recorded as one.
     */
    private function checkMeterDirection(
        int $businessId,
        string $settlementNo,
        int $operatorId,
        array $shiftIds
    ): array {
        $backwards = DB::table('pump_operator_meter_sales as s')
            ->join('pump_operator_meter_sale_details as d', 'd.sale_id', '=', 's.id')
            ->where('s.settlement_no', $settlementNo)
            ->whereColumn('d.new_meter', '<', 'd.received_meter')
            ->select('d.id', 'd.pump_id', 'd.received_meter', 'd.new_meter')
            ->get();

        if ($backwards->isEmpty()) {
            return [];
        }

        $issues = [];

        foreach ($backwards as $row) {
            $issues[] = [
                'severity' => self::SEVERITY_ERROR,
                'check' => 'meter_direction',
                'message' => sprintf(
                    'Closing meter is lower than opening on %s: opened %s, closed %s. '
                        . 'A meter cannot run backwards.',
                    $this->pumpNames([(int) $row->pump_id]),
                    number_format((float) $row->received_meter, 2),
                    number_format((float) $row->new_meter, 2)
                ),
                'ids' => ['detail_id' => (int) $row->id],
            ];
        }

        return $issues;
    }

    /**
     * PDST17: moving a meter sale to another shift left its DETAIL row holding
     * the old shift and operator, because both records carry their own copy.
     * The header was found and contributed nothing.
     */
    private function checkHeaderDetailAgreement(
        int $businessId,
        string $settlementNo,
        int $operatorId,
        array $shiftIds
    ): array {
        $mismatched = DB::table('pump_operator_meter_sales as s')
            ->join('pump_operator_meter_sale_details as d', 'd.sale_id', '=', 's.id')
            ->where('s.settlement_no', $settlementNo)
            ->whereNotNull('d.pump_operator_id')
            ->whereColumn('d.pump_operator_id', '<>', 's.pump_operator_id')
            ->pluck('d.id')
            ->toArray();

        if (empty($mismatched)) {
            return [];
        }

        return [[
            'severity' => self::SEVERITY_WARNING,
            'check' => 'header_detail_agreement',
            'message' => 'Meter sale detail rows disagree with their own header about the operator. '
                . 'Totals read from details may not match rows read from headers.',
            'ids' => ['detail_ids' => $mismatched],
        ]];
    }

    /**
     * PDST17: 38 card payments moved shift, but their daily_cards slip records
     * stayed behind under a third operator, so the reconfirmation showed no
     * card details at all while the total was right.
     */
    private function checkSupportingRecords(
        int $businessId,
        string $settlementNo,
        int $operatorId,
        array $shiftIds
    ): array {
        if (empty($shiftIds) || ! Schema::hasTable('daily_cards')) {
            return [];
        }

        $cardPayments = DB::table('pump_operator_payments')
            ->where('business_id', $businessId)
            ->where('payment_type', 'card')
            ->whereIn('shift_id', $shiftIds)
            ->where('pump_operator_id', $operatorId)
            ->count();

        if ($cardPayments === 0) {
            return [];
        }

        $cardRecords = DB::table('daily_cards')
            ->where('business_id', $businessId)
            ->where('settlement_no', $settlementNo)
            ->count();

        if ($cardRecords >= $cardPayments) {
            return [];
        }

        return [[
            'severity' => self::SEVERITY_WARNING,
            'check' => 'supporting_records',
            'message' => sprintf(
                'This settlement has %d card payments but only %d card slip records. '
                    . 'Card details will be missing from the reconfirmation.',
                $cardPayments,
                $cardRecords
            ),
            'ids' => ['card_payments' => $cardPayments, 'card_records' => $cardRecords],
        ]];
    }

    private function shiftIdsOf($settlement): array
    {
        $raw = $settlement->work_shift;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : explode(',', $raw);
        }

        return array_values(array_filter(array_map('intval', (array) $raw)));
    }

    private function pumpNames(array $pumpIds): string
    {
        if (empty($pumpIds)) {
            return '-';
        }

        $names = DB::table('pumps')
            ->whereIn('id', $pumpIds)
            ->pluck('pump_name')
            ->filter()
            ->toArray();

        return empty($names) ? implode(', ', $pumpIds) : implode(', ', $names);
    }
}

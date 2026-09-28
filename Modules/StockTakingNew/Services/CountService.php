<?php

namespace Modules\StockTakingNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\StockTakingNew\Entities\StockTakeApproval;
use Modules\StockTakingNew\Entities\StockTakeCount;
use Modules\StockTakingNew\Entities\StockTakeLine;
use Modules\StockTakingNew\Entities\StockTakeSession;

class CountService
{
    public function __construct(
        private SessionService $sessions,
        private SettingsService $settings,
        private AuditService $audit
    ) {}

    public function save(StockTakeSession $session, array $counts, bool $isRecount = false): int
    {
        if (! in_array($session->status, ['counting', 'recount'], true)) {
            throw new \RuntimeException('This session is not open for counting.');
        }
        if ($isRecount && $session->status !== 'recount') {
            throw new \RuntimeException('Complete the initial count before entering recount quantities.');
        }
        if (! $isRecount && $session->status === 'recount') {
            throw new \RuntimeException('This session now accepts recount quantities only.');
        }

        return DB::transaction(function () use ($session, $counts, $isRecount): int {
            $saved = 0;
            foreach ($counts as $row) {
                $line = StockTakeLine::where('session_id', $session->id)
                    ->where('id', (int) $row['line_id'])->lockForUpdate()->firstOrFail();

                if ($isRecount && ! $line->requires_recount) {
                    continue;
                }

                $quantity = (float) $row['counted_qty'];
                $attempt = (int) $line->counts()->max('attempt_no') + 1;
                StockTakeCount::create([
                    'business_id' => $session->business_id,
                    'session_id' => $session->id,
                    'line_id' => $line->id,
                    'attempt_no' => $attempt,
                    'count_type' => $isRecount ? 'recount' : 'initial',
                    'counted_qty' => $quantity,
                    'notes' => $row['notes'] ?? null,
                    'barcode' => $row['barcode'] ?? null,
                    'bin_location' => $row['bin_location'] ?? $line->bin_location,
                    'counted_by' => auth()->id(),
                    'counted_at' => now(),
                ]);

                $variance = $quantity - (float) $line->system_qty;
                $varianceValue = $variance * (float) $line->unit_cost;
                $requiresRecount = ! $isRecount
                    && $session->require_recount
                    && (
                        abs($variance) > (float) $session->variance_qty_threshold
                        || abs($varianceValue) > (float) $session->variance_value_threshold
                    );

                $payload = [
                    'final_count_qty' => $quantity,
                    'variance_qty' => $variance,
                    'variance_value' => $varianceValue,
                    'is_counted' => true,
                    'count_status' => $requiresRecount ? 'recount_required' : 'counted',
                    'requires_recount' => $requiresRecount,
                    'counted_by' => auth()->id(),
                    'counted_at' => now(),
                    'notes' => $row['notes'] ?? $line->notes,
                    'bin_location' => $row['bin_location'] ?? $line->bin_location,
                ];

                if ($isRecount) {
                    $payload['recount_qty'] = $quantity;
                    $payload['recounted_by'] = auth()->id();
                    $payload['recounted_at'] = now();
                    $payload['requires_recount'] = false;
                    $payload['count_status'] = 'recounted';
                } elseif ($line->first_count_qty === null) {
                    $payload['first_count_qty'] = $quantity;
                }

                $line->update($payload);
                $saved++;
            }

            $this->sessions->recalculate($session);
            $this->audit->log($session->business_id, $session->id, $isRecount ? 'recounts_saved' : 'counts_saved',
                'session', $session->id, [], ['rows' => $saved]);

            return $saved;
        });
    }

    /**
     * @return string recount|submitted|approved
     */
    public function submit(StockTakeSession $session): string
    {
        $this->sessions->recalculate($session);
        if ((int) $session->counted_line_count < (int) $session->line_count) {
            throw new \RuntimeException('All product lines must be counted before completion.');
        }

        if ($session->lines()->where('requires_recount', 1)->exists()) {
            $session->update(['status' => 'recount']);
            $this->audit->log($session->business_id, $session->id, 'recount_started', 'session', $session->id);
            return 'recount';
        }

        $moduleSettings = $this->settings->all((int) $session->business_id);
        $requireApproval = $this->settings->bool($moduleSettings, 'require_approval', true);
        if (! $requireApproval) {
            DB::transaction(function () use ($session): void {
                $session->update([
                    'status' => 'approved',
                    'submitted_by' => auth()->id(),
                    'submitted_at' => now(),
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'approval_remarks' => 'Automatically approved by module settings.',
                ]);
                StockTakeApproval::create([
                    'business_id' => $session->business_id,
                    'session_id' => $session->id,
                    'level_no' => 1,
                    'action' => 'auto_approved',
                    'remarks' => 'Approval was disabled in Stock Taking settings.',
                    'acted_by' => auth()->id(),
                    'acted_at' => now(),
                ]);
            });
            $this->audit->log($session->business_id, $session->id, 'counts_auto_approved', 'session', $session->id);
            return 'approved';
        }

        $session->update([
            'status' => 'submitted',
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
        ]);
        $this->audit->log($session->business_id, $session->id, 'counts_submitted', 'session', $session->id, [], [
            'line_count' => $session->line_count,
        ]);
        return 'submitted';
    }
}

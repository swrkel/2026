<?php

namespace Modules\PumperDashboardNew\Services\Integration;

use Illuminate\Support\Facades\DB;
use Modules\PumperDashboardNew\Entities\PoneDayEntry;
use Modules\PumperDashboardNew\Entities\PoneIntegrationOutbox;
use Modules\PumperDashboardNew\Entities\PoneOtherSale;
use Modules\PumperDashboardNew\Entities\PonePayment;
use Modules\PumperDashboardNew\Entities\PonePumpAssignment;
use Modules\PumperDashboardNew\Entities\PoneShift;
use Modules\PumperDashboardNew\Entities\PoneUnloadStock;
use RuntimeException;

class PoneIntegrationProcessor
{
    private const PROCESSING_LOCK_MINUTES = 15;

    public function __construct(private PonePetroPdNewPublisher $bridge) {}

    public function process(PoneIntegrationOutbox $candidate): bool
    {
        $jobId = (int) $candidate->id;
        $job = $this->claim($jobId);

        if (! $job) {
            return (string) PoneIntegrationOutbox::query()
                ->whereKey($jobId)
                ->value('status') === 'processed';
        }

        try {
            $success = $this->dispatch($job);
            $attempts = max(1, (int) $job->attempts);

            $job->update([
                'status' => $success ? 'processed' : 'failed',
                'processed_at' => $success ? now() : null,
                'last_error' => $success ? null : (string) $job->fresh()->last_error,
                'available_at' => $success
                    ? null
                    : now()->addMinutes(min(60, 2 ** min(6, $attempts))),
            ]);

            return $success;
        } catch (\Throwable $exception) {
            report($exception);
            $attempts = max(1, (int) $job->attempts);

            $job->update([
                'status' => 'failed',
                'processed_at' => null,
                'last_error' => substr($exception->getMessage(), 0, 4000),
                'available_at' => now()->addMinutes(min(60, 2 ** min(6, $attempts))),
            ]);

            return false;
        }
    }

    public function retry(PoneIntegrationOutbox $job): bool
    {
        $jobId = (int) $job->id;

        $ready = DB::transaction(function () use ($jobId): bool {
            $locked = PoneIntegrationOutbox::query()
                ->whereKey($jobId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((string) $locked->status === 'processed') {
                return false;
            }

            $stale = (string) $locked->status === 'processing'
                && $locked->updated_at
                && $locked->updated_at->lte(now()->subMinutes(self::PROCESSING_LOCK_MINUTES));

            if ((string) $locked->status === 'processing' && ! $stale) {
                throw new RuntimeException(
                    'This Pumper Dashboard-New integration operation is already being processed.'
                );
            }

            $maximum = max(1, (int) config(
                'pumperdashboardnew.integration.maximum_retry_attempts',
                10
            ));

            $locked->update([
                'status' => 'pending',
                'attempts' => min((int) $locked->attempts, $maximum - 1),
                'available_at' => now(),
                'processed_at' => null,
                'last_error' => $stale
                    ? 'Recovered a stale processing claim for an authorized manual retry.'
                    : null,
            ]);

            return true;
        }, 3);

        if (! $ready) {
            return true;
        }

        return $this->process(PoneIntegrationOutbox::query()->findOrFail($jobId));
    }

    public function processPending(?int $businessId = null, int $limit = 100): array
    {
        $query = PoneIntegrationOutbox::query()
            ->where(function ($outer): void {
                $outer->where(function ($available): void {
                    $available->whereIn('status', ['pending', 'failed'])
                        ->where(function ($time): void {
                            $time->whereNull('available_at')
                                ->orWhere('available_at', '<=', now());
                        });
                })->orWhere(function ($stale): void {
                    $stale->where('status', 'processing')
                        ->where('updated_at', '<=', now()->subMinutes(self::PROCESSING_LOCK_MINUTES));
                });
            });

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        $processed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($query->orderBy('id')->limit($limit)->get() as $candidate) {
            $success = $this->process($candidate);
            $status = (string) PoneIntegrationOutbox::query()
                ->whereKey($candidate->id)
                ->value('status');

            if ($success && $status === 'processed') {
                $processed++;
            } elseif ($status === 'processing') {
                // Another worker owns the non-stale claim.
                $skipped++;
            } else {
                $failed++;
            }
        }

        return compact('processed', 'failed', 'skipped');
    }

    private function claim(int $jobId): ?PoneIntegrationOutbox
    {
        return DB::transaction(function () use ($jobId): ?PoneIntegrationOutbox {
            $job = PoneIntegrationOutbox::query()
                ->whereKey($jobId)
                ->lockForUpdate()
                ->first();

            if (! $job || (string) $job->status === 'processed') {
                return null;
            }

            $isStale = (string) $job->status === 'processing'
                && $job->updated_at
                && $job->updated_at->lte(now()->subMinutes(self::PROCESSING_LOCK_MINUTES));

            if ((string) $job->status === 'processing' && ! $isStale) {
                return null;
            }

            if (! in_array((string) $job->status, ['pending', 'failed', 'processing'], true)) {
                return null;
            }

            if (! $isStale && $job->available_at && $job->available_at->isFuture()) {
                return null;
            }

            $maximum = max(1, (int) config(
                'pumperdashboardnew.integration.maximum_retry_attempts',
                10
            ));
            if ((int) $job->attempts >= $maximum) {
                $job->update([
                    'status' => 'failed',
                    'last_error' => 'The maximum number of Petro PD-New publication attempts has been reached.',
                ]);

                return null;
            }

            $job->update([
                'status' => 'processing',
                'attempts' => (int) $job->attempts + 1,
                'processed_at' => null,
                'last_error' => $isStale
                    ? 'Recovered a stale Pumper Dashboard-New publication claim.'
                    : $job->last_error,
            ]);

            return $job->fresh();
        }, 3);
    }

    private function dispatch(PoneIntegrationOutbox $job): bool
    {
        switch ($job->aggregate_type) {
            case 'shift':
                $shift = PoneShift::query()->where('business_id', $job->business_id)->findOrFail($job->aggregate_id);
                return $job->event_type === 'shift.close'
                    ? $this->bridge->closeShift($shift)
                    : $this->bridge->syncShift($shift);

            case 'assignment':
                return $this->bridge->syncAssignment(
                    PonePumpAssignment::query()->where('business_id', $job->business_id)->findOrFail($job->aggregate_id)
                );

            case 'payment':
                $payment = PonePayment::query()->where('business_id', $job->business_id)->findOrFail($job->aggregate_id);
                return $job->event_type === 'payment.void' || $payment->status === 'void'
                    ? $this->bridge->voidPayment($payment)
                    : $this->bridge->syncPayment($payment);

            case 'other_sale':
                $sale = PoneOtherSale::query()->where('business_id', $job->business_id)->findOrFail($job->aggregate_id);
                return $job->event_type === 'other_sale.void' || $sale->status === 'void'
                    ? $this->bridge->voidOtherSale($sale)
                    : $this->bridge->syncOtherSale($sale);

            case 'unload_stock':
                $unload = PoneUnloadStock::query()->where('business_id', $job->business_id)->findOrFail($job->aggregate_id);
                return $job->event_type === 'unload_stock.void' || $unload->status === 'void'
                    ? $this->bridge->voidUnloadStock($unload)
                    : $this->bridge->syncUnloadStock($unload);

            case 'day_entry':
                $entry = PoneDayEntry::query()->where('business_id', $job->business_id)->findOrFail($job->aggregate_id);
                return $job->event_type === 'day_entry.void' || $entry->status === 'void'
                    ? $this->bridge->voidDayEntry($entry)
                    : $this->bridge->syncDayEntry($entry);

            default:
                throw new RuntimeException(
                    'Unknown Pumper Dashboard-New integration aggregate: ' . $job->aggregate_type
                );
        }
    }
}

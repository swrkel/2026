<?php

namespace Modules\PetroPDNew\Services\Integration;

use Illuminate\Support\Facades\DB;
use Modules\PetroPDNew\Entities\PdnewDayEnd;
use Modules\PetroPDNew\Entities\PdnewIntegrationLog;
use Modules\PetroPDNew\Entities\PdnewIntegrationOutbox;
use Modules\PetroPDNew\Entities\PdnewSettlement;
use Modules\PetroPDNew\Services\Notification\PdnewNotificationService;
use RuntimeException;

class PdnewIntegrationProcessor
{
    private const PROCESSING_LOCK_MINUTES = 15;

    public function __construct(private PdnewNotificationService $notifications) {}

    public function process(PdnewIntegrationOutbox $job): bool
    {
        $jobId = (int) $job->id;
        $job = $this->claim($jobId);

        if (! $job) {
            return (string) PdnewIntegrationOutbox::query()
                ->whereKey($jobId)
                ->value('status') === 'processed';
        }

        $log = PdnewIntegrationLog::query()->create([
            'business_id' => $job->business_id,
            'direction' => 'internal',
            'operation' => $job->event_type,
            'aggregate_type' => $job->aggregate_type,
            'aggregate_id' => $job->aggregate_id,
            'status' => 'processing',
            'payload' => $job->payload,
            'started_at' => now(),
        ]);

        try {
            $response = $this->dispatch($job);
            $job->update([
                'status' => 'processed',
                'processed_at' => now(),
                'last_error' => null,
            ]);
            $log->update([
                'status' => 'processed',
                'response' => $response,
                'completed_at' => now(),
            ]);

            return true;
        } catch (\Throwable $exception) {
            report($exception);
            $attempts = max(1, (int) $job->attempts);
            $job->update([
                'status' => 'failed',
                'last_error' => substr($exception->getMessage(), 0, 4000),
                'available_at' => now()->addMinutes(min(60, 2 ** min(6, $attempts))),
            ]);
            $log->update([
                'status' => 'failed',
                'error_message' => substr($exception->getMessage(), 0, 4000),
                'completed_at' => now(),
            ]);

            return false;
        }
    }

    public function retry(PdnewIntegrationOutbox $job): bool
    {
        $jobId = (int) $job->id;

        $ready = DB::transaction(function () use ($jobId): bool {
            $locked = PdnewIntegrationOutbox::query()
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
                    'This integration operation is already being processed by another request.'
                );
            }

            $locked->update([
                'status' => 'pending',
                'available_at' => now(),
                'processed_at' => null,
                'last_error' => $stale
                    ? 'Recovered a stale processing claim for a manual retry.'
                    : null,
            ]);

            return true;
        }, 3);

        if (! $ready) {
            return true;
        }

        return $this->process(PdnewIntegrationOutbox::query()->findOrFail($jobId));
    }

    public function processPending(?int $businessId = null, int $limit = 100, ?int $locationId = null): array
    {
        $query = PdnewIntegrationOutbox::query()
            ->where(function ($outer): void {
                $outer->where(function ($ready): void {
                    $ready->whereIn('status', ['pending', 'failed'])
                        ->where(function ($available): void {
                            $available->whereNull('available_at')
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
        if ($locationId) {
            $this->scopeToLocation($query, $locationId);
        }

        $processed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($query->orderBy('id')->limit($limit)->get() as $candidate) {
            $before = (string) $candidate->status;
            $ok = $this->process($candidate);
            $after = (string) PdnewIntegrationOutbox::query()
                ->whereKey($candidate->id)
                ->value('status');

            if ($ok && $after === 'processed') {
                $processed++;
            } elseif ($after === 'processing' && $before !== 'processing') {
                $skipped++;
            } elseif ($after === 'processing') {
                $skipped++;
            } else {
                $failed++;
            }
        }

        return compact('processed', 'failed', 'skipped');
    }


    private function scopeToLocation($query, int $locationId): void
    {
        $table = 'pdnew_integration_outbox';
        $query->where(function ($scope) use ($locationId, $table): void {
            $scope->where(function ($settlementJob) use ($locationId, $table): void {
                $settlementJob->where($table . '.aggregate_type', 'settlement')
                    ->whereExists(function ($source) use ($locationId, $table): void {
                        $source->selectRaw('1')
                            ->from('pdnew_settlements as scoped_settlement')
                            ->whereColumn('scoped_settlement.id', $table . '.aggregate_id')
                            ->where('scoped_settlement.location_id', $locationId);
                    });
            })->orWhere(function ($dayEndJob) use ($locationId, $table): void {
                $dayEndJob->where($table . '.aggregate_type', 'day_end')
                    ->whereExists(function ($source) use ($locationId, $table): void {
                        $source->selectRaw('1')
                            ->from('pdnew_day_ends as scoped_day_end')
                            ->whereColumn('scoped_day_end.id', $table . '.aggregate_id')
                            ->where('scoped_day_end.location_id', $locationId);
                    });
            });
        });
    }

    private function claim(int $jobId): ?PdnewIntegrationOutbox
    {
        return DB::transaction(function () use ($jobId): ?PdnewIntegrationOutbox {
            $job = PdnewIntegrationOutbox::query()
                ->whereKey($jobId)
                ->lockForUpdate()
                ->first();

            if (! $job || $job->status === 'processed') {
                return null;
            }

            $isStaleProcessing = $job->status === 'processing'
                && $job->updated_at
                && $job->updated_at->lte(now()->subMinutes(self::PROCESSING_LOCK_MINUTES));

            if ($job->status === 'processing' && ! $isStaleProcessing) {
                return null;
            }

            if (! in_array((string) $job->status, ['pending', 'failed', 'processing'], true)) {
                return null;
            }

            if (! $isStaleProcessing && $job->available_at && $job->available_at->isFuture()) {
                return null;
            }

            $job->update([
                'status' => 'processing',
                'attempts' => (int) $job->attempts + 1,
                'processed_at' => null,
                'last_error' => $isStaleProcessing
                    ? 'Recovered a stale processing claim and retried the operation.'
                    : $job->last_error,
            ]);

            return $job->fresh();
        }, 3);
    }

    private function dispatch(PdnewIntegrationOutbox $job): array
    {
        if ($job->event_type === 'settlement.finalized') {
            $settlement = PdnewSettlement::query()
                ->where('business_id', $job->business_id)
                ->findOrFail($job->aggregate_id);

            return [
                'notifications_queued' => $this->notifications
                    ->queueForSettlement($settlement, 'settlement.finalized'),
            ];
        }

        if ($job->event_type === 'day_end.finalized') {
            PdnewDayEnd::query()
                ->where('business_id', $job->business_id)
                ->findOrFail($job->aggregate_id);

            return ['day_end_logged' => true];
        }

        throw new RuntimeException(
            'Unsupported Petro PD-New integration event: ' . $job->event_type
        );
    }
}

<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Http\Request;
use Modules\PetroPDNew\Entities\PdnewDayEnd;
use Modules\PetroPDNew\Entities\PdnewIntegrationLog;
use Modules\PetroPDNew\Entities\PdnewIntegrationOutbox;
use Modules\PetroPDNew\Entities\PdnewSettlement;
use Modules\PetroPDNew\Entities\PdnewSourceImport;
use Modules\PetroPDNew\Services\Integration\PdnewIntegrationProcessor;
use Modules\PetroPDNew\Services\Source\PoneSourceImportService;

class IntegrationController extends PdnewController
{
    public function index(Request $request)
    {
        $businessId = $this->context->businessId();
        $locationId = $this->context->locationId();

        $jobsQuery = PdnewIntegrationOutbox::query()->forBusiness($businessId);
        if ($request->filled('status')) {
            $jobsQuery->where('status', $request->status);
        }

        $logsQuery = PdnewIntegrationLog::query()->forBusiness($businessId);
        $sourcesQuery = PdnewSourceImport::query()
            ->forBusiness($businessId)
            ->forLocation($locationId)
            ->where('import_status', 'changed');

        if ($locationId) {
            $this->scopeAggregateQueryToLocation(
                $jobsQuery,
                $locationId,
                'pdnew_integration_outbox'
            );
            $this->scopeAggregateQueryToLocation(
                $logsQuery,
                $locationId,
                'pdnew_integration_logs'
            );
        }

        $jobs = $jobsQuery->orderByDesc('id')
            ->paginate(30, ['*'], 'jobs_page')
            ->withQueryString();
        $logs = $logsQuery->orderByDesc('id')
            ->paginate(30, ['*'], 'logs_page')
            ->withQueryString();
        $changedSources = $sourcesQuery->orderByDesc('verified_at')->limit(100)->get();

        return view('petropdnew::integration.index', compact('jobs', 'logs', 'changedSources'));
    }

    public function retry(int $job, PdnewIntegrationProcessor $processor)
    {
        try {
            $model = PdnewIntegrationOutbox::query()
                ->forBusiness($this->context->businessId())
                ->findOrFail($job);
            $this->authorizeAggregateLocation(
                (string) $model->aggregate_type,
                (int) $model->aggregate_id
            );

            $ok = $processor->retry($model);
            return back()->with(
                $ok ? 'success' : 'error',
                $ok
                    ? 'Integration operation processed.'
                    : 'Integration operation failed again.'
            );
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function retryAll(PdnewIntegrationProcessor $processor)
    {
        try {
            $result = $processor->processPending(
                $this->context->businessId(),
                200,
                $this->context->locationId()
            );

            return back()->with(
                'success',
                "Processed {$result['processed']} operation(s); "
                . "{$result['failed']} failed; {$result['skipped']} already claimed."
            );
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function verifySources(PoneSourceImportService $imports)
    {
        try {
            $verified = 0;
            $changed = 0;
            $query = PdnewSourceImport::query()
                ->forBusiness($this->context->businessId())
                ->forLocation($this->context->locationId())
                ->orderBy('id');

            foreach ($query->cursor() as $source) {
                $result = $imports->verify($source);
                $result['matches'] ? $verified++ : $changed++;
            }

            return back()->with(
                'success',
                "Verified {$verified} unchanged source(s); {$changed} changed source(s)."
            );
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    private function authorizeAggregateLocation(string $aggregateType, int $aggregateId): void
    {
        if (! $this->context->locationId()) {
            return;
        }

        if ($aggregateType === 'settlement') {
            $model = PdnewSettlement::query()
                ->forBusiness($this->context->businessId())
                ->findOrFail($aggregateId);
            $this->context->authorizeLocation(
                $model->location_id ? (int) $model->location_id : null
            );
            return;
        }

        if ($aggregateType === 'day_end') {
            $model = PdnewDayEnd::query()
                ->forBusiness($this->context->businessId())
                ->findOrFail($aggregateId);
            $this->context->authorizeLocation(
                $model->location_id ? (int) $model->location_id : null
            );
            return;
        }

        abort(403, 'This integration operation is not available in the active location.');
    }

    private function scopeAggregateQueryToLocation(
        $query,
        int $locationId,
        string $table
    ): void {
        $query->where(function ($scope) use ($locationId, $table): void {
            $scope->where(function ($settlementJob) use ($locationId, $table): void {
                $settlementJob->where($table . '.aggregate_type', 'settlement')
                    ->whereExists(function ($source) use ($locationId, $table): void {
                        $source->selectRaw('1')
                            ->from('pdnew_settlements as scoped_settlement')
                            ->whereColumn(
                                'scoped_settlement.id',
                                $table . '.aggregate_id'
                            )
                            ->where('scoped_settlement.location_id', $locationId);
                    });
            })->orWhere(function ($dayEndJob) use ($locationId, $table): void {
                $dayEndJob->where($table . '.aggregate_type', 'day_end')
                    ->whereExists(function ($source) use ($locationId, $table): void {
                        $source->selectRaw('1')
                            ->from('pdnew_day_ends as scoped_day_end')
                            ->whereColumn(
                                'scoped_day_end.id',
                                $table . '.aggregate_id'
                            )
                            ->where('scoped_day_end.location_id', $locationId);
                    });
            });
        });
    }
}

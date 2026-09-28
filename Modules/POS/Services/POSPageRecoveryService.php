<?php

namespace Modules\POS\Services;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class POSPageRecoveryService
{
    public function returnsPage(Request $request): array
    {
        $stats = [
            'returns_count' => 0,
            'refund_total' => 0,
            'pending_approval' => 0,
            'exchanges_count' => 0,
        ];
        $returns = $this->emptyPaginator($request, 25);
        $sales = collect();
        $warnings = [];

        try {
            $service = app(POSReturnService::class);
            $loadedStats = $service->dashboardStats($request);
            if (is_array($loadedStats)) {
                $stats = array_merge($stats, $loadedStats);
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'Return totals are temporarily unavailable.';
            $this->logFailure('returns totals', $exception);
        }

        try {
            $service = $service ?? app(POSReturnService::class);
            $loadedReturns = $service->returnsList($request);
            if (is_iterable($loadedReturns)) {
                $returns = $loadedReturns;
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'Return records are temporarily unavailable.';
            $this->logFailure('returns list', $exception);
        }

        try {
            $service = $service ?? app(POSReturnService::class);
            $loadedSales = $service->recentSales();
            $sales = $this->toCollection($loadedSales);
        } catch (\Throwable $exception) {
            $warnings[] = 'Recent sales are temporarily unavailable.';
            $this->logFailure('returns recent sales', $exception);
        }

        return compact('stats', 'returns', 'sales', 'warnings');
    }

    public function shiftsPage(Request $request): array
    {
        $dashboard = [
            'open_shifts' => 0,
            'closed_today' => 0,
            'cash_in_today' => 0,
            'cash_out_today' => 0,
        ];
        $shifts = $this->emptyPaginator($request, 25);
        $warnings = [];

        try {
            $service = app(POSShiftService::class);
            $loadedShifts = $service->list();
            if (is_iterable($loadedShifts)) {
                $shifts = $loadedShifts;
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'Shift records are temporarily unavailable.';
            $this->logFailure('shifts list', $exception);
        }

        try {
            $service = $service ?? app(POSShiftService::class);
            $loadedDashboard = $service->dashboard();
            if (is_array($loadedDashboard)) {
                $dashboard = array_merge($dashboard, $loadedDashboard);
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'Shift totals are temporarily unavailable.';
            $this->logFailure('shifts dashboard', $exception);
        }

        return compact('dashboard', 'shifts', 'warnings');
    }

    private function emptyPaginator(Request $request, int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            $perPage,
            max(1, (int) $request->input('page', 1)),
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function toCollection($value): Collection
    {
        if ($value instanceof LengthAwarePaginator) {
            return collect($value->items());
        }

        if ($value instanceof Collection) {
            return $value;
        }

        return is_iterable($value) ? collect($value) : collect();
    }

    private function logFailure(string $area, \Throwable $exception): void
    {
        try {
            Log::error('POS page recovery: ' . $area . ' failed', [
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        } catch (\Throwable $ignored) {
            // Rendering the page must never fail because logging is unavailable.
        }
    }
}

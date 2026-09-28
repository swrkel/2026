<?php

namespace Modules\RiceMill\Services;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared Rice Mill list/report filtering rules.
 *
 * One small service keeps page-size, global-search and the ERP-standard date
 * range behaviour consistent on every Rice Mill data page without copying the
 * same request parsing into each controller.
 */
class StandardListService
{
    private array $stateCache = [];

    public function perPage(Request $request, int $default = 25): int
    {
        $value = strtolower(trim((string) $request->input('per_page', $default)));
        if ($value === 'all') {
            // "All" is intentionally on-demand. A generous hard ceiling avoids
            // an accidental unbounded browser response on very large tenants.
            return 10000;
        }

        $allowed = [10, 25, 50, 100, 250, 500];
        $number = (int) $value;
        return in_array($number, $allowed, true) ? $number : $default;
    }

    public function requestedPageSize(Request $request, int $default = 25): string
    {
        $value = strtolower(trim((string) $request->input('per_page', $default)));
        if ($value === 'all') {
            return 'all';
        }
        return (string) $this->perPage($request, $default);
    }

    public function searchTerm(Request $request): string
    {
        return trim((string) $request->input('q', ''));
    }

    /**
     * @param EloquentBuilder|QueryBuilder $query
     * @param string[] $columns
     */
    public function applySearch($query, Request $request, array $columns): void
    {
        $term = $this->searchTerm($request);
        if ($term === '' || ! $columns) {
            return;
        }

        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term) . '%';
        $query->where(function ($where) use ($columns, $like) {
            foreach (array_values($columns) as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $where->{$method}($column, 'like', $like);
            }
        });
    }

    /**
     * Add a contact-name branch to a global search without introducing N+1
     * lookups. Useful for pages whose table shows Supplier/Customer names.
     */
    public function applyContactSearch($query, Request $request, int $businessId, string $foreignKey, array $baseColumns): void
    {
        $term = $this->searchTerm($request);
        if ($term === '') {
            return;
        }

        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term) . '%';
        $query->where(function ($where) use ($baseColumns, $like, $businessId, $foreignKey) {
            foreach (array_values($baseColumns) as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $where->{$method}($column, 'like', $like);
            }

            if (Schema::hasTable('contacts')) {
                $where->orWhereIn($foreignKey, function ($sub) use ($businessId, $like) {
                    $sub->from('contacts')->select('id')->where('business_id', $businessId)->where('name', 'like', $like);
                });
            }
        });
    }

    /**
     * Resolve the same date presets used by the ERP system-standard
     * DateRangePicker. `from`/`to` remain ISO dates internally so every
     * Rice Mill query is unambiguous while the browser can display the host
     * standard MM/DD/YYYY - MM/DD/YYYY control.
     *
     * @return array{range:string,from:string,to:string,fy_start_month:int}
     */
    public function dateState(Request $request, int $businessId): array
    {
        $key = $businessId . '|' . md5(json_encode($request->only(['range', 'from', 'to', 'date_range'])));
        if (isset($this->stateCache[$key])) {
            return $this->stateCache[$key];
        }

        $range = strtolower(trim((string) $request->input('range', 'this_year')));
        $allowed = [
            'today', 'yesterday', 'last_7_days', 'last_30_days',
            'this_month', 'last_month', 'this_month_last_year',
            'this_year', 'last_year', 'this_fy', 'last_fy', 'custom', 'all',
        ];
        if (! in_array($range, $allowed, true)) {
            $range = 'this_year';
        }

        $fyStart = $this->fiscalStartMonth($businessId);
        $today = today();
        $from = '';
        $to = '';

        switch ($range) {
            case 'all':
                break;
            case 'today':
                $from = $today->toDateString();
                $to = $today->toDateString();
                break;
            case 'yesterday':
                $from = $today->copy()->subDay()->toDateString();
                $to = $from;
                break;
            case 'last_7_days':
                $from = $today->copy()->subDays(6)->toDateString();
                $to = $today->toDateString();
                break;
            case 'last_30_days':
                $from = $today->copy()->subDays(29)->toDateString();
                $to = $today->toDateString();
                break;
            case 'this_month':
                $from = $today->copy()->startOfMonth()->toDateString();
                $to = $today->copy()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $lastMonth = $today->copy()->subMonthNoOverflow();
                $from = $lastMonth->copy()->startOfMonth()->toDateString();
                $to = $lastMonth->copy()->endOfMonth()->toDateString();
                break;
            case 'this_month_last_year':
                $lastYearMonth = $today->copy()->subYear();
                $from = $lastYearMonth->copy()->startOfMonth()->toDateString();
                $to = $lastYearMonth->copy()->endOfMonth()->toDateString();
                break;
            case 'last_year':
                $from = $today->copy()->subYear()->startOfYear()->toDateString();
                $to = $today->copy()->subYear()->endOfYear()->toDateString();
                break;
            case 'this_fy':
                [$from, $to] = $this->fiscalDates($today, $fyStart, 0);
                break;
            case 'last_fy':
                [$from, $to] = $this->fiscalDates($today, $fyStart, -1);
                break;
            case 'custom':
                $from = $this->safeDate((string) $request->input('from', ''));
                $to = $this->safeDate((string) $request->input('to', ''));
                if ($from !== '' && $to !== '' && $from > $to) {
                    [$from, $to] = [$to, $from];
                }
                break;
            case 'this_year':
            default:
                $range = 'this_year';
                $from = $today->copy()->startOfYear()->toDateString();
                $to = $today->copy()->endOfYear()->toDateString();
                break;
        }

        return $this->stateCache[$key] = [
            'range' => $range,
            'from' => $from,
            'to' => $to,
            'fy_start_month' => $fyStart,
        ];
    }

    /**
     * @param EloquentBuilder|QueryBuilder $query
     */
    public function applyDate($query, Request $request, int $businessId, string $column, bool $dateTime = false): void
    {
        $state = $this->dateState($request, $businessId);
        if ($state['range'] === 'all') {
            return;
        }
        if ($state['from'] === '' && $state['to'] === '') {
            return;
        }

        if ($dateTime) {
            if ($state['from'] !== '') {
                $query->where($column, '>=', Carbon::parse($state['from'])->startOfDay());
            }
            if ($state['to'] !== '') {
                $query->where($column, '<=', Carbon::parse($state['to'])->endOfDay());
            }
            return;
        }

        if ($state['from'] !== '') {
            $query->whereDate($column, '>=', $state['from']);
        }
        if ($state['to'] !== '') {
            $query->whereDate($column, '<=', $state['to']);
        }
    }

    private function fiscalStartMonth(int $businessId): int
    {
        try {
            $value = (int) DB::table('business')->where('id', $businessId)->value('fy_start_month');
            if ($value >= 1 && $value <= 12) {
                return $value;
            }
        } catch (\Throwable $e) {
            // Fall through to January if an older tenant lacks the field.
        }
        return 1;
    }

    private function fiscalDates(Carbon $today, int $startMonth, int $yearOffset): array
    {
        $startYear = $today->month >= $startMonth ? $today->year : $today->year - 1;
        $startYear += $yearOffset;
        $from = Carbon::create($startYear, $startMonth, 1)->startOfDay();
        $to = $from->copy()->addYear()->subDay()->endOfDay();
        return [$from->toDateString(), $to->toDateString()];
    }

    private function safeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return '';
        }
    }
}

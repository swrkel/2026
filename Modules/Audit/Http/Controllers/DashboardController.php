<?php
namespace Modules\Audit\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Audit\Models\AuditFinding;
use Modules\Audit\Models\AuditRun;
use Modules\Audit\Services\DateRangeService;
use Modules\Audit\Services\FilterOptionService;

class DashboardController extends Controller
{
    public function index(DateRangeService $dates, FilterOptionService $filters)
    {
        [$from, $to] = $dates->resolve(request('preset'), request('from'), request('to'));

        $base = AuditFinding::query()->whereBetween('last_seen_at', [$from, $to]);
        if (request('business_id')) {
            $base->where('business_id', request('business_id'));
        }
        if (request('location_id')) {
            $base->where('location_id', request('location_id'));
        }
        if ($search = trim((string) request('search'))) {
            $base->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('finding_no', 'like', $like)
                    ->orWhere('rule_code', 'like', $like)
                    ->orWhere('module', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhere('message', 'like', $like);
            });
        }

        // The dashboard is a CURRENT EXCEPTION view. Resolved, ignored and known
        // false-positive history remains available on Findings/Reports, but must not
        // make a clean latest run look as though the ERP currently has critical errors.
        $active = (clone $base)->whereNotIn('status', ['resolved', 'false_positive', 'ignored']);

        $runsQuery = AuditRun::query()->whereBetween('started_at', [$from, $to]);
        if (request('business_id')) {
            $runsQuery->where('business_id', request('business_id'));
        }
        if (request('location_id')) {
            $runsQuery->where('location_id', request('location_id'));
        }

        $latestRun = (clone $runsQuery)->where('status', 'completed')->latest('id')->first();
        $latestRunFindings = (int) data_get($latestRun ? $latestRun->summary : [], 'findings', 0);

        $summary = [
            'total' => (clone $active)->count(),
            'critical' => (clone $active)->where('severity', 'critical')->count(),
            'high' => (clone $active)->where('severity', 'high')->count(),
            'warning' => (clone $active)->where('severity', 'warning')->count(),
            'latest_run' => $latestRunFindings,
            'resolved' => (clone $base)->where('status', 'resolved')->count(),
        ];

        $byModule = (clone $active)
            ->selectRaw('module, COUNT(*) total')
            ->groupBy('module')
            ->orderByDesc('total')
            ->get();

        $recent = (clone $active)->latest('last_seen_at')->limit(10)->get();
        $runs = (clone $runsQuery)->latest('id')->limit(8)->get();

        return view('audit::dashboard', compact('summary', 'byModule', 'recent', 'runs', 'from', 'to', 'latestRun') + [
            'businesses' => $filters->businesses(),
            'locations' => $filters->locations(request('business_id') ? : (int) session('user.business_id')),
        ]);
    }
}

<?php

namespace Modules\ChurchManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ChurchManagement\Http\Controllers\Concerns\ChurchTenantContext;

/**
 * Church Management dashboard.
 *
 * Laid out to the ERP Dashboard Standard used by the POS module: a row of KPI
 * tiles, then panels. Every figure links to the list that produced it, so a
 * number on this page is a starting point rather than a dead end.
 */
class DashboardController extends Controller
{
    use ChurchTenantContext;

    public function index(Request $request)
    {
        $installed = $this->moduleInstalled();

        $counts = [
            'members'   => $this->safeCount('members', fn ($q) => $q->where('membership_status', 'member')),
            'families'  => $this->safeCount('families'),
            'visitors'  => $this->safeCount('members', fn ($q) => $q->where('membership_status', 'visitor')),
            'inactive'  => $this->safeCount('members', fn ($q) => $q->where('membership_status', 'inactive')),

            // Phase 2. safeCount returns 0 rather than throwing where the phase
            // 2 tables are not installed, so the dashboard works on a tenant
            // running phase 1 only.
            'events'    => $this->safeCount('events', fn ($q) => $q
                ->whereDate('event_date', '>=', now()->format('Y-m-d'))
                ->whereIn('status', ['planned', 'confirmed'])),
        ];

        $donationsThisMonth = 0.0;
        $upcomingEvents = collect();

        if ($this->tableExists('donations')) {
            $donationsThisMonth = (float) $this->scopedQuery('donations')
                ->whereBetween('donation_date', [
                    now()->startOfMonth()->format('Y-m-d'),
                    now()->endOfMonth()->format('Y-m-d'),
                ])->sum('amount');
        }

        if ($this->tableExists('events')) {
            $upcomingEvents = $this->scopedQuery('events')
                ->whereDate('event_date', '>=', now()->format('Y-m-d'))
                ->whereIn('status', ['planned', 'confirmed'])
                ->orderBy('event_date')
                ->limit(6)
                ->get();
        }

        $recentMembers = collect();
        $birthdays = collect();

        if ($installed) {
            $recentMembers = $this->scopedQuery('members')
                ->orderBy('id', 'desc')
                ->limit(8)
                ->get();

            /*
             | Birthdays this month.
             |
             | Matched on the month alone, ignoring the year, which is what a
             | birthday means. Rows with no date_of_birth are excluded rather
             | than treated as January.
             */
            $birthdays = $this->scopedQuery('members')
                ->whereNotNull('date_of_birth')
                ->whereRaw('MONTH(date_of_birth) = ?', [(int) now()->format('n')])
                ->orderByRaw('DAY(date_of_birth) ASC')
                ->limit(8)
                ->get();
        }

        return view('churchmanagement::dashboard.index', compact(
            'installed',
            'counts',
            'recentMembers',
            'birthdays',
            'donationsThisMonth',
            'upcomingEvents'
        ));
    }
}

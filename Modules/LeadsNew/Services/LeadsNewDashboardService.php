<?php

namespace Modules\LeadsNew\Services;

use Modules\LeadsNew\Models\LeadsNewFollowup;
use Modules\LeadsNew\Models\LeadsNewLead;

class LeadsNewDashboardService
{
    protected $tableGuard;

    public function __construct(LeadsNewTableGuard $tableGuard)
    {
        $this->tableGuard = $tableGuard;
    }

    protected function businessId()
    {
        return session('business.id') ?? request()->session()->get('user.business_id') ?? request()->session()->get('business_id');
    }

    protected function scopedLeads()
    {
        $query = LeadsNewLead::query();

        if ($this->businessId() && $this->tableGuard->hasColumn('leads_new_leads', 'business_id')) {
            $businessId = $this->businessId();
            $query->where(function ($q) use ($businessId) {
                $q->where('business_id', $businessId)->orWhereNull('business_id');
            });
        }

        return $query;
    }

    public function summary(): array
    {
        $missingTables = $this->tableGuard->missingCoreTables();

        if ($this->tableGuard->exists('leads_new_leads') === false) {
            return [
                'totalLeads' => 0,
                'todayLeads' => 0,
                'monthlyLeads' => 0,
                'convertedLeads' => 0,
                'lostLeads' => 0,
                'dueFollowups' => 0,
                'recentLeads' => collect(),
                'pipeline' => [
                    'new' => 0,
                    'in_progress' => 0,
                    'converted' => 0,
                    'lost' => 0,
                ],
                'missingTables' => $missingTables,
            ];
        }

        $leadBase = $this->scopedLeads();

        $followups = null;
        if ($this->tableGuard->exists('leads_new_followups')) {
            $followups = LeadsNewFollowup::query();
            if ($this->businessId() && $this->tableGuard->hasColumn('leads_new_followups', 'business_id')) {
                $businessId = $this->businessId();
                $followups->where(function ($q) use ($businessId) {
                    $q->where('business_id', $businessId)->orWhereNull('business_id');
                });
            }
        }

        $newLeads = (clone $leadBase)->whereIn('status', ['New', 'new'])->count();
        $inProgressLeads = (clone $leadBase)->whereIn('status', [
            'In Progress',
            'in progress',
            'in_progress',
            'Follow Up',
            'follow up',
            'follow_up',
            'Contacted',
            'contacted',
            'Qualified',
            'qualified',
        ])->count();
        $convertedLeads = (clone $leadBase)->whereIn('status', ['Converted', 'converted'])->count();
        $lostLeads = (clone $leadBase)->whereIn('status', ['Lost', 'lost'])->count();

        return [
            'totalLeads' => (clone $leadBase)->count(),
            'todayLeads' => (clone $leadBase)->whereDate('created_at', today())->count(),
            'monthlyLeads' => (clone $leadBase)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'convertedLeads' => $convertedLeads,
            'lostLeads' => $lostLeads,
            'dueFollowups' => $followups
                ? $followups->whereNull('completed_at')->where('followup_at', '<=', now())->count()
                : 0,
            'recentLeads' => (clone $leadBase)->latest('id')->limit(8)->get(),
            'pipeline' => [
                'new' => $newLeads,
                'in_progress' => $inProgressLeads,
                'converted' => $convertedLeads,
                'lost' => $lostLeads,
            ],
            'missingTables' => $missingTables,
        ];
    }
}

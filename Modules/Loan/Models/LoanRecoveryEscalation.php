<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRecoveryEscalation extends Model
{
    protected $table =
        'loan_recovery_escalations';

    protected $guarded = [];

    /*
    |--------------------------------------------------------------------------
    | Loan
    |--------------------------------------------------------------------------
    */

    public function loan()
    {
        return $this->belongsTo(
            Loan::class,
            'loan_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Customer
    |--------------------------------------------------------------------------
    */

    public function customer()
    {
        return $this->belongsTo(
            \App\Contact::class,
            'customer_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Assigned Officer
    |--------------------------------------------------------------------------
    */

    public function assignedOfficer()
    {
        return $this->belongsTo(
            \App\User::class,
            'assigned_to'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Created By
    |--------------------------------------------------------------------------
    */

    public function createdBy()
    {
        return $this->belongsTo(
            \App\User::class,
            'created_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Open Escalations
    |--------------------------------------------------------------------------
    */

    public function scopeOpen($query)
    {
        return $query->where(
            'status',
            'open'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Critical Escalations
    |--------------------------------------------------------------------------
    */

    public function scopeCritical($query)
    {
        return $query->where(
            'priority_level',
            'critical'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope High Priority Escalations
    |--------------------------------------------------------------------------
    */

    public function scopeHighPriority($query)
    {
        return $query->whereIn(
            'priority_level',
            [
                'high',
                'critical'
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Unresolved Escalations
    |--------------------------------------------------------------------------
    */

    public function scopeUnresolved($query)
    {
        return $query->whereNull(
            'resolution_date'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Aged Escalations
    |--------------------------------------------------------------------------
    */

    public function scopeAged($query, $days = 30)
    {
        return $query->whereDate(
            'escalation_date',
            '<=',
            now()->subDays($days)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Compliance Risk Escalations
    |--------------------------------------------------------------------------
    */

    public function scopeComplianceRisk($query)
    {
        return $query->whereIn(
            'priority_level',
            [
                'high',
                'critical'
            ]
        )->whereNull(
            'resolution_date'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Autonomous Priority Ranking
    |--------------------------------------------------------------------------
    */

    public function scopePriorityRanking($query)
    {
        return $query
            ->orderByDesc('priority_level')
            ->orderBy('escalation_date');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Officer Workload Ranking
    |--------------------------------------------------------------------------
    */

    public function scopeOfficerWorkloadRanking($query)
    {
        return $query
            ->selectRaw(
                'assigned_to, COUNT(*) as workload_count'
            )
            ->groupBy('assigned_to')
            ->orderBy('workload_count');
    }

    /*
    |--------------------------------------------------------------------------
    | Auto Risk Category
    |--------------------------------------------------------------------------
    */

    public function getAutoRiskCategoryAttribute()
    {
        $days_open =
            now()->diffInDays(
                $this->escalation_date
            );

        if (
            $this->priority_level == 'critical'
            || $days_open >= 60
        ) {

            return 'critical_risk';
        }

        if (
            $this->priority_level == 'high'
            || $days_open >= 30
        ) {

            return 'high_risk';
        }

        return 'standard_risk';
    }

    /*
    |--------------------------------------------------------------------------
    | Escalation Aging Days
    |--------------------------------------------------------------------------
    */

    public function getEscalationAgeAttribute()
    {
        return now()->diffInDays(
            $this->escalation_date
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Autonomous Recovery Score
    |--------------------------------------------------------------------------
    */

    public function getRecoveryScoreAttribute()
    {
        $score = 0;

        /*
        |--------------------------------------------------------------------------
        | Priority Weight
        |--------------------------------------------------------------------------
        */

        if (
            $this->priority_level == 'critical'
        ) {

            $score += 50;

        } elseif (
            $this->priority_level == 'high'
        ) {

            $score += 35;

        } elseif (
            $this->priority_level == 'medium'
        ) {

            $score += 20;

        } else {

            $score += 10;
        }

        /*
        |--------------------------------------------------------------------------
        | Aging Weight
        |--------------------------------------------------------------------------
        */

        $days_open =
            now()->diffInDays(
                $this->escalation_date
            );

        if ($days_open >= 90) {

            $score += 40;

        } elseif ($days_open >= 60) {

            $score += 30;

        } elseif ($days_open >= 30) {

            $score += 20;

        } else {

            $score += 10;
        }

        /*
        |--------------------------------------------------------------------------
        | Status Weight
        |--------------------------------------------------------------------------
        */

        if (
            $this->status == 'open'
        ) {

            $score += 25;

        } elseif (
            $this->status == 'in_progress'
        ) {

            $score += 10;
        }

        return $score;
    }

    /*
    |--------------------------------------------------------------------------
    | Autonomous Urgency Level
    |--------------------------------------------------------------------------
    */

    public function getUrgencyLevelAttribute()
    {
        $score =
            $this->recovery_score;

        if ($score >= 100) {

            return 'critical';

        } elseif ($score >= 70) {

            return 'high';

        } elseif ($score >= 40) {

            return 'medium';
        }

        return 'low';
    }

    /*
    |--------------------------------------------------------------------------
    | Autonomous Escalation Color
    |--------------------------------------------------------------------------
    */

    public function getUrgencyColorAttribute()
    {
        switch (
            $this->urgency_level
        ) {

            case 'critical':
                return 'danger';

            case 'high':
                return 'warning';

            case 'medium':
                return 'primary';

            default:
                return 'success';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Intelligent Assignment Score
    |--------------------------------------------------------------------------
    */

    public function getAssignmentScoreAttribute()
    {
        $score = 100;

        /*
        |--------------------------------------------------------------------------
        | Reduce score for critical urgency
        |--------------------------------------------------------------------------
        */

        if (
            $this->urgency_level == 'critical'
        ) {

            $score -= 40;

        } elseif (
            $this->urgency_level == 'high'
        ) {

            $score -= 25;

        } elseif (
            $this->urgency_level == 'medium'
        ) {

            $score -= 10;
        }

        /*
        |--------------------------------------------------------------------------
        | Reduce score for aging
        |--------------------------------------------------------------------------
        */

        if (
            $this->escalation_age >= 90
        ) {

            $score -= 30;

        } elseif (
            $this->escalation_age >= 60
        ) {

            $score -= 20;

        } elseif (
            $this->escalation_age >= 30
        ) {

            $score -= 10;
        }

        return max($score, 0);
    }

    /*
    |--------------------------------------------------------------------------
    | Workforce Optimization Category
    |--------------------------------------------------------------------------
    */

    public function getWorkforceCategoryAttribute()
    {
        if (
            $this->assignment_score <= 30
        ) {

            return 'immediate_attention';
        }

        if (
            $this->assignment_score <= 60
        ) {

            return 'priority_assignment';
        }

        return 'standard_assignment';
    }

    /*
    |--------------------------------------------------------------------------
    | Workforce Optimization Color
    |--------------------------------------------------------------------------
    */

    public function getWorkforceColorAttribute()
    {
        switch (
            $this->workforce_category
        ) {

            case 'immediate_attention':
                return 'danger';

            case 'priority_assignment':
                return 'warning';

            default:
                return 'success';
        }
    }
}
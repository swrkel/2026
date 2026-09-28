<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\LoanRepaymentSchedule;
use Modules\Loan\Models\LoanCollectionNote;
use Modules\Loan\Models\LoanRecoveryEscalation;
use Modules\Loan\Models\LoanRecoverySettlement;

use Modules\Loan\Services\RecoveryMonitoringService;

use DB;

class CollectionsDashboardController extends Controller
{
    /**
     * Collections dashboard.
     */
    public function index(
        Request $request,
        RecoveryMonitoringService $monitoringService
    ) {
        $business_id = $request->session()
            ->get('user.business_id');

        /*
        |--------------------------------------------------------------------------
        | Overdue schedules
        |--------------------------------------------------------------------------
        */

        $overdue_schedules =
            LoanRepaymentSchedule::with([
                    'loan',
                    'loan.customer'
                ])
                ->where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'is_overdue',
                    1
                )
                ->orderBy(
                    'days_overdue',
                    'desc'
                )
                ->paginate(20);

        /*
        |--------------------------------------------------------------------------
        | Dashboard metrics
        |--------------------------------------------------------------------------
        */

        $total_overdue_accounts =
            LoanRepaymentSchedule::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'is_overdue',
                    1
                )
                ->count();

        $total_overdue_amount =
            LoanRepaymentSchedule::where(
                    'business_id',
                    $business_id
                )
                ->sum('overdue_amount');

        /*
        |--------------------------------------------------------------------------
        | Promise To Pay Monitoring
        |--------------------------------------------------------------------------
        */

        $promises_due_today =
            LoanCollectionNote::where(
                    'business_id',
                    $business_id
                )
                ->whereDate(
                    'promise_to_pay_date',
                    now()->toDateString()
                )
                ->count();

        $broken_promises =
            LoanCollectionNote::where(
                    'business_id',
                    $business_id
                )
                ->whereDate(
                    'promise_to_pay_date',
                    '<',
                    now()->toDateString()
                )
                ->count();

        $follow_ups_due =
            LoanCollectionNote::where(
                    'business_id',
                    $business_id
                )
                ->whereDate(
                    'follow_up_date',
                    now()->toDateString()
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Recent collection activities
        |--------------------------------------------------------------------------
        */

        $recent_collection_notes =
            LoanCollectionNote::with([
                    'loan',
                    'customer',
                    'createdBy'
                ])
                ->where(
                    'business_id',
                    $business_id
                )
                ->latest()
                ->limit(10)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Officer collection analytics
        |--------------------------------------------------------------------------
        */

        $top_collection_officers =
            LoanCollectionNote::select(
                    'created_by',
                    DB::raw('COUNT(*) as total_activities')
                )
                ->with('createdBy')
                ->where(
                    'business_id',
                    $business_id
                )
                ->groupBy('created_by')
                ->orderByDesc('total_activities')
                ->limit(5)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Collection activity counters
        |--------------------------------------------------------------------------
        */

        $phone_calls_count =
            LoanCollectionNote::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'collection_type',
                    'phone_call'
                )
                ->count();

        $sms_count =
            LoanCollectionNote::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'collection_type',
                    'sms'
                )
                ->count();

        $field_visits_count =
            LoanCollectionNote::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'collection_type',
                    'field_visit'
                )
                ->count();

        $promise_to_pay_count =
            LoanCollectionNote::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'collection_type',
                    'promise_to_pay'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Recovery prioritization analytics
        |--------------------------------------------------------------------------
        */

        $critical_accounts =
            LoanRepaymentSchedule::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'is_overdue',
                    1
                )
                ->where(
                    'days_overdue',
                    '>=',
                    90
                )
                ->count();

        $high_risk_accounts =
            LoanRepaymentSchedule::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'is_overdue',
                    1
                )
                ->where(
                    'days_overdue',
                    '>=',
                    30
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Recovery Monitoring Service Intelligence
        |--------------------------------------------------------------------------
        */

        $critical_risk_escalations =
            $monitoringService
                ->getCriticalRiskEscalations(
                    $business_id
                );

        $aged_risk_escalations =
            $monitoringService
                ->getAgedEscalations(
                    $business_id,
                    30
                );

        $unresolved_risk_escalations =
            $monitoringService
                ->getUnresolvedEscalations(
                    $business_id
                );

        $high_priority_escalations =
            $monitoringService
                ->getHighPriorityEscalations(
                    $business_id
                );

        $governance_alerts =
            $monitoringService
                ->getGovernanceAlerts(
                    $business_id
                );

        /*
        |--------------------------------------------------------------------------
        | Autonomous Recovery Prioritization
        |--------------------------------------------------------------------------
        */

        $priority_ranked_escalations =
            LoanRecoveryEscalation::with([
                    'loan',
                    'customer',
                    'assignedOfficer'
                ])
                ->where(
                    'business_id',
                    $business_id
                )
                ->priorityRanking()
                ->limit(10)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Autonomous urgency intelligence
        |--------------------------------------------------------------------------
        */

        $critical_urgency_cases =
            $priority_ranked_escalations
                ->filter(function ($item) {

                    return
                        $item->urgency_level
                        == 'critical';
                })
                ->count();

        $high_urgency_cases =
            $priority_ranked_escalations
                ->filter(function ($item) {

                    return
                        $item->urgency_level
                        == 'high';
                })
                ->count();

        $medium_urgency_cases =
            $priority_ranked_escalations
                ->filter(function ($item) {

                    return
                        $item->urgency_level
                        == 'medium';
                })
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Autonomous recovery scoring analytics
        |--------------------------------------------------------------------------
        */

        $average_recovery_score = 0;

        if (
            $priority_ranked_escalations->count() > 0
        ) {

            $average_recovery_score =
                $priority_ranked_escalations
                    ->avg('recovery_score');
        }

        /*
        |--------------------------------------------------------------------------
        | Automated monitoring counters
        |--------------------------------------------------------------------------
        */

        $critical_risk_alert_count =
            $critical_risk_escalations->count();

        $aged_risk_alert_count =
            $aged_risk_escalations->count();

        $unresolved_alert_count =
            $unresolved_risk_escalations->count();

        $high_priority_alert_count =
            $high_priority_escalations->count();

        $governance_alert_count =
            $governance_alerts->count();

        /*
        |--------------------------------------------------------------------------
        | Priority recovery queue
        |--------------------------------------------------------------------------
        */

        $priority_recovery_queue =
            LoanRepaymentSchedule::with([
                    'loan',
                    'loan.customer'
                ])
                ->where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'is_overdue',
                    1
                )
                ->orderByDesc(
                    'overdue_amount'
                )
                ->orderByDesc(
                    'days_overdue'
                )
                ->limit(10)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Severe delinquency exposure
        |--------------------------------------------------------------------------
        */

        $severe_delinquency_amount =
            LoanRepaymentSchedule::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'days_overdue',
                    '>=',
                    90
                )
                ->sum('overdue_amount');

        /*
        |--------------------------------------------------------------------------
        | Recovery escalation monitoring
        |--------------------------------------------------------------------------
        */

        $open_escalations =
            LoanRecoveryEscalation::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'status',
                    'open'
                )
                ->count();

        $critical_escalations =
            LoanRecoveryEscalation::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'priority_level',
                    'critical'
                )
                ->count();

        $unresolved_escalations =
            LoanRecoveryEscalation::where(
                    'business_id',
                    $business_id
                )
                ->whereNull(
                    'resolution_date'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Escalation aging analysis
        |--------------------------------------------------------------------------
        */

        $aged_escalations =
            LoanRecoveryEscalation::where(
                    'business_id',
                    $business_id
                )
                ->whereDate(
                    'escalation_date',
                    '<=',
                    now()->subDays(30)
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Escalation aging buckets
        |--------------------------------------------------------------------------
        */

        $escalations_0_30_days =
            LoanRecoveryEscalation::where(
                    'business_id',
                    $business_id
                )
                ->whereBetween(
                    DB::raw('DATEDIFF(NOW(), escalation_date)'),
                    [0, 30]
                )
                ->count();

        $escalations_31_60_days =
            LoanRecoveryEscalation::where(
                    'business_id',
                    $business_id
                )
                ->whereBetween(
                    DB::raw('DATEDIFF(NOW(), escalation_date)'),
                    [31, 60]
                )
                ->count();

        $escalations_over_60_days =
            LoanRecoveryEscalation::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    DB::raw('DATEDIFF(NOW(), escalation_date)'),
                    '>',
                    60
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Escalation workload
        |--------------------------------------------------------------------------
        */

        $top_escalation_officers =
            LoanRecoveryEscalation::select(
                    'assigned_to',
                    DB::raw('COUNT(*) as total_cases')
                )
                ->with('assignedOfficer')
                ->where(
                    'business_id',
                    $business_id
                )
                ->groupBy('assigned_to')
                ->orderByDesc('total_cases')
                ->limit(5)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Recent escalations
        |--------------------------------------------------------------------------
        */

        $recent_escalations =
            LoanRecoveryEscalation::with([
                    'loan',
                    'customer',
                    'assignedOfficer'
                ])
                ->where(
                    'business_id',
                    $business_id
                )
                ->latest()
                ->limit(10)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Settlement analytics
        |--------------------------------------------------------------------------
        */

        $pending_settlements =
            LoanRecoverySettlement::where(
                    'business_id',
                    $business_id
                )
                ->pending()
                ->count();

        $approved_settlements =
            LoanRecoverySettlement::where(
                    'business_id',
                    $business_id
                )
                ->approved()
                ->count();

        $rejected_settlements =
            LoanRecoverySettlement::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'settlement_status',
                    'rejected'
                )
                ->count();

        $total_settlement_discounts =
            LoanRecoverySettlement::where(
                    'business_id',
                    $business_id
                )
                ->sum('discount_amount');

        /*
        |--------------------------------------------------------------------------
        | Settlement performance intelligence
        |--------------------------------------------------------------------------
        */

        $total_approved_settlement_value =
            LoanRecoverySettlement::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'settlement_status',
                    'approved'
                )
                ->sum('settlement_amount');

        $total_rejected_settlement_value =
            LoanRecoverySettlement::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'settlement_status',
                    'rejected'
                )
                ->sum('settlement_amount');

        $unresolved_settlement_exposure =
            LoanRecoverySettlement::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'settlement_status',
                    'pending'
                )
                ->sum('settlement_amount');

        $total_settlement_requests =
            LoanRecoverySettlement::where(
                    'business_id',
                    $business_id
                )
                ->count();

        $settlement_approval_rate = 0;

        if ($total_settlement_requests > 0) {

            $settlement_approval_rate =
                (
                    $approved_settlements
                    / $total_settlement_requests
                ) * 100;
        }

        $average_discount_percentage = 0;

        $approved_discount_records =
            LoanRecoverySettlement::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'settlement_status',
                    'approved'
                )
                ->get();

        if ($approved_discount_records->count() > 0) {

            $average_discount_percentage =
                $approved_discount_records
                    ->avg(function ($settlement) {

                        if (
                            $settlement->original_outstanding > 0
                        ) {

                            return (
                                $settlement->discount_amount
                                /
                                $settlement->original_outstanding
                            ) * 100;
                        }

                        return 0;
                    });
        }

        /*
        |--------------------------------------------------------------------------
        | Recovery efficiency metrics
        |--------------------------------------------------------------------------
        */

        $recovery_efficiency_rate = 0;

        if ($total_overdue_amount > 0) {

            $recovery_efficiency_rate =
                (
                    $total_approved_settlement_value
                    / $total_overdue_amount
                ) * 100;
        }

        /*
        |--------------------------------------------------------------------------
        | Governance exception monitoring
        |--------------------------------------------------------------------------
        */

        $governance_exceptions =
            LoanRecoverySettlement::where(
                    'business_id',
                    $business_id
                )
                ->where(
                    'discount_amount',
                    '>',
                    DB::raw(
                        'original_outstanding * 0.50'
                    )
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Recent settlements
        |--------------------------------------------------------------------------
        */

        $recent_settlements =
            LoanRecoverySettlement::with([
                    'loan',
                    'customer',
                    'approvedBy',
                    'createdBy'
                ])
                ->where(
                    'business_id',
                    $business_id
                )
                ->latest()
                ->limit(10)
                ->get();

        return view(
            'loan::collections.dashboard',
            compact(
                'overdue_schedules',
                'total_overdue_accounts',
                'total_overdue_amount',
                'promises_due_today',
                'broken_promises',
                'follow_ups_due',
                'recent_collection_notes',
                'top_collection_officers',
                'phone_calls_count',
                'sms_count',
                'field_visits_count',
                'promise_to_pay_count',
                'critical_accounts',
                'high_risk_accounts',
                'critical_risk_escalations',
                'aged_risk_escalations',
                'unresolved_risk_escalations',
                'high_priority_escalations',
                'governance_alerts',
                'priority_ranked_escalations',
                'critical_urgency_cases',
                'high_urgency_cases',
                'medium_urgency_cases',
                'average_recovery_score',
                'critical_risk_alert_count',
                'aged_risk_alert_count',
                'unresolved_alert_count',
                'high_priority_alert_count',
                'governance_alert_count',
                'priority_recovery_queue',
                'severe_delinquency_amount',
                'open_escalations',
                'critical_escalations',
                'unresolved_escalations',
                'aged_escalations',
                'escalations_0_30_days',
                'escalations_31_60_days',
                'escalations_over_60_days',
                'top_escalation_officers',
                'recent_escalations',
                'pending_settlements',
                'approved_settlements',
                'rejected_settlements',
                'total_settlement_discounts',
                'total_approved_settlement_value',
                'total_rejected_settlement_value',
                'unresolved_settlement_exposure',
                'settlement_approval_rate',
                'average_discount_percentage',
                'recovery_efficiency_rate',
                'governance_exceptions',
                'recent_settlements'
            )
        );
    }
}
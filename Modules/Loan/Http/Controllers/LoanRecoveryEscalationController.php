<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

use App\User;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanRecoveryEscalation;
use Modules\Loan\Models\LoanCollectionNote;
use Modules\Loan\Models\LoanNotification;
use Modules\Loan\Models\LoanAuditLog;
use Modules\Loan\Models\LoanRecovery;

class LoanRecoveryEscalationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Enterprise Multi-Branch Escalation Dashboard
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $business_id = request()
            ->session()
            ->get('user.business_id');

        $user_id = request()
            ->session()
            ->get('user.id');

        /*
        |--------------------------------------------------------------------------
        | Logged User
        |--------------------------------------------------------------------------
        */

        $user = User::find($user_id);

        /*
        |--------------------------------------------------------------------------
        | Multi Branch Access
        |--------------------------------------------------------------------------
        */

        $allowedLocations = [];

        if (!empty($user->location_permissions)) {

            $decodedLocations =
                json_decode(
                    $user->location_permissions,
                    true
                );

            if (
                is_array($decodedLocations)
            ) {

                $allowedLocations =
                    $decodedLocations;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Head Office Logic
        |--------------------------------------------------------------------------
        */

        $isHeadOfficeUser = false;

        if (empty($allowedLocations)) {

            $isHeadOfficeUser = true;
        }

        /*
        |--------------------------------------------------------------------------
        | Base Escalation Query
        |--------------------------------------------------------------------------
        */

        $escalationBaseQuery =
            LoanRecoveryEscalation::leftJoin(
                    'loan_applications',
                    'loan_recovery_escalations.loan_id',
                    '=',
                    'loan_applications.id'
                )
                ->where(
                    'loan_recovery_escalations.business_id',
                    $business_id
                );

        /*
        |--------------------------------------------------------------------------
        | Branch Restriction
        |--------------------------------------------------------------------------
        */

        if (!$isHeadOfficeUser) {

            $escalationBaseQuery->whereIn(
                'loan_applications.location_id',
                $allowedLocations
            );
        }

        /*
        |--------------------------------------------------------------------------
        | KPI Metrics
        |--------------------------------------------------------------------------
        */

        $total_escalations =
            (clone $escalationBaseQuery)
                ->count();

        $open_escalations =
            (clone $escalationBaseQuery)
                ->where(
                    'loan_recovery_escalations.status',
                    'open'
                )
                ->count();

        $critical_escalations =
            (clone $escalationBaseQuery)
                ->where(
                    'loan_recovery_escalations.priority_level',
                    'critical'
                )
                ->count();

        $legal_escalations =
            (clone $escalationBaseQuery)
                ->where(
                    'loan_recovery_escalations.escalation_type',
                    'legal'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Escalation Listing
        |--------------------------------------------------------------------------
        */

        $escalations =
            LoanRecoveryEscalation::leftJoin(
                    'loan_applications',
                    'loan_recovery_escalations.loan_id',
                    '=',
                    'loan_applications.id'
                )
                ->leftJoin(
                    'contacts',
                    'loan_applications.customer_id',
                    '=',
                    'contacts.id'
                )
                ->leftJoin(
                    'business_locations',
                    'loan_applications.location_id',
                    '=',
                    'business_locations.id'
                )
                ->where(
                    'loan_recovery_escalations.business_id',
                    $business_id
                );

        /*
        |--------------------------------------------------------------------------
        | Branch Restriction
        |--------------------------------------------------------------------------
        */

        if (!$isHeadOfficeUser) {

            $escalations->whereIn(
                'loan_applications.location_id',
                $allowedLocations
            );
        }

        $escalations =
            $escalations
                ->select(

                    'loan_recovery_escalations.*',

                    'loan_applications.application_no',

                    'loan_applications.dpd',

                    'contacts.name as customer_name',

                    'contacts.mobile as customer_mobile',

                    'business_locations.name as branch_name'
                )
                ->orderByDesc(
                    'loan_recovery_escalations.id'
                )
                ->paginate(20);

        /*
        |--------------------------------------------------------------------------
        | Branch Escalation Summary
        |--------------------------------------------------------------------------
        */

        $branchEscalationSummary =
            LoanRecoveryEscalation::leftJoin(
                    'loan_applications',
                    'loan_recovery_escalations.loan_id',
                    '=',
                    'loan_applications.id'
                )
                ->leftJoin(
                    'business_locations',
                    'loan_applications.location_id',
                    '=',
                    'business_locations.id'
                )
                ->where(
                    'loan_recovery_escalations.business_id',
                    $business_id
                );

        if (!$isHeadOfficeUser) {

            $branchEscalationSummary->whereIn(
                'loan_applications.location_id',
                $allowedLocations
            );
        }

        $branchEscalationSummary =
            $branchEscalationSummary
                ->select(

                    'business_locations.name as branch_name',

                    DB::raw(
                        'COUNT(loan_recovery_escalations.id) as total_escalations'
                    ),

                    DB::raw(
                        'SUM(CASE WHEN loan_recovery_escalations.priority_level = "critical" THEN 1 ELSE 0 END) as critical_cases'
                    ),

                    DB::raw(
                        'SUM(CASE WHEN loan_recovery_escalations.escalation_type = "legal" THEN 1 ELSE 0 END) as legal_cases'
                    )
                )
                ->groupBy(
                    'business_locations.name'
                )
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Dashboard View
        |--------------------------------------------------------------------------
        */

        return view(
            'loan::loan_recovery_escalations.index',
            compact(

                'escalations',

                'total_escalations',

                'open_escalations',

                'critical_escalations',

                'legal_escalations',

                'branchEscalationSummary',

                'allowedLocations',

                'isHeadOfficeUser'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Escalation
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            $business_id = $request
                ->session()
                ->get('user.business_id');

            $user_id = $request
                ->session()
                ->get('user.id');

            /*
            |--------------------------------------------------------------------------
            | Logged User
            |--------------------------------------------------------------------------
            */

            $loggedUser = User::find($user_id);

            /*
            |--------------------------------------------------------------------------
            | Multi Branch Access
            |--------------------------------------------------------------------------
            */

            $allowedLocations = [];

            if (
                !empty(
                    $loggedUser->location_permissions
                )
            ) {

                $decodedLocations =
                    json_decode(
                        $loggedUser->location_permissions,
                        true
                    );

                if (
                    is_array($decodedLocations)
                ) {

                    $allowedLocations =
                        $decodedLocations;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Head Office Logic
            |--------------------------------------------------------------------------
            */

            $isHeadOfficeUser = false;

            if (empty($allowedLocations)) {

                $isHeadOfficeUser = true;
            }

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $request->validate([

                'loan_id' =>
                    'required',

                'escalation_type' =>
                    'required',

                'priority_level' =>
                    'required',

                'notes' =>
                    'required'

            ]);

            /*
            |--------------------------------------------------------------------------
            | Loan Validation
            |--------------------------------------------------------------------------
            */

            $loan = Loan::where(
                    'business_id',
                    $business_id
                )
                ->findOrFail(
                    $request->loan_id
                );

            /*
            |--------------------------------------------------------------------------
            | Branch Governance Validation
            |--------------------------------------------------------------------------
            */

            if (
                !$isHeadOfficeUser
            ) {

                if (
                    !in_array(
                        $loan->location_id,
                        $allowedLocations
                    )
                ) {

                    return redirect()
                        ->back()
                        ->with(
                            'error',
                            'You do not have branch access for this escalation.'
                        );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Duplicate Active Escalation Prevention
            |--------------------------------------------------------------------------
            */

            $existing_open_escalation =
                LoanRecoveryEscalation::where(
                        'business_id',
                        $business_id
                    )
                    ->where(
                        'loan_id',
                        $loan->id
                    )
                    ->where(
                        'status',
                        'open'
                    )
                    ->first();

            if ($existing_open_escalation) {

                return redirect()
                    ->back()
                    ->with(
                        'error',
                        'This loan already has an active escalation.'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Automatic DPD Escalation Logic
            |--------------------------------------------------------------------------
            */

            if ($loan->dpd >= 90) {

                $auto_escalation_level =
                    'critical';

                $auto_escalation_type =
                    'legal';

            } elseif ($loan->dpd >= 60) {

                $auto_escalation_level =
                    'critical';

                $auto_escalation_type =
                    'collections';

            } elseif ($loan->dpd >= 30) {

                $auto_escalation_level =
                    'high';

                $auto_escalation_type =
                    'collections';

            } else {

                $auto_escalation_level =
                    $request->priority_level;

                $auto_escalation_type =
                    $request->escalation_type;
            }

            /*
            |--------------------------------------------------------------------------
            | SLA Target
            |--------------------------------------------------------------------------
            */

            $sla_due_date = now();

            if (
                $auto_escalation_level == 'critical'
            ) {

                $sla_due_date =
                    now()->addDay();

            } elseif (
                $auto_escalation_level == 'high'
            ) {

                $sla_due_date =
                    now()->addDays(3);

            } else {

                $sla_due_date =
                    now()->addDays(7);
            }

            /*
            |--------------------------------------------------------------------------
            | Workforce Priority Score
            |--------------------------------------------------------------------------
            */

            $priority_score = 50;

            if ($loan->dpd >= 90) {

                $priority_score = 100;

            } elseif ($loan->dpd >= 60) {

                $priority_score = 80;

            } elseif ($loan->dpd >= 30) {

                $priority_score = 60;
            }

            /*
            |--------------------------------------------------------------------------
            | Escalation Creation
            |--------------------------------------------------------------------------
            */

            $escalation =
                LoanRecoveryEscalation::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'customer_id' =>
                        $loan->customer_id,

                    'escalation_type' =>
                        $auto_escalation_type,

                    'priority_level' =>
                        $auto_escalation_level,

                    'status' =>
                        'open',

                    'notes' =>
                        $request->notes,

                    'assigned_to' =>
                        $request->assigned_to,

                    'escalation_date' =>
                        now()->toDateString(),

                    'sla_due_date' =>
                        $sla_due_date,

                    'legal_action_required' =>
                        $request->legal_action_required
                        ? 1 : 0,

                    'field_visit_required' =>
                        $request->field_visit_required
                        ? 1 : 0,

                    'workforce_priority_score' =>
                        $priority_score,

                    'created_by' =>
                        $user_id
                ]);

            /*
            |--------------------------------------------------------------------------
            | Recovery Status Update
            |--------------------------------------------------------------------------
            */

            if ($loan->dpd >= 90) {

                $loan->recovery_status =
                    'legal_escalation';

            } elseif ($loan->dpd >= 60) {

                $loan->recovery_status =
                    'critical_escalation';

            } elseif ($loan->dpd >= 30) {

                $loan->recovery_status =
                    'warning_escalation';

            } else {

                $loan->recovery_status =
                    'normal_recovery';
            }

            $loan->save();

            /*
            |--------------------------------------------------------------------------
            | Recovery Tracking
            |--------------------------------------------------------------------------
            */

            LoanRecovery::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $loan->id,

                'recovery_date' =>
                    now()->toDateString(),

                'status' =>
                    'escalated',

                'notes' =>
                    'Loan escalated under enterprise recovery workflow.',

                'created_by' =>
                    $user_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Collection Note
            |--------------------------------------------------------------------------
            */

            LoanCollectionNote::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $loan->id,

                'customer_id' =>
                    $loan->customer_id,

                'collection_type' =>
                    'escalation',

                'note' =>
                    $request->notes,

                'priority_level' =>
                    $auto_escalation_level,

                'escalation_required' =>
                    1,

                'follow_up_date' =>
                    $sla_due_date,

                'created_by' =>
                    $user_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Legal Workflow
            |--------------------------------------------------------------------------
            */

            if (
                $request->legal_action_required
            ) {

                LoanAuditLog::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'action_type' =>
                        'legal_escalation_triggered',

                    'description' =>
                        'Legal escalation workflow initiated.',

                    'performed_by' =>
                        $user_id
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Field Recovery Workflow
            |--------------------------------------------------------------------------
            */

            if (
                $request->field_visit_required
            ) {

                LoanAuditLog::create([

                    'business_id' =>
                        $business_id,

                    'loan_id' =>
                        $loan->id,

                    'action_type' =>
                        'field_recovery_required',

                    'description' =>
                        'Field recovery visit required.',

                    'performed_by' =>
                        $user_id
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Notification Workflow
            |--------------------------------------------------------------------------
            */

            LoanNotification::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $loan->id,

                'customer_id' =>
                    $loan->customer_id,

                'notification_type' =>
                    'recovery_escalation',

                'channel' =>
                    'system',

                'message' =>
                    'Loan account has entered recovery escalation workflow.',

                'status' =>
                    'pending'
            ]);

            /*
            |--------------------------------------------------------------------------
            | Audit Trail
            |--------------------------------------------------------------------------
            */

            LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $loan->id,

                'action_type' =>
                    'recovery_escalation_created',

                'description' =>
                    'Enterprise recovery escalation created.',

                'performed_by' =>
                    $user_id
            ]);

            DB::commit();

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Recovery escalation created successfully.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Escalation Profile
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $business_id = request()
            ->session()
            ->get('user.business_id');

        $user_id = request()
            ->session()
            ->get('user.id');

        $user = User::find($user_id);

        /*
        |--------------------------------------------------------------------------
        | Multi Branch Access
        |--------------------------------------------------------------------------
        */

        $allowedLocations = [];

        if (!empty($user->location_permissions)) {

            $decodedLocations =
                json_decode(
                    $user->location_permissions,
                    true
                );

            if (
                is_array($decodedLocations)
            ) {

                $allowedLocations =
                    $decodedLocations;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Head Office Logic
        |--------------------------------------------------------------------------
        */

        $isHeadOfficeUser = false;

        if (empty($allowedLocations)) {

            $isHeadOfficeUser = true;
        }

        /*
        |--------------------------------------------------------------------------
        | Escalation Query
        |--------------------------------------------------------------------------
        */

        $escalation =
            LoanRecoveryEscalation::leftJoin(
                    'loan_applications',
                    'loan_recovery_escalations.loan_id',
                    '=',
                    'loan_applications.id'
                )
                ->where(
                    'loan_recovery_escalations.business_id',
                    $business_id
                )
                ->where(
                    'loan_recovery_escalations.id',
                    $id
                );

        if (!$isHeadOfficeUser) {

            $escalation->whereIn(
                'loan_applications.location_id',
                $allowedLocations
            );
        }

        $escalation =
            $escalation
                ->select(
                    'loan_recovery_escalations.*'
                )
                ->firstOrFail();

        return view(
            'loan::loan_recovery_escalations.show',
            compact('escalation')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Close Escalation
    |--------------------------------------------------------------------------
    */

    public function close($id)
    {
        DB::beginTransaction();

        try {

            $business_id = request()
                ->session()
                ->get('user.business_id');

            $user_id = request()
                ->session()
                ->get('user.id');

            $user = User::find($user_id);

            /*
            |--------------------------------------------------------------------------
            | Multi Branch Access
            |--------------------------------------------------------------------------
            */

            $allowedLocations = [];

            if (!empty($user->location_permissions)) {

                $decodedLocations =
                    json_decode(
                        $user->location_permissions,
                        true
                    );

                if (
                    is_array($decodedLocations)
                ) {

                    $allowedLocations =
                        $decodedLocations;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Head Office Logic
            |--------------------------------------------------------------------------
            */

            $isHeadOfficeUser = false;

            if (empty($allowedLocations)) {

                $isHeadOfficeUser = true;
            }

            /*
            |--------------------------------------------------------------------------
            | Escalation Validation
            |--------------------------------------------------------------------------
            */

            $escalation =
                LoanRecoveryEscalation::leftJoin(
                        'loan_applications',
                        'loan_recovery_escalations.loan_id',
                        '=',
                        'loan_applications.id'
                    )
                    ->where(
                        'loan_recovery_escalations.business_id',
                        $business_id
                    )
                    ->where(
                        'loan_recovery_escalations.id',
                        $id
                    );

            if (!$isHeadOfficeUser) {

                $escalation->whereIn(
                    'loan_applications.location_id',
                    $allowedLocations
                );
            }

            $escalation =
                $escalation
                    ->select(
                        'loan_recovery_escalations.*'
                    )
                    ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Close Escalation
            |--------------------------------------------------------------------------
            */

            $escalation->status =
                'closed';

            $escalation->closed_at =
                now();

            $escalation->closed_by =
                $user_id;

            $escalation->save();

            /*
            |--------------------------------------------------------------------------
            | Recovery Tracking
            |--------------------------------------------------------------------------
            */

            LoanRecovery::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $escalation->loan_id,

                'recovery_date' =>
                    now()->toDateString(),

                'status' =>
                    'escalation_closed',

                'notes' =>
                    'Recovery escalation closed successfully.',

                'created_by' =>
                    $user_id
            ]);

            /*
            |--------------------------------------------------------------------------
            | Audit Trail
            |--------------------------------------------------------------------------
            */

            LoanAuditLog::create([

                'business_id' =>
                    $business_id,

                'loan_id' =>
                    $escalation->loan_id,

                'action_type' =>
                    'recovery_escalation_closed',

                'description' =>
                    'Recovery escalation closed.',

                'performed_by' =>
                    $user_id
            ]);

            DB::commit();

            return redirect()
                ->back()
                ->with(
                    'success',
                    'Recovery escalation closed successfully.'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error($e);

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }
}
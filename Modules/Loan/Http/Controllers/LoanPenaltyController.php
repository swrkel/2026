<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\LoanPenalty;

class LoanPenaltyController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Store Penalty
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $business_id = session('business.id');

        $request->validate([

            'name' => 'required|string|max:255',

            'penalty_type' => 'required|string|max:100',

            'default_value' => 'nullable|numeric',

        ]);

        LoanPenalty::create([

            /*
            |--------------------------------------------------------------------------
            | Tenant
            |--------------------------------------------------------------------------
            */

            'business_id' => $business_id,

            /*
            |--------------------------------------------------------------------------
            | Penalty Details
            |--------------------------------------------------------------------------
            */

            'name' => $request->name,

            'code' => $request->code,

            'description' => $request->description,

            'penalty_type' => $request->penalty_type,

            'calculation_method' =>
                $request->calculation_method,

            'default_value' =>
                $request->default_value,

            'minimum_value' =>
                $request->minimum_value,

            'maximum_value' =>
                $request->maximum_value,

            /*
            |--------------------------------------------------------------------------
            | Penalty Rules
            |--------------------------------------------------------------------------
            */

            'grace_period_days' =>
                $request->grace_period_days,

            'apply_after_days' =>
                $request->apply_after_days,

            'maximum_penalty_limit' =>
                $request->maximum_penalty_limit,

            'daily_accrual' =>
                $request->daily_accrual ? 1 : 0,

            'compound_penalty' =>
                $request->compound_penalty ? 1 : 0,

            /*
            |--------------------------------------------------------------------------
            | Governance Controls
            |--------------------------------------------------------------------------
            */

            'is_waivable' =>
                $request->is_waivable ? 1 : 0,

            'requires_approval' =>
                $request->requires_approval ? 1 : 0,

            'auto_apply' =>
                $request->auto_apply ? 1 : 0,

            'priority_level' =>
                $request->priority_level,

            /*
            |--------------------------------------------------------------------------
            | Recovery Optimization
            |--------------------------------------------------------------------------
            */

            'recovery_stage' =>
                $request->recovery_stage,

            'escalation_trigger' =>
                $request->escalation_trigger,

            'legal_recovery_applicable' =>
                $request->legal_recovery_applicable ? 1 : 0,

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' => 'active',

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            'created_by' => auth()->id(),

        ]);

        return redirect()
            ->back()
            ->with(
                'success',
                'Loan Penalty Created Successfully'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Penalty
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $id
    ) {
        $business_id = session('business.id');

        $penalty = LoanPenalty::where(
                'business_id',
                $business_id
            )
            ->findOrFail($id);

        $request->validate([

            'name' => 'required|string|max:255',

        ]);

        $penalty->update([

            /*
            |--------------------------------------------------------------------------
            | Penalty Details
            |--------------------------------------------------------------------------
            */

            'name' => $request->name,

            'code' => $request->code,

            'description' => $request->description,

            'penalty_type' => $request->penalty_type,

            'calculation_method' =>
                $request->calculation_method,

            'default_value' =>
                $request->default_value,

            'minimum_value' =>
                $request->minimum_value,

            'maximum_value' =>
                $request->maximum_value,

            /*
            |--------------------------------------------------------------------------
            | Penalty Rules
            |--------------------------------------------------------------------------
            */

            'grace_period_days' =>
                $request->grace_period_days,

            'apply_after_days' =>
                $request->apply_after_days,

            'maximum_penalty_limit' =>
                $request->maximum_penalty_limit,

            'daily_accrual' =>
                $request->daily_accrual ? 1 : 0,

            'compound_penalty' =>
                $request->compound_penalty ? 1 : 0,

            /*
            |--------------------------------------------------------------------------
            | Governance Controls
            |--------------------------------------------------------------------------
            */

            'is_waivable' =>
                $request->is_waivable ? 1 : 0,

            'requires_approval' =>
                $request->requires_approval ? 1 : 0,

            'auto_apply' =>
                $request->auto_apply ? 1 : 0,

            'priority_level' =>
                $request->priority_level,

            /*
            |--------------------------------------------------------------------------
            | Recovery Optimization
            |--------------------------------------------------------------------------
            */

            'recovery_stage' =>
                $request->recovery_stage,

            'escalation_trigger' =>
                $request->escalation_trigger,

            'legal_recovery_applicable' =>
                $request->legal_recovery_applicable ? 1 : 0,

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' =>
                $request->status ?? 'active',

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            'updated_by' => auth()->id(),

        ]);

        return redirect()
            ->back()
            ->with(
                'success',
                'Loan Penalty Updated Successfully'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Penalty
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $business_id = session('business.id');

        $penalty = LoanPenalty::where(
                'business_id',
                $business_id
            )
            ->findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | Product Dependency Protection
        |--------------------------------------------------------------------------
        */

        if (
            method_exists($penalty, 'loanProducts') &&
            $penalty->loanProducts()->count() > 0
        ) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Cannot delete penalty linked to loan products.'
                );
        }

        $penalty->delete();

        return redirect()
            ->back()
            ->with(
                'success',
                'Loan Penalty Deleted Successfully'
            );
    }
}
<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\LoanFee;

class LoanFeeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Store Fee
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $business_id = session('business.id');

        $request->validate([

            'name' => 'required|string|max:255',

            'fee_type' => 'required|string|max:100',

            'default_value' => 'nullable|numeric',

        ]);

        LoanFee::create([

            /*
            |--------------------------------------------------------------------------
            | Tenant
            |--------------------------------------------------------------------------
            */

            'business_id' => $business_id,

            /*
            |--------------------------------------------------------------------------
            | Fee Details
            |--------------------------------------------------------------------------
            */

            'name' => $request->name,

            'code' => $request->code,

            'description' => $request->description,

            'fee_type' => $request->fee_type,

            'calculation_method' =>
                $request->calculation_method,

            'apply_when' =>
                $request->apply_when,

            'default_value' =>
                $request->default_value,

            'minimum_value' =>
                $request->minimum_value,

            'maximum_value' =>
                $request->maximum_value,

            /*
            |--------------------------------------------------------------------------
            | Governance
            |--------------------------------------------------------------------------
            */

            'is_mandatory' =>
                $request->is_mandatory ? 1 : 0,

            'is_editable' =>
                $request->is_editable ? 1 : 0,

            'is_taxable' =>
                $request->is_taxable ? 1 : 0,

            'priority_order' =>
                $request->priority_order,

            /*
            |--------------------------------------------------------------------------
            | Recovery Intelligence
            |--------------------------------------------------------------------------
            */

            'recovery_applicable' =>
                $request->recovery_applicable ? 1 : 0,

            'allow_fee_waiver' =>
                $request->allow_fee_waiver ? 1 : 0,

            'auto_apply' =>
                $request->auto_apply ? 1 : 0,

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
                'Loan Fee Created Successfully'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Fee
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $id
    ) {
        $business_id = session('business.id');

        $fee = LoanFee::where(
                'business_id',
                $business_id
            )
            ->findOrFail($id);

        $request->validate([

            'name' => 'required|string|max:255',

        ]);

        $fee->update([

            /*
            |--------------------------------------------------------------------------
            | Fee Details
            |--------------------------------------------------------------------------
            */

            'name' => $request->name,

            'code' => $request->code,

            'description' => $request->description,

            'fee_type' => $request->fee_type,

            'calculation_method' =>
                $request->calculation_method,

            'apply_when' =>
                $request->apply_when,

            'default_value' =>
                $request->default_value,

            'minimum_value' =>
                $request->minimum_value,

            'maximum_value' =>
                $request->maximum_value,

            /*
            |--------------------------------------------------------------------------
            | Governance
            |--------------------------------------------------------------------------
            */

            'is_mandatory' =>
                $request->is_mandatory ? 1 : 0,

            'is_editable' =>
                $request->is_editable ? 1 : 0,

            'is_taxable' =>
                $request->is_taxable ? 1 : 0,

            'priority_order' =>
                $request->priority_order,

            /*
            |--------------------------------------------------------------------------
            | Recovery Intelligence
            |--------------------------------------------------------------------------
            */

            'recovery_applicable' =>
                $request->recovery_applicable ? 1 : 0,

            'allow_fee_waiver' =>
                $request->allow_fee_waiver ? 1 : 0,

            'auto_apply' =>
                $request->auto_apply ? 1 : 0,

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
                'Loan Fee Updated Successfully'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Fee
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $business_id = session('business.id');

        $fee = LoanFee::where(
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
            method_exists($fee, 'loanProducts') &&
            $fee->loanProducts()->count() > 0
        ) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Cannot delete fee linked to loan products.'
                );
        }

        $fee->delete();

        return redirect()
            ->back()
            ->with(
                'success',
                'Loan Fee Deleted Successfully'
            );
    }
}
<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Modules\Loan\Models\LoanCategory;

class LoanCategoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Store Category
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $business_id = session('business.id');

        $request->validate([

            'name' => 'required|string|max:255',

            'code' => 'nullable|string|max:100',

            'risk_level' => 'nullable|string|max:50',

            'priority_level' => 'nullable|string|max:50',

        ]);

        LoanCategory::create([

            /*
            |--------------------------------------------------------------------------
            | Tenant
            |--------------------------------------------------------------------------
            */

            'business_id' => $business_id,

            /*
            |--------------------------------------------------------------------------
            | Basic Details
            |--------------------------------------------------------------------------
            */

            'name' => $request->name,

            'code' => $request->code,

            'description' => $request->description,

            /*
            |--------------------------------------------------------------------------
            | Governance
            |--------------------------------------------------------------------------
            */

            'risk_level' =>
                $request->risk_level,

            'priority_level' =>
                $request->priority_level,

            'recovery_strategy' =>
                $request->recovery_strategy,

            /*
            |--------------------------------------------------------------------------
            | Workforce Optimization
            |--------------------------------------------------------------------------
            */

            'auto_assign_collectors' =>
                $request->auto_assign_collectors ? 1 : 0,

            'enable_escalations' =>
                $request->enable_escalations ? 1 : 0,

            'allow_settlements' =>
                $request->allow_settlements ? 1 : 0,

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
                'Loan Category Created Successfully'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Category
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        $id
    ) {
        $business_id = session('business.id');

        $category = LoanCategory::where(
                'business_id',
                $business_id
            )
            ->findOrFail($id);

        $request->validate([

            'name' => 'required|string|max:255',

        ]);

        $category->update([

            /*
            |--------------------------------------------------------------------------
            | Basic Details
            |--------------------------------------------------------------------------
            */

            'name' => $request->name,

            'code' => $request->code,

            'description' => $request->description,

            /*
            |--------------------------------------------------------------------------
            | Governance
            |--------------------------------------------------------------------------
            */

            'risk_level' =>
                $request->risk_level,

            'priority_level' =>
                $request->priority_level,

            'recovery_strategy' =>
                $request->recovery_strategy,

            /*
            |--------------------------------------------------------------------------
            | Workforce Optimization
            |--------------------------------------------------------------------------
            */

            'auto_assign_collectors' =>
                $request->auto_assign_collectors ? 1 : 0,

            'enable_escalations' =>
                $request->enable_escalations ? 1 : 0,

            'allow_settlements' =>
                $request->allow_settlements ? 1 : 0,

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
                'Loan Category Updated Successfully'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Category
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $business_id = session('business.id');

        $category = LoanCategory::where(
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
            method_exists($category, 'products') &&
            $category->products()->count() > 0
        ) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Cannot delete category with linked loan products.'
                );
        }

        $category->delete();

        return redirect()
            ->back()
            ->with(
                'success',
                'Loan Category Deleted Successfully'
            );
    }
}
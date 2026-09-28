<?php

namespace Modules\Loan\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Currency;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Loan\Models\LoanCategory;
use Modules\Loan\Models\LoanFee;
use Modules\Loan\Models\LoanPenalty;
use Modules\Loan\Models\LoanProduct;

class LoanProductController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session('business.id') ?: session('user.business_id');

        $query = LoanProduct::where('business_id', $business_id)
            ->with(['category', 'currency', 'locations', 'creator'])
            ->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $categories = Schema::hasTable('loan_categories')
            ? LoanCategory::where('business_id', $business_id)->orderBy('name')->get()
            : collect();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->get();

        return view('loan::loan_products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return view('loan::loan_products.create', $this->formData());
    }

    public function store(Request $request)
    {
        return $this->saveLoanProduct($request);
    }

    public function show($id)
    {
        $business_id = session('business.id') ?: session('user.business_id');

        $product = LoanProduct::where('business_id', $business_id)
            ->with(['category', 'currency', 'fees', 'penalties', 'locations', 'creator'])
            ->findOrFail($id);

        return view('loan::loan_products.show', compact('product'));
    }

    public function edit($id)
    {
        $business_id = session('business.id') ?: session('user.business_id');

        $product = LoanProduct::where('business_id', $business_id)
            ->with(['fees', 'penalties', 'locations'])
            ->findOrFail($id);

        return view('loan::loan_products.edit', array_merge(
            compact('product'),
            $this->formData()
        ));
    }

    public function update(Request $request, $id)
    {
        return $this->saveLoanProduct($request, $id);
    }

    public function activate($id)
    {
        $this->updateStatus($id, 'active');
        return back()->with('success', 'Loan Product Activated Successfully');
    }

    public function deactivate($id)
    {
        $this->updateStatus($id, 'inactive');
        return back()->with('success', 'Loan Product Deactivated Successfully');
    }

    public function duplicate($id)
    {
        $business_id = session('business.id') ?: session('user.business_id');

        DB::beginTransaction();

        try {
            $product = LoanProduct::where('business_id', $business_id)
                ->with(['fees', 'penalties', 'locations'])
                ->findOrFail($id);

            $new_product = $product->replicate();
            $new_product->name = $product->name . ' (Copy)';
            $new_product->code = $product->code ? $product->code . '_COPY' : null;
            $new_product->status = 'inactive';
            $new_product->created_by = auth()->id();
            $new_product->save();

            if (Schema::hasTable('loan_product_locations')) {
                $new_product->locations()->sync($product->locations->pluck('id')->toArray());
            }

            if (Schema::hasTable('loan_product_fees')) {
                foreach ($product->fees as $fee) {
                    DB::table('loan_product_fees')->insert([
                        'business_id' => $business_id,
                        'loan_product_id' => $new_product->id,
                        'loan_fee_id' => $fee->id,
                        'fee_type' => $fee->pivot->fee_type,
                        'fee_value' => $fee->pivot->fee_value,
                        'calculation_based_on' => $fee->pivot->calculation_based_on,
                        'applied_when' => $fee->pivot->applied_when,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if (Schema::hasTable('loan_product_penalties')) {
                foreach ($product->penalties as $penalty) {
                    DB::table('loan_product_penalties')->insert([
                        'business_id' => $business_id,
                        'loan_product_id' => $new_product->id,
                        'loan_penalty_id' => $penalty->id,
                        'penalty_type' => $penalty->pivot->penalty_type,
                        'penalty_value' => $penalty->pivot->penalty_value,
                        'grace_period' => $penalty->pivot->grace_period,
                        'grace_period_cycle' => $penalty->pivot->grace_period_cycle,
                        'created_by' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Loan Product Duplicated Successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $business_id = session('business.id') ?: session('user.business_id');

        $product = LoanProduct::where('business_id', $business_id)->findOrFail($id);

        if (Schema::hasTable('loans') && DB::table('loans')->where('loan_product_id', $product->id)->exists()) {
            return back()->withErrors(['error' => 'This product is already linked to loans and cannot be deleted.']);
        }

        DB::beginTransaction();

        try {
            if (Schema::hasTable('loan_product_fees')) {
                DB::table('loan_product_fees')->where('loan_product_id', $product->id)->delete();
            }

            if (Schema::hasTable('loan_product_penalties')) {
                DB::table('loan_product_penalties')->where('loan_product_id', $product->id)->delete();
            }

            if (Schema::hasTable('loan_product_locations')) {
                $product->locations()->detach();
            }

            $product->delete();

            DB::commit();
            return back()->with('success', 'Loan Product Deleted Successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    private function formData()
    {
        $business_id = session('business.id') ?: session('user.business_id');

        $categories = Schema::hasTable('loan_categories')
            ? LoanCategory::where('business_id', $business_id)->orderBy('name')->get()
            : collect();

        $fees = Schema::hasTable('loan_fees')
            ? LoanFee::where('business_id', $business_id)->orderBy('name')->get()
            : collect();

        $penalties = Schema::hasTable('loan_penalties')
            ? LoanPenalty::where('business_id', $business_id)->orderBy('name')->get()
            : collect();

        $currencies = Currency::orderBy('country')->get();

        $locations = BusinessLocation::where('business_id', $business_id)
            ->orderBy('name')
            ->get();

        $default_currency = Business::where('id', $business_id)->value('currency_id');
        $default_location = session('user.business_location_id');

        $interest_types = [
            'flat_rate' => 'Flat Rate',
            'reducing_balance' => 'Reducing Balance',
            'compound_interest' => 'Compound Interest',
        ];

        $interest_frequencies = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'annual' => 'Annual',
        ];

        $calculation_methods = [
            'fixed' => 'Fixed %',
            'variable' => 'Variable %',
        ];

        $compounding_periods = [
            'none' => 'None',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ];

        $loan_term_cycles = [
            'days' => 'Days',
            'weeks' => 'Weeks',
            'months' => 'Months',
            'years' => 'Years',
        ];

        $installment_frequencies = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'biweekly' => 'Biweekly',
            'monthly' => 'Monthly',
            'annually' => 'Annually',
        ];

        return compact(
            'categories',
            'fees',
            'penalties',
            'currencies',
            'locations',
            'default_currency',
            'default_location',
            'interest_types',
            'interest_frequencies',
            'calculation_methods',
            'compounding_periods',
            'loan_term_cycles',
            'installment_frequencies'
        );
    }

    private function saveLoanProduct(Request $request, $id = null)
    {
        $business_id = session('business.id') ?: session('user.business_id');

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'interest_rate' => 'nullable|numeric|min:0',
            'default_interest_rate' => 'nullable|numeric|min:0',
            'minimum_interest_rate' => 'nullable|numeric|min:0',
            'maximum_interest_rate' => 'nullable|numeric|min:0',
            'number_of_installments' => 'nullable|integer|min:0',
            'processing_fee' => 'nullable|numeric|min:0',
            'late_payment_charge' => 'nullable|numeric|min:0',
            'location_ids' => 'nullable|array',
            'fees' => 'nullable|array',
            'penalties' => 'nullable|array',
        ]);

        if ($request->filled('code')) {
            $duplicate = LoanProduct::where('business_id', $business_id)->where('code', $request->code);
            if ($id) {
                $duplicate->where('id', '!=', $id);
            }

            if ($duplicate->exists()) {
                return back()->withErrors(['code' => 'Product code already exists'])->withInput();
            }
        }

        DB::beginTransaction();

        try {
            $data = [
                'business_id' => $business_id,
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'status' => $request->status ?: 'active',
                'minimum_amount' => $request->minimum_amount,
                'maximum_amount' => $request->maximum_amount,
                'minimum_loan_term' => $request->minimum_loan_term,
                'maximum_loan_term' => $request->maximum_loan_term,
                'duration_type' => $request->duration_type,
                'interest_rate' => $request->interest_rate,
                'minimum_interest_rate' => $request->minimum_interest_rate,
                'maximum_interest_rate' => $request->maximum_interest_rate,
                'default_interest_rate' => $request->default_interest_rate,
                'interest_method' => $request->interest_method,
                'interest_frequency' => $request->interest_frequency,
                'calculation_method' => $request->calculation_method,
                'compounding_period' => $request->compounding_period,
                'grace_period' => $request->grace_period,
                'grace_period_cycle' => $request->grace_period_cycle,
                'repayment_frequency' => $request->repayment_frequency,
                'number_of_installments' => $request->number_of_installments,
                'allow_partial_payments' => $request->has('allow_partial_payments') ? 1 : 0,
                'allow_early_settlement' => $request->has('allow_early_settlement') ? 1 : 0,
                'auto_generate_schedule' => $request->has('auto_generate_schedule') ? 1 : 0,
                'category_id' => $request->category_id,
                'currency_id' => $request->currency_id,
                'location_id' => is_array($request->location_ids) && count($request->location_ids) ? $request->location_ids[0] : null,
            ];

            if (Schema::hasColumn('loan_products', 'processing_fee')) {
                $data['processing_fee'] = $request->processing_fee;
            }

            if (Schema::hasColumn('loan_products', 'late_payment_charge')) {
                $data['late_payment_charge'] = $request->late_payment_charge;
            }

            if (Schema::hasColumn('loan_products', 'notes')) {
                $data['notes'] = $request->notes;
            }


            if ($id) {
                $loan_product = LoanProduct::where('business_id', $business_id)->findOrFail($id);
                $loan_product->update($data);
            } else {
                $data['created_by'] = auth()->id();
                $loan_product = LoanProduct::create($data);
            }

            if (Schema::hasTable('loan_product_locations')) {
                $loan_product->locations()->sync($request->location_ids ?: []);
            }

            $this->syncFees($loan_product, $request, $business_id);
            $this->syncPenalties($loan_product, $request, $business_id);

            DB::commit();

            return redirect('/loan/loan-products')
                ->with('success', $id ? 'Loan Product Updated Successfully' : 'Loan Product Added Successfully');
        } catch (\Exception $exception) {
            DB::rollBack();

            Log::error('Loan Product Save Failed', [
                'message' => $exception->getMessage(),
                'line' => $exception->getLine(),
            ]);

            return back()->withErrors(['error' => $exception->getMessage()])->withInput();
        }
    }

    private function syncFees(LoanProduct $loan_product, Request $request, $business_id)
    {
        if (!Schema::hasTable('loan_product_fees')) {
            return;
        }

        DB::table('loan_product_fees')->where('loan_product_id', $loan_product->id)->delete();

        foreach (($request->fees ?: []) as $fee) {
            if (empty($fee['fee_id'])) {
                continue;
            }

            DB::table('loan_product_fees')->insert([
                'business_id' => $business_id,
                'loan_product_id' => $loan_product->id,
                'loan_fee_id' => $fee['fee_id'],
                'fee_type' => $fee['type'] ?? null,
                'fee_value' => $fee['value'] ?? null,
                'calculation_based_on' => $fee['based_on'] ?? null,
                'applied_when' => $fee['applied_when'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function syncPenalties(LoanProduct $loan_product, Request $request, $business_id)
    {
        if (!Schema::hasTable('loan_product_penalties')) {
            return;
        }

        DB::table('loan_product_penalties')->where('loan_product_id', $loan_product->id)->delete();

        foreach (($request->penalties ?: []) as $penalty) {
            if (empty($penalty['penalty_id'])) {
                continue;
            }

            DB::table('loan_product_penalties')->insert([
                'business_id' => $business_id,
                'loan_product_id' => $loan_product->id,
                'loan_penalty_id' => $penalty['penalty_id'],
                'penalty_type' => $penalty['type'] ?? null,
                'penalty_value' => $penalty['value'] ?? null,
                'grace_period' => $penalty['grace_period'] ?? null,
                'grace_period_cycle' => $penalty['grace_period_cycle'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function updateStatus($id, $status)
    {
        $business_id = session('business.id') ?: session('user.business_id');

        LoanProduct::where('business_id', $business_id)
            ->findOrFail($id)
            ->update(['status' => $status]);
    }
}

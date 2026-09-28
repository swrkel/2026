<?php

namespace Modules\ProductsNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Validator;

abstract class ProductFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        /*
         * IS2215 - make Profit Percentage On deterministic at the request edge.
         *
         * Some installations still have an older copied Products New form under
         * resources/views/modules/productsnew. That form can omit profit_basis
         * completely even though the operator selected a VAT-inclusive product.
         * validated() would then drop the setting and the next edit could fall
         * back to the legacy database default, Exclusive.
         *
         * A current form always posts exclusive|inclusive and its explicit value
         * wins. Only when the key is absent/invalid do we infer the safe business
         * meaning from Selling Price Tax Type: VAT-inclusive => inclusive.
         */
        $profitBasis = strtolower(trim((string) $this->input('profit_basis', '')));

        if (! in_array($profitBasis, ['exclusive', 'inclusive'], true)) {
            $taxType = strtolower(trim((string) $this->input('tax_type', 'exclusive')));
            $profitBasis = $taxType === 'inclusive' ? 'inclusive' : 'exclusive';
        }

        $this->merge([
            'enable_stock' => $this->boolean('enable_stock'),
            'vat_claimed' => $this->boolean('vat_claimed'),
            'not_for_selling' => $this->boolean('not_for_selling'),
            'product_locations_present' => $this->boolean('product_locations_present'),
            'profit_basis' => $profitBasis,
        ]);
    }

    protected function commonRules(): array
    {
        return [
            /*
             | Show in Pumper Dashboard.
             |
             | In commonRules so create AND edit both accept it. Named here or
             | validated() drops the value, and the field would appear on the
             | form and never save.
            */
            'show_in_pumper_dashboard' => 'nullable|boolean',

            'name' => 'required|string|max:255',
            'type' => 'required|string|in:single,variable,combo',
            'unit_id' => 'nullable|integer',
            'brand_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'sub_category_id' => 'nullable|integer',
            'tax' => 'nullable|integer',
            // MA-002: only these two values are meaningful; anything else is
            // rejected rather than stored and silently misread later.
            'profit_basis' => 'nullable|in:exclusive,inclusive',
            'sale_tax' => 'nullable|integer',
            'tax_type' => 'required|string|in:inclusive,exclusive',
            'barcode' => 'nullable|string|max:191',
            'alert_quantity' => 'nullable|numeric|min:0',
            'enable_stock' => 'required|boolean',
            'vat_claimed' => 'required|boolean',
            'not_for_selling' => 'required|boolean',
            'stock_type' => 'nullable|string|max:191',
            'weight' => 'nullable|string|max:191',
            'product_description' => 'nullable|string',
            'preparation_time_in_minutes' => 'nullable|integer|min:0|max:14400',
            'date' => 'nullable|date',
            'warranty_id' => 'nullable|integer',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
            'image_current' => 'nullable|string|max:191',
            'gallery' => 'nullable|array',
            'attachments' => 'nullable|array',

            /*
             * MA-002: min:0 REMOVED from the five pricing figures.
             *
             * A negative profit percentage is a real business situation - selling
             * below cost - and this form CALCULATES it. Enter a selling price
             * lower than the purchase price and the margin comes out negative,
             * at which point the form refused to save the number it had just
             * worked out itself.
             *
             * The prices go with it, because a credit or correction line can
             * legitimately be negative and blocking it only forces someone to
             * fake the figure somewhere else.
             *
             * The rules stay 'numeric', so text is still rejected. Only the
             * lower bound is gone.
             *
             * DELIBERATELY UNCHANGED: alert_quantity, opening_stock qty and
             * unit_cost still carry min:0. A negative alert threshold or a
             * negative opening quantity is not a business case, it is a typing
             * mistake, and letting it through would put wrong stock into the
             * ledger. You asked for the pricing figures and that is what has
             * changed.
             */
            'single_dpp' => 'nullable|numeric',
            'single_dpp_inc_tax' => 'nullable|numeric',
            'profit_percent' => 'nullable|numeric',
            'single_dsp' => 'nullable|numeric',
            'single_dsp_inc_tax' => 'nullable|numeric',

            'product_locations_present' => 'required|boolean',
            'product_locations' => 'nullable|array',
            'product_locations.*' => 'integer',

            'opening_stock_date' => 'nullable|date',
            'opening_stock_reference' => 'nullable|string|max:100',
            'opening_stock' => 'nullable|array',
            'opening_stock.*.location_id' => 'nullable|integer',
            'opening_stock.*.store_id' => 'nullable|integer',
            'opening_stock.*.qty' => 'nullable|numeric|min:0',
            'opening_stock.*.unit_cost' => 'nullable|numeric|min:0',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $categoryId = (int) ($this->input('category_id') ?? 0);
            $subCategoryId = (int) ($this->input('sub_category_id') ?? 0);

            if ($subCategoryId <= 0) {
                return;
            }

            if ($categoryId <= 0) {
                $validator->errors()->add('sub_category_id', 'Please select the parent category before selecting a subcategory.');
                return;
            }

            if (!Schema::hasTable('categories') || !Schema::hasColumn('categories', 'parent_id')) {
                return;
            }

            $query = DB::table('categories')
                ->where('id', $subCategoryId)
                ->where('parent_id', $categoryId);

            if (Schema::hasColumn('categories', 'business_id')) {
                $businessId = (int) (session('business.id')
                    ?? $this->session()->get('user.business_id')
                    ?? optional($this->user())->business_id
                    ?? 0);

                if ($businessId > 0) {
                    $query->where('business_id', $businessId);
                }
            }

            if (Schema::hasColumn('categories', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            if (!$query->exists()) {
                $validator->errors()->add('sub_category_id', 'The selected subcategory does not belong to the selected category.');
            }
        });
    }
}

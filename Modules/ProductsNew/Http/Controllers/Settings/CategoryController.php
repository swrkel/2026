<?php

namespace Modules\ProductsNew\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Modules\ProductsNew\Services\Settings\CategoryImportService;
use Modules\ProductsNew\Services\Settings\ProductSettingLookupService;
use Modules\ProductsNew\Services\Settings\ProductSettingWriteService;

class CategoryController extends Controller
{
    public function index(Request $request, ProductSettingLookupService $lookup)
    {
        $filters = $request->only(['search', 'type', 'vat_exempted']);

        return view('productsnew::settings.categories.index', [
            'categories' => $lookup->categories($filters),
            'parentCategories' => $lookup->parentCategories(),
            'cogsAccounts' => $lookup->cogsAccounts(),
            'salesIncomeAccounts' => $lookup->salesIncomeAccounts(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request, ProductSettingWriteService $writer)
    {
        try {
            $writer->createCategory($this->validated($request));
        } catch (QueryException $exception) {
            Log::error('Products New category creation failed.', [
                'business_id' => session('business.id') ?? session('user.business_id'),
                'user_id' => auth()->id(),
                'sql_state' => $exception->errorInfo[0] ?? null,
                'driver_code' => $exception->errorInfo[1] ?? null,
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['category' => $this->databaseErrorMessage($exception)]);
        }

        return redirect()
            ->route('products-new.settings.categories.index')
            ->with('status', 'Category saved successfully.');
    }

    public function update(Request $request, int $category, ProductSettingWriteService $writer)
    {
        try {
            $writer->updateCategory($category, $this->validated($request));
        } catch (QueryException $exception) {
            Log::error('Products New category update failed.', [
                'category_id' => $category,
                'business_id' => session('business.id') ?? session('user.business_id'),
                'user_id' => auth()->id(),
                'sql_state' => $exception->errorInfo[0] ?? null,
                'driver_code' => $exception->errorInfo[1] ?? null,
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['category' => $this->databaseErrorMessage($exception)]);
        }

        return redirect()
            ->route('products-new.settings.categories.index')
            ->with('status', 'Category updated successfully.');
    }


    public function import(Request $request, CategoryImportService $importer)
    {
        $request->validateWithBag('categoryImport', [
            'category_import_file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('category_import_file');
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (!in_array($extension, ['csv', 'txt', 'xlsx'], true)) {
            return back()
                ->withErrors([
                    'category_import_file' => 'Use a .csv or .xlsx file. Old .xls files must be saved as .xlsx first.',
                ], 'categoryImport')
                ->with('open_category_import', true);
        }

        try {
            $result = $importer->import($file);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()
                ->withErrors($exception->errors(), 'categoryImport')
                ->with('open_category_import', true);
        } catch (\Throwable $exception) {
            Log::error('Products New category import failed.', [
                'business_id' => session('business.id') ?? session('user.business_id'),
                'user_id' => auth()->id(),
                'file_name' => $file->getClientOriginalName(),
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withErrors([
                    'category_import_file' => 'The category import could not be completed. Technical details were written to the Laravel log.',
                ], 'categoryImport')
                ->with('open_category_import', true);
        }

        $message = $result['created'] . ' product categor' . ($result['created'] === 1 ? 'y' : 'ies') . ' imported successfully.';
        if ($result['skipped'] > 0) {
            $message .= ' ' . $result['skipped'] . ' existing duplicate row(s) were skipped.';
        }

        return redirect()
            ->route('products-new.settings.categories.index')
            ->with('status', $message);
    }

    public function importTemplate(CategoryImportService $importer)
    {
        return response()->streamDownload(function () use ($importer): void {
            echo $importer->csvTemplate();
        }, 'products-new-category-import-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function destroy(int $category, ProductSettingWriteService $writer)
    {
        $writer->deleteCategory($category);

        return redirect()
            ->route('products-new.settings.categories.index')
            ->with('status', 'Category deleted successfully.');
    }


    private function databaseErrorMessage(QueryException $exception): string
    {
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        if ($driverCode === 1062) {
            return 'A category with the same unique value already exists.';
        }

        if ($driverCode === 1452) {
            return 'The selected parent category or account is not valid for the active tenant database.';
        }

        if ($driverCode === 1364 || $driverCode === 1048) {
            return 'A required category value is missing. Please review the form and save again.';
        }

        return 'The category could not be saved. The technical details were written to the Laravel log.';
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'category_code_is_hsn' => $request->boolean('category_code_is_hsn'),
            'add_as_sub_category' => $request->boolean('add_as_sub_category'),
            'weight_excess_loss_applicable' => $request->boolean('weight_excess_loss_applicable'),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'short_code' => ['nullable', 'string', 'max:50'],
            'category_code_is_hsn' => ['required', 'boolean'],
            'add_as_sub_category' => ['required', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'required_if:add_as_sub_category,1'],
            'add_related_account' => ['nullable', Rule::in(['category_level', 'sub_category_level'])],
            'cogs_account_id' => ['nullable', 'integer'],
            'sales_income_account_id' => ['nullable', 'integer'],
            'weight_excess_loss_applicable' => ['required', 'boolean'],
            'vat_exempted' => ['required', Rule::in(['Yes', 'No'])],
            'vat_based_on' => ['required', Rule::in(['sale_price', 'purchase_price'])],
            'apply_vat_on' => ['required', Rule::in([
                'on_product_sub_category_settings',
                'on_product_tax_settings_section',
            ])],
        ]);
    }
}

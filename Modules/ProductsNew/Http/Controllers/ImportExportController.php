<?php

namespace Modules\ProductsNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Entities\ProductsNewImportLine;
use Modules\ProductsNew\Entities\ProductsNewImportSession;
use Modules\ProductsNew\Services\ImportExport\ProductExportService;
use Modules\ProductsNew\Services\ImportExport\ProductImportService;
use Modules\ProductsNew\Services\ImportExport\ProductImportTemplateService;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class ImportExportController extends Controller
{
    public function index()
    {
        $businessId = ProductsNewTenantGuard::businessId();
        $userId = ProductsNewTenantGuard::userId();

        $sessions = ProductsNewImportSession::query()
            ->where(function ($query) use ($businessId, $userId): void {
                $query->where('business_id', $businessId);

                if ($userId) {
                    $query->orWhere(function ($legacy) use ($userId): void {
                        $legacy->whereNull('business_id')
                            ->where('created_by', $userId);
                    });
                }
            })
            ->latest()
            ->limit(20)
            ->get();

        $locations = collect();
        if (Schema::hasTable('business_locations')) {
            $locationQuery = DB::table('business_locations')
                ->select(array_values(array_filter(
                    ['id', 'name'],
                    fn (string $column): bool => Schema::hasColumn('business_locations', $column)
                )));

            if (Schema::hasColumn('business_locations', 'business_id')) {
                ProductsNewTenantGuard::applyBusiness($locationQuery, 'business_locations.business_id');
            }
            if (Schema::hasColumn('business_locations', 'deleted_at')) {
                $locationQuery->whereNull('business_locations.deleted_at');
            }

            $allowed = ProductsNewTenantGuard::allowedLocationIds();
            if ($allowed === []) {
                $locationQuery->whereRaw('1 = 0');
            } elseif ($allowed !== ['all']) {
                $locationQuery->whereIn('business_locations.id', array_map('intval', $allowed));
            }

            $locations = $locationQuery
                ->orderBy(Schema::hasColumn('business_locations', 'name') ? 'name' : 'id')
                ->get();
        }

        $defaultLocationId = old('business_location_id');
        if (!$defaultLocationId && $locations->isNotEmpty()) {
            $defaultLocationId = (int) $locations->first()->id;
        }

        return view('productsnew::import_export.index', compact(
            'sessions',
            'locations',
            'defaultLocationId'
        ));
    }

    public function import(Request $request, ProductImportService $service)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'business_location_id' => 'nullable|integer',
        ]);

        // The active authenticated business is authoritative. Do not accept a
        // forged business_id from the browser for an import session.
        $session = $service->createSession($request->file('file'), [
            'business_id' => ProductsNewTenantGuard::businessId(),
            'business_location_id' => $request->input('business_location_id'),
        ]);

        $service->parseCsv($session, $request->file('file'));

        return redirect()
            ->route('products-new.import-export.review', $session->id)
            ->with('status', 'Import file validated successfully. Review the rows and click Commit / Repair Products to create the products.');
    }

    public function review(int $session, ProductImportService $service)
    {
        // Do not use implicit model binding here. The Products New tenant
        // middleware must run before the import-session row is resolved.
        $session = $service->findSessionForCurrentBusiness($session, true);

        $lines = ProductsNewImportLine::where('import_session_id', $session->id)
            ->orderBy('line_no')
            ->paginate(100);

        return view('productsnew::import_export.review', compact('session', 'lines'));
    }

    public function commit(int $session, ProductImportService $service)
    {
        // Resolve the session only after Products New tenancy is initialized.
        $session = $service->findSessionForCurrentBusiness($session, true);
        $result = $service->commit($session);

        $message = $result['created'] . ' product(s) imported successfully and verified in List Products.';
        if ($result['already_present'] > 0) {
            $message .= ' ' . $result['already_present'] . ' previously imported product(s) were already present and were not duplicated.';
        }

        return redirect()
            ->route('products-new.products.index')
            ->with('status', $message);
    }

    public function export(Request $request, ProductExportService $service)
    {
        return response()->streamDownload(function () use ($service, $request) {
            echo $service->csv($request->all());
        }, 'products-new-export.csv');
    }

    public function template(ProductImportTemplateService $service)
    {
        return response()->streamDownload(function () use ($service) {
            echo $service->csvTemplate();
        }, 'products-new-import-template.csv');
    }
}

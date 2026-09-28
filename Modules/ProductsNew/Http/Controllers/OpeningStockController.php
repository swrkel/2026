<?php
namespace Modules\ProductsNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Entities\ProductsNewOpeningStockSession;
use Modules\ProductsNew\Services\InventoryMovementService;
use Modules\ProductsNew\Services\OpeningStockImportService;
use Modules\ProductsNew\Services\OpeningStockService;

class OpeningStockController extends Controller
{
    public function __construct(
        protected OpeningStockService $service,
        protected InventoryMovementService $inventory
    ) {}

    public function index(Request $request)
    {
        $rows = $this->service->sessions($request->all())->paginate(50);
        return view('productsnew::opening_stock.index', [
            'rows' => $rows,
            'locations' => $this->inventory->locations(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'nullable|integer',
            'reference_no' => 'nullable|string|max:100',
            'session_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);
        $session = $this->service->createSession($data);
        return redirect()->route('products-new.opening-stock.show', $session->id)
            ->with('status', 'Opening stock session created. You may enter lines individually or import a CSV file.');
    }

    public function show(ProductsNewOpeningStockSession $opening_stock)
    {
        return view('productsnew::opening_stock.show', [
            'session' => $opening_stock,
            'products' => $this->inventory->products(),
            'locations' => $this->inventory->locations(),
        ]);
    }

    public function line(Request $request, ProductsNewOpeningStockSession $opening_stock)
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
            'variation_id' => 'nullable|integer',
            'qty' => 'required|numeric|min:0.001',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);
        $this->service->postLine($opening_stock, $data);
        return back()->with('status', 'Opening stock line posted and stock updated.');
    }

    public function template(OpeningStockImportService $importer)
    {
        return response()->streamDownload(
            fn () => print($importer->templateCsv()),
            'products-new-opening-stock-template.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    public function import(
        Request $request,
        ProductsNewOpeningStockSession $opening_stock,
        OpeningStockImportService $importer
    ) {
        $request->validate([
            'opening_stock_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $result = $importer->import($opening_stock, $request->file('opening_stock_file'));

        return back()->with(
            'status',
            sprintf('%d opening stock lines imported successfully. Total quantity: %s.', $result['lines'], number_format($result['qty'], 3))
        );
    }
}

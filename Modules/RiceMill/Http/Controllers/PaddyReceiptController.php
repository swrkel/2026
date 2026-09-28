<?php
namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\RiceMill\Models\{PaddyReceipt,PaddyVariety,PaddyPurchase};
use Modules\RiceMill\Services\{TenantContext,PaddyReceivingService,ExternalMasterDataService,NumberSeriesService,OperationalPaymentService};

class PaddyReceiptController extends BaseController
{
    public function __construct(
        TenantContext $context,
        private PaddyReceivingService $service,
        private ExternalMasterDataService $masters,
        private NumberSeriesService $numbers,
        private OperationalPaymentService $payments
    ) {
        parent::__construct($context);
    }

    public function index(Request $request)
    {
        $rows = $this->receiptRows($request);
        return view('RiceMill::receipts.index', compact('rows'));
    }

    public function create(Request $request)
    {
        $b = $this->bid();
        $varieties = PaddyVariety::forBusiness($b)
            ->where('active',1)
            ->orderBy('name')
            ->get([
                'id','code','name','default_moisture_percent','foreign_matter_limit_percent',
                'quality_grade','lot_opening_number'
            ]);

        // v27: one number-series query for every preview on the page instead of
        // one query per Paddy Variety plus separate Weighbridge/Receipt queries.
        $previewSpecs = [
            'weighbridge' => ['type'=>'weighbridge','prefix'=>'PD-WB-','opening'=>1],
            'receipt' => ['type'=>'paddy_receipt','prefix'=>'PD-RCV-','opening'=>1],
        ];
        foreach ($varieties as $variety) {
            $previewSpecs['variety_' . $variety->id] = [
                'type' => $this->numbers->varietyLotType((int) $variety->id),
                'prefix' => $this->numbers->varietyLotPrefix('PD', (string) $variety->code),
                'opening' => max(1, (int) ($variety->lot_opening_number ?: 1)),
            ];
        }
        $previews = $this->numbers->peekMany($b, $previewSpecs);
        foreach ($varieties as $variety) {
            $variety->setAttribute('lot_next_preview', $previews['variety_' . $variety->id]['preview']);
        }

        return view('RiceMill::receipts.form', [
            'suppliers'=>$this->masters->suppliers($b),
            'varieties'=>$varieties,
            'purchases'=>PaddyPurchase::forBusiness($b)
                ->where('status','approved')
                ->latest('id')
                ->get(['id','purchase_no']),
            'locations'=>$this->masters->businessLocations($b),
            'stores'=>($businessStores = $this->masters->businessStores($b)),
            'defaultStoreId'=>! empty($businessStores) ? (int) ($businessStores[0]['id'] ?? 0) : null,
            'receivedAtDefault'=>now()->format('Y-m-d\TH:i'),
            'weighbridgePreview'=>$previews['weighbridge']['preview'],
            'receiptPreview'=>$previews['receipt']['preview'],
            'receiptPaymentMap'=>$this->masters->paymentMethodAccounts($b,'purchase'),
            'rows'=>$this->receiptRows($request),
        ]);
    }

    private function receiptRows(Request $request)
    {
        $query = PaddyReceipt::forBusiness($this->bid())
            ->select([
                'id','business_id','receipt_no','weighbridge_entry_id','paddy_lot_id',
                'received_at','supplier_id','paddy_variety_id','vehicle_no','gross_weight',
                'tare_weight','net_weight','moisture_percent','quality_grade'
            ])
            ->with(['paddyLot:id,lot_no','variety:id,code,name','weighbridgeEntry:id,entry_no']);

        $this->applyListFilters(
            $query,
            $request,
            ['receipt_no','supplier_id','vehicle_no','quality_grade','net_weight'],
            'received_at',
            true
        );

        return $query
            ->latest('id')
            ->paginate($this->listPerPage($request,25))
            ->appends($request->query());
    }

    public function purchaseDetails(int $id)
    {
        $businessId = $this->bid();
        $purchase = PaddyPurchase::forBusiness($businessId)
            ->where('status', 'approved')
            ->select(['id','business_id','purchase_no','supplier_id','location_id','store_id'])
            ->findOrFail($id);

        $lines = DB::table('rcm_paddy_purchase_lines as pl')
            ->join('rcm_paddy_varieties as pv', function ($join) use ($businessId) {
                $join->on('pv.id', '=', 'pl.paddy_variety_id')
                    ->where('pv.business_id', '=', $businessId);
            })
            ->where('pl.business_id', $businessId)
            ->where('pl.purchase_id', $purchase->id)
            ->orderBy('pl.id')
            ->get([
                'pl.id', 'pl.paddy_variety_id', 'pl.net_weight',
                'pv.code as variety_code', 'pv.name as variety_name',
                'pv.default_moisture_percent', 'pv.foreign_matter_limit_percent', 'pv.quality_grade',
            ])
            ->map(static fn($row) => [
                'id' => (int) $row->id,
                'paddy_variety_id' => (int) $row->paddy_variety_id,
                'net_weight' => (float) $row->net_weight,
                'variety_code' => (string) $row->variety_code,
                'variety_name' => (string) $row->variety_name,
                'default_moisture_percent' => $row->default_moisture_percent !== null ? (float) $row->default_moisture_percent : null,
                'foreign_matter_limit_percent' => $row->foreign_matter_limit_percent !== null ? (float) $row->foreign_matter_limit_percent : null,
                'quality_grade' => $row->quality_grade,
            ])->values();

        return response()->json([
            'ok' => true,
            'purchase' => [
                'id' => (int) $purchase->id,
                'purchase_no' => (string) $purchase->purchase_no,
                'supplier_id' => (int) $purchase->supplier_id,
                'location_id' => $purchase->location_id ? (int) $purchase->location_id : null,
                'store_id' => $purchase->store_id ? (int) $purchase->store_id : null,
            ],
            'lines' => $lines,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'purchase_id'=>'nullable|integer',
            'supplier_id'=>'required|integer',
            'location_id'=>'nullable|integer',
            'store_id'=>'nullable|integer',
            'paddy_variety_id'=>'required|integer',
            'vehicle_no'=>'nullable|max:50',
            'gross_weight'=>'required|numeric|min:0.001',
            'tare_weight'=>'required|numeric|min:0',
            'moisture_percent'=>'nullable|numeric|min:0|max:100',
            'foreign_matter_percent'=>'nullable|numeric|min:0|max:100',
            'foreign_matter_limit_percent'=>'nullable|numeric|min:0|max:100',
            'quality_grade'=>'nullable|max:50',
            'received_at'=>'nullable|date',
            'note'=>'nullable|string',
            'payment_method'=>'nullable|string|max:60',
            'payment_account_id'=>'nullable|integer|min:1',
            'payment_amount'=>'nullable|numeric|min:0',
            'payment_note'=>'nullable|string|max:2000',
        ]);

        $businessId = $this->bid();
        if (!empty($data['purchase_id']) && !PaddyPurchase::forBusiness($businessId)->where('status','approved')->whereKey((int)$data['purchase_id'])->exists()) {
            abort(422, 'The selected Related Purchase Order is not available for this business.');
        }
        $locationId = ! empty($data['location_id']) ? (int) $data['location_id'] : null;
        $storeId = ! empty($data['store_id']) ? (int) $data['store_id'] : null;
        $this->masters->assertBusinessLocation($locationId, $businessId);
        $this->masters->assertBusinessStore($storeId, $businessId, $locationId);

        $paymentInput = [
            'payment_method'=>$data['payment_method'] ?? null,
            'payment_account_id'=>$data['payment_account_id'] ?? null,
            'payment_amount'=>$data['payment_amount'] ?? null,
            'payment_note'=>$data['payment_note'] ?? null,
        ];
        unset($data['payment_method'],$data['payment_account_id'],$data['payment_amount'],$data['payment_note']);
        $payment = $this->payments->normalise($businessId,'purchase',$paymentInput,$locationId);

        $x = DB::transaction(function () use ($businessId,$data,$payment) {
            $receipt = $this->service->receive($businessId,$this->uid(),$data);
            if ($payment) {
                $this->payments->record(
                    $businessId,
                    $this->uid(),
                    'paddy_receipt',
                    (int)$receipt->id,
                    'supplier_payment',
                    $payment,
                    [
                        'reference_no'=>(string)$receipt->receipt_no,
                        'purchase_id'=>$receipt->purchase_id ? (int)$receipt->purchase_id : null,
                        'supplier_id'=>(int)$receipt->supplier_id,
                        'transaction_date'=>substr((string)$receipt->received_at,0,10),
                        'location_id'=>$receipt->location_id ? (int)$receipt->location_id : null,
                        'store_id'=>$receipt->store_id ? (int)$receipt->store_id : null,
                    ],
                    true
                );
            }
            return $receipt;
        });

        return redirect()->route('rice-mill.receipts.index')->with('status','Paddy receipt '.$x->receipt_no.' saved and stock updated'.($payment ? ' with Payment recorded.' : '.'));
    }
}

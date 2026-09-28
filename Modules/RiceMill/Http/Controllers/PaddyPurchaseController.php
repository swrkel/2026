<?php
namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Modules\RiceMill\Models\{PaddyPurchase,PaddyVariety,Setting};
use Modules\RiceMill\Services\{TenantContext,PaddyPurchaseService,ExternalMasterDataService,NumberSeriesService,PermissionAccessService};

class PaddyPurchaseController extends BaseController
{
    public function __construct(
        TenantContext $context,
        private PaddyPurchaseService $service,
        private ExternalMasterDataService $masters,
        private NumberSeriesService $numbers,
        private PermissionAccessService $permissions
    ) {
        parent::__construct($context);
    }

    public function index(Request $request)
    {
        $businessId = $this->bid();
        $query = PaddyPurchase::forBusiness($businessId)
            ->select(['id','business_id','purchase_no','purchase_date','supplier_id','status','net_total','created_at']);

        $this->listTools()->applyContactSearch(
            $query,
            $request,
            $businessId,
            'supplier_id',
            ['purchase_no','purchase_date','status','net_total']
        );
        $this->listTools()->applyDate($query, $request, $businessId, 'purchase_date');

        $rows = $query->latest('id')->paginate($this->listPerPage($request, 25))->appends($request->query());
        $supplierNames = $this->masters->contactNamesByIds(
            $businessId,
            $rows->getCollection()->pluck('supplier_id')->all()
        );

        return view('RiceMill::purchases.index', [
            'rows' => $rows,
            'supplierNames' => $supplierNames,
            'canApprovePurchase' => $this->permissions->allows($request->user(), 'rice_mill.paddy_purchase.approve'),
        ]);
    }

    public function create()
    {
        $b = $this->bid();
        $settings = (array) optional(Setting::forBusiness($b)->first())->settings;
        $opening = max(1, (int) ($settings['paddy_purchase_opening_number'] ?? 1));
        $payableAccounts = $this->masters->currentLiabilityAccounts($b);
        $mappingEnabled = array_key_exists('paddy_product_category_mapping_enabled', $settings)
            ? (bool) $settings['paddy_product_category_mapping_enabled']
            : true;
        $payableId = $mappingEnabled ? (int) ($settings['paddy_payment_account_id'] ?? 0) : 0;
        $paymentMap = $this->masters->purchasePaymentMethodAccounts($b);

        // Keep the core Purchase-module Credit Purchase (Due) behaviour. The
        // selected Paddy Current Liabilities mapping is shown as its account even when the host
        // default-payment mapping does not contain a separate credit_purchase key.
        $businessLocations = $this->masters->businessLocations($b);
        $mappedPayable = collect($payableAccounts)->firstWhere('id', $payableId);
        if ($mappedPayable) {
            $paymentMap['methods']['credit_purchase'] = $paymentMap['methods']['credit_purchase'] ?? 'Credit Purchase (Due)';
            $paymentMap['accounts']['credit_purchase'][$payableId] = $mappedPayable['name'];
            foreach ($businessLocations as $location) {
                $locationId=(int)($location['id'] ?? 0);
                if ($locationId>0) {
                    $paymentMap['by_location'][$locationId]['credit_purchase'][$payableId]=$mappedPayable['name'];
                }
            }
        }

        $businessStores = $this->masters->businessStores($b);

        return view('RiceMill::purchases.form', [
            'suppliers'=>$this->masters->suppliers($b),
            'varieties'=>PaddyVariety::forBusiness($b)->where('active',1)->orderBy('name')->get(['id','code','name']),
            'locations'=>$businessLocations,
            'stores'=>$businessStores,
            'defaultStoreId'=>! empty($businessStores) ? (int) ($businessStores[0]['id'] ?? 0) : null,
            'purchaseNumberPreview'=>$this->numbers->peek($b,'paddy_purchase','PD-PUR-',$opening)['preview'],
            'purchasePaymentMap'=>$paymentMap,
            'paddyPayableAccounts'=>$payableAccounts,
            'paddyPayableAccountId'=>$payableId,
            'purchaseTaxes'=>$this->masters->purchaseTaxes($b),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'purchase_date'=>'required|date',
            'supplier_id'=>'required|integer',
            'location_id'=>'nullable|integer',
            'store_id'=>'nullable|integer',
            'other_charges'=>'nullable|numeric',
            'note'=>'nullable|string',
            'purchase_tax_id'=>'nullable|integer|min:1',
            'payment_method'=>'required|string|max:60',
            'payment_account_id'=>'required|integer|min:1',
            'cheque_number'=>'nullable|string|max:120|required_if:payment_method,cheque',
            'payment_note'=>'nullable|string|max:2000',
            'lines'=>'required|array|min:1',
            'lines.*.paddy_variety_id'=>'required|integer',
            'lines.*.net_weight'=>'required|numeric|min:0.001',
            'lines.*.unit_rate'=>'required|numeric|min:0',
            'lines.*.deduction_amount'=>'nullable|numeric|min:0',
        ]);

        $businessId = $this->bid();
        $locationId = ! empty($data['location_id']) ? (int) $data['location_id'] : null;
        $storeId = ! empty($data['store_id']) ? (int) $data['store_id'] : null;

        $this->masters->assertBusinessLocation($locationId, $businessId);
        $this->masters->assertBusinessStore($storeId, $businessId, $locationId);

        $tax = null;
        if (! empty($data['purchase_tax_id'])) {
            $tax = $this->masters->purchaseTaxById($businessId, (int) $data['purchase_tax_id']);
            if (! $tax) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'purchase_tax_id' => 'The selected Purchase Tax is not available for this business.',
                ]);
            }
        }
        $data['purchase_tax_percent'] = (float) ($tax['amount'] ?? 0);
        // Purchase Order stage: retain tax setup only. Do not calculate/post a
        // separate tax amount until the actual purchase/receipt is completed.
        $data['purchase_tax_amount'] = 0;

        $p = $this->service->create($businessId,$this->uid(),$data);
        return redirect()->route('rice-mill.purchases.index')->with('status','Purchase Order '.$p->purchase_no.' created. Purchase Payment was saved through the standard Purchase payment flow; Purchase Tax is retained for the actual purchase/receipt stage.');
    }

    public function approve($id)
    {
        $p=$this->service->approve($this->bid(),(int)$id,$this->uid());
        return back()->with('status','Purchase Order '.$p->purchase_no.' approved.');
    }
}

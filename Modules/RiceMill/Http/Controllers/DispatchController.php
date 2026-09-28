<?php
namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RiceMill\Models\{Dispatch,RiceProduct};
use Modules\RiceMill\Services\{
    TenantContext,
    DispatchService,
    ExternalMasterDataService,
    NumberSeriesService,
    PermissionAccessService,
    SalesInvoiceCalculator,
    SalesApprovalNotificationService,
    OperationalPaymentService
};

class DispatchController extends BaseController
{
    private const PREVIEW_SESSION_PREFIX = 'rice_mill.dispatch_previews.';
    private const PREVIEW_TTL_SECONDS = 7200;

    public function __construct(
        TenantContext $context,
        private DispatchService $service,
        private ExternalMasterDataService $masters,
        private NumberSeriesService $numbers,
        private PermissionAccessService $permissions,
        private SalesInvoiceCalculator $calculator,
        private SalesApprovalNotificationService $approvalNotifications,
        private OperationalPaymentService $payments
    ) {
        parent::__construct($context);
    }

    public function index(Request $request)
    {
        $businessId = $this->bid();
        $query = Dispatch::forBusiness($businessId)
            ->select(['id','business_id','dispatch_no','dispatch_date','customer_id','status','net_total','created_at']);

        $this->listTools()->applyContactSearch(
            $query,
            $request,
            $businessId,
            'customer_id',
            ['dispatch_no','dispatch_date','status','net_total']
        );
        $this->listTools()->applyDate($query, $request, $businessId, 'dispatch_date');

        $rows = $query->latest('id')->paginate($this->listPerPage($request,25))->appends($request->query());
        $customerNames = $this->masters->contactNamesByIds(
            $businessId,
            $rows->getCollection()->pluck('customer_id')->all()
        );

        return view('RiceMill::dispatch.index', compact('rows','customerNames'));
    }

    public function create()
    {
        $b=$this->bid();
        return view('RiceMill::dispatch.form', [
            'customers'=>$this->masters->customers($b),
            'products'=>RiceProduct::forBusiness($b)
                ->where('active',1)
                ->where('current_qty','>',0)
                ->orderBy('name')
                ->get(['id','name','current_qty']),
            'locations'=>$this->masters->locations($b),
            'stores'=>$this->masters->stores($b),
            'salesPaymentMap'=>$this->masters->paymentMethodAccounts($b,'sale'),
        ]);
    }

    /**
     * First step of the sales workflow. Nothing is posted yet: the submitted
     * sale is kept in this user's session and rendered as a Sales Invoice for
     * review. The user can then save a draft or approve the sale from that
     * invoice page.
     */
    public function preview(Request $request)
    {
        [$data,$lines,$payment] = $this->validatedPayload($request);
        $businessId = $this->bid();

        $this->masters->assertStore(
            !empty($data['store_id']) ? (int)$data['store_id'] : null,
            $businessId,
            !empty($data['location_id']) ? (int)$data['location_id'] : null
        );

        $invoice = $this->invoiceDataFromPayload($businessId, $data, $lines);
        $token = bin2hex(random_bytes(24));

        $request->session()->put(self::PREVIEW_SESSION_PREFIX.$token, [
            'business_id'=>$businessId,
            'user_id'=>$this->uid(),
            'created_at'=>time(),
            'data'=>$data,
            'lines'=>$lines,
            'payment'=>$payment,
        ]);

        $invoice['isPreview'] = true;
        $invoice['previewToken'] = $token;
        $invoice['invoiceNo'] = $this->numbers->peek($businessId,'dispatch','RCD-')['preview'];
        $invoice['status'] = 'Preview';
        $invoice['canApproveDispatch'] = $this->permissions->allows($request->user(), 'rice_mill.dispatch.approve');

        return view('RiceMill::dispatch.invoice', $invoice);
    }

    public function savePreview(Request $request, string $token)
    {
        $payload = $this->previewPayload($request, $token);
        $businessId = $this->bid();
        $dispatch = DB::transaction(function () use ($businessId,$payload) {
            $saved = $this->service->create(
                $businessId,
                $this->uid(),
                $payload['data'],
                $payload['lines']
            );
            if (!empty($payload['payment'])) {
                $this->recordDispatchPayment($businessId,$saved,$payload['payment'],false);
            }
            return $saved;
        });

        $request->session()->forget(self::PREVIEW_SESSION_PREFIX.$token);

        // This is a real Draft save, so honour the Settings switch and send an
        // in-app message to every user in this business who currently holds the
        // Sales Invoice approval permission. Direct Approve from preview does
        // not generate a redundant approval request.
        $notification = $this->approvalNotifications->notifyDraft($this->bid(), $dispatch, $this->uid());
        $message = 'Draft Sales Invoice '.$dispatch->dispatch_no.' saved.';
        if ($notification['enabled']) {
            if ($notification['sent'] > 0) {
                $message .= ' Approval notification sent to '.$notification['sent'].' permitted user'.($notification['sent'] === 1 ? '.' : 's.');
            } elseif ($notification['reason'] === 'no_permitted_users') {
                $message .= ' Auto notification is enabled, but no permitted Sales Approval user was found for this business.';
            } else {
                $message .= ' Auto notification is enabled, but the approval message could not be delivered.';
            }
        }

        return redirect()->route('rice-mill.dispatch.show',$dispatch->id)
            ->with('status',$message);
    }

    public function approvePreview(Request $request, string $token)
    {
        $payload = $this->previewPayload($request, $token);
        $businessId = $this->bid();
        $dispatch = DB::transaction(function () use ($businessId,$payload) {
            $approved = $this->service->createAndApprove(
                $businessId,
                $this->uid(),
                $payload['data'],
                $payload['lines']
            );
            if (!empty($payload['payment'])) {
                $this->recordDispatchPayment($businessId,$approved,$payload['payment'],true);
            }
            return $approved;
        });

        $request->session()->forget(self::PREVIEW_SESSION_PREFIX.$token);
        $this->approvalNotifications->markResolved($this->bid(), (int)$dispatch->id);

        return redirect()->route('rice-mill.dispatch.show',$dispatch->id)
            ->with('status','Sales Invoice '.$dispatch->dispatch_no.' approved and stock posted.');
    }

    public function show(Request $request, $id)
    {
        $businessId = $this->bid();
        $dispatch = Dispatch::forBusiness($businessId)
            ->with(['lines:id,business_id,dispatch_id,product_id,quantity,unit_price,unit_discount_type,unit_discount_value,unit_discount_amount,net_unit_price,line_total'])
            ->findOrFail((int)$id);

        $invoice = $this->invoiceDataFromDispatch($businessId, $dispatch);
        $invoice['isPreview'] = false;
        $invoice['previewToken'] = null;
        $invoice['canApproveDispatch'] = $this->permissions->allows($request->user(), 'rice_mill.dispatch.approve');

        return view('RiceMill::dispatch.invoice', $invoice);
    }

    public function approve($id)
    {
        $businessId=$this->bid();
        $dispatch=DB::transaction(function () use ($businessId,$id) {
            $approved=$this->service->approve($businessId,(int)$id,$this->uid());
            $this->payments->postIfRecorded($businessId,'dispatch',(int)$approved->id);
            return $approved;
        });
        $this->approvalNotifications->markResolved($businessId, (int)$dispatch->id);
        return redirect()->route('rice-mill.dispatch.show',$dispatch->id)
            ->with('status','Sales Invoice '.$dispatch->dispatch_no.' approved and stock posted.');
    }

    private function validatedPayload(Request $request): array
    {
        $data=$request->validate([
            'dispatch_date'=>'required|date',
            'customer_id'=>'required|integer',
            'location_id'=>'nullable|integer',
            'store_id'=>'nullable|integer',
            'vehicle_no'=>'nullable|max:60',
            'driver_name'=>'nullable|max:120',
            'discount_type'=>'required|in:percentage,fixed',
            'discount_value'=>'nullable|numeric|min:0',
            'tax_percent'=>'nullable|numeric|min:0|max:100',
            'note'=>'nullable|string',
            'payment_method'=>'nullable|string|max:60',
            'payment_account_id'=>'nullable|integer|min:1',
            'payment_amount'=>'nullable|numeric|min:0',
            'payment_note'=>'nullable|string|max:2000',
            'lines'=>'required|array|min:1',
            'lines.*.product_id'=>'required|integer',
            'lines.*.quantity'=>'required|numeric|min:0.001',
            'lines.*.unit_price'=>'required|numeric|min:0',
            'lines.*.unit_discount_type'=>'required|in:percentage,fixed',
            'lines.*.unit_discount_value'=>'nullable|numeric|min:0'
        ]);

        $lines=$data['lines'];
        $paymentInput=[
            'payment_method'=>$data['payment_method'] ?? null,
            'payment_account_id'=>$data['payment_account_id'] ?? null,
            'payment_amount'=>$data['payment_amount'] ?? null,
            'payment_note'=>$data['payment_note'] ?? null,
        ];
        unset($data['lines'],$data['payment_method'],$data['payment_account_id'],$data['payment_amount'],$data['payment_note']);
        foreach ($lines as $index => $line) {
            $lines[$index]['unit_discount_type'] = strtolower((string)($line['unit_discount_type'] ?? 'fixed'));
            $lines[$index]['unit_discount_value'] = (float)($line['unit_discount_value'] ?? 0);
            if ($lines[$index]['unit_discount_type'] === 'percentage' && $lines[$index]['unit_discount_value'] > 100) {
                throw ValidationException::withMessages([
                    'lines.'.$index.'.unit_discount_value'=>'Unit Percentage Discount cannot be more than 100%.',
                ]);
            }
        }

        $data['discount_type'] = strtolower((string)($data['discount_type'] ?? 'fixed'));
        $data['discount_value'] = (float)($data['discount_value'] ?? 0);
        $data['tax_percent'] = (float)($data['tax_percent'] ?? 0);

        if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
            throw ValidationException::withMessages([
                'discount_value'=>'Percentage discount cannot be more than 100%.',
            ]);
        }

        // Run the same authoritative server calculation used during the actual
        // save, so invalid fixed discounts are rejected before the preview is
        // stored in session.
        try {
            $this->calculator->calculate(
                $lines,
                $data['discount_type'],
                $data['discount_value'],
                $data['tax_percent']
            );
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
            $lower = strtolower($message);
            if (str_contains($lower, 'tax')) {
                $field = 'tax_percent';
            } elseif (str_contains($lower, 'unit discount')) {
                $field = 'lines.0.unit_discount_value';
            } else {
                $field = 'discount_value';
            }
            throw ValidationException::withMessages([$field=>$message]);
        }

        $payment=$this->payments->normalise(
            $this->bid(),
            'sale',
            $paymentInput,
            !empty($data['location_id']) ? (int)$data['location_id'] : null
        );

        return [$data,$lines,$payment];
    }

    private function recordDispatchPayment(int $businessId,Dispatch $dispatch,array $payment,bool $postNow): void
    {
        $this->payments->record(
            $businessId,
            $this->uid(),
            'dispatch',
            (int)$dispatch->id,
            'customer_payment',
            $payment,
            [
                'reference_no'=>(string)$dispatch->dispatch_no,
                'customer_id'=>(int)$dispatch->customer_id,
                'transaction_date'=>(string)$dispatch->dispatch_date,
                'location_id'=>$dispatch->location_id ? (int)$dispatch->location_id : null,
                'store_id'=>$dispatch->store_id ? (int)$dispatch->store_id : null,
                'invoice_total'=>(float)$dispatch->net_total,
            ],
            $postNow
        );
    }

    private function previewPayload(Request $request, string $token): array
    {
        abort_unless((bool)preg_match('/^[a-f0-9]{48}$/',$token),404);
        $key = self::PREVIEW_SESSION_PREFIX.$token;
        $payload = $request->session()->get($key);

        abort_unless(is_array($payload),410,'This Sales Invoice preview has expired. Please create it again.');
        abort_unless((int)($payload['business_id'] ?? 0) === $this->bid(),403);
        abort_unless((int)($payload['user_id'] ?? 0) === $this->uid(),403);

        if ((int)($payload['created_at'] ?? 0) < (time()-self::PREVIEW_TTL_SECONDS)) {
            $request->session()->forget($key);
            abort(410,'This Sales Invoice preview has expired. Please create it again.');
        }

        abort_unless(isset($payload['data'],$payload['lines']) && is_array($payload['data']) && is_array($payload['lines']),410);
        return $payload;
    }

    private function invoiceDataFromPayload(int $businessId, array $data, array $lines): array
    {
        $productIds = array_values(array_unique(array_map(static fn($line)=>(int)$line['product_id'],$lines)));
        $products = RiceProduct::forBusiness($businessId)
            ->whereIn('id',$productIds)
            ->get(['id','name','current_qty'])
            ->keyBy('id');
        abort_unless($products->count() === count($productIds),422,'One of the selected Rice Products is not available for this business.');

        $customerNames = $this->masters->contactNamesByIds($businessId,[(int)$data['customer_id']]);
        abort_unless(isset($customerNames[(int)$data['customer_id']]),422,'The selected customer is not available for this business.');

        $pricing = $this->calculator->calculate(
            $lines,
            (string)($data['discount_type'] ?? 'fixed'),
            (float)($data['discount_value'] ?? 0),
            (float)($data['tax_percent'] ?? 0)
        );

        $invoiceLines=[];
        foreach($lines as $index=>$line){
            $product=$products->get((int)$line['product_id']);
            $qty=(float)$line['quantity'];
            $rate=(float)$line['unit_price'];
            $linePricing=$pricing['line_results'][$index] ?? [];
            $invoiceLines[]=[
                'product_id'=>(int)$product->id,
                'product_name'=>(string)$product->name,
                'quantity'=>$qty,
                'unit_price'=>$rate,
                'gross_line_total'=>$linePricing['gross_line_total'] ?? round($qty*$rate,4),
                'unit_discount_type'=>$linePricing['unit_discount_type'] ?? 'fixed',
                'unit_discount_value'=>$linePricing['unit_discount_value'] ?? 0,
                'unit_discount_per_unit'=>$linePricing['unit_discount_per_unit'] ?? 0,
                'unit_discount_amount'=>$linePricing['unit_discount_amount'] ?? 0,
                'net_unit_price'=>$linePricing['net_unit_price'] ?? $rate,
                'line_total'=>$linePricing['line_total'] ?? round($qty*$rate,4),
                'available_qty'=>(float)$product->current_qty,
            ];
        }

        $locationName=null;
        if(!empty($data['location_id'])){
            foreach($this->masters->locations($businessId) as $location){
                if((int)$location['id']===(int)$data['location_id']){
                    $locationName=(string)$location['name'];
                    break;
                }
            }
        }

        $dateTime = Carbon::parse((string)$data['dispatch_date'].' '.now()->format('H:i:s'));

        return [
            'dispatch'=>null,
            'invoiceNo'=>null,
            'invoiceDateTime'=>$dateTime,
            'customerName'=>$customerNames[(int)$data['customer_id']],
            'locationName'=>$locationName,
            'storeName'=>$this->masters->businessStoreName($businessId,!empty($data['store_id'])?(int)$data['store_id']:null),
            'vehicleNo'=>$data['vehicle_no'] ?? null,
            'driverName'=>$data['driver_name'] ?? null,
            'note'=>$data['note'] ?? null,
            'invoiceLines'=>$invoiceLines,
            'subtotal'=>$pricing['subtotal'],
            'unitDiscountAmount'=>$pricing['unit_discount_amount'],
            'subtotalAfterUnitDiscount'=>$pricing['subtotal_after_unit_discount'],
            'discountType'=>$pricing['discount_type'],
            'discountValue'=>$pricing['discount_value'],
            'discountAmount'=>$pricing['discount_amount'],
            'taxableAmount'=>$pricing['taxable_amount'],
            'taxPercent'=>$pricing['tax_percent'],
            'taxAmount'=>$pricing['tax_amount'],
            'netTotal'=>$pricing['net_total'],
        ];
    }

    private function invoiceDataFromDispatch(int $businessId, Dispatch $dispatch): array
    {
        $productIds=$dispatch->lines->pluck('product_id')->map(static fn($id)=>(int)$id)->unique()->values()->all();
        $products=RiceProduct::forBusiness($businessId)
            ->whereIn('id',$productIds)
            ->get(['id','name','current_qty'])
            ->keyBy('id');
        $customerNames=$this->masters->contactNamesByIds($businessId,[(int)$dispatch->customer_id]);

        $invoiceLines=[];
        foreach($dispatch->lines as $line){
            $product=$products->get((int)$line->product_id);
            $unitDiscountType=strtolower((string)($line->getAttribute('unit_discount_type') ?: 'fixed'));
            if(!in_array($unitDiscountType,['fixed','percentage'],true)){$unitDiscountType='fixed';}
            $unitDiscountValue=(float)($line->getAttribute('unit_discount_value') ?? 0);
            $unitDiscountAmount=(float)($line->getAttribute('unit_discount_amount') ?? 0);
            $netUnitPrice=$line->getAttribute('net_unit_price');
            $netUnitPrice=$netUnitPrice !== null ? (float)$netUnitPrice : (float)$line->unit_price;
            $qty=(float)$line->quantity;
            $grossLineTotal=round($qty*(float)$line->unit_price,4);
            $unitDiscountPerUnit=$qty>0 ? round($unitDiscountAmount/$qty,4) : 0;
            $invoiceLines[]=[
                'product_id'=>(int)$line->product_id,
                'product_name'=>$product ? (string)$product->name : ('Rice Product #'.$line->product_id),
                'quantity'=>$qty,
                'unit_price'=>(float)$line->unit_price,
                'gross_line_total'=>$grossLineTotal,
                'unit_discount_type'=>$unitDiscountType,
                'unit_discount_value'=>$unitDiscountValue,
                'unit_discount_per_unit'=>$unitDiscountPerUnit,
                'unit_discount_amount'=>$unitDiscountAmount,
                'net_unit_price'=>$netUnitPrice,
                'line_total'=>(float)$line->line_total,
                'available_qty'=>$product ? (float)$product->current_qty : null,
            ];
        }

        $locationName=null;
        if($dispatch->location_id){
            try {
                $locationName=(string)(\Illuminate\Support\Facades\DB::table('business_locations')
                    ->where('business_id',$businessId)
                    ->where('id',(int)$dispatch->location_id)
                    ->value('name') ?? '');
                if($locationName===''){$locationName=null;}
            } catch(\Throwable $e) {
                $locationName=null;
            }
        }

        $date=(string)$dispatch->dispatch_date->format('Y-m-d');
        $time=$dispatch->created_at ? $dispatch->created_at->format('H:i:s') : '00:00:00';
        $subtotal=(float)$dispatch->subtotal;
        $unitDiscountAmount=(float)($dispatch->getAttribute('unit_discount_amount') ?? 0);
        $subtotalAfterUnitDiscount=max(0,round($subtotal-$unitDiscountAmount,4));
        $discountAmount=(float)$dispatch->discount_amount;
        $taxAmount=(float)$dispatch->tax_amount;
        $discountType=strtolower((string)($dispatch->getAttribute('discount_type') ?: 'fixed'));
        if(!in_array($discountType,['fixed','percentage'],true)){$discountType='fixed';}
        $discountValue=$dispatch->getAttribute('discount_value');
        $discountValue=$discountValue !== null ? (float)$discountValue : $discountAmount;
        $taxableAmount=max(0,round($subtotalAfterUnitDiscount-$discountAmount,4));
        $taxPercent=$dispatch->getAttribute('tax_percent');
        if($taxPercent === null){
            $taxPercent=$taxableAmount>0 ? ($taxAmount/$taxableAmount)*100 : 0;
        }

        return [
            'dispatch'=>$dispatch,
            'invoiceNo'=>(string)$dispatch->dispatch_no,
            'invoiceDateTime'=>Carbon::parse($date.' '.$time),
            'customerName'=>$customerNames[(int)$dispatch->customer_id] ?? ('Customer #'.$dispatch->customer_id),
            'locationName'=>$locationName,
            'storeName'=>$this->masters->businessStoreName($businessId,$dispatch->store_id?(int)$dispatch->store_id:null),
            'vehicleNo'=>$dispatch->vehicle_no,
            'driverName'=>$dispatch->driver_name,
            'note'=>$dispatch->note,
            'invoiceLines'=>$invoiceLines,
            'subtotal'=>$subtotal,
            'unitDiscountAmount'=>$unitDiscountAmount,
            'subtotalAfterUnitDiscount'=>$subtotalAfterUnitDiscount,
            'discountType'=>$discountType,
            'discountValue'=>(float)$discountValue,
            'discountAmount'=>$discountAmount,
            'taxableAmount'=>$taxableAmount,
            'taxPercent'=>(float)$taxPercent,
            'taxAmount'=>$taxAmount,
            'netTotal'=>(float)$dispatch->net_total,
            'status'=>(string)$dispatch->status,
        ];
    }
}

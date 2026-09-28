<?php
namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RiceMill\Models\{ProductionBatch,PaddyLot,Mill,RiceProduct};
use Modules\RiceMill\Services\{ProductionService,ExternalMasterDataService,TenantContext,PermissionAccessService,OutputTypeProductService,OperationalPaymentService};

class ProductionController extends BaseController
{
    public function __construct(
        TenantContext $context,
        private ProductionService $service,
        private ExternalMasterDataService $masters,
        private OutputTypeProductService $outputTypeProducts,
        private OperationalPaymentService $payments
    ) {
        parent::__construct($context);
    }

    /**
     * Normal Production menu entry. Operators with both Create + Complete
     * permissions go straight to the unified operation page; view-only users
     * fall back to Production History instead of receiving a 403.
     */
    public function entry()
    {
        $access=app(PermissionAccessService::class);
        $user=auth()->user();

        if ($access->allows($user,'rice_mill.production.create')
            && $access->allows($user,'rice_mill.production.complete')) {
            return redirect()->route('rice-mill.production.create');
        }

        return redirect()->route('rice-mill.production.index');
    }

    /**
     * Production history remains available at /rice-mill/production.  The
     * normal sidebar entry opens the unified operation directly (see v34
     * sidebar change), while this history page gives users a View action for
     * every batch and a Complete action for unfinished batches.
     */
    public function index(Request $request)
    {
        $businessId=$this->bid();
        $paddySub=DB::table('rcm_production_inputs as pi')
            ->join('rcm_paddy_lots as l',function($join) use($businessId){
                $join->on('l.id','=','pi.paddy_lot_id')
                    ->where('l.business_id','=',$businessId);
            })
            ->leftJoin('rcm_paddy_varieties as pv',function($join) use($businessId){
                $join->on('pv.id','=','l.paddy_variety_id')
                    ->where('pv.business_id','=',$businessId);
            })
            ->where('pi.business_id',$businessId)
            ->groupBy('pi.production_batch_id')
            ->selectRaw("pi.production_batch_id, GROUP_CONCAT(DISTINCT TRIM(CONCAT(COALESCE(pv.name,''), CASE WHEN pv.code IS NOT NULL AND pv.code <> '' THEN CONCAT(' (',pv.code,')') ELSE '' END)) ORDER BY pv.name SEPARATOR ', ') as paddy_names");

        $query=ProductionBatch::forBusiness($businessId)
            ->leftJoinSub($paddySub,'paddy_source',function($join){
                $join->on('paddy_source.production_batch_id','=','rcm_production_batches.id');
            })
            ->select([
                'rcm_production_batches.id','rcm_production_batches.business_id','rcm_production_batches.batch_no',
                'rcm_production_batches.status','rcm_production_batches.started_at','rcm_production_batches.completed_at',
                'rcm_production_batches.input_qty','rcm_production_batches.rice_output_qty',
                'rcm_production_batches.rice_yield_percent','rcm_production_batches.cost_per_kg',
                'paddy_source.paddy_names'
            ]);

        // Standard list filtering remains server-side. Paddy name is also
        // searchable through an EXISTS branch without turning the main query
        // into an N+1 lookup.
        $term=$this->listTools()->searchTerm($request);
        if($term!==''){
            $like='%'.str_replace(['\\','%','_'],['\\\\','\%','\_'],$term).'%';
            $query->where(function($where) use($like,$businessId){
                $where->where('rcm_production_batches.batch_no','like',$like)
                    ->orWhere('rcm_production_batches.status','like',$like)
                    ->orWhere('rcm_production_batches.input_qty','like',$like)
                    ->orWhere('rcm_production_batches.rice_output_qty','like',$like)
                    ->orWhere('rcm_production_batches.rice_yield_percent','like',$like)
                    ->orWhere('rcm_production_batches.cost_per_kg','like',$like)
                    ->orWhereExists(function($sub) use($like,$businessId){
                        $sub->select(DB::raw(1))
                            ->from('rcm_production_inputs as spi')
                            ->join('rcm_paddy_lots as sl','sl.id','=','spi.paddy_lot_id')
                            ->leftJoin('rcm_paddy_varieties as spv','spv.id','=','sl.paddy_variety_id')
                            ->whereColumn('spi.production_batch_id','rcm_production_batches.id')
                            ->where('spi.business_id',$businessId)
                            ->where('sl.business_id',$businessId)
                            ->where(function($p) use($like){
                                $p->where('spv.name','like',$like)->orWhere('spv.code','like',$like)->orWhere('sl.lot_no','like',$like);
                            });
                    });
            });
        }
        $this->listTools()->applyDate($query,$request,$businessId,'rcm_production_batches.started_at',true);

        $rows=$query->latest('id')
            ->paginate($this->listPerPage($request,25))
            ->appends($request->query());

        return view('RiceMill::production.index',compact('rows'));
    }

    /**
     * Read-only full batch view.  All queries are tenant-connection +
     * business scoped so a crafted URL cannot cross the active business.
     */
    public function show($id)
    {
        $businessId=$this->bid();

        $batch=ProductionBatch::forBusiness($businessId)
            ->select([
                'id','business_id','batch_no','status','location_id','store_id','mill_id',
                'started_at','completed_at','input_qty','rice_output_qty','total_output_qty',
                'process_loss_qty','rice_yield_percent','production_cost','cost_per_kg',
                'note','created_by','completed_by','created_at','updated_at'
            ])
            ->findOrFail((int)$id);

        $inputs=DB::table('rcm_production_inputs as pi')
            ->leftJoin('rcm_paddy_lots as l',function($join) use($businessId){
                $join->on('l.id','=','pi.paddy_lot_id')
                    ->where('l.business_id','=',$businessId);
            })
            ->leftJoin('rcm_paddy_varieties as pv',function($join) use($businessId){
                $join->on('pv.id','=','l.paddy_variety_id')
                    ->where('pv.business_id','=',$businessId);
            })
            ->where('pi.business_id',$businessId)
            ->where('pi.production_batch_id',$batch->id)
            ->orderBy('pi.id')
            ->get([
                'pi.id','pi.paddy_lot_id','pi.quantity',
                'l.lot_no','l.paddy_variety_id',
                'pv.code as paddy_code','pv.name as paddy_name'
            ]);

        $outputs=DB::table('rcm_production_outputs as po')
            ->leftJoin('rcm_products as p',function($join) use($businessId){
                $join->on('p.id','=','po.product_id')
                    ->where('p.business_id','=',$businessId);
            })
            ->where('po.business_id',$businessId)
            ->where('po.production_batch_id',$batch->id)
            ->orderBy('po.id')
            ->get([
                'po.id','po.output_type','po.product_id','po.quantity','po.unit_cost',
                'p.code as product_code','p.name as product_name'
            ]);

        $outputLabels=$this->outputTypeProducts->typeLabels(
            $businessId,
            $outputs->pluck('output_type')->map(static fn($type)=>(string)$type)->all()
        );

        $costs=DB::table('rcm_cost_entries')
            ->where('business_id',$businessId)
            ->where('production_batch_id',$batch->id)
            ->orderBy('id')
            ->get(['id','cost_date','cost_type','amount','note']);

        $millName=null;
        if ($batch->mill_id) {
            $millName=Mill::forBusiness($businessId)
                ->whereKey((int)$batch->mill_id)
                ->value('name');
        }

        $locationName=null;
        if ($batch->location_id) {
            $locationName=DB::table('business_locations')
                ->where('business_id',$businessId)
                ->where('id',(int)$batch->location_id)
                ->value('name');
        }

        $storeName=$this->masters->businessStoreName(
            $businessId,
            $batch->store_id ? (int)$batch->store_id : null
        );

        $userNames=[];
        $wantedUserIds=array_values(array_unique(array_filter([
            (int)($batch->created_by??0),
            (int)($batch->completed_by??0),
        ])));
        if ($wantedUserIds) {
            foreach ($this->masters->users($businessId) as $user) {
                if (in_array((int)$user['id'],$wantedUserIds,true)) {
                    $userNames[(int)$user['id']]=(string)$user['name'];
                }
            }
        }

        return view('RiceMill::production.show',compact(
            'batch','inputs','outputs','costs','millName','locationName','storeName','userNames','outputLabels'
        ));
    }

    /**
     * Open the unified Milling / production Operation page.  If an unfinished
     * batch already exists for this business, resume it instead of silently
     * creating another draft on a GET request.
     */
    public function create()
    {
        $b=$this->bid();

        $openBatch=ProductionBatch::forBusiness($b)
            ->whereIn('status',['draft','in_progress'])
            ->latest('id')
            ->first(['id']);

        if ($openBatch) {
            return redirect()->route('rice-mill.production.complete-form',$openBatch->id);
        }

        $batch=new ProductionBatch();
        $batch->started_at=now();

        return view('RiceMill::production.complete', array_merge(
            $this->operationData($b),
            [
                'batch'=>$batch,
                'isNewOperation'=>true,
            ]
        ));
    }

    /**
     * Save a brand-new milling operation in one action.  Batch creation and
     * completion are inside one outer transaction so a failed stock/output
     * posting does not leave an accidental draft batch behind.
     */
    public function store(Request $r)
    {
        $d=$this->validateOperation($r);
        $businessId=$this->bid();
        $this->validateBatchMasterData($d,$businessId);
        $payment=$this->pullOperationalPayment($d,$businessId);
        $d=$this->resolveMappedOutputs($d,$businessId);

        $batchData=$this->batchData($d);

        $batch=DB::transaction(function() use($businessId,$d,$batchData,$payment){
            $created=$this->service->create($businessId,$this->uid(),$batchData);

            $completed=$this->service->complete(
                $businessId,
                (int)$created->id,
                $this->uid(),
                $d['inputs'],
                $d['outputs'],
                $d['costs']??[],
                $batchData
            );

            if ($payment) {
                $this->recordProductionPayment($businessId,$completed,$payment);
            }

            return $completed;
        });

        return redirect()->route('rice-mill.production.create')
            ->with('status','Milling / production operation '.$batch->batch_no.' completed. Yield '.number_format($batch->rice_yield_percent,2).'%.');
    }

    /** Resume a legacy/existing unfinished batch on the same unified page. */
    public function completeForm($id)
    {
        $b=$this->bid();
        $batch=ProductionBatch::forBusiness($b)
            ->select(['id','business_id','batch_no','location_id','store_id','mill_id','status','started_at','note'])
            ->findOrFail($id);

        return view('RiceMill::production.complete', array_merge(
            $this->operationData($b),
            [
                'batch'=>$batch,
                'isNewOperation'=>false,
            ]
        ));
    }

    public function complete(Request $r,$id)
    {
        $d=$this->validateOperation($r);
        $businessId=$this->bid();
        $this->validateBatchMasterData($d,$businessId);
        $payment=$this->pullOperationalPayment($d,$businessId);
        $d=$this->resolveMappedOutputs($d,$businessId);
        $batchData=$this->batchData($d);

        $b=DB::transaction(function () use ($businessId,$id,$d,$batchData,$payment) {
            $completed=$this->service->complete(
                $businessId,
                (int)$id,
                $this->uid(),
                $d['inputs'],
                $d['outputs'],
                $d['costs']??[],
                $batchData
            );

            if ($payment) {
                $this->recordProductionPayment($businessId,$completed,$payment);
            }

            return $completed;
        });

        return redirect()->route('rice-mill.production.create')
            ->with('status','Milling / production operation '.$b->batch_no.' completed. Yield '.number_format($b->rice_yield_percent,2).'%.');
    }

    /** Shared master data for both a new operation and an existing open batch. */
    private function operationData(int $businessId): array
    {
        $lots=PaddyLot::forBusiness($businessId)
            ->leftJoin('rcm_paddy_varieties as pv', function ($join) use ($businessId) {
                $join->on('pv.id', '=', 'rcm_paddy_lots.paddy_variety_id')
                    ->where('pv.business_id', '=', $businessId);
            })
            ->where('rcm_paddy_lots.balance_qty','>',0)
            ->orderBy('rcm_paddy_lots.received_date')
            ->get([
                'rcm_paddy_lots.id','rcm_paddy_lots.lot_no','rcm_paddy_lots.balance_qty',
                'rcm_paddy_lots.received_date','rcm_paddy_lots.paddy_variety_id',
                'pv.code as paddy_code','pv.name as paddy_name',
                'pv.expected_rice_yield_percent','pv.expected_broken_rice_percent',
                'pv.expected_bran_percent','pv.expected_husk_percent','pv.expected_process_loss_percent',
            ]);

        $stores = $this->masters->stores($businessId);

        return [
            'lots'=>$lots,
            'products'=>RiceProduct::forBusiness($businessId)
                ->where('active',1)
                ->orderBy('name')
                ->get(['id','name','code','paddy_variety_id']),
            'mills'=>Mill::forBusiness($businessId)
                ->where('active',1)
                ->orderBy('name')
                ->get(['id','name','location_id']),
            'locations'=>$this->masters->locations($businessId),
            'stores'=>$stores,
            'defaultStoreId'=>! empty($stores) ? (int) ($stores[0]['id'] ?? 0) : null,
            'outputTypes'=>$this->outputTypeProducts->productionDefinitions($businessId),
            'productionPaymentMap'=>$this->masters->paymentMethodAccounts($businessId,'expense'),
        ];
    }

    private function validateOperation(Request $r): array
    {
        return $r->validate([
            'location_id'=>'nullable|integer',
            'store_id'=>'nullable|integer',
            'mill_id'=>'nullable|integer',
            'started_at'=>'nullable|date',
            'note'=>'nullable|string',
            'inputs'=>'required|array|min:1',
            'inputs.*.paddy_lot_id'=>'required|integer',
            'inputs.*.quantity'=>'required|numeric|min:0.001',
            'outputs'=>'required|array|min:1',
            'outputs.*.source_product_id'=>'required|integer|distinct',
            'outputs.*.quantity'=>'required|numeric|min:0',
            'costs'=>'nullable|array',
            'costs.*.cost_type'=>'required_with:costs.*.amount|max:80',
            'costs.*.amount'=>'nullable|numeric|min:0',
            'costs.*.note'=>'nullable|max:255',
            'payment_method'=>'nullable|string|max:60',
            'payment_account_id'=>'nullable|integer|min:1',
            'payment_amount'=>'nullable|numeric|min:0',
            'payment_note'=>'nullable|string|max:2000'
        ]);
    }

    private function validateBatchMasterData(array $data,int $businessId): void
    {
        $this->masters->assertStore(
            !empty($data['store_id'])?(int)$data['store_id']:null,
            $businessId,
            !empty($data['location_id'])?(int)$data['location_id']:null
        );

        if (! empty($data['mill_id'])) {
            $millQuery = Mill::forBusiness($businessId)
                ->where('active',1)
                ->whereKey((int) $data['mill_id']);
            if (! empty($data['location_id'])) {
                $millQuery->where('location_id', (int) $data['location_id']);
            }
            if (! $millQuery->exists()) {
                throw ValidationException::withMessages([
                    'mill_id'=>'The selected Mill is not linked to the selected Location for this business.',
                ]);
            }
        }
    }

    /**
     * Never trust hidden browser values for Production Outputs. The current
     * Settings > Product Category Mapping > Out Put Type mapping is the source
     * of truth. Convert the posted Products New ids into the internal output
     * type / Rice Product ids server-side.
     */
    private function resolveMappedOutputs(array $data, int $businessId): array
    {
        $definitions = $this->outputTypeProducts->definitionMap($businessId);
        if (! $definitions) {
            throw ValidationException::withMessages([
                'outputs' => 'No Out Put Type Products are mapped. Configure Rice Mill / Settings / Product Category Mapping / Out Put Type first.',
            ]);
        }

        $resolved = [];
        foreach (($data['outputs'] ?? []) as $index => $output) {
            $sourceId = (int) ($output['source_product_id'] ?? 0);
            $definition = $definitions[$sourceId] ?? null;
            if (! $definition) {
                throw ValidationException::withMessages([
                    "outputs.$index.source_product_id" => 'This Output Product is not mapped in Rice Mill Settings / Product Category Mapping / Out Put Type.',
                ]);
            }

            $resolved[] = [
                'source_product_id' => $sourceId,
                'output_type' => (string) $definition['output_type'],
                'product_id' => ! empty($definition['rice_product_id']) ? (int) $definition['rice_product_id'] : null,
                'quantity' => (float) ($output['quantity'] ?? 0),
            ];
        }

        $data['outputs'] = $resolved;
        return $data;
    }

    // Output Products are controlled by the explicit Out Put Type mapping.
    // Paddy Lot selection controls input stock and yield suggestions only; it no
    // longer overrides or blocks an explicitly mapped Output Product.

    private function pullOperationalPayment(array &$data,int $businessId): ?array
    {
        $input=[
            'payment_method'=>$data['payment_method'] ?? null,
            'payment_account_id'=>$data['payment_account_id'] ?? null,
            'payment_amount'=>$data['payment_amount'] ?? null,
            'payment_note'=>$data['payment_note'] ?? null,
        ];
        unset($data['payment_method'],$data['payment_account_id'],$data['payment_amount'],$data['payment_note']);

        return $this->payments->normalise(
            $businessId,
            'expense',
            $input,
            !empty($data['location_id']) ? (int)$data['location_id'] : null
        );
    }

    private function recordProductionPayment(int $businessId,ProductionBatch $batch,array $payment): void
    {
        $this->payments->record(
            $businessId,
            $this->uid(),
            'production_batch',
            (int)$batch->id,
            'production_expense_payment',
            $payment,
            [
                'reference_no'=>(string)$batch->batch_no,
                'transaction_date'=>substr((string)($batch->completed_at ?: $batch->started_at ?: now()),0,10),
                'location_id'=>$batch->location_id ? (int)$batch->location_id : null,
                'store_id'=>$batch->store_id ? (int)$batch->store_id : null,
                'mill_id'=>$batch->mill_id ? (int)$batch->mill_id : null,
            ],
            true
        );
    }

    private function batchData(array $data): array
    {
        return [
            'location_id'=>$data['location_id']??null,
            'store_id'=>$data['store_id']??null,
            'mill_id'=>$data['mill_id']??null,
            'started_at'=>$data['started_at']??null,
            'note'=>$data['note']??null,
        ];
    }
}

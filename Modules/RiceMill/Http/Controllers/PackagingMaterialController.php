<?php
namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\RiceMill\Models\{PackagingMaterial,PackagingMaterialMovement};
use Modules\RiceMill\Services\{ExternalMasterDataService,TenantContext,PackagingMaterialProductSyncService};

class PackagingMaterialController extends BaseController
{
    public function __construct(
        TenantContext $context,
        private ExternalMasterDataService $masters,
        private PackagingMaterialProductSyncService $packagingProductSync
    ) {
        parent::__construct($context);
    }

    public function index(Request $request)
    {
        $businessId = $this->bid();

        // Only Products explicitly saved in Settings > Product Category
        // Mapping > Packaging Material are part of the operational Packaging
        // Material list. Legacy/manual rows stay untouched in the database but
        // are hidden until they are selected through that mapping workflow.
        $selections = $this->packagingProductSync->syncSavedSelections($businessId, $this->uid());
        $mappedMaterialIds = $this->packagingProductSync->materialIds($selections);

        $query=PackagingMaterial::forBusiness($businessId)
            ->whereIn('id',$mappedMaterialIds)
            ->select(['id','business_id','code','name','unit','current_qty','active','note']);
        $this->listTools()->applySearch($query,$request,['code','name','unit','note']);
        $rows=$query->orderByDesc('active')->orderBy('name')->paginate($this->listPerPage($request,25))->appends($request->query());

        // The Packaging Materials screen mirrors Products New Products. Display
        // the exact Products New Stock Center Available quantity instead of the
        // Rice Mill legacy material balance.
        $productByMaterial=[];
        foreach ($selections as $selection) {
            $materialId=(int)($selection['material_id'] ?? 0);
            $productId=(int)($selection['product_id'] ?? 0);
            if ($materialId>0 && $productId>0) {
                $productByMaterial[$materialId]=$productId;
            }
        }
        $availableByProduct=$this->masters->productsNewAvailableStockByProduct(
            $businessId,
            array_values($productByMaterial)
        );
        $rows->getCollection()->transform(function ($row) use ($productByMaterial,$availableByProduct) {
            $productId=$productByMaterial[(int)$row->id] ?? 0;
            $row->setAttribute(
                'products_new_current_qty',
                $productId>0 ? (float)($availableByProduct[$productId] ?? 0) : (float)$row->current_qty
            );
            return $row;
        });

        return view('RiceMill::packaging-materials.index',compact('rows'));
    }

    public function store(Request $request)
    {
        $b=$this->bid();
        $d=$request->validate([
            'code'=>'nullable|string|max:40',
            'name'=>['required','string','max:150',Rule::unique('rcm_packaging_materials','name')->where(fn($q)=>$q->where('business_id',$b))],
            'unit'=>'required|string|max:30',
            'opening_qty'=>'nullable|numeric|min:0',
            'note'=>'nullable|string|max:1000',
        ]);
        DB::transaction(function() use($d,$b){
            $opening=(float)($d['opening_qty']??0);
            $material=PackagingMaterial::create([
                'business_id'=>$b,
                'code'=>$d['code']??null,
                'name'=>$d['name'],
                'unit'=>$d['unit'],
                'current_qty'=>$opening,
                'active'=>1,
                'note'=>$d['note']??null,
                'created_by'=>$this->uid(),
            ]);
            if($opening>0){
                PackagingMaterialMovement::create([
                    'business_id'=>$b,
                    'material_id'=>$material->id,
                    'movement_date'=>today(),
                    'movement_type'=>'opening_stock',
                    'quantity'=>$opening,
                    'signed_quantity'=>$opening,
                    'reference_type'=>'opening_stock',
                    'reference_id'=>$material->id,
                    'note'=>'Opening stock',
                    'created_by'=>$this->uid(),
                ]);
            }
        });
        return back()->with('status','Packaging material added.');
    }

    public function toggle(int $id)
    {
        $m=PackagingMaterial::forBusiness($this->bid())->findOrFail($id);
        $m->update(['active'=>!$m->active]);
        return back()->with('status','Packaging material status updated.');
    }

    public function ledger(Request $request,int $id)
    {
        $material=PackagingMaterial::forBusiness($this->bid())->findOrFail($id);
        $query=PackagingMaterialMovement::forBusiness($this->bid())
            ->where('material_id',$material->id)
            ->with(['packingBatch:id,business_id,packing_no'])
            ->select(['id','business_id','material_id','location_id','store_id','movement_date','movement_type','quantity','signed_quantity','packing_batch_id','reference_type','reference_id','note','created_by']);
        $this->applyListFilters($query,$request,['movement_type','note','reference_type'],'movement_date');
        $rows=$query->orderBy('movement_date')->orderBy('id')->paginate($this->listPerPage($request,25))->appends($request->query());
        $locations=$this->masters->locations($this->bid());
        $stores=$this->masters->stores($this->bid());
        return view('RiceMill::packaging-materials.ledger',compact('material','rows','locations','stores'));
    }

    public function adjust(Request $request,int $id)
    {
        $b=$this->bid();
        $d=$request->validate([
            'movement_type'=>'required|in:adjustment_in,adjustment_out',
            'quantity'=>'required|numeric|min:0.0001',
            'movement_date'=>'nullable|date',
            'note'=>'required|string|max:1000',
            'location_id'=>'nullable|integer',
            'store_id'=>'nullable|integer',
        ]);
        $this->masters->assertStore(!empty($d['store_id'])?(int)$d['store_id']:null,$b,!empty($d['location_id'])?(int)$d['location_id']:null);

        DB::transaction(function() use($b,$id,$d){
            $m=PackagingMaterial::forBusiness($b)->lockForUpdate()->findOrFail($id);
            $qty=(float)$d['quantity'];
            $out=$d['movement_type']==='adjustment_out';
            if($out && (float)$m->current_qty+0.0000001<$qty){
                throw ValidationException::withMessages([
                    'quantity'=>'Adjustment cannot exceed available '.$m->name.' stock. Available: '.number_format((float)$m->current_qty,(int)app(\Modules\RiceMill\Services\BusinessPrecisionService::class)->forBusiness($b)['quantity']).' '.$m->unit.'.',
                ]);
            }
            $signed=$out?-$qty:$qty;
            $m->update(['current_qty'=>(float)$m->current_qty+$signed]);
            PackagingMaterialMovement::create([
                'business_id'=>$b,
                'material_id'=>$m->id,
                'location_id'=>$d['location_id']??null,
                'store_id'=>$d['store_id']??null,
                'movement_date'=>$d['movement_date']??today()->toDateString(),
                'movement_type'=>$d['movement_type'],
                'quantity'=>$qty,
                'signed_quantity'=>$signed,
                'reference_type'=>'stock_adjustment',
                'reference_id'=>$m->id,
                'note'=>$d['note'],
                'created_by'=>$this->uid(),
            ]);
        });
        return back()->with('status','Packaging material stock adjustment posted.');
    }
}

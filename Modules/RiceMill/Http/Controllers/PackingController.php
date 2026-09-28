<?php
namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\RiceMill\Models\{PackingBatch,RiceProduct,PackagingMaterialMapping};
use Modules\RiceMill\Services\{PackingService,ExternalMasterDataService,TenantContext};

class PackingController extends BaseController
{
    public function __construct(
        TenantContext $context,
        private PackingService $service,
        private ExternalMasterDataService $masters
    ) {
        parent::__construct($context);
    }

    public function index(Request $request)
    {
        $b=$this->bid();
        // v36 can reconstruct source links for older packing rows once the new
        // trace table exists, so the Production Batch column is useful for both
        // historical and newly-created packing operations.
        $this->service->ensureLegacySources($b);

        $query=PackingBatch::forBusiness($b)
            ->with([
                'lines:id,business_id,packing_batch_id,product_id,bag_size_kg,bag_count,total_qty',
                'lines.product:id,business_id,name',
                'lines.sources:id,business_id,packing_line_id,production_batch_id,quantity',
                'lines.sources.productionBatch:id,business_id,batch_no',
            ])
            ->select(['id','business_id','packing_no','packed_at','status','total_packed_qty']);

        $term=$this->listTools()->searchTerm($request);
        if($term!==''){
            $like='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$term).'%';
            $query->where(function($q) use($like){
                $q->where('packing_no','like',$like)
                  ->orWhere('status','like',$like)
                  ->orWhere('total_packed_qty','like',$like)
                  ->orWhereHas('lines.product',fn($p)=>$p->where('name','like',$like))
                  ->orWhereHas('lines.sources.productionBatch',fn($pb)=>$pb->where('batch_no','like',$like));
            });
        }
        $this->listTools()->applyDate($query,$request,$b,'packed_at',true);
        $rows=$query->latest('id')->paginate($this->listPerPage($request,25))->appends($request->query());
        return view('RiceMill::packing.index',compact('rows'));
    }

    public function create()
    {
        $b=$this->bid();
        $products=RiceProduct::forBusiness($b)
            ->where('active',1)
            ->orderBy('name')
            ->get(['id','name','current_qty']);
        $availableToPack=$this->service->availableToPack($b);
        foreach($products as $product){
            $product->setAttribute('available_to_pack',(float)($availableToPack[(int)$product->id]??0));
        }

        $materialMappings=[];
        if(Schema::hasTable('rcm_packaging_material_mappings')){
            $mappings=PackagingMaterialMapping::forBusiness($b)
                ->where('active',1)
                ->with('material:id,business_id,name,unit,current_qty,active')
                ->whereHas('material',fn($q)=>$q->where('active',1))
                ->orderBy('product_id')
                ->orderBy('bag_size_kg')
                ->get(['id','business_id','product_id','bag_size_kg','material_id','usage_per_bag']);
            foreach($mappings as $mapping){
                $key=(int)$mapping->product_id.'|'.number_format((float)$mapping->bag_size_kg,3,'.','');
                $materialMappings[$key][]=[
                    'material_id'=>(int)$mapping->material_id,
                    'name'=>$mapping->material?->name ?? ('Material #'.$mapping->material_id),
                    'unit'=>$mapping->material?->unit ?? '',
                    'available'=>(float)($mapping->material?->current_qty ?? 0),
                    'usage_per_bag'=>(float)$mapping->usage_per_bag,
                ];
            }
        }

        return view('RiceMill::packing.form', [
            'products'=>$products,
            'locations'=>$this->masters->locations($b),
            'stores'=>$this->masters->stores($b),
            'materialMappings'=>$materialMappings,
        ]);
    }

    public function store(Request $r)
    {
        $d=$r->validate([
            'location_id'=>'nullable|integer',
            'store_id'=>'nullable|integer',
            'packed_at'=>'nullable|date',
            'note'=>'nullable|string',
            'lines'=>'required|array|min:1',
            'lines.*.product_id'=>'required|integer',
            'lines.*.bag_size_kg'=>'required|numeric|min:0.001',
            'lines.*.bag_count'=>'required|integer|min:1'
        ]);
        $lines=$d['lines'];
        unset($d['lines']);
        $b=$this->bid();
        $this->masters->assertStore(!empty($d['store_id'])?(int)$d['store_id']:null,$b,!empty($d['location_id'])?(int)$d['location_id']:null);
        $x=$this->service->create($b,$this->uid(),$d,$lines);
        return redirect()->route('rice-mill.packing.index')->with('status','Packing batch '.$x->packing_no.' saved.');
    }
}

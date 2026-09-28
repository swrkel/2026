<?php
namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RiceMill\Models\{PackagingMaterial,PackagingMaterialMapping,RiceProduct};
use Modules\RiceMill\Services\{TenantContext,PackagingMaterialProductSyncService};

class PackagingMaterialMappingController extends BaseController
{
    public function __construct(
        TenantContext $context,
        private PackagingMaterialProductSyncService $packagingProductSync
    ){ parent::__construct($context); }

    public function index(Request $request)
    {
        $b=$this->bid();
        $mappedMaterialIds=$this->packagingProductSync->materialIds(
            $this->packagingProductSync->syncSavedSelections($b,$this->uid())
        );
        $query=PackagingMaterialMapping::forBusiness($b)
            ->whereIn('material_id',$mappedMaterialIds)
            ->with(['product:id,business_id,name','material:id,business_id,name,unit'])
            ->select(['id','business_id','product_id','bag_size_kg','material_id','usage_per_bag','active','created_by']);
        $term=$this->listTools()->searchTerm($request);
        if($term!==''){
            $query->where(function($q) use($term){
                $like='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$term).'%';
                $q->where('bag_size_kg','like',$like)
                  ->orWhereHas('product',fn($p)=>$p->where('name','like',$like))
                  ->orWhereHas('material',fn($m)=>$m->where('name','like',$like)->orWhere('unit','like',$like));
            });
        }
        $rows=$query->orderBy('product_id')->orderBy('bag_size_kg')->orderBy('material_id')
            ->paginate($this->listPerPage($request,25))->appends($request->query());
        $products=RiceProduct::forBusiness($b)->where('active',1)->orderBy('name')->get(['id','name']);
        $materials=PackagingMaterial::forBusiness($b)
            ->where('active',1)
            ->whereIn('id',$mappedMaterialIds)
            ->orderBy('name')
            ->get(['id','name','unit','current_qty']);
        return view('RiceMill::packaging-material-mappings.index',compact('rows','products','materials'));
    }

    public function store(Request $request)
    {
        $b=$this->bid();

        /*
         * Settings / Material Usage Mapping supports a bulk entry form: the
         * Rice Product and Bag Size are selected once and every active
         * Packaging Material is shown underneath with its own Usage per Bag.
         * Keep the legacy single-material payload supported because the
         * standalone mapping page still posts material_id + usage_per_bag.
         */
        if ($request->has('material_usages')) {
            $d=$request->validate([
                'product_id'=>'required|integer',
                'bag_size_kg'=>'required|numeric|min:0.001',
                'material_usages'=>'required|array',
                'material_usages.*'=>'nullable|numeric|min:0',
            ]);

            abort_unless(
                RiceProduct::forBusiness($b)->where('active',1)->whereKey($d['product_id'])->exists(),
                422,
                'Rice Product is not available.'
            );

            $entered=[];
            foreach ((array)$d['material_usages'] as $materialId=>$usage) {
                $materialId=(int)$materialId;
                if ($materialId<=0 || $usage===null || $usage==='' || (float)$usage<=0) {
                    continue;
                }
                $entered[$materialId]=(float)$usage;
            }

            if (empty($entered)) {
                throw ValidationException::withMessages([
                    'material_usages'=>'Enter Usage per Bag for at least one Packaging Material.',
                ]);
            }

            $mappedMaterialIds=$this->packagingProductSync->materialIds(
                $this->packagingProductSync->syncSavedSelections($b,$this->uid())
            );
            $validMaterialIds=PackagingMaterial::forBusiness($b)
                ->where('active',1)
                ->whereIn('id',$mappedMaterialIds)
                ->whereIn('id',array_keys($entered))
                ->pluck('id')
                ->map(fn($id)=>(int)$id)
                ->all();

            if (count($validMaterialIds)!==count($entered)) {
                throw ValidationException::withMessages([
                    'material_usages'=>'One or more selected Packaging Materials are not mapped in Settings or are inactive.',
                ]);
            }

            DB::transaction(function() use($b,$d,$entered){
                foreach ($entered as $materialId=>$usage) {
                    PackagingMaterialMapping::updateOrCreate([
                        'business_id'=>$b,
                        'product_id'=>(int)$d['product_id'],
                        'bag_size_kg'=>(float)$d['bag_size_kg'],
                        'material_id'=>(int)$materialId,
                    ],[
                        'usage_per_bag'=>$usage,
                        'active'=>1,
                        'created_by'=>$this->uid(),
                    ]);
                }
            });

            $target = $request->boolean('return_to_settings')
                ? route('rice-mill.settings.index').'#rcm-settings-material-usage-mapping'
                : route('rice-mill.packaging-material-mappings.index');

            $count=count($entered);
            return redirect()->to($target)->with(
                'status',
                $count.' Material Usage '.($count===1?'Mapping':'Mappings').' saved.'
            );
        }

        $d=$request->validate([
            'product_id'=>'required|integer',
            'bag_size_kg'=>'required|numeric|min:0.001',
            'material_id'=>'required|integer',
            'usage_per_bag'=>'required|numeric|min:0.0001',
        ]);
        abort_unless(RiceProduct::forBusiness($b)->where('active',1)->whereKey($d['product_id'])->exists(),422,'Rice Product is not available.');
        $mappedMaterialIds=$this->packagingProductSync->materialIds(
            $this->packagingProductSync->syncSavedSelections($b,$this->uid())
        );
        abort_unless(
            in_array((int)$d['material_id'],$mappedMaterialIds,true)
            && PackagingMaterial::forBusiness($b)->where('active',1)->whereKey($d['material_id'])->exists(),
            422,
            'Packaging material is not mapped in Settings > Product Category Mapping > Packaging Material.'
        );

        PackagingMaterialMapping::updateOrCreate([
            'business_id'=>$b,
            'product_id'=>(int)$d['product_id'],
            'bag_size_kg'=>(float)$d['bag_size_kg'],
            'material_id'=>(int)$d['material_id'],
        ],[
            'usage_per_bag'=>(float)$d['usage_per_bag'],
            'active'=>1,
            'created_by'=>$this->uid(),
        ]);
        $target = $request->boolean('return_to_settings')
            ? route('rice-mill.settings.index').'#rcm-settings-material-usage-mapping'
            : route('rice-mill.packaging-material-mappings.index');
        return redirect()->to($target)->with('status','Material Usage Mapping saved.');
    }

    public function destroy(Request $request, int $id)
    {
        PackagingMaterialMapping::forBusiness($this->bid())->findOrFail($id)->delete();
        $target = $request->boolean('return_to_settings')
            ? route('rice-mill.settings.index').'#rcm-settings-material-usage-mapping'
            : route('rice-mill.packaging-material-mappings.index');
        return redirect()->to($target)->with('status','Material Usage Mapping removed.');
    }
}

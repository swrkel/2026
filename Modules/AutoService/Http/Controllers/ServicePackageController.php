<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServicePackageCategory;
use Modules\AutoService\Entities\AutoServicePackageLine;
use Modules\AutoService\Entities\AutoServiceServicePackage;
use Modules\AutoService\Services\ProductPartsAdapter;

class ServicePackageController extends AutoServiceBaseController
{
    public function index(Request $request)
    {
        $q=AutoServiceServicePackage::with('category');
        if($this->businessId()) $q->where('business_id',$this->businessId());
        if($request->filled('search')) $q->where(fn($x)=>$x->where('name','like','%'.$request->search.'%')->orWhere('package_code','like','%'.$request->search.'%'));
        if($request->filled('category_id')) $q->where('category_id',$request->category_id);
        return view('autoservice::packages.index',[
            'packages'=>$q->orderBy('name')->paginate(25)->withQueryString(),
            'categories'=>AutoServicePackageCategory::where('business_id',$this->businessId())->orderBy('name')->get()
        ]);
    }

    public function create(){ return view('autoservice::packages.form',$this->formData(new AutoServiceServicePackage())); }
    public function store(Request $r){ $this->save($r); return redirect()->route('autoservice.packages.index')->with('status','Service package saved successfully.'); }
    public function edit($id){ return view('autoservice::packages.form',$this->formData(AutoServiceServicePackage::with('lines')->where('business_id',$this->businessId())->findOrFail($id))); }
    public function update(Request $r,$id){ $this->save($r,$id); return redirect()->route('autoservice.packages.index')->with('status','Service package updated successfully.'); }

    public function payload($id)
    {
        $package=AutoServiceServicePackage::with('lines')->where('business_id',$this->businessId())->where('is_active',1)->findOrFail($id);
        return response()->json(app(\Modules\AutoService\Services\AutoServicePackageManagerService::class)->packagePayload($package));
    }

    public function storeCategory(Request $request)
    {
        $data=$request->validate(['name'=>'required|string|max:150','code'=>'nullable|string|max:50']);
        AutoServicePackageCategory::updateOrCreate(
            ['business_id'=>$this->businessId(),'code'=>$data['code'] ?: str($data['name'])->slug('_')],
            ['location_id'=>$this->locationId(),'name'=>$data['name'],'is_active'=>1]
        );
        return back()->with('status','Package category saved.');
    }

    private function formData($package): array
    {
        return [
            'package'=>$package,
            'categories'=>AutoServicePackageCategory::where('business_id',$this->businessId())->where('is_active',1)->orderBy('name')->get(),
            'stockItems'=>app(ProductPartsAdapter::class)->packageStockItems($this->businessId(),$this->locationId()),
        ];
    }

    private function save(Request $r,$id=null)
    {
        $r->validate(['name'=>'required|string|max:190','lines'=>'array']);
        return DB::transaction(function() use($r,$id){
            $p=$id
                ? AutoServiceServicePackage::where('business_id',$this->businessId())->findOrFail($id)
                : new AutoServiceServicePackage();

            $p->fill($r->only([
                'package_code','name','category_id','vehicle_type','vehicle_brand','vehicle_model',
                'description','estimated_minutes','warranty_days','package_discount','package_tax','selling_price'
            ]));
            $p->business_id=$this->businessId();
            $p->location_id=$this->locationId();
            $p->is_active=$r->boolean('is_active');
            $p->save();

            AutoServicePackageLine::where('package_id',$p->id)->delete();
            $parts=$labour=$nonStock=$subtotal=0;

            foreach($r->input('lines',[]) as $i=>$line){
                if(empty($line['description']) && empty($line['product_id'])) continue;
                $type=$line['component_type'] ?? 'non_stock_item';
                $qty=(float)($line['quantity'] ?? 1);
                $unit=(float)($line['unit_price'] ?? 0);
                $discount=(float)($line['discount_amount'] ?? 0);
                $tax=(float)($line['tax_amount'] ?? 0);
                $total=max(0,$qty*$unit-$discount+$tax);

                AutoServicePackageLine::create([
                    'business_id'=>$p->business_id,'location_id'=>$p->location_id,'package_id'=>$p->id,
                    'component_type'=>$type,'line_type'=>$type,'product_id'=>$line['product_id'] ?: null,
                    'variation_id'=>$line['variation_id'] ?: null,'item_name'=>$line['description'] ?? null,
                    'description'=>$line['description'] ?? null,'quantity'=>$qty,'unit_name'=>$line['unit_name'] ?? null,
                    'unit_price'=>$unit,'discount_amount'=>$discount,'tax_amount'=>$tax,'line_total'=>$total,
                    'is_optional'=>!empty($line['is_optional']),'is_stock_item'=>$type==='stock_item',
                    'sort_order'=>$i,
                ]);

                $subtotal += $total;
                if($type==='stock_item') $parts += $total;
                elseif($type==='labour') $labour += $total;
                else $nonStock += $total;
            }

            $p->parts_amount=$parts;
            $p->labour_amount=$labour;
            $p->non_stock_amount=$nonStock;
            $p->subtotal_amount=$subtotal;
            if(!$p->selling_price) $p->selling_price=max(0,$subtotal-(float)$p->package_discount+(float)$p->package_tax);
            $p->total_amount=$p->selling_price;
            $p->save();
            return $p;
        });
    }
}

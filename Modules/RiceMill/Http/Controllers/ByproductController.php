<?php
namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RiceMill\Models\ByproductMovement;
use Modules\RiceMill\Services\{ByproductStockService,OutputTypeProductService};

class ByproductController extends BaseController
{
    public function index(Request $request)
    {
        $stock = app(ByproductStockService::class)->currentStock($this->bid(), $request);
        $summary = $stock['summary'];
        $rows = $stock['rows'];
        $types = [];
        foreach ($summary as $row) { $types[] = (string) ($row['byproduct_type'] ?? ''); }
        foreach ($rows as $row) { $types[] = (string) ($row['byproduct_type'] ?? ''); }
        $typeLabels = app(OutputTypeProductService::class)->typeLabels($this->bid(), array_values(array_unique(array_filter($types))));
        return view('RiceMill::byproducts.index', compact('summary', 'rows', 'typeLabels'));
    }

    public function ledger(Request $request,string $type)
    {
        $businessId=$this->bid();
        $rows=ByproductMovement::forBusiness($businessId)
            ->where('byproduct_type',$type)
            ->orderByDesc('id')
            ->select(['id','business_id','movement_date','movement_type','quantity','signed_quantity','reference_type','reference_id','note']);
        $this->applyListFilters($rows,$request,['movement_type','reference_type','reference_id','note','quantity','signed_quantity'],'movement_date');
        $rows=$rows->paginate($this->listPerPage($request,50))->appends($request->query());

        $ledgerSources=app(ByproductStockService::class)->ledgerSourceAllocations($businessId,$type);
        $typeLabels=app(OutputTypeProductService::class)->typeLabels($businessId,[$type]);
        $typeLabel=$typeLabels[$type] ?? ucwords(str_replace('_',' ',$type));

        return view('RiceMill::byproducts.ledger',compact('rows','type','ledgerSources','typeLabel'));
    }

    public function adjust(Request $r,string $type)
    {
        $d=$r->validate([
            'movement_type'=>'required|in:adjustment_in,adjustment_out,sale,free_issue,dispose',
            'quantity'=>'required|numeric|min:0.001',
            'note'=>'required|max:500'
        ]);
        $b=$this->bid();
        $out=in_array($d['movement_type'],['adjustment_out','sale','free_issue','dispose'],true);

        DB::transaction(function() use($b,$type,$d,$out){
            // Lock this by-product type's movement rows before checking the total
            // so two simultaneous issues cannot both consume the same balance.
            ByproductMovement::forBusiness($b)
                ->where('byproduct_type',$type)
                ->lockForUpdate()
                ->get(['id']);

            $balance=(float)ByproductMovement::forBusiness($b)
                ->where('byproduct_type',$type)
                ->sum('signed_quantity');
            $qty=(float)$d['quantity'];
            if($out && $balance+0.0000001<$qty){
                $labels=app(OutputTypeProductService::class)->typeLabels($b,[$type]);
                $label=$labels[$type] ?? ucwords(str_replace('_',' ',$type));
                throw ValidationException::withMessages([
                    'quantity'=>'Insufficient '.$label.' stock. Available: '.number_format(max(0,$balance),3).' kg.',
                ]);
            }

            ByproductMovement::create([
                'business_id'=>$b,
                'byproduct_type'=>$type,
                'movement_date'=>today(),
                'movement_type'=>$d['movement_type'],
                'quantity'=>$qty,
                'signed_quantity'=>$out?-$qty:$qty,
                'note'=>$d['note'],
                'created_by'=>$this->uid()
            ]);
        });

        return back()->with('status','By-product movement posted.');
    }
}

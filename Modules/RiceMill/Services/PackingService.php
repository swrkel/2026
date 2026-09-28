<?php
namespace Modules\RiceMill\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\RiceMill\Models\{PackingBatch,RiceProduct,PackagingMaterial};

class PackingService
{
    public function __construct(private NumberSeriesService $numbers, private BusinessPrecisionService $precision) {}

    public function create(int $businessId,int $userId,array $data,array $lines): PackingBatch
    {
        return DB::transaction(function() use($businessId,$userId,$data,$lines){
            $batch=PackingBatch::create(array_merge($data,[
                'business_id'=>$businessId,
                'packing_no'=>$this->numbers->next($businessId,'packing','RPK-'),
                'status'=>'completed',
                'packed_at'=>$data['packed_at']??now(),
                'created_by'=>$userId,
            ]));

            $productIds=array_values(array_unique(array_map(static fn($line)=>(int)$line['product_id'],$lines)));
            $products=RiceProduct::forBusiness($businessId)
                ->where('active',1)
                ->whereIn('id',$productIds)
                ->lockForUpdate()
                ->get(['id','name','current_qty'])
                ->keyBy('id');
            abort_unless($products->count()===count($productIds),422,'One of the selected Rice Products is not available for this business.');

            $requestedByProduct=[];
            foreach($lines as $line){
                $productId=(int)$line['product_id'];
                $requestedByProduct[$productId]=($requestedByProduct[$productId]??0)
                    + ((int)$line['bag_count'] * (float)$line['bag_size_kg']);
            }

            $available=$this->availableToPackForProducts($businessId,$products);
            foreach($requestedByProduct as $productId=>$requestedQty){
                $limit=(float)($available[$productId]??0);
                if($requestedQty>$limit+0.0000001){
                    $product=$products->get($productId);
                    throw ValidationException::withMessages([
                        'lines'=>sprintf(
                            'Packing quantity for %s cannot exceed the available quantity. Available: %s kg; requested: %s kg.',
                            $product?->name ?? ('Product #'.$productId),
                            number_format(max(0,$limit),3,'.',''),
                            number_format($requestedQty,3,'.','')
                        ),
                    ]);
                }
            }

            // Calculate the configured bags/thread/etc. before lines are saved.
            // One material can be mapped more than once across submitted packing
            // lines, so requirements are aggregated and stock is locked once.
            $materialRequirements=$this->materialRequirements($businessId,$lines);
            $quantityPrecision=(int)$this->precision->forBusiness($businessId)['quantity'];
            $materials=collect();
            if($materialRequirements){
                $materials=PackagingMaterial::forBusiness($businessId)
                    ->where('active',1)
                    ->whereIn('id',array_keys($materialRequirements))
                    ->lockForUpdate()
                    ->get(['id','name','unit','current_qty'])
                    ->keyBy('id');
                abort_unless($materials->count()===count($materialRequirements),422,'One or more mapped packaging materials are inactive or unavailable.');
                foreach($materialRequirements as $materialId=>$requiredQty){
                    $material=$materials->get($materialId);
                    $availableMaterial=(float)$material->current_qty;
                    if($requiredQty>$availableMaterial+0.0000001){
                        throw ValidationException::withMessages([
                            'lines'=>sprintf(
                                'Insufficient %s for this packing operation. Available: %s %s; required: %s %s.',
                                $material->name,
                                number_format(max(0,$availableMaterial),$quantityPrecision,'.',''),
                                $material->unit,
                                number_format($requiredQty,$quantityPrecision,'.',''),
                                $material->unit
                            ),
                        ]);
                    }
                }
            }

            $total=0.0;
            $now=now();
            $savedLines=[];
            foreach($lines as $line){
                $productId=(int)$line['product_id'];
                $bags=(int)$line['bag_count'];
                $size=(float)$line['bag_size_kg'];
                $qty=$bags*$size;
                $total+=$qty;
                $lineId=DB::table('rcm_packing_lines')->insertGetId([
                    'business_id'=>$businessId,
                    'packing_batch_id'=>$batch->id,
                    'product_id'=>$productId,
                    'bag_size_kg'=>$size,
                    'bag_count'=>$bags,
                    'total_qty'=>$qty,
                    'created_at'=>$now,
                    'updated_at'=>$now,
                ]);
                $savedLines[]=['id'=>(int)$lineId,'product_id'=>$productId,'quantity'=>$qty];
            }

            if(Schema::hasTable('rcm_packing_sources')){
                foreach($savedLines as $savedLine){
                    $this->allocateProductionSources(
                        $businessId,
                        (int)$savedLine['id'],
                        (int)$savedLine['product_id'],
                        (float)$savedLine['quantity'],
                        $batch->packed_at ?? now()
                    );
                }
            }

            if($materialRequirements){
                $movementRows=[];
                foreach($materialRequirements as $materialId=>$requiredQty){
                    $material=$materials->get($materialId);
                    $material->update(['current_qty'=>(float)$material->current_qty-$requiredQty]);
                    $movementRows[]=[
                        'business_id'=>$businessId,
                        'material_id'=>(int)$materialId,
                        'location_id'=>$batch->location_id,
                        'store_id'=>$batch->store_id,
                        'movement_date'=>($batch->packed_at ?? now())->toDateString(),
                        'movement_type'=>'packing_consumption',
                        'quantity'=>$requiredQty,
                        'signed_quantity'=>-$requiredQty,
                        'packing_batch_id'=>$batch->id,
                        'reference_type'=>'packing_batch',
                        'reference_id'=>$batch->id,
                        'note'=>'Auto consumption for packing operation '.$batch->packing_no,
                        'created_by'=>$userId,
                        'created_at'=>$now,
                        'updated_at'=>$now,
                    ];
                }
                DB::table('rcm_packaging_material_movements')->insert($movementRows);
            }

            $batch->update(['total_packed_qty'=>$total]);
            return $batch;
        });
    }

    /** @return array<int,float> keyed by packaging material id */
    public function materialRequirements(int $businessId,array $lines): array
    {
        if(!Schema::hasTable('rcm_packaging_material_mappings')){
            return [];
        }
        $productIds=array_values(array_unique(array_map(static fn($line)=>(int)$line['product_id'],$lines)));
        if(!$productIds){ return []; }

        $mappings=DB::table('rcm_packaging_material_mappings')
            ->where('business_id',$businessId)
            ->where('active',1)
            ->whereIn('product_id',$productIds)
            ->get(['product_id','bag_size_kg','material_id','usage_per_bag']);

        $required=[];
        foreach($lines as $line){
            $productId=(int)$line['product_id'];
            $bagSize=(float)$line['bag_size_kg'];
            $bagCount=(int)$line['bag_count'];
            foreach($mappings as $mapping){
                if((int)$mapping->product_id!==$productId){ continue; }
                if(abs((float)$mapping->bag_size_kg-$bagSize)>0.0005){ continue; }
                $materialId=(int)$mapping->material_id;
                $required[$materialId]=($required[$materialId]??0)+((float)$mapping->usage_per_bag*$bagCount);
            }
        }
        return $required;
    }

    private function allocateProductionSources(int $businessId,int $packingLineId,int $productId,float $quantity,$packedAt): void
    {
        $alreadyAllocated=DB::table('rcm_packing_sources as ps')
            ->join('rcm_packing_lines as pl','pl.id','=','ps.packing_line_id')
            ->where('ps.business_id',$businessId)
            ->where('pl.product_id',$productId)
            ->whereNotNull('ps.production_batch_id')
            ->groupBy('ps.production_batch_id')
            ->select('ps.production_batch_id',DB::raw('SUM(ps.quantity) as qty'))
            ->pluck('qty','ps.production_batch_id');

        $sources=DB::table('rcm_finished_stock_movements as fsm')
            ->join('rcm_production_batches as pb','pb.id','=','fsm.reference_id')
            ->where('fsm.business_id',$businessId)
            ->where('fsm.product_id',$productId)
            ->where('fsm.movement_type','production')
            ->where('fsm.reference_type','production_batch')
            ->where('fsm.signed_quantity','>',0)
            ->where(function($q) use($packedAt){
                $date=\Illuminate\Support\Carbon::parse($packedAt)->toDateString();
                $q->whereDate('fsm.movement_date','<=',$date)->orWhereNull('fsm.movement_date');
            })
            ->groupBy('pb.id','pb.batch_no','pb.completed_at')
            ->orderBy('pb.completed_at')
            ->orderBy('pb.id')
            ->get(['pb.id','pb.batch_no','pb.completed_at',DB::raw('SUM(fsm.quantity) as produced_qty')]);

        $remaining=$quantity;
        $rows=[];
        $now=now();
        foreach($sources as $source){
            if($remaining<=0.0000001){ break; }
            $available=max(0.0,(float)$source->produced_qty-(float)($alreadyAllocated[(int)$source->id]??0));
            if($available<=0.0000001){ continue; }
            $take=min($available,$remaining);
            $rows[]=[
                'business_id'=>$businessId,
                'packing_line_id'=>$packingLineId,
                'production_batch_id'=>(int)$source->id,
                'quantity'=>$take,
                'created_at'=>$now,
                'updated_at'=>$now,
            ];
            $remaining-=$take;
        }

        // Keep the quantity trace complete even when stock originated from a
        // manual positive adjustment rather than a milling/production batch.
        if($remaining>0.0000001){
            $rows[]=[
                'business_id'=>$businessId,
                'packing_line_id'=>$packingLineId,
                'production_batch_id'=>null,
                'quantity'=>$remaining,
                'created_at'=>$now,
                'updated_at'=>$now,
            ];
        }
        if($rows){ DB::table('rcm_packing_sources')->insert($rows); }
    }

    /**
     * Backfill source links for legacy packing lines created before v36. This is
     * idempotent and is only invoked from the packing history page.
     */
    public function ensureLegacySources(int $businessId): void
    {
        if(!Schema::hasTable('rcm_packing_sources')){ return; }
        $missing=DB::table('rcm_packing_lines as pl')
            ->leftJoin('rcm_packing_sources as ps','ps.packing_line_id','=','pl.id')
            ->where('pl.business_id',$businessId)
            ->whereNull('ps.id')
            ->exists();
        if(!$missing){ return; }

        DB::transaction(function() use($businessId){
            $lines=DB::table('rcm_packing_lines as pl')
                ->join('rcm_packing_batches as pb','pb.id','=','pl.packing_batch_id')
                ->leftJoin('rcm_packing_sources as ps','ps.packing_line_id','=','pl.id')
                ->where('pl.business_id',$businessId)
                ->whereNull('ps.id')
                ->orderBy('pb.packed_at')
                ->orderBy('pl.id')
                ->get(['pl.id','pl.product_id','pl.total_qty','pb.packed_at']);
            foreach($lines as $line){
                $this->allocateProductionSources($businessId,(int)$line->id,(int)$line->product_id,(float)$line->total_qty,$line->packed_at??now());
            }
        });
    }

    /**
     * Available quantity that can still be packaged without repackaging the
     * same physical stock. Returned values are keyed by Rice Product ID.
     */
    private function availableToPackForProducts(int $businessId,$products): array
    {
        $productIds=$products->keys()->map(static fn($id)=>(int)$id)->all();
        if(!$productIds){ return []; }

        $packed=DB::table('rcm_packing_lines')
            ->where('business_id',$businessId)
            ->whereIn('product_id',$productIds)
            ->groupBy('product_id')
            ->select('product_id',DB::raw('SUM(total_qty) as qty'))
            ->pluck('qty','product_id');

        $dispatched=DB::table('rcm_finished_stock_movements')
            ->where('business_id',$businessId)
            ->whereIn('product_id',$productIds)
            ->where('movement_type','dispatch')
            ->where('signed_quantity','<',0)
            ->groupBy('product_id')
            ->select('product_id',DB::raw('SUM(quantity) as qty'))
            ->pluck('qty','product_id');

        $available=[];
        foreach($products as $product){
            $id=(int)$product->id;
            $available[$id]=max(0.0,(float)$product->current_qty-(float)($packed[$id]??0)+(float)($dispatched[$id]??0));
        }
        return $available;
    }

    /** @return array<int,float> */
    public function availableToPack(int $businessId): array
    {
        $products=RiceProduct::forBusiness($businessId)->where('active',1)->get(['id','name','current_qty'])->keyBy('id');
        return $this->availableToPackForProducts($businessId,$products);
    }
}

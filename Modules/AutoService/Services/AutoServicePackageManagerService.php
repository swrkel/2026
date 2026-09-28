<?php
namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceJobPackage;
use Modules\AutoService\Entities\AutoServicePackageLine;
use Modules\AutoService\Entities\AutoServiceServicePackage;

class AutoServicePackageManagerService
{
    public function packagePayload(AutoServiceServicePackage $package): array
    {
        $package->loadMissing('lines');
        return [
            'id'=>$package->id,
            'code'=>$package->package_code,
            'name'=>$package->name,
            'selling_price'=>(float)($package->selling_price ?: $package->total_amount),
            'lines'=>$package->lines->map(function($line){
                return [
                    'package_line_id'=>$line->id,
                    'package_id'=>$line->package_id,
                    'line_type'=>$this->jobLineType($line->component_type),
                    'component_type'=>$line->component_type,
                    'product_id'=>$line->product_id,
                    'variation_id'=>$line->variation_id,
                    'description'=>$line->description ?: $line->item_name,
                    'quantity'=>(float)$line->quantity,
                    'unit_price'=>(float)$line->unit_price,
                    'discount_amount'=>(float)$line->discount_amount,
                    'tax_amount'=>(float)$line->tax_amount,
                    'is_optional'=>(bool)$line->is_optional,
                    'is_stock_item'=>(bool)$line->is_stock_item,
                ];
            })->values()->all(),
        ];
    }

    public function syncJobPackages(AutoServiceJob $job, array $packageIds): void
    {
        AutoServiceJobPackage::where('job_id',$job->id)->delete();
        foreach(array_unique(array_filter($packageIds)) as $packageId){
            $package=AutoServiceServicePackage::where('business_id',$job->business_id)->find($packageId);
            if(!$package) continue;
            AutoServiceJobPackage::create([
                'business_id'=>$job->business_id,
                'location_id'=>$job->location_id,
                'job_id'=>$job->id,
                'package_id'=>$package->id,
                'package_name'=>$package->name,
                'package_price'=>$package->selling_price ?: $package->total_amount,
                'created_by'=>auth()->id(),
            ]);
        }
    }

    private function jobLineType(?string $type): string
    {
        return match($type){
            'stock_item'=>'part',
            'labour'=>'service',
            'non_stock_item','external_service'=>'manual',
            default=>'manual',
        };
    }
}

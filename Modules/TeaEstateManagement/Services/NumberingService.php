<?php
namespace Modules\TeaEstateManagement\Services;
use Illuminate\Support\Facades\DB;

class NumberingService
{
    public function __construct(private TenantContextService $context) {}
    public function next(string $type, string $prefix): string
    {
        return DB::transaction(function() use ($type,$prefix) {
            $businessId=$this->context->businessId();
            $row=DB::table('tea_number_sequences')->where('business_id',$businessId)->where('document_type',$type)->lockForUpdate()->first();
            if (!$row) {
                DB::table('tea_number_sequences')->insert(['business_id'=>$businessId,'document_type'=>$type,'prefix'=>$prefix,'current_number'=>1,'created_at'=>now(),'updated_at'=>now()]);
                $n=1;
            } else {
                $n=(int)$row->current_number+1;
                DB::table('tea_number_sequences')->where('id',$row->id)->update(['current_number'=>$n,'updated_at'=>now()]);
                $prefix=$row->prefix ?: $prefix;
            }
            return $prefix.'-'.str_pad((string)$n,6,'0',STR_PAD_LEFT);
        });
    }
}

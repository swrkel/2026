<?php
namespace Modules\EggManagement\Services;
use Illuminate\Support\Facades\DB;
class SequenceService
{
    protected $context;
    public function __construct(EggContext $context){$this->context=$context;}
    public function next($key,$prefix)
    {
        $db=DB::connection(config('egg.connection'));
        return $db->transaction(function() use($db,$key,$prefix){
            $row=$db->table('egg_sequences')->where('business_id',$this->context->businessId())->where('sequence_key',$key)->lockForUpdate()->first();
            $n=$row ? ((int)$row->current_value+1) : 1;
            if($row) $db->table('egg_sequences')->where('id',$row->id)->update(['current_value'=>$n,'updated_at'=>now()]);
            else $db->table('egg_sequences')->insert(['business_id'=>$this->context->businessId(),'sequence_key'=>$key,'current_value'=>$n,'created_at'=>now(),'updated_at'=>now()]);
            return $prefix.str_pad((string)$n,6,'0',STR_PAD_LEFT);
        });
    }
}

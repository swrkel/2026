<?php
namespace Modules\StockTakingNew\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class StockTakeShareLink extends Model
{
    protected $table='stk_share_links'; protected $guarded=[]; protected $casts=['expires_at'=>'datetime','last_accessed_at'=>'datetime','revoked_at'=>'datetime','download_allowed'=>'boolean'];
    public function dispatches(): HasMany { return $this->hasMany(StockTakeShareDispatch::class,'share_link_id'); }
}

<?php
namespace Modules\EggManagement\Http\Controllers;
use Illuminate\Routing\Controller;
use Modules\EggManagement\Services\EggContext;
class BaseController extends Controller
{
    protected $context;
    public function __construct(EggContext $context){$this->context=$context;}
    protected function scope($query){return $query->where('business_id',$this->context->businessId());}
}

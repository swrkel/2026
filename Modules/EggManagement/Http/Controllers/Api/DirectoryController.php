<?php
namespace Modules\EggManagement\Http\Controllers\Api;
use Illuminate\Routing\Controller;use Illuminate\Http\Request;use Modules\EggManagement\Integrations\CustomerGateway;use Modules\EggManagement\Integrations\SupplierGateway;use Modules\EggManagement\Integrations\ProductGateway;
class DirectoryController extends Controller
{
    public function customers(Request $r,CustomerGateway $g){return response()->json($g->options($r->q));}
    public function suppliers(Request $r,SupplierGateway $g){return response()->json($g->options($r->q));}
    public function products(Request $r,ProductGateway $g){return response()->json($g->options($r->q));}
}

<?php
namespace Modules\RestaurantNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Modules\RestaurantNew\Entities\{Order,OrderItem};
use Modules\RestaurantNew\Http\Requests\{TransferOrderTableRequest,VoidOrderItemRequest};
use Modules\RestaurantNew\Services\OrderAdjustmentService;
class OrderAdjustmentController extends Controller
{
 public function voidItem(VoidOrderItemRequest $request,Order $order,OrderItem $item,OrderAdjustmentService $service){$service->voidItem($order,$item,$request->validated('reason'));return back()->with('success','Order item voided and stock reversed where required.');}
 public function transferTable(TransferOrderTableRequest $request,Order $order,OrderAdjustmentService $service){$service->transferTable($order,(int)$request->validated('table_id'),$request->validated('reason'));return back()->with('success','Order transferred to the selected table.');}
}

<?php
namespace Modules\Ran\Http\Controllers;
use Modules\Ran\Entities\ProductionOrder;use Modules\Ran\Http\Requests\ProductionReceiptRequest;use Modules\Ran\Services\ProductionService;
class ProductionReceiptController extends RanController {public function store(ProductionReceiptRequest $request,ProductionOrder $production,ProductionService $service){$service->receive($production,$request->validated());return back()->with('status','Finished jewellery received.');}}

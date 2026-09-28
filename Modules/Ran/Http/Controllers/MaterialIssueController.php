<?php
namespace Modules\Ran\Http\Controllers;
use Modules\Ran\Entities\ProductionOrder;use Modules\Ran\Http\Requests\MaterialIssueRequest;use Modules\Ran\Services\ProductionService;
class MaterialIssueController extends RanController {public function store(MaterialIssueRequest $request,ProductionOrder $production,ProductionService $service){$service->issue($production,$request->validated());return back()->with('status','Materials issued to production.');}}

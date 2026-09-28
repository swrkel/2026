<?php
namespace Modules\ProductsNew\Http\Controllers\ImportExport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Entities\ProductsNewDataCleanupTask;
use Modules\ProductsNew\Services\ImportExport\ProductDataCleanupService;
class DataCleanupController extends Controller
{
    public function index(ProductDataCleanupService $service)
    {
        $tasks = ProductsNewDataCleanupTask::latest()->limit(50)->get();
        $availableTasks = $service->availableTasks();
        return view('productsnew::import_export.cleanup', compact('tasks','availableTasks'));
    }
    public function store(Request $request, ProductDataCleanupService $service)
    {
        $request->validate(['task_type'=>'required|string']);
        $service->createTask($request->task_type, $request->all());
        return back()->with('status','Cleanup task created.');
    }
}

<?php
namespace Modules\DistributionNew\Http\Controllers\LiveOperations;

use Illuminate\Routing\Controller;
use Modules\DistributionNew\Services\LiveOperations\CommandCentreService;

class CommandCentreController extends Controller
{
    public function index() { return view('distributionnew::live_operations.command_centre.index'); }
    public function data(CommandCentreService $service) { return response()->json($service->dashboard(request())); }
}

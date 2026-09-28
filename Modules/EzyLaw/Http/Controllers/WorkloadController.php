<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\EzyLaw\Services\WorkloadService;
class WorkloadController extends Controller {public function index(Request $r,WorkloadService $s){$from=$r->input('from',now()->startOfMonth()->toDateString());$to=$r->input('to',now()->endOfMonth()->toDateString());return view('ezylaw::workload.index',['from'=>$from,'to'=>$to,'rows'=>$s->data($from,$to)]);}}

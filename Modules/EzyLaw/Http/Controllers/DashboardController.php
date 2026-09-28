<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Routing\Controller;
use Modules\EzyLaw\Services\DashboardService;
class DashboardController extends Controller{ public function index(DashboardService $s){ return view('ezylaw::dashboard.index',$s->data()); } }

<?php
namespace Modules\Tailoring\OrderManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Tailoring\OrderManagement\Services\TailoringJobCardService;

class TailoringJobCardController extends Controller
{
    protected $service;

    public function __construct(TailoringJobCardService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $summary = $this->service->summary($request);
        return view('tailoring::order_management.job_cards.index', compact('summary'));
    }

    public function show($id)
    {
        $jobCard = $this->service->find($id);
        return view('tailoring::order_management.job_cards.show', compact('jobCard'));
    }

    public function print($id)
    {
        $jobCard = $this->service->find($id);
        return view('tailoring::order_management.job_cards.print', compact('jobCard'));
    }
}

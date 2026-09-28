<?php
namespace Modules\Tailoring\OrderManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Tailoring\OrderManagement\Services\TailoringQuotationService;

class TailoringQuotationController extends Controller
{
    protected $service;

    public function __construct(TailoringQuotationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $summary = $this->service->summary($request);
        return view('tailoring::order_management.quotations.index', compact('summary'));
    }

    public function create()
    {
        return view('tailoring::order_management.quotations.create');
    }

    public function show($id)
    {
        $quotation = $this->service->find($id);
        return view('tailoring::order_management.quotations.show', compact('quotation'));
    }

    public function convert($id)
    {
        return view('tailoring::order_management.quotations.convert', compact('id'));
    }
}

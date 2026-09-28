<?php
namespace Modules\Tailoring\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Tailoring\Entities\TailoringQuotation;
use Modules\Tailoring\Services\TailoringQuotationService;
class TailoringQuotationController extends Controller
{
    public function index(){ $quotations = TailoringQuotation::latest()->paginate(25); return view('tailoring::quotations.index', compact('quotations')); }
    public function create(){ return view('tailoring::quotations.create'); }
    public function store(Request $request, TailoringQuotationService $service){ $service->create($request->all()); return redirect()->route('tailoring.quotations.index')->with('status', 'Quotation saved successfully'); }
    public function convert(TailoringQuotation $quotation, TailoringQuotationService $service){ $payload = $service->convertToOrder($quotation); return redirect()->route('tailoring.orders.create')->with('tailoring_order_payload', $payload); }
}

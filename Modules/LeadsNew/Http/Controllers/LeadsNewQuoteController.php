<?php
namespace Modules\LeadsNew\Http\Controllers;
use App\Http\Controllers\Controller; use Illuminate\Http\Request; use Modules\LeadsNew\Models\LeadsNewQuote; use Modules\LeadsNew\Services\LeadsNewQuoteService;
class LeadsNewQuoteController extends Controller { public function index(){ $quotes=LeadsNewQuote::latest()->paginate(25); return view('leadsnew::quotes.index',compact('quotes')); } public function store(Request $r){ app(LeadsNewQuoteService::class)->createDraft($r->all()+['business_id'=>session('business.id')]); return back()->with('status',['success'=>1,'msg'=>'Quotation saved']); } }

<?php
namespace Modules\LeadsNew\Http\Controllers;
use App\Http\Controllers\Controller; use Illuminate\Http\Request; use Modules\LeadsNew\Models\LeadsNewMessageTemplate;
class LeadsNewTemplateController extends Controller { public function index(){ $templates=LeadsNewMessageTemplate::latest()->paginate(25); return view('leadsnew::templates.index',compact('templates')); } public function store(Request $r){ LeadsNewMessageTemplate::create($r->all()+['business_id'=>session('business.id')]); return back()->with('status',['success'=>1,'msg'=>'Template saved']); } }

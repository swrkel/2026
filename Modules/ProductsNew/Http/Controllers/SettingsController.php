<?php
namespace Modules\ProductsNew\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class SettingsController extends Controller { public function index(){ return view('productsnew::settings.index'); } public function store(Request $request){ return back()->with('status',__('productsnew::product.settings_saved')); } }

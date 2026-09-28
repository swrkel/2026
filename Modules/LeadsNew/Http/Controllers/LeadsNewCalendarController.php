<?php
namespace Modules\LeadsNew\Http\Controllers;
use App\Http\Controllers\Controller; use Illuminate\Http\Request; use Modules\LeadsNew\Models\LeadsNewCalendarEvent;
class LeadsNewCalendarController extends Controller { public function index(){ $events=LeadsNewCalendarEvent::where('business_id',session('business.id'))->orderBy('starts_at')->paginate(50); return view('leadsnew::calendar.index',compact('events')); } public function feed(){ return LeadsNewCalendarEvent::where('business_id',session('business.id'))->get(['id','title','starts_at as start','ends_at as end','status']); } }

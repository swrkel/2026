<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawClient,LawMatter,LawAppointment,LawReminder};
use Modules\EzyLaw\Services\CalendarService;
class CalendarController extends Controller
{
    public function index(Request $r,CalendarService $s){$from=$r->input('from',now()->startOfMonth()->toDateString());$to=$r->input('to',now()->addMonth()->endOfMonth()->toDateString());return view('ezylaw::calendar.index',['from'=>$from,'to'=>$to,'events'=>$s->events($from,$to),'clients'=>LawClient::where('status','active')->orderBy('name')->get(),'matters'=>LawMatter::whereIn('status',['open','pending'])->orderByDesc('id')->get(),'appointments'=>LawAppointment::whereBetween('start_at',[$from.' 00:00:00',$to.' 23:59:59'])->orderBy('start_at')->get(),'reminders'=>LawReminder::whereBetween('remind_at',[$from.' 00:00:00',$to.' 23:59:59'])->orderBy('remind_at')->get()]);}
}

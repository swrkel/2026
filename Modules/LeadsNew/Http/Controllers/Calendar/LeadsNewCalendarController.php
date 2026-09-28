<?php

namespace Modules\LeadsNew\Http\Controllers\Calendar;

use Illuminate\Routing\Controller;

class LeadsNewCalendarController extends Controller
{
    public function index()
    {
        return view('leadsnew::calendar.index');
    }
}

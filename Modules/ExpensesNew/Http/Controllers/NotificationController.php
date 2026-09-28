<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index()
    {
        $templates = DB::table('expnew_notification_templates')->orderBy('event_key')->get();
        return view('expensesnew::integration.notifications', compact('templates'));
    }
}

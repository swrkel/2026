<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;

class StaffController extends Controller
{
    public function index() { return view('beautysaloons::staff.index'); }
    public function create() { return view('beautysaloons::staff.create'); }
}

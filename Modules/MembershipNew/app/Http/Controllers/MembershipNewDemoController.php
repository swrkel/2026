<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MembershipNew\app\Testing\MembershipNewDemoScenarioBuilder;

class MembershipNewDemoController extends Controller
{
    public function index(MembershipNewDemoScenarioBuilder $builder)
    {
        $summary = $builder->summary();

        return view('membershipnew::demo.index', compact('summary'));
    }
}

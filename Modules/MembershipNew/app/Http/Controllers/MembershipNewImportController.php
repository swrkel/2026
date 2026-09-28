<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MembershipNew\app\Imports\MembershipNewImportTemplate;

class MembershipNewImportController extends Controller
{
    public function index()
    {
        $templates = [
            'central_members' => MembershipNewImportTemplate::centralMembersHeaders(),
            'shares' => MembershipNewImportTemplate::sharesHeaders(),
            'point_rules' => MembershipNewImportTemplate::pointRulesHeaders(),
        ];

        return view('membershipnew::imports.index', compact('templates'));
    }
}

<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerStandaloneAuditService;

class CustomerStandaloneAuditController extends Controller
{
    public function index(Request $request, CustomerStandaloneAuditService $auditService)
    {
        $audit = $auditService->scan();

        if ($request->expectsJson() || $request->get('format') === 'json') {
            return response()->json($audit);
        }

        return view('customers::audit.standalone', compact('audit'));
    }
}

<?php

namespace Modules\EnterpriseFramework\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EnterpriseFramework\Services\Search\GlobalReportSearchService;

class ReportSearchController extends Controller
{
    public function __invoke(Request $request, GlobalReportSearchService $search)
    {
        return response()->json(['data' => $search->search($request->get('q', ''))]);
    }
}

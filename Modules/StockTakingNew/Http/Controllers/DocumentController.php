<?php

namespace Modules\StockTakingNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTakingNew\Entities\StockTakeSession;
use Modules\StockTakingNew\Services\DocumentService;
use Modules\StockTakingNew\Services\TenantScopeService;

class DocumentController extends Controller
{
    public function print(
        StockTakeSession $session,
        string $type,
        Request $request,
        TenantScopeService $scope,
        DocumentService $documents
    ) {
        $scope->assertBusinessRecord($session, $scope->businessId($request));

        return view('stocktakingnew::documents.print', $documents->viewData($session, $type));
    }

    public function pdf(
        StockTakeSession $session,
        string $type,
        Request $request,
        TenantScopeService $scope,
        DocumentService $documents
    ) {
        $scope->assertBusinessRecord($session, $scope->businessId($request));

        return $documents->pdf($session, $type, false);
    }

    public function download(
        StockTakeSession $session,
        string $type,
        Request $request,
        TenantScopeService $scope,
        DocumentService $documents
    ) {
        $scope->assertBusinessRecord($session, $scope->businessId($request));

        return $documents->pdf($session, $type, true);
    }
}

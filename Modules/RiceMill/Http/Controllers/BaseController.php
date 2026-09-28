<?php
namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RiceMill\Services\StandardListService;
use Modules\RiceMill\Services\TenantContext;

abstract class BaseController extends Controller
{
    public function __construct(protected TenantContext $context) {}

    protected function bid(): int { return $this->context->businessId(); }
    protected function uid(): int { return $this->context->userId(); }
    protected function listTools(): StandardListService { return app(StandardListService::class); }

    /** Apply the shared Global Search + ERP-standard Date Range to a list query. */
    protected function applyListFilters($query, Request $request, array $searchColumns, ?string $dateColumn = null, bool $dateTime = false): void
    {
        $tools = $this->listTools();
        $tools->applySearch($query, $request, $searchColumns);
        if ($dateColumn) {
            $tools->applyDate($query, $request, $this->bid(), $dateColumn, $dateTime);
        }
    }

    protected function listPerPage(Request $request, int $default = 25): int
    {
        return $this->listTools()->perPage($request, $default);
    }
}

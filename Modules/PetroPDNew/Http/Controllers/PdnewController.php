<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\PetroPDNew\Entities\PdnewDayEnd;
use Modules\PetroPDNew\Entities\PdnewDocument;
use Modules\PetroPDNew\Entities\PdnewNotificationTemplate;
use Modules\PetroPDNew\Entities\PdnewOperatorMapping;
use Modules\PetroPDNew\Entities\PdnewReconciliationIssue;
use Modules\PetroPDNew\Entities\PdnewSettlement;
use Modules\PetroPDNew\Entities\PdnewSettlementAdjustment;
use Modules\PetroPDNew\Entities\PdnewSettlementPayment;
use Modules\PetroPDNew\Services\PdnewContextService;

abstract class PdnewController extends Controller
{
    public function __construct(protected PdnewContextService $context) {}

    protected function settlement(int $id): PdnewSettlement
    {
        $model = PdnewSettlement::query()
            ->forBusiness($this->context->businessId())
            ->findOrFail($id);
        $this->context->authorizeLocation($model->location_id ? (int) $model->location_id : null);

        return $model;
    }

    protected function payment(int $id): PdnewSettlementPayment
    {
        $model = PdnewSettlementPayment::query()
            ->forBusiness($this->context->businessId())
            ->findOrFail($id);
        $locationId = $model->settlement()->value('location_id');
        $this->context->authorizeLocation($locationId !== null ? (int) $locationId : null);

        return $model;
    }

    protected function adjustment(int $id): PdnewSettlementAdjustment
    {
        $model = PdnewSettlementAdjustment::query()
            ->forBusiness($this->context->businessId())
            ->findOrFail($id);
        $this->authorizeSettlementOwner((int) $model->settlement_id);

        return $model;
    }

    protected function issue(int $id): PdnewReconciliationIssue
    {
        $model = PdnewReconciliationIssue::query()
            ->forBusiness($this->context->businessId())
            ->findOrFail($id);
        $this->authorizeSettlementOwner((int) $model->settlement_id);

        return $model;
    }

    protected function dayEnd(int $id): PdnewDayEnd
    {
        $model = PdnewDayEnd::query()
            ->forBusiness($this->context->businessId())
            ->findOrFail($id);
        $this->context->authorizeLocation($model->location_id ? (int) $model->location_id : null);

        return $model;
    }

    protected function operatorMapping(int $id): PdnewOperatorMapping
    {
        $model = PdnewOperatorMapping::query()
            ->forBusiness($this->context->businessId())
            ->findOrFail($id);
        $this->context->authorizeLocation($model->location_id ? (int) $model->location_id : null);

        return $model;
    }

    protected function notificationTemplate(int $id): PdnewNotificationTemplate
    {
        return PdnewNotificationTemplate::query()
            ->forBusiness($this->context->businessId())
            ->findOrFail($id);
    }

    protected function document(int $id): PdnewDocument
    {
        $model = PdnewDocument::query()
            ->forBusiness($this->context->businessId())
            ->findOrFail($id);
        $this->authorizeSettlementOwner((int) $model->settlement_id);

        return $model;
    }

    protected function error(\Throwable $exception)
    {
        if ($exception instanceof \RuntimeException) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        report($exception);

        return back()->withInput()->with(
            'error',
            'The Petro PD-New operation could not be completed. Please review the server log for the recorded error.'
        );
    }

    private function authorizeSettlementOwner(int $settlementId): void
    {
        $locationId = PdnewSettlement::query()
            ->forBusiness($this->context->businessId())
            ->whereKey($settlementId)
            ->value('location_id');

        abort_if($locationId === null && ! PdnewSettlement::query()
            ->forBusiness($this->context->businessId())
            ->whereKey($settlementId)
            ->exists(), 404);

        $this->context->authorizeLocation($locationId !== null ? (int) $locationId : null);
    }
}

<?php

namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\RiceMill\Services\{
    CustomerAccountService,
    ExternalMasterDataService,
    LocationAccessService,
    TenantContext,
    PermissionAccessService
};

class CustomerAccountController extends BaseController
{
    public function __construct(
        TenantContext $context,
        private CustomerAccountService $accounts,
        private ExternalMasterDataService $masters,
        private LocationAccessService $locationAccess,
        private PermissionAccessService $permissions
    ) {
        parent::__construct($context);
    }

    public function outstanding(Request $request)
    {
        $businessId = $this->bid();
        $filters = $this->filters($request, $businessId);
        $q = $this->accounts->invoiceQuery($businessId, $this->locationAccess->ids($businessId));
        $this->accounts->onlyOutstanding($q);
        $this->accounts->applyInvoiceFilters($q, $filters['customer_id'], $filters['location_id'], $filters['store_id']);
        $this->applyInvoiceSearch($q, $request);
        $this->listTools()->applyDate($q, $request, $businessId, 'd.dispatch_date');

        $summaryRows = (clone $q)->get();
        $rows = $q->orderByDesc('d.dispatch_date')->orderByDesc('d.id')
            ->paginate($this->listPerPage($request,25))->appends($request->query());
        $this->decorateOutstandingRows($rows->getCollection());

        return view('RiceMill::customer-accounts.outstanding', $this->baseViewData($businessId, $filters) + [
            'rows'=>$rows,
            'coreReady'=>$this->accounts->coreReady(),
            'summary'=>[
                'invoice_count'=>$summaryRows->count(),
                'invoice_total'=>(float)$summaryRows->sum(fn ($r) => (float)($r->finance_final_total ?? $r->rcm_net_total ?? 0)),
                'paid_total'=>(float)$summaryRows->sum('paid_amount'),
                'outstanding_total'=>(float)$summaryRows->sum('outstanding_amount'),
                'sync_pending'=>$summaryRows->whereNull('transaction_id')->count(),
            ],
        ]);
    }

    public function ledger(Request $request)
    {
        return $this->ledgerOrStatement($request, false);
    }

    public function statement(Request $request)
    {
        return $this->ledgerOrStatement($request, true);
    }

    public function payments(Request $request)
    {
        $businessId = $this->bid();
        $filters = $this->filters($request, $businessId);
        $rows = collect();
        if ($filters['customer_id']) {
            $q = $this->accounts->invoiceQuery($businessId, $this->locationAccess->ids($businessId));
            $this->accounts->onlyOutstanding($q);
            $this->accounts->applyInvoiceFilters($q, $filters['customer_id'], $filters['location_id'], $filters['store_id']);
            $this->applyInvoiceSearch($q, $request);
            $this->listTools()->applyDate($q, $request, $businessId, 'd.dispatch_date');
            $rows = $q->orderBy('d.dispatch_date')->orderBy('d.id')->limit(10000)->get();
            $this->decorateOutstandingRows($rows);
        }

        return view('RiceMill::customer-accounts.payments', $this->baseViewData($businessId, $filters) + [
            'rows'=>$rows,
            'coreReady'=>$this->accounts->coreReady(),
            'paymentMethods'=>$this->accounts->paymentMethods(),
            'paymentAccounts'=>$this->accounts->paymentAccounts($businessId),
            'canCreatePayment'=>$this->permissions->allows($request->user(),'rice_mill.customer_accounts.payment.create'),
        ]);
    }

    public function storePayment(Request $request)
    {
        $businessId = $this->bid();
        $validated = $request->validate([
            'customer_id'=>'required|integer|min:1',
            'paid_on'=>'required|date',
            'method'=>'required|in:cash,card,cheque,bank_transfer,direct_bank_deposit',
            'account_id'=>'nullable|integer|min:1',
            'cheque_number'=>'nullable|string|max:191',
            'cheque_date'=>'nullable|date',
            'bank_name'=>'nullable|string|max:191',
            'card_transaction_number'=>'nullable|string|max:191',
            'note'=>'nullable|string|max:191',
            'allocations'=>'required|array|min:1',
            'allocations.*'=>'nullable|numeric|min:0',
        ]);
        $customerId = (int)$validated['customer_id'];
        $this->assertCustomer($businessId,$customerId);

        $result = $this->accounts->postAllocatedPayment(
            $businessId,
            $this->uid(),
            $customerId,
            $validated,
            $this->locationAccess->ids($businessId)
        );

        return redirect()->route('rice-mill.customer-accounts.payment-history', ['customer_id'=>$customerId])
            ->with('status', 'Customer payment '.$result['group_ref'].' posted to '.count($result['payments']).' selected invoice'.(count($result['payments'])===1?'':'s').' for '.number_format($result['total'],2).'.');
    }

    public function aging(Request $request)
    {
        $businessId = $this->bid();
        $filters = $this->filters($request, $businessId);
        $q = $this->accounts->invoiceQuery($businessId, $this->locationAccess->ids($businessId));
        $this->accounts->onlyOutstanding($q);
        $this->accounts->applyInvoiceFilters($q, $filters['customer_id'], $filters['location_id'], $filters['store_id']);
        $this->applyInvoiceSearch($q, $request);
        $this->listTools()->applyDate($q, $request, $businessId, 'd.dispatch_date');

        $allRows = (clone $q)->orderBy('d.customer_id')->orderBy('d.dispatch_date')->get();
        $state = $this->listTools()->dateState($request,$businessId);
        $asOf = $state['to'] ? Carbon::parse($state['to'])->endOfDay() : now();
        $this->decorateAgingRows($allRows, $asOf);

        $summary = [
            'current'=>0.0,'1_30'=>0.0,'31_60'=>0.0,'61_90'=>0.0,'91_120'=>0.0,'over_120'=>0.0,'total'=>0.0,'sync_pending'=>0,
        ];
        foreach ($allRows as $row) {
            $amount = (float)$row->outstanding_amount;
            $summary[$row->aging_bucket] += $amount;
            $summary['total'] += $amount;
            if ($row->sync_status === 'pending') $summary['sync_pending']++;
        }

        $perPage = $this->listPerPage($request,25);
        $page = max(1,(int)$request->input('page',1));
        $slice = $allRows->slice(($page-1)*$perPage,$perPage)->values();
        $rows = new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $allRows->count(),
            $perPage,
            $page,
            ['path'=>$request->url(),'query'=>$request->query()]
        );

        return view('RiceMill::customer-accounts.aging', $this->baseViewData($businessId, $filters) + [
            'rows'=>$rows,
            'summary'=>$summary,
            'asOf'=>$asOf,
            'coreReady'=>$this->accounts->coreReady(),
        ]);
    }

    public function paymentHistory(Request $request)
    {
        $businessId = $this->bid();
        $filters = $this->filters($request, $businessId);
        $q = $this->accounts->paymentHistoryQuery($businessId, $this->locationAccess->ids($businessId));
        if ($filters['customer_id']) $q->where('d.customer_id',$filters['customer_id']);
        if ($filters['location_id']) $q->where('d.location_id',$filters['location_id']);
        if ($filters['store_id']) $q->where('d.store_id',$filters['store_id']);

        $term = trim((string)$request->input('q',''));
        if ($term !== '') {
            $like = '%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$term).'%';
            $q->where(function ($w) use ($like) {
                $w->where('d.dispatch_no','like',$like)
                    ->orWhere('tp.payment_ref_no','like',$like)
                    ->orWhere('tp.method','like',$like)
                    ->orWhere('tp.amount','like',$like);
                if (Schema::hasTable('contacts')) $w->orWhere('c.name','like',$like);
            });
        }
        $this->listTools()->applyDate($q,$request,$businessId,'tp.paid_on',true);
        $rows = $q->orderByDesc('tp.paid_on')->orderByDesc('tp.id')
            ->paginate($this->listPerPage($request,25))->appends($request->query());

        return view('RiceMill::customer-accounts.payment-history', $this->baseViewData($businessId, $filters) + [
            'rows'=>$rows,
            'coreReady'=>$this->accounts->coreReady(),
        ]);
    }

    private function ledgerOrStatement(Request $request, bool $statement)
    {
        $businessId = $this->bid();
        $filters = $this->filters($request, $businessId);
        $state = $this->listTools()->dateState($request,$businessId);
        $ledger = [
            'rows'=>collect(),'opening_balance'=>0.0,'period_debits'=>0.0,'period_credits'=>0.0,'closing_balance'=>0.0,'invoice_count'=>0,'sync_pending_count'=>0,
        ];
        if ($filters['customer_id']) {
            $ledger = $this->accounts->ledgerData(
                $businessId,
                $filters['customer_id'],
                $this->locationAccess->ids($businessId),
                $filters['location_id'],
                $filters['store_id'],
                $state['from'] ?: null,
                $state['to'] ?: null
            );
        }

        $view = $statement ? 'RiceMill::customer-accounts.statement' : 'RiceMill::customer-accounts.ledger';
        return view($view, $this->baseViewData($businessId,$filters) + [
            'ledger'=>$ledger,
            'coreReady'=>$this->accounts->coreReady(),
            'dateState'=>$state,
        ]);
    }

    private function filters(Request $request, int $businessId): array
    {
        $customerId = $this->intFilter($request->input('customer_id'));
        $locationId = $this->intFilter($request->input('location_id'));
        $storeId = $this->intFilter($request->input('store_id'));
        if ($customerId) $this->assertCustomer($businessId,$customerId);
        if ($locationId) $this->locationAccess->assert($locationId,$businessId);
        if ($storeId) $this->masters->assertStore($storeId,$businessId,$locationId);
        return ['customer_id'=>$customerId,'location_id'=>$locationId,'store_id'=>$storeId];
    }

    private function baseViewData(int $businessId, array $filters): array
    {
        return [
            'customers'=>$this->masters->customers($businessId),
            'locations'=>$this->masters->locations($businessId),
            'stores'=>$this->masters->stores($businessId),
            'selectedCustomerId'=>$filters['customer_id'],
            'selectedLocationId'=>$filters['location_id'],
            'selectedStoreId'=>$filters['store_id'],
            'canViewLedger'=>$this->permissions->allows(auth()->user(),'rice_mill.customer_accounts.ledger.view'),
            'canViewOutstanding'=>$this->permissions->allows(auth()->user(),'rice_mill.customer_accounts.outstanding.view'),
            'canViewPayments'=>$this->permissions->allows(auth()->user(),'rice_mill.customer_accounts.payment.view'),
            'canViewAging'=>$this->permissions->allows(auth()->user(),'rice_mill.customer_accounts.aging.view'),
            'canViewStatement'=>$this->permissions->allows(auth()->user(),'rice_mill.customer_accounts.statement.view'),
            'canViewPaymentHistory'=>$this->permissions->allows(auth()->user(),'rice_mill.customer_accounts.payment_history.view'),
        ];
    }

    private function applyInvoiceSearch($q, Request $request): void
    {
        $term = trim((string)$request->input('q',''));
        if ($term === '') return;
        $like = '%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$term).'%';
        $q->where(function ($w) use ($like) {
            $w->where('d.dispatch_no','like',$like)
                ->orWhere('d.net_total','like',$like);
            if (Schema::hasTable('contacts')) $w->orWhere('c.name','like',$like);
            if ($this->accounts->coreReady()) {
                $w->orWhere('t.payment_status','like',$like)
                    ->orWhere('t.final_total','like',$like);
            }
        });
    }

    private function decorateOutstandingRows($rows): void
    {
        foreach ($rows as $row) {
            $row->sync_status = empty($row->transaction_id) ? 'pending' : 'synced';
            $row->invoice_amount = (float)($row->finance_final_total ?? $row->rcm_net_total ?? 0);
            $row->paid_amount = (float)($row->paid_amount ?? 0);
            $row->outstanding_amount = (float)($row->outstanding_amount ?? 0);
            $row->due_date = $this->accounts->dueDate($row);
            $row->days_outstanding = $this->accounts->daysOutstanding($row, now());
        }
    }

    private function decorateAgingRows($rows, Carbon $asOf): void
    {
        foreach ($rows as $row) {
            $row->sync_status = empty($row->transaction_id) ? 'pending' : 'synced';
            $row->invoice_amount = (float)($row->finance_final_total ?? $row->rcm_net_total ?? 0);
            $row->paid_amount = (float)($row->paid_amount ?? 0);
            $row->outstanding_amount = (float)($row->outstanding_amount ?? 0);
            $row->due_date = $this->accounts->dueDate($row);
            $row->days_outstanding = $this->accounts->daysOutstanding($row,$asOf);
            $row->aging_bucket = $this->accounts->agingBucket($row,$asOf);
        }
    }

    private function assertCustomer(int $businessId, int $customerId): void
    {
        if (!Schema::hasTable('contacts')) {
            throw ValidationException::withMessages(['customer_id'=>'Customer master is not available in this tenant.']);
        }
        $q = DB::table('contacts')->where('business_id',$businessId)->where('id',$customerId);
        $cols = Schema::getColumnListing('contacts');
        if (in_array('type',$cols,true)) $q->whereIn('type',['customer','both']);
        if (in_array('deleted_at',$cols,true)) $q->whereNull('deleted_at');
        if (!$q->exists()) throw ValidationException::withMessages(['customer_id'=>'The selected customer does not belong to this business.']);
    }

    private function intFilter($value): ?int
    {
        if ($value === null || $value === '' || $value === 'all') return null;
        $number = (int)$value;
        return $number > 0 ? $number : null;
    }
}

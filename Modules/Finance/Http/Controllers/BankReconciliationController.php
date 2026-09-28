<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Entities\BankReconciliation;
use Modules\Finance\Services\BankReconciliation\BankReconciliationService;
use Modules\Finance\Utils\FinancePermissionHelper;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class BankReconciliationController extends Controller
{
    protected $service;

    public function __construct(BankReconciliationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $this->authorizeFinance('finance.bank_reconciliation.view');

        // Do not crash with SQLSTATE/Table-not-found on a newly deployed tenant.
        // Show a controlled one-click initializer for the CURRENT tenant DB.
        if (!$this->bankReconciliationSchemaReady()) {
            return view('finance::bank_reconciliation.setup', [
                'missingItems' => $this->bankReconciliationSchemaMissing(),
            ]);
        }

        $businessId = $this->businessId();
        $query = BankReconciliation::with('account')
            ->where('business_id', $businessId)
            ->orderByDesc('statement_date')
            ->orderByDesc('id');

        if ($request->filled('account_id')) {
            $query->where('account_id', (int) $request->account_id);
        }
        if ($request->filled('status') && in_array($request->status, ['draft', 'reconciled'], true)) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('statement_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('statement_date', '<=', $request->to_date);
        }

        return view('finance::bank_reconciliation.index', [
            'reconciliations' => $query->paginate(25)->appends($request->query()),
            'bankAccounts' => $this->service->bankAccounts($businessId, $this->permittedLocations()),
        ]);
    }

    public function setup()
    {
        $this->authorizeFinance('finance.bank_reconciliation.create');

        try {
            $this->ensureBankReconciliationSchema();
        } catch (\Throwable $e) {
            return redirect()->route('finance.bank-reconciliation.index')
                ->with('error', 'Unable to initialize Bank Reconciliation: ' . $e->getMessage());
        }

        return redirect()->route('finance.bank-reconciliation.index')
            ->with('success', 'Bank Reconciliation is ready for this tenant database.');
    }

    public function create()
    {
        if (!$this->bankReconciliationSchemaReady()) {
            return redirect()->route('finance.bank-reconciliation.index')->with('error', 'Bank Reconciliation database setup is pending for this tenant.');
        }
        $this->authorizeFinance('finance.bank_reconciliation.create');
        $businessId = $this->businessId();

        return view('finance::bank_reconciliation.form', [
            'reconciliation' => null,
            'bankAccounts' => $this->service->bankAccounts($businessId, $this->permittedLocations()),
            'selectedTransactionIds' => [],
        ]);
    }

    public function edit($id)
    {
        if (!$this->bankReconciliationSchemaReady()) {
            return redirect()->route('finance.bank-reconciliation.index')->with('error', 'Bank Reconciliation database setup is pending for this tenant.');
        }
        $this->authorizeFinance('finance.bank_reconciliation.update');
        $reconciliation = $this->findReconciliation($id);
        abort_if($reconciliation->status !== 'draft', 422, 'Only draft bank reconciliations can be edited.');

        return view('finance::bank_reconciliation.form', [
            'reconciliation' => $reconciliation->load('lines'),
            'bankAccounts' => $this->service->bankAccounts($this->businessId(), $this->permittedLocations()),
            'selectedTransactionIds' => $reconciliation->lines->where('is_cleared', true)->pluck('account_transaction_id')->filter()->values()->all(),
        ]);
    }

    public function transactions(Request $request)
    {
        if (!$this->bankReconciliationSchemaReady()) {
            return response()->json(['success' => false, 'msg' => 'Bank Reconciliation database setup is pending for this tenant.'], 503);
        }
        $this->authorizeFinance('finance.bank_reconciliation.view');
        $validated = $request->validate([
            'account_id' => 'required|integer|min:1',
            'statement_date' => 'required|date',
        ]);

        $businessId = $this->businessId();
        $this->service->assertBankAccount($businessId, (int) $validated['account_id'], $this->permittedLocations());

        $rows = $this->service->candidateTransactions($businessId, (int) $validated['account_id'], $validated['statement_date']);
        $bookBalance = $this->service->bookBalance($businessId, (int) $validated['account_id'], $validated['statement_date']);

        return response()->json([
            'success' => true,
            'book_balance' => $bookBalance,
            'transactions' => $rows->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'date' => substr((string) $row->operation_date, 0, 10),
                    'reference' => (string) $row->reference,
                    'description' => (string) $row->description,
                    'type' => (string) $row->type,
                    'deposit' => round((float) $row->deposit, 4),
                    'payment' => round((float) $row->payment, 4),
                    'amount' => round((float) $row->amount, 4),
                ];
            })->values(),
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->bankReconciliationSchemaReady()) {
            return redirect()->route('finance.bank-reconciliation.index')->with('error', 'Bank Reconciliation database setup is pending for this tenant.');
        }
        $this->authorizeFinance('finance.bank_reconciliation.create');
        $data = $this->validatedData($request);

        try {
            $reconciliation = $this->service->saveDraft(
                $data,
                $this->businessId(),
                (int) Auth::id(),
                $this->permittedLocations()
            );
        } catch (HttpExceptionInterface $e) {
            if ((int) $e->getStatusCode() === 422) {
                return back()->withInput()->withErrors(['reconciliation' => $e->getMessage()]);
            }
            throw $e;
        }

        return redirect()->route('finance.bank-reconciliation.show', $reconciliation->id)
            ->with('success', 'Bank reconciliation draft saved successfully.');
    }

    public function update(Request $request, $id)
    {
        if (!$this->bankReconciliationSchemaReady()) {
            return redirect()->route('finance.bank-reconciliation.index')->with('error', 'Bank Reconciliation database setup is pending for this tenant.');
        }
        $this->authorizeFinance('finance.bank_reconciliation.update');
        $reconciliation = $this->findReconciliation($id);
        $data = $this->validatedData($request);

        try {
            $reconciliation = $this->service->updateDraft(
                $reconciliation,
                $data,
                $this->businessId(),
                $this->permittedLocations()
            );
        } catch (HttpExceptionInterface $e) {
            if ((int) $e->getStatusCode() === 422) {
                return back()->withInput()->withErrors(['reconciliation' => $e->getMessage()]);
            }
            throw $e;
        }

        return redirect()->route('finance.bank-reconciliation.show', $reconciliation->id)
            ->with('success', 'Bank reconciliation draft updated successfully.');
    }

    public function show($id)
    {
        if (!$this->bankReconciliationSchemaReady()) {
            return redirect()->route('finance.bank-reconciliation.index')->with('error', 'Bank Reconciliation database setup is pending for this tenant.');
        }
        $this->authorizeFinance('finance.bank_reconciliation.view');
        $reconciliation = $this->findReconciliation($id)->load(['account', 'lines']);

        return view('finance::bank_reconciliation.show', compact('reconciliation'));
    }

    public function finalize($id)
    {
        if (!$this->bankReconciliationSchemaReady()) {
            return redirect()->route('finance.bank-reconciliation.index')->with('error', 'Bank Reconciliation database setup is pending for this tenant.');
        }
        $this->authorizeFinance('finance.bank_reconciliation.finalize');
        try {
            $reconciliation = $this->service->finalize($this->findReconciliation($id), $this->businessId(), (int) Auth::id());
        } catch (HttpExceptionInterface $e) {
            if ((int) $e->getStatusCode() === 422) {
                return back()->with('error', $e->getMessage());
            }
            throw $e;
        }

        return redirect()->route('finance.bank-reconciliation.show', $reconciliation->id)
            ->with('success', 'Bank reconciliation finalized successfully.');
    }

    public function reopen($id)
    {
        if (!$this->bankReconciliationSchemaReady()) {
            return redirect()->route('finance.bank-reconciliation.index')->with('error', 'Bank Reconciliation database setup is pending for this tenant.');
        }
        $this->authorizeFinance('finance.bank_reconciliation.reopen');
        try {
            $reconciliation = $this->service->reopen($this->findReconciliation($id), $this->businessId());
        } catch (HttpExceptionInterface $e) {
            if ((int) $e->getStatusCode() === 422) {
                return back()->with('error', $e->getMessage());
            }
            throw $e;
        }

        return redirect()->route('finance.bank-reconciliation.edit', $reconciliation->id)
            ->with('success', 'Bank reconciliation reopened as a draft.');
    }

    public function destroy($id)
    {
        if (!$this->bankReconciliationSchemaReady()) {
            return redirect()->route('finance.bank-reconciliation.index')->with('error', 'Bank Reconciliation database setup is pending for this tenant.');
        }
        $this->authorizeFinance('finance.bank_reconciliation.update');
        $reconciliation = $this->findReconciliation($id);
        abort_if($reconciliation->status !== 'draft', 422, 'A finalized bank reconciliation cannot be deleted. Reopen it first.');

        $reconciliation->lines()->delete();
        $reconciliation->delete();

        return redirect()->route('finance.bank-reconciliation.index')
            ->with('success', 'Bank reconciliation draft deleted successfully.');
    }


    /**
     * Keep the first-run Bank Reconciliation schema check inside this
     * controller instead of constructor-injecting a separate schema service.
     *
     * This intentionally removes a deployment/autoload failure mode where an
     * older or changed-files-only upload contains the controller route but not
     * Modules\\Finance\\Services\\BankReconciliation\\BankReconciliationSchemaService.
     * The feature can therefore open its setup page instead of failing during
     * Laravel controller resolution with BindingResolutionException.
     *
     * Use the schema builder from the CURRENT default database connection so
     * this remains tenant-safe after InitializeFinanceTenantContext switches
     * the connection for the logged-in tenant.
     */
    private function bankReconciliationSchemaReady(): bool
    {
        $schema = DB::connection()->getSchemaBuilder();

        return $schema->hasTable('account_transactions')
            && $schema->hasColumn('account_transactions', 'reconcile_status')
            && $schema->hasTable('finance_bank_reconciliations')
            && $schema->hasTable('finance_bank_reconciliation_lines');
    }

    private function bankReconciliationSchemaMissing(): array
    {
        $schema = DB::connection()->getSchemaBuilder();
        $missing = [];

        if (!$schema->hasTable('account_transactions')) {
            $missing[] = 'account_transactions (core Finance table)';
        }
        if (!$schema->hasTable('finance_bank_reconciliations')) {
            $missing[] = 'finance_bank_reconciliations';
        }
        if (!$schema->hasTable('finance_bank_reconciliation_lines')) {
            $missing[] = 'finance_bank_reconciliation_lines';
        }
        if ($schema->hasTable('account_transactions') && !$schema->hasColumn('account_transactions', 'reconcile_status')) {
            $missing[] = 'account_transactions.reconcile_status';
        }

        return $missing;
    }

    private function ensureBankReconciliationSchema(): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        if (!$schema->hasTable('account_transactions')) {
            throw new RuntimeException('The core account_transactions table is not available in the current tenant database.');
        }

        if (!$schema->hasTable('finance_bank_reconciliations')) {
            $schema->create('finance_bank_reconciliations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id');
                $table->unsignedBigInteger('location_id')->nullable();
                $table->unsignedBigInteger('account_id');
                $table->string('reconciliation_no', 50);
                $table->date('statement_date');
                $table->decimal('statement_ending_balance', 22, 4)->default(0);
                $table->decimal('book_ending_balance', 22, 4)->default(0);
                $table->decimal('outstanding_deposits', 22, 4)->default(0);
                $table->decimal('outstanding_payments', 22, 4)->default(0);
                $table->decimal('adjusted_bank_balance', 22, 4)->default(0);
                $table->decimal('difference', 22, 4)->default(0);
                $table->string('status', 20)->default('draft');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->unsignedBigInteger('reconciled_by')->nullable();
                $table->dateTime('reconciled_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['business_id', 'reconciliation_no'], 'finance_bank_rec_business_no_uq');
                $table->index(['business_id', 'account_id', 'statement_date'], 'finance_bank_rec_account_date_idx');
                $table->index(['business_id', 'status'], 'finance_bank_rec_status_idx');
                $table->index(['business_id', 'location_id'], 'finance_bank_rec_location_idx');
            });
        }

        if (!$schema->hasTable('finance_bank_reconciliation_lines')) {
            $schema->create('finance_bank_reconciliation_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('reconciliation_id');
                $table->unsignedBigInteger('account_transaction_id')->nullable();
                $table->dateTime('transaction_date')->nullable();
                $table->string('reference', 191)->nullable();
                $table->text('description')->nullable();
                $table->string('transaction_type', 20);
                $table->decimal('amount', 22, 4)->default(0);
                $table->boolean('is_cleared')->default(false);
                $table->timestamps();

                $table->index(['reconciliation_id', 'is_cleared'], 'finance_bank_rec_lines_status_idx');
                $table->index('account_transaction_id', 'finance_bank_rec_lines_tx_idx');
            });
        }

        if (!$schema->hasColumn('account_transactions', 'reconcile_status')) {
            $schema->table('account_transactions', function (Blueprint $table) {
                $table->boolean('reconcile_status')->default(false)->index();
            });
        }
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'account_id' => 'required|integer|min:1',
            'statement_date' => 'required|date',
            'statement_ending_balance' => 'required|numeric',
            'notes' => 'nullable|string|max:2000',
            'cleared_transaction_ids' => 'nullable|array',
            'cleared_transaction_ids.*' => 'integer|min:1',
        ]);
    }

    private function findReconciliation($id): BankReconciliation
    {
        return BankReconciliation::where('business_id', $this->businessId())->findOrFail((int) $id);
    }

    private function businessId(): int
    {
        $businessId = (int) (session('user.business_id') ?: session('business.id'));
        abort_if($businessId <= 0, 403, 'Business context is not available.');
        return $businessId;
    }

    private function permittedLocations()
    {
        $user = Auth::user();
        if (!$user || !method_exists($user, 'permitted_locations')) {
            return 'all';
        }

        try {
            return $user->permitted_locations();
        } catch (\Throwable $e) {
            return 'all';
        }
    }

    private function authorizeFinance(string $permission): void
    {
        abort_unless(FinancePermissionHelper::can($permission), 403);
    }
}

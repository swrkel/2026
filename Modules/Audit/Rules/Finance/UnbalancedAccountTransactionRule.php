<?php
namespace Modules\Audit\Rules\Finance;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Services\Adapters\FinanceAdapter;
use Modules\Audit\Services\AuditContext;
use Modules\Audit\Support\BaseAuditRule;

class UnbalancedAccountTransactionRule extends BaseAuditRule
{
    protected $module = 'Finance';
    protected $severity = 'critical';
    protected $description = 'Checks explicit debit/credit account-transaction pairs without assuming every transaction_id is a complete double-entry journal.';
    protected $adapter;

    public function __construct(FinanceAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function code(): string
    {
        return 'FIN-BAL-001';
    }

    public function title(): string
    {
        return 'Broken explicit debit/credit pair';
    }

    public function supports(AuditContext $context): bool
    {
        $t = $this->adapter->accountTransactions();

        return $t
            && $this->adapter->hasColumn($t, 'id')
            && $this->adapter->hasColumn($t, 'pair_at_id')
            && $this->adapter->hasColumn($t, 'type')
            && $this->adapter->hasColumn($t, 'amount');
    }

    public function run(AuditContext $context): array
    {
        $t = $this->adapter->accountTransactions();
        $tol = (float) config('audit.amount_tolerance', 0.005);

        /*
         * IMPORTANT ERP ACCOUNTING RULE
         * -----------------------------
         * `transaction_id` is a source/business transaction link in this ERP; it is
         * NOT guaranteed to represent a self-contained double-entry journal. A sale,
         * opening stock record, stock adjustment, payment, settlement, etc. may
         * legitimately contribute only one account_transactions row under that
         * transaction_id while its accounting counterpart is represented elsewhere.
         *
         * Therefore this audit rule only validates rows where the ERP has explicitly
         * declared a counterpart through `pair_at_id`. This avoids reporting normal
         * one-sided source postings as critical accounting errors.
         */
        $select = [
            'child.id as child_id',
            'child.pair_at_id',
            'child.type as child_type',
            'child.amount as child_amount',
            'pair.id as pair_id',
            'pair.type as pair_type',
            'pair.amount as pair_amount',
        ];
        if ($this->adapter->hasColumn($t, 'business_id')) {
            $select[] = 'child.business_id as child_business_id';
            $select[] = 'pair.business_id as pair_business_id';
        }
        if ($this->adapter->hasColumn($t, 'location_id')) {
            $select[] = 'child.location_id as child_location_id';
        }
        if ($this->adapter->hasColumn($t, 'deleted_at')) {
            $select[] = 'pair.deleted_at as pair_deleted_at';
        }
        if ($this->adapter->hasColumn($t, 'new_deleted_at')) {
            $select[] = 'pair.new_deleted_at as pair_new_deleted_at';
        }
        if ($this->adapter->hasColumn($t, 'journal_deleted')) {
            $select[] = 'pair.journal_deleted as pair_journal_deleted';
        }

        $q = DB::table($t . ' as child')
            ->leftJoin($t . ' as pair', 'pair.id', '=', 'child.pair_at_id')
            ->select($select)
            ->whereNotNull('child.pair_at_id')
            ->where('child.pair_at_id', '>', 0);

        $this->applyActiveFilter($q, 'child', $t);

        if ($context->businessId && $this->adapter->hasColumn($t, 'business_id')) {
            $q->where('child.business_id', $context->businessId);
        }

        // account_transactions in this ERP commonly does not contain location_id.
        // Only apply a location filter when the tenant schema actually has it.
        if ($context->locationId && $this->adapter->hasColumn($t, 'location_id')) {
            $q->where('child.location_id', $context->locationId);
        }

        $out = [];
        foreach ($q->limit(5000)->get() as $r) {
            if (!$r->pair_id) {
                $out[] = $this->scopedFinding($this->finding(
                    $t,
                    $r->child_id,
                    'Paired account transaction is missing',
                    'Account transaction #' . $r->child_id . ' references missing pair #' . $r->pair_at_id . '.',
                    'Existing active counterpart row',
                    'Missing pair_at_id ' . $r->pair_at_id,
                    [
                        'account_transaction_id' => (int) $r->child_id,
                        'pair_at_id' => (int) $r->pair_at_id,
                    ]
                ), $r, $context);
                continue;
            }

            if (!$this->isActivePair($r, $t)) {
                $out[] = $this->scopedFinding($this->finding(
                    $t,
                    $r->child_id,
                    'Paired account transaction is inactive',
                    'Account transaction #' . $r->child_id . ' points to counterpart #' . $r->pair_id . ' which is deleted, journal-deleted, or otherwise inactive.',
                    'Active counterpart row',
                    'Inactive counterpart #' . $r->pair_id,
                    [
                        'account_transaction_id' => (int) $r->child_id,
                        'pair_at_id' => (int) $r->pair_id,
                    ]
                ), $r, $context);
                continue;
            }

            $childType = strtolower((string) $r->child_type);
            $pairType = strtolower((string) $r->pair_type);
            $childSide = $this->normalSide($childType);
            $pairSide = $this->normalSide($pairType);

            if (!$childSide || !$pairSide || $childSide === $pairSide) {
                $out[] = $this->scopedFinding($this->finding(
                    $t,
                    $r->child_id,
                    'Paired entries are not opposite debit/credit sides',
                    'Account transaction #' . $r->child_id . ' and pair #' . $r->pair_id . ' do not form one debit and one credit.',
                    'One debit and one credit',
                    ($r->child_type ?? 'NULL') . ' / ' . ($r->pair_type ?? 'NULL'),
                    [
                        'account_transaction_id' => (int) $r->child_id,
                        'pair_at_id' => (int) $r->pair_id,
                        'child_type' => $r->child_type,
                        'pair_type' => $r->pair_type,
                    ]
                ), $r, $context);
                continue;
            }

            $childAmount = (float) $r->child_amount;
            $pairAmount = (float) $r->pair_amount;
            if (abs($childAmount - $pairAmount) > $tol) {
                $out[] = $this->scopedFinding($this->finding(
                    $t,
                    $r->child_id,
                    'Paired debit/credit amounts do not match',
                    'Account transaction #' . $r->child_id . ' and pair #' . $r->pair_id . ' have different amounts.',
                    number_format($childAmount, 4) . ' = ' . number_format($pairAmount, 4),
                    number_format($childAmount, 4) . ' vs ' . number_format($pairAmount, 4),
                    [
                        'account_transaction_id' => (int) $r->child_id,
                        'pair_at_id' => (int) $r->pair_id,
                        'child_amount' => $childAmount,
                        'pair_amount' => $pairAmount,
                    ]
                ), $r, $context);
                continue;
            }

            if ($this->adapter->hasColumn($t, 'business_id')
                && isset($r->child_business_id, $r->pair_business_id)
                && $r->child_business_id !== null
                && $r->pair_business_id !== null
                && (string) $r->child_business_id !== (string) $r->pair_business_id) {
                $out[] = $this->scopedFinding($this->finding(
                    $t,
                    $r->child_id,
                    'Paired entries belong to different businesses',
                    'Account transaction #' . $r->child_id . ' and pair #' . $r->pair_id . ' do not belong to the same business.',
                    (string) $r->child_business_id,
                    (string) $r->pair_business_id,
                    [
                        'account_transaction_id' => (int) $r->child_id,
                        'pair_at_id' => (int) $r->pair_id,
                        'child_business_id' => $r->child_business_id,
                        'pair_business_id' => $r->pair_business_id,
                    ]
                ), $r, $context);
            }
        }

        return $out;
    }

    private function scopedFinding(array $finding, $row, AuditContext $context): array
    {
        $businessId = isset($row->child_business_id) ? $row->child_business_id : $context->businessId;
        $locationId = isset($row->child_location_id) ? $row->child_location_id : $context->locationId;
        return $this->scopeFinding($finding, $businessId, $locationId);
    }

    private function normalSide(string $type): ?string
    {
        if (in_array($type, ['debit', 'dr'], true)) {
            return 'debit';
        }
        if (in_array($type, ['credit', 'cr'], true)) {
            return 'credit';
        }

        return null;
    }

    private function applyActiveFilter($q, string $alias, string $table): void
    {
        if ($this->adapter->hasColumn($table, 'deleted_at')) {
            $q->whereNull($alias . '.deleted_at');
        }
        if ($this->adapter->hasColumn($table, 'new_deleted_at')) {
            $q->whereNull($alias . '.new_deleted_at');
        }
        if ($this->adapter->hasColumn($table, 'journal_deleted')) {
            $q->where(function ($x) use ($alias) {
                $x->whereNull($alias . '.journal_deleted')->orWhere($alias . '.journal_deleted', 0);
            });
        }
    }

    private function isActivePair($row, string $table): bool
    {
        if (!$row->pair_id) {
            return false;
        }
        if ($this->adapter->hasColumn($table, 'deleted_at') && !empty($row->pair_deleted_at)) {
            return false;
        }
        if ($this->adapter->hasColumn($table, 'new_deleted_at') && !empty($row->pair_new_deleted_at)) {
            return false;
        }
        if ($this->adapter->hasColumn($table, 'journal_deleted') && !empty($row->pair_journal_deleted)) {
            return false;
        }

        return true;
    }
}

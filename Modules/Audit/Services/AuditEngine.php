<?php

namespace Modules\Audit\Services;

use Illuminate\Support\Facades\DB;
use Modules\Audit\Models\AuditFinding;
use Modules\Audit\Models\AuditFindingHistory;
use Modules\Audit\Models\AuditRuleSetting;
use Modules\Audit\Models\AuditRun;

class AuditEngine
{
    protected $registry;

    public function __construct(ModuleRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function run(AuditContext $context, array $modules = [], array $onlyRules = []): AuditRun
    {
        $this->recoverStaleRuns();
        $this->reconcileSupersededFindings($context);

        $run = AuditRun::create([
            'run_no' => $this->nextRunNo(),
            'tenant_key' => (string) $context->tenantKey,
            'business_id' => $context->businessId,
            'location_id' => $context->locationId,
            'started_by' => $context->userId,
            'status' => 'running',
            'started_at' => now(),
            'context' => $this->progressContext($context, null, 'starting'),
        ]);

        $summary = [
            'rules' => 0,
            'checked' => 0,
            'findings' => 0,
            'skipped' => 0,
            'errors' => 0,
            'by_severity' => [],
            'last_rule' => null,
        ];

        $finished = false;
        $this->registerFatalShutdownGuard($run->id, $finished);

        try {
            foreach ($this->registry->rules() as $class) {
                $rule = app($class);

                if ($modules && !in_array($rule->module(), $modules, true)) {
                    continue;
                }
                if ($onlyRules && !in_array($rule->code(), $onlyRules, true)) {
                    continue;
                }
                if (!$this->ruleEnabled($rule->code(), $context)) {
                    $summary['skipped']++;
                    continue;
                }
                if (!$rule->supports($context)) {
                    $summary['skipped']++;
                    continue;
                }

                $summary['rules']++;
                $summary['last_rule'] = $rule->code();
                $this->updateProgress($run, $context, $rule->code(), 'running');

                try {
                    $items = $rule->run($context);
                    $summary['checked']++;

                    // If this rule failed safely on an earlier run but completes now,
                    // close the old audit-engine warning. This changes Audit history
                    // only; it never touches operational ERP records.
                    $this->resolvePreviousRuleErrors($run, $context, $rule);

                    foreach ($items as $item) {
                        $finding = $this->saveFinding($run, $context, $rule, $item);
                        $summary['findings']++;
                        $severity = $finding->severity ?: 'warning';
                        $summary['by_severity'][$severity] = ($summary['by_severity'][$severity] ?? 0) + 1;
                    }

                    $this->updateProgress($run, $context, $rule->code(), 'completed');
                } catch (\Throwable $e) {
                    $summary['errors']++;
                    $this->saveRuleError($run, $context, $rule, $e);
                    $this->updateProgress($run, $context, $rule->code(), 'failed_safely', $e->getMessage());
                }
            }

            $run->update([
                'status' => 'completed',
                'finished_at' => now(),
                'summary' => $summary,
                'context' => $this->progressContext($context, null, 'completed', null, $summary['last_rule']),
            ]);

            $finished = true;
            return $run->fresh();
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'summary' => array_merge($summary, ['fatal' => $e->getMessage()]),
                'context' => $this->progressContext($context, $summary['last_rule'], 'failed', $e->getMessage(), $summary['last_rule']),
            ]);
            $finished = true;
            throw $e;
        }
    }

    protected function reconcileSupersededFindings(AuditContext $context): void
    {
        /*
         * v1.0.10 superseded-rule reconciliation.
         *
         * Earlier Audit versions produced findings from assumptions that do not
         * match this ERP's accounting/payment model:
         *  - v1.0.7 FIN-BAL-001 assumed every transaction_id was a complete journal.
         *  - v1.0.8 FIN-BAL-001 repeated that assumption after filtering deleted rows.
         *  - pre-v1.0.8 XMOD-PAY-001 treated legitimate direct payments with a NULL
         *    transaction_id as orphan payments.
         *
         * These are Audit false positives, not repaired ERP records. Reclassify only
         * the recognisable old finding signatures. Genuine v1.0.9+ pair findings use
         * different messages and are never touched here.
         */
        try {
            $query = AuditFinding::where('tenant_key', (string) $context->tenantKey)
                ->where(function ($q) {
                    $q->where(function ($x) {
                        $x->where('rule_code', 'FIN-BAL-001')
                            ->where(function ($m) {
                                $m->where('message', 'like', 'Transaction #% has unequal debit and credit totals.%')
                                    ->orWhere('message', 'like', 'Transaction #%has unequal debit and credit totals.%')
                                    ->orWhere('message', 'like', 'Active transaction #% has unequal active debit and credit totals.%')
                                    ->orWhere('message', 'like', 'Active transaction #%has unequal active debit and credit totals.%');
                            });
                    })->orWhere(function ($x) {
                        $x->where('rule_code', 'XMOD-PAY-001')
                            ->where(function ($m) {
                                $m->where('payload', 'like', '%"transaction_id":null%')
                                    ->orWhere('message', 'like', '%without parent transaction%');
                            });
                    });
                });

            if ($context->businessId === null) {
                $query->whereNull('business_id');
            } else {
                $query->where('business_id', $context->businessId);
            }

            if ($context->locationId === null) {
                $query->whereNull('location_id');
            } else {
                $query->where('location_id', $context->locationId);
            }

            $query->orderBy('id')->chunkById(100, function ($findings) use ($context) {
                foreach ($findings as $finding) {
                    if ($finding->status === 'false_positive') {
                        continue;
                    }

                    $from = $finding->status;
                    $finding->update([
                        'status' => 'false_positive',
                        'resolved_at' => null,
                        'resolved_by' => null,
                    ]);

                    $this->history(
                        $finding,
                        $from,
                        'false_positive',
                        'Automatically reclassified: finding was produced by superseded Audit rule logic corrected in v1.0.10.',
                        $context->userId
                    );
                }
            });
        } catch (\Throwable $e) {
            // Reconciliation is best-effort and must never prevent an audit run.
        }
    }

    protected function recoverStaleRuns(): void
    {
        $minutes = max(15, (int) config('audit.stale_run_minutes', 60));

        try {
            AuditRun::where('status', 'running')
                ->whereNotNull('started_at')
                ->where('started_at', '<', now()->subMinutes($minutes))
                ->orderBy('id')
                ->chunkById(100, function ($runs) use ($minutes) {
                    foreach ($runs as $stale) {
                        $summary = is_array($stale->summary) ? $stale->summary : [];
                        $summary['recovered_as_stale'] = true;
                        $summary['stale_after_minutes'] = $minutes;

                        $context = is_array($stale->context) ? $stale->context : [];
                        $context['progress_status'] = 'stale_recovered';
                        $context['progress_at'] = now()->toDateTimeString();

                        $stale->update([
                            'status' => 'failed',
                            'finished_at' => now(),
                            'summary' => $summary,
                            'context' => $context,
                        ]);
                    }
                });
        } catch (\Throwable $e) {
            // Stale-run recovery must never prevent a new audit from starting.
        }
    }

    protected function registerFatalShutdownGuard(int $runId, bool &$finished): void
    {
        register_shutdown_function(function () use ($runId, &$finished) {
            if ($finished) {
                return;
            }

            $error = error_get_last();
            if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
                return;
            }

            try {
                $run = AuditRun::find($runId);
                if (!$run || $run->status !== 'running') {
                    return;
                }

                $summary = is_array($run->summary) ? $run->summary : [];
                $summary['fatal'] = (string) ($error['message'] ?? 'Fatal PHP error');
                $summary['fatal_file'] = $error['file'] ?? null;
                $summary['fatal_line'] = $error['line'] ?? null;

                $context = is_array($run->context) ? $run->context : [];
                $context['progress_status'] = 'fatal';
                $context['progress_at'] = now()->toDateTimeString();

                $run->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'summary' => $summary,
                    'context' => $context,
                ]);
            } catch (\Throwable $ignored) {
            }
        });
    }

    protected function updateProgress(AuditRun $run, AuditContext $context, string $ruleCode, string $status, ?string $message = null): void
    {
        try {
            $run->update([
                'context' => $this->progressContext($context, $ruleCode, $status, $message, $status === 'completed' ? $ruleCode : null),
            ]);
        } catch (\Throwable $e) {
            // Progress telemetry is best-effort and must not break the audit.
        }
    }

    protected function progressContext(AuditContext $context, ?string $currentRule, string $status, ?string $message = null, ?string $lastRule = null): array
    {
        $data = $context->toArray();
        $data['current_rule'] = $currentRule;
        $data['last_rule'] = $lastRule;
        $data['progress_status'] = $status;
        $data['progress_at'] = now()->toDateTimeString();
        if ($message !== null && $message !== '') {
            $data['progress_message'] = $message;
        }
        return $data;
    }

    protected function saveFinding(AuditRun $run, AuditContext $context, $rule, array $item): AuditFinding
    {
        $sourceTable = (string) ($item['source_table'] ?? 'system');
        $sourceId = (string) ($item['source_id'] ?? '0');
        $businessId = array_key_exists('business_id', $item) ? $item['business_id'] : $context->businessId;
        $locationId = array_key_exists('location_id', $item) ? $item['location_id'] : $context->locationId;
        $businessId = ($businessId === '' || $businessId === false) ? null : $businessId;
        $locationId = ($locationId === '' || $locationId === false) ? null : $locationId;

        $fingerprint = hash('sha256', implode('|', [
            (string) $context->tenantKey,
            (string) $businessId,
            (string) $locationId,
            $rule->code(),
            $sourceTable,
            $sourceId,
            (string) ($item['message'] ?? ''),
        ]));

        $finding = AuditFinding::where('fingerprint', $fingerprint)->first();
        $attributes = [
            'audit_run_id' => $run->id,
            'rule_code' => $rule->code(),
            'module' => $rule->module(),
            'tenant_key' => (string) $context->tenantKey,
            'business_id' => $businessId,
            'location_id' => $locationId,
            'source_table' => $sourceTable,
            'source_id' => $sourceId,
            'severity' => $item['severity'] ?? $rule->defaultSeverity(),
            'title' => $item['title'] ?? $rule->title(),
            'message' => $item['message'] ?? $rule->description(),
            'expected_value' => isset($item['expected']) ? (string) $item['expected'] : null,
            'actual_value' => isset($item['actual']) ? (string) $item['actual'] : null,
            'payload' => $item['payload'] ?? [],
            'last_seen_at' => now(),
        ];

        if (!$finding) {
            $attributes['finding_no'] = $this->nextFindingNo();
            $attributes['fingerprint'] = $fingerprint;
            $attributes['status'] = 'open';
            $attributes['first_seen_at'] = now();
            $finding = AuditFinding::create($attributes);
            $this->history($finding, null, 'open', 'Finding created by audit engine.', $context->userId);
        } else {
            if (in_array($finding->status, ['resolved', 'false_positive'], true)) {
                $this->history($finding, $finding->status, 'reopened', 'Issue detected again by audit engine.', $context->userId);
                $attributes['status'] = 'reopened';
                $attributes['resolved_at'] = null;
            }
            $finding->update($attributes);
        }

        return $finding;
    }

    protected function resolvePreviousRuleErrors(AuditRun $run, AuditContext $context, $rule): void
    {
        try {
            $query = AuditFinding::where('tenant_key', (string) $context->tenantKey)
                ->where('rule_code', $rule->code())
                ->where('source_table', 'audit_engine')
                ->where('source_id', $rule->code())
                ->whereIn('status', ['open', 'reopened']);

            if ($context->businessId === null) {
                $query->whereNull('business_id');
            } else {
                $query->where('business_id', $context->businessId);
            }

            if ($context->locationId === null) {
                $query->whereNull('location_id');
            } else {
                $query->where('location_id', $context->locationId);
            }

            foreach ($query->get() as $finding) {
                $from = $finding->status;
                $finding->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                    'resolved_by' => $context->userId,
                ]);

                $this->history(
                    $finding,
                    $from,
                    'resolved',
                    'Audit rule completed successfully in '.$run->run_no.' after an earlier safe execution failure.',
                    $context->userId
                );
            }
        } catch (\Throwable $e) {
            // Error-warning reconciliation is best-effort and must not break a run.
        }
    }

    protected function saveRuleError(AuditRun $run, AuditContext $context, $rule, \Throwable $e): void
    {
        $item = [
            'source_table' => 'audit_engine',
            'source_id' => $rule->code(),
            'title' => 'Audit rule could not complete',
            'message' => $rule->code().' failed safely: '.$e->getMessage(),
            'expected' => 'Rule completes or skips without affecting ERP data',
            'actual' => 'Rule exception caught',
            'severity' => 'warning',
            'payload' => ['exception' => get_class($e)],
        ];
        $this->saveFinding($run, $context, $rule, $item);
    }

    protected function history(AuditFinding $finding, $from, $to, string $note, $userId): void
    {
        AuditFindingHistory::create([
            'audit_finding_id' => $finding->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'changed_by' => $userId,
        ]);
    }

    protected function ruleEnabled(string $code, AuditContext $context): bool
    {
        try {
            $query = AuditRuleSetting::where('rule_code', $code);
            if ($context->businessId) {
                $query->where(function ($q) use ($context) {
                    $q->whereNull('business_id')->orWhere('business_id', $context->businessId);
                });
            }
            $setting = $query->orderByRaw('business_id IS NULL')->first();
            return $setting ? (bool) $setting->is_enabled : true;
        } catch (\Throwable $e) {
            return true;
        }
    }

    protected function nextRunNo(): string
    {
        $id = (int) (AuditRun::max('id') ?: 0) + 1;
        return 'AUDRUN-'.date('Y').'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    protected function nextFindingNo(): string
    {
        $id = (int) (AuditFinding::max('id') ?: 0) + 1;
        return 'AUD-'.date('Y').'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}

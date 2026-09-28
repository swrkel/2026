<?php

namespace App\Console\Commands;

use App\Services\BusinessIdentityReconciler;
use Illuminate\Console\Command;

/**
 * Read-only. Writes nothing to any database. Safe to run at any time,
 * including on a live estate during working hours.
 */
class BusinessIdentityAudit extends Command
{
    protected $signature = 'business:identity-audit
        {--central= : Central database name (default: the app connection database)}
        {--pattern= : Database name pattern to scan, e.g. nivasa_%}
        {--connection= : Connection whose credentials to borrow}';

    protected $description = 'Report the state of global_uid identity across the whole estate. Changes nothing.';

    public function handle(): int
    {
        $r = new BusinessIdentityReconciler(
            $this->option('connection') ?: null,
            $this->option('central') ?: null,
            $this->option('pattern') ?: null
        );

        $this->info('Central database : ' . $r->centralDb());
        $this->info('Scanning pattern : ' . $r->pattern());
        $this->newLine();

        // ---- central ---------------------------------------------------------
        $central = $r->conn($r->centralDb());

        $c = $central->selectOne(
            "SELECT COUNT(*) total, COUNT(global_uid) with_uid,
                    COUNT(DISTINCT global_uid) distinct_uid,
                    COUNT(DISTINCT tenant_id) tenants
             FROM `business`"
        );

        $this->line("<comment>CENTRAL business</comment>");
        $this->line("  businesses ........ {$c->total}");
        $this->line("  with global_uid ... {$c->with_uid}");
        $this->line("  distinct uid ...... {$c->distinct_uid}");
        $this->line("  distinct tenant_id  {$c->tenants}");

        $subCols = $r->columns($r->centralDb(), 'subscriptions');
        $hasUid  = in_array('global_uid', $subCols, true);
        $s = $central->selectOne(
            "SELECT COUNT(*) rows_total, COUNT(DISTINCT business_id) distinct_bid FROM `subscriptions`"
        );
        $orph = $central->selectOne(
            "SELECT COUNT(*) c FROM `subscriptions` s
             LEFT JOIN `business` b ON b.id = s.business_id WHERE b.id IS NULL"
        );

        $this->newLine();
        $this->line("<comment>CENTRAL subscriptions</comment>");
        $this->line("  rows .............. {$s->rows_total}");
        $this->line("  distinct business . {$s->distinct_bid}");
        $this->line("  global_uid column . " . ($hasUid ? 'present' : 'MISSING'));
        $this->line("  orphaned rows ..... {$orph->c}" . ($orph->c > 0 ? '  <- needs a decision' : ''));

        // ---- tenants ---------------------------------------------------------
        $table   = [];
        $totals  = ['biz' => 0, 'nouid' => 0, 'unreg' => 0];

        foreach ($r->tenantDatabases() as $db) {
            if (! $r->tableExists($db, 'business')) {
                $table[] = [$db, '-', '-', '-', '-', 'no business table'];
                continue;
            }

            $cols = $r->columns($db, 'business');
            if (! in_array('global_uid', $cols, true)) {
                $n = $r->conn($db)->selectOne("SELECT COUNT(*) c FROM `business`");
                $totals['biz']   += (int) $n->c;
                $totals['nouid'] += (int) $n->c;
                $totals['unreg'] += (int) $n->c;
                $table[] = [$db, $n->c, $n->c, $n->c, '-', 'NO global_uid COLUMN'];
                continue;
            }

            $row = $r->conn($db)->selectOne(
                "SELECT COUNT(*) total,
                        SUM(CASE WHEN global_uid IS NULL OR global_uid = '' THEN 1 ELSE 0 END) nouid
                 FROM `business`"
            );

            $unreg = $r->conn($db)->selectOne(
                "SELECT COUNT(*) c FROM `business` b
                 LEFT JOIN `{$r->centralDb()}`.`business` cb ON cb.global_uid = b.global_uid
                 WHERE cb.id IS NULL"
            );

            $tids = $r->conn($db)->selectOne(
                "SELECT COUNT(DISTINCT tenant_id) c FROM `business` WHERE tenant_id IS NOT NULL"
            );

            $totals['biz']   += (int) $row->total;
            $totals['nouid'] += (int) $row->nouid;
            $totals['unreg'] += (int) $unreg->c;

            $note = (int) $unreg->c === 0 ? 'ok' : 'needs registering';
            if ((int) $row->nouid > 0) {
                $note = 'needs seeding';
            }

            $table[] = [$db, $row->total, $row->nouid, $unreg->c, $tids->c, $note];
        }

        $this->newLine();
        $this->table(
            ['database', 'businesses', 'no uid', 'not in central', 'tenant ids', 'state'],
            $table
        );

        // ---- estate-wide duplicates -----------------------------------------
        $dupes = array_filter($r->estateUidMap(true), fn ($places) => count($places) > 1);

        $this->newLine();
        if ($dupes) {
            $this->error('DUPLICATE global_uid values found across the estate:');
            foreach ($dupes as $uid => $places) {
                $this->line("  {$uid}  ->  " . implode(', ', $places));
            }
        } else {
            $this->info('No duplicate global_uid values anywhere in the estate.');
        }

        $this->newLine();
        $this->line("<comment>Estate totals</comment>");
        $this->line("  tenant businesses ........... {$totals['biz']}");
        $this->line("  without a global_uid ........ {$totals['nouid']}");
        $this->line("  not registered in central ... {$totals['unreg']}");
        $this->newLine();
        $this->line('Nothing was changed. This command only reads.');

        return self::SUCCESS;
    }
}

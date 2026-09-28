<?php

namespace Modules\PetroPD\Console\Commands;

use Illuminate\Console\Command;
use Modules\PetroPD\Entities\Settlement;
use Modules\PetroPD\Services\PdSettlementIntegrityChecker;

/**
 * Check every PD settlement's records against each other.
 *
 *     php artisan petropd:check-settlements
 *     php artisan petropd:check-settlements --business=2
 *     php artisan petropd:check-settlements --settlement=PDST17
 *
 * Reports only. Changes nothing.
 *
 * WHY
 * ---
 * On 22 August, PDST13, PDST16 and PDST17 were each found wrong only when a
 * user noticed a figure they did not recognise. By then two were finalised and
 * posted to the accounts.
 *
 * Nothing had looked at them. This is the thing that looks - run it after a
 * day's settlements, or before finalising, and the same faults surface while
 * they are still cheap to correct.
 *
 * It is deliberately a report rather than a block. Several findings on
 * 22 August needed a human decision - whether a credit sale belonged to one
 * shift or another could only be answered from the physical slips - so this
 * hands the findings over rather than refusing to proceed.
 */
class CheckSettlementIntegrity extends Command
{
    protected $signature = 'petropd:check-settlements
                            {--business= : limit to one business id}
                            {--settlement= : check one settlement number}
                            {--errors-only : hide warnings}';

    protected $description = 'Report PD settlements whose records disagree with each other';

    public function handle(PdSettlementIntegrityChecker $checker): int
    {
        $query = Settlement::query()
            ->where('settlement_no', 'LIKE', 'PDST%')
            ->orderBy('id');

        if ($this->option('business')) {
            $query->where('business_id', (int) $this->option('business'));
        }

        if ($this->option('settlement')) {
            $query->where('settlement_no', $this->option('settlement'));
        }

        $settlements = $query->get();

        if ($settlements->isEmpty()) {
            $this->info('No PD settlements matched.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->line(sprintf('Checking %d settlement(s).', $settlements->count()));
        $this->line('');

        $clean = 0;
        $flagged = 0;
        $errorCount = 0;

        foreach ($settlements as $settlement) {
            $issues = $checker->check($settlement);

            if ($this->option('errors-only')) {
                $issues = array_values(array_filter(
                    $issues,
                    fn ($i) => $i['severity'] === PdSettlementIntegrityChecker::SEVERITY_ERROR
                ));
            }

            if (empty($issues)) {
                $clean++;
                continue;
            }

            $flagged++;

            $this->line(sprintf(
                '<comment>%s</comment>  (%s, operator %d)',
                $settlement->settlement_no,
                (int) $settlement->status === 0 ? 'finalised' : 'open',
                (int) $settlement->pump_operator_id
            ));

            foreach ($issues as $issue) {
                $isError = $issue['severity'] === PdSettlementIntegrityChecker::SEVERITY_ERROR;

                if ($isError) {
                    $errorCount++;
                }

                $this->line(sprintf(
                    '    %s %s',
                    $isError ? '<fg=red>ERROR  </>' : '<fg=yellow>warning</>',
                    $issue['message']
                ));
            }

            $this->line('');
        }

        $this->line(str_repeat('-', 60));
        $this->line(sprintf(
            '%d clean, %d with findings, %d errors.',
            $clean,
            $flagged,
            $errorCount
        ));

        if ($flagged > 0) {
            $this->line('');
            $this->line('Nothing has been changed. Each finding needs a decision -');
            $this->line('several will turn out to be correct once checked against');
            $this->line('the physical records.');
        }

        return self::SUCCESS;
    }
}

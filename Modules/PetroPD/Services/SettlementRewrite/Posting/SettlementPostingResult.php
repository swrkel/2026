<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

class SettlementPostingResult
{
    protected array $posted = [];
    protected array $skipped = [];
    protected array $warnings = [];

    /*
     |--------------------------------------------------------------------------
     | $message accepts a string OR an array of details.
     |--------------------------------------------------------------------------
     |
     | The signature required a string, but SIX callers pass an array of details:
     |
     |     SalesIncomePostingService, CustomerLedgerPostingService,
     |     PumpOperatorLedgerPostingService, PaymentAccountBookPostingService,
     |     CogsPostingService, SettlementLegacyPostingBridge
     |
     | PHP raised
     |     Argument #2 ($message) must be of type string, array given
     | which aborted the whole posting run and surfaced as "Something went wrong"
     | when finalising a settlement.
     |
     | Widening the parameter here fixes all six at once. Changing the callers
     | instead would mean rewriting six services and losing the detail they
     | record - detail that is useful when a posting has to be traced later.
     |
     | Arrays are stored as JSON so everything reading ->posted() still receives
     | a string, exactly as before.
     */
    public function posted(string $area, $message = ''): void
    {
        $message = $this->normaliseMessage($message);

        $this->posted[] = compact('area', 'message');
    }

    public function skipped(string $area, $message = ''): void
    {
        $message = $this->normaliseMessage($message);

        $this->skipped[] = compact('area', 'message');
    }

    /**
     * Coerces a message of any shape into a string, without throwing.
     */
    private function normaliseMessage($message): string
    {
        if (is_string($message)) {
            return $message;
        }

        if ($message === null || $message === false) {
            return '';
        }

        if (is_scalar($message)) {
            return (string) $message;
        }

        $encoded = json_encode($message);

        return $encoded === false ? '' : $encoded;
    }

    public function warning(string $area, string $message = ''): void
    {
        $this->warnings[] = compact('area', 'message');
    }

    public function toArray(): array
    {
        return [
            'posted' => $this->posted,
            'skipped' => $this->skipped,
            'warnings' => $this->warnings,
        ];
    }
}

<?php
namespace Modules\BankingAtmDebitCard\Services;

use Illuminate\Support\Facades\DB;
use Modules\BankingAtmDebitCard\Entities\DebitCard;

class CardIssueService
{
    public function issue(array $data): DebitCard
    {
        return DB::transaction(function () use ($data) {
            return DebitCard::create(array_merge($data, ['status' => 'pending']));
        });
    }

    public function activate(DebitCard $card): DebitCard
    {
        $card->update(['status' => 'active', 'activated_date' => now()->toDateString()]);
        return $card;
    }

    public function hotlist(DebitCard $card, ?string $remarks = null): DebitCard
    {
        $card->update(['status' => 'hotlisted', 'remarks' => $remarks]);
        return $card;
    }
}

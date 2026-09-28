<?php
namespace Modules\AirlineTicketingNew\Services\PostTicket;

use Modules\AirlineTicketingNew\Entities\RefundRule;
use Modules\AirlineTicketingNew\Entities\Ticket;

class RefundRuleEngine
{
    public function calculate(Ticket $ticket, string $requestDate): array
    {
        $rule = RefundRule::query()
            ->where('business_id', $ticket->business_id)
            ->where(function ($q) use ($ticket) {
                $q->whereNull('airline_id')->orWhere('airline_id', $ticket->airline_id);
            })
            ->whereDate('effective_from', '<=', $requestDate)
            ->where(function ($q) use ($requestDate) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $requestDate);
            })
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->first();

        $gross = (float)$ticket->grand_total;
        $fixed = (float)($rule?->penalty_amount ?? 0);
        $percent = (float)($rule?->penalty_percent ?? 0);
        $penalty = round($fixed + ($gross * $percent / 100), 4);

        return [
            'gross_amount' => round($gross, 4),
            'penalty_amount' => $penalty,
            'refund_amount' => max(0, round($gross - $penalty, 4)),
            'rule_id' => $rule?->id,
        ];
    }
}

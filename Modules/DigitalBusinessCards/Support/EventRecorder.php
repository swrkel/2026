<?php

namespace Modules\DigitalBusinessCards\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\DigitalBusinessCards\Models\Card;
use Modules\DigitalBusinessCards\Models\CardView;

class EventRecorder
{
    /**
     * Records a card interaction. IPs are never stored in the clear — only a
     * keyed hash, which is enough to de-duplicate but not to identify.
     */
    public function record(Card $card, string $event, Request $request): void
    {
        if (! config('digital-business-cards.analytics.enabled', true)) {
            return;
        }

        CardView::create([
            'card_id'    => $card->getKey(),
            'event'      => $event,
            'ip_hash'    => $this->ipHash($request),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'referrer'   => config('digital-business-cards.analytics.store_referrer', true)
                ? Str::limit((string) $request->headers->get('referer'), 250, '')
                : null,
            'created_at' => now(),
        ]);

        $column = $event === 'save' ? 'saves_count' : 'views_count';
        $card->newQuery()->whereKey($card->getKey())->increment($column);
    }

    protected function ipHash(Request $request): ?string
    {
        if (! config('digital-business-cards.analytics.store_ip_hash', true)) {
            return null;
        }

        return hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
    }
}

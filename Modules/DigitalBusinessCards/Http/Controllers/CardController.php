<?php

namespace Modules\DigitalBusinessCards\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\DigitalBusinessCards\Http\Requests\CardRequest;
use Modules\DigitalBusinessCards\Models\Card;
use Modules\DigitalBusinessCards\Support\QrCode;

class CardController extends Controller
{
    public function index(Request $request): View
    {
        $cards = $this->scope()
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        // scope() returns a fresh builder each call, so these don't interfere.
        $stats = [
            'total' => $this->scope()->count(),
            'live'  => $this->scope()->published()->count(),
            'views' => (int) $this->scope()->sum('views_count'),
            'saves' => (int) $this->scope()->sum('saves_count'),
        ];

        return view('dbc::index', [
            'cards'       => $cards,
            'stats'       => $stats,
            'qrAvailable' => app(QrCode::class)->available(),
        ]);
    }

    public function create(): View
    {
        return view('dbc::form', [
            'card' => new Card(['accent_color' => config('digital-business-cards.defaults.accent_color')]),
        ]);
    }

    public function store(CardRequest $request): RedirectResponse
    {
        $card = new Card($request->cardAttributes());
        $card->owner_id = $this->ownerId();
        $this->attachMedia($card, $request);
        $card->save();

        return redirect()
            ->route('dbc.edit', $card)
            ->with('dbc.status', 'Card created. It is '.($card->is_published ? 'live' : 'still a draft').'.');
    }

    public function edit(Card $card): View
    {
        $this->authorizeCard($card);

        return view('dbc::form', [
            'card'        => $card,
            'qrAvailable' => app(QrCode::class)->available(),
        ]);
    }

    public function update(CardRequest $request, Card $card): RedirectResponse
    {
        $this->authorizeCard($card);

        $card->fill($request->cardAttributes());
        $this->attachMedia($card, $request);
        $card->save();

        return redirect()
            ->route('dbc.edit', $card)
            ->with('dbc.status', 'Changes saved.');
    }

    public function destroy(Card $card): RedirectResponse
    {
        $this->authorizeCard($card);
        $card->delete();

        return redirect()
            ->route('dbc.index')
            ->with('dbc.status', 'Card deleted.');
    }

    /* -------------------------------------------------------------- Internals */

    protected function scope()
    {
        $query = Card::query();

        if (config('digital-business-cards.owner.scope_to_user', true) && $this->ownerId()) {
            $query->ownedBy($this->ownerId());
        }

        return $query;
    }

    protected function authorizeCard(Card $card): void
    {
        if (! config('digital-business-cards.owner.scope_to_user', true)) {
            return;
        }

        abort_if($this->ownerId() && $card->owner_id !== $this->ownerId(), 403);
    }

    protected function ownerId()
    {
        return auth(config('digital-business-cards.owner.guard'))->id();
    }

    protected function attachMedia(Card $card, CardRequest $request): void
    {
        $disk = config('digital-business-cards.media.disk', 'public');
        $path = config('digital-business-cards.media.path', 'digital-business-cards');

        foreach (['photo' => 'photo_path', 'logo' => 'logo_path'] as $input => $column) {
            if (! $request->hasFile($input)) {
                continue;
            }

            if ($card->{$column}) {
                app('filesystem')->disk($disk)->delete($card->{$column});
            }

            $card->{$column} = $request->file($input)->store($path, $disk);
        }
    }
}

<?php

namespace Modules\DigitalBusinessCards\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\DigitalBusinessCards\Models\Card;
use Modules\DigitalBusinessCards\Support\EventRecorder;
use Modules\DigitalBusinessCards\Support\QrCode;
use Modules\DigitalBusinessCards\Support\VCard;

class PublicCardController extends Controller
{
    public function __construct(protected EventRecorder $events)
    {
    }

    public function show(Request $request, string $slug): View
    {
        $card = $this->resolve($slug);

        $this->events->record($card, 'view', $request);

        return view('dbc::show', [
            'card'        => $card,
            'qrAvailable' => app(QrCode::class)->available(),
        ]);
    }

    public function vcard(Request $request, string $slug): Response
    {
        $card  = $this->resolve($slug);
        $vcard = VCard::for($card);

        $this->events->record($card, 'save', $request);

        return response($vcard->render(), 200, [
            'Content-Type'        => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$vcard->filename().'"',
            'Cache-Control'       => 'no-store',
        ]);
    }

    public function qr(string $slug): Response
    {
        $card  = $this->resolve($slug);
        $image = app(QrCode::class)->png($card->publicUrl());

        abort_if($image === null, 404, 'No QR renderer is installed.');

        return response($image['body'], 200, [
            'Content-Type'  => $image['mime'],
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    protected function resolve(string $slug): Card
    {
        return Card::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();
    }
}

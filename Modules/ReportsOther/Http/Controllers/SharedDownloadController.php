<?php

namespace Modules\ReportsOther\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\ReportsOther\Models\ShareLink;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SharedDownloadController extends Controller
{
    public function __invoke(string $token): BinaryFileResponse
    {
        $link = ShareLink::query()->where('token', $token)->firstOrFail();
        abort_if($link->expires_at && $link->expires_at->isPast(), 410, 'This download link has expired.');

        $base = realpath(storage_path('app/reports-other/shares'));
        $path = $base ? realpath($base.DIRECTORY_SEPARATOR.$link->relative_path) : false;
        abort_unless($base && $path && str_starts_with($path, $base.DIRECTORY_SEPARATOR) && is_file($path), 404);

        $link->increment('downloads');
        $link->forceFill(['last_downloaded_at' => now()])->save();

        return response()->download($path, $link->download_name, ['Content-Type' => $link->mime_type]);
    }
}

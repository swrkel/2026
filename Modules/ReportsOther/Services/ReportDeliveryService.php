<?php

namespace Modules\ReportsOther\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\ReportsOther\Models\ShareLink;
use Modules\ReportsOther\Support\CurrentScope;
use RuntimeException;

class ReportDeliveryService
{
    public function __construct(private readonly CurrentScope $scope)
    {
    }

    public function publishFile(string $reportKey, string $absoluteFile, string $downloadName, string $mimeType, string $channel = 'link'): ShareLink
    {
        $real = realpath($absoluteFile);
        if (!$real || !is_file($real)) {
            throw new RuntimeException('Report file not found.');
        }

        $base = storage_path('app/reports-other/shares');
        if (!is_dir($base) && !mkdir($base, 0755, true) && !is_dir($base)) {
            throw new RuntimeException('Unable to create Reports - Other share directory.');
        }

        $storedName = Str::random(40).'-'.preg_replace('/[^A-Za-z0-9._-]/', '_', basename($downloadName));
        $target = $base.DIRECTORY_SEPARATOR.$storedName;
        if (!copy($real, $target)) {
            throw new RuntimeException('Unable to prepare downloadable report link.');
        }

        return ShareLink::query()->create([
            'business_id' => $this->scope->businessId(),
            'location_id' => $this->scope->locationId(),
            'store_id' => $this->scope->storeId(),
            'token' => Str::random(64),
            'channel' => $channel,
            'report_key' => $reportKey,
            'payload' => [],
            'relative_path' => $storedName,
            'download_name' => $downloadName,
            'mime_type' => $mimeType,
            'expires_at' => now()->addDays((int) config('reportsother.share_expiry_days', 7)),
            'created_by' => $this->scope->userId(),
            'downloads' => 0,
        ]);
    }

    public function publishContent(string $reportKey, string $content, string $downloadName, string $mimeType = 'text/html; charset=utf-8', string $channel = 'link'): ShareLink
    {
        $tmpDir = storage_path('app/reports-other/tmp');
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0755, true) && !is_dir($tmpDir)) {
            throw new RuntimeException('Unable to create Reports - Other temporary directory.');
        }

        $tmp = $tmpDir.DIRECTORY_SEPARATOR.Str::random(48).'.html';
        if (file_put_contents($tmp, $content) === false) {
            throw new RuntimeException('Unable to create downloadable report content.');
        }

        try {
            return $this->publishFile($reportKey, $tmp, $downloadName, $mimeType, $channel);
        } finally {
            @unlink($tmp);
        }
    }

    public function publicUrl(ShareLink $link): string
    {
        return route('reports-other.shared.download', ['token' => $link->token]);
    }

    public function sendEmail(string $to, string $subject, string $message, ShareLink $link): void
    {
        $url = $this->publicUrl($link);
        Mail::raw(trim($message)."\n\nDownload: ".$url, function ($mail) use ($to, $subject) {
            $mail->to($to)->subject($subject);
        });
    }

    public function sendSms(string $to, string $message, ShareLink $link): void
    {
        $cfg = config('reportsother.sms');
        if (empty($cfg['endpoint'])) {
            throw new RuntimeException('REO_SMS_ENDPOINT is not configured.');
        }

        $client = Http::timeout((int) $cfg['timeout']);
        if (!empty($cfg['token'])) {
            $client = $client->withToken($cfg['token']);
        }

        $response = $client->post($cfg['endpoint'], [
            $cfg['to_field'] => $to,
            $cfg['message_field'] => trim($message).' '.$this->publicUrl($link),
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('SMS gateway rejected the message (HTTP '.$response->status().').');
        }
    }

    public function whatsappUrl(string $phone, string $message, ShareLink $link): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        $base = $digits !== '' ? 'https://wa.me/'.$digits : 'https://wa.me/';
        return $base.'?text='.rawurlencode(trim($message).' '.$this->publicUrl($link));
    }
}

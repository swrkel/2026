<?php

namespace Modules\ReportsOther\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Modules\ReportsOther\Models\Receipt;
use Modules\ReportsOther\Services\BusinessSettingsGateway;
use Modules\ReportsOther\Services\OrganizationGateway;
use Modules\ReportsOther\Services\ReceiptService;
use Modules\ReportsOther\Services\ReportDeliveryService;
use Modules\ReportsOther\Support\CurrentScope;

class ReceiptShareController extends Controller
{
    public function receipt(
        Request $request,
        Receipt $receipt,
        ReceiptService $receiptService,
        ReportDeliveryService $delivery,
        OrganizationGateway $organisation,
        BusinessSettingsGateway $settings,
        CurrentScope $scope,
    ) {
        $receiptService->guardReceipt($receipt);
        $data = $this->shareData($request);
        $receipt->load(['details', 'cheques']);

        $content = view('reportsother::cash-receipt.share.receipt', [
            'receipt' => $receipt,
            'organisation' => $organisation->identity(),
            'currencyPrecision' => $settings->currencyPrecision($scope->businessId()),
        ])->render();

        $link = $delivery->publishContent(
            'cash_receipt',
            $content,
            'Receipt-'.$this->fileSafe($receipt->receipt_no).'.html',
            'text/html; charset=utf-8',
            $data['channel']
        );

        $subject = 'Cash Receipt '.$receipt->receipt_no;
        $message = trim((string) ($data['message'] ?? '')) ?: $subject;
        return $this->deliver($delivery, $link, $data, $subject, $message);
    }

    public function list(
        Request $request,
        ReportDeliveryService $delivery,
        OrganizationGateway $organisation,
        BusinessSettingsGateway $settings,
        CurrentScope $scope,
    ) {
        $data = $this->shareData($request) + $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:190'],
        ]);

        try {
            $from = !empty($data['date_from']) ? Carbon::parse($data['date_from']) : now()->startOfMonth();
            $to = !empty($data['date_to']) ? Carbon::parse($data['date_to']) : now();
        } catch (\Throwable) {
            $from = now()->startOfMonth();
            $to = now();
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $query = Receipt::query()
            ->where('scope_key', $scope->key())
            ->whereBetween('receipt_date', [$from->toDateString(), $to->toDateString()]);

        $search = trim((string) ($data['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('receipt_no', 'like', $like)
                    ->orWhere('source_name', 'like', $like)
                    ->orWhere('membership_no', 'like', $like)
                    ->orWhere('entered_by_name', 'like', $like);
            });
        }

        $receipts = $query->orderByDesc('receipt_date')->orderByDesc('id')->get();
        $content = view('reportsother::cash-receipt.share.list', [
            'receipts' => $receipts,
            'organisation' => $organisation->identity(),
            'currencyPrecision' => $settings->currencyPrecision($scope->businessId()),
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
        ])->render();

        $link = $delivery->publishContent(
            'cash_receipt_list',
            $content,
            'Cash-Receipt-List-'.$from->format('Ymd').'-'.$to->format('Ymd').'.html',
            'text/html; charset=utf-8',
            $data['channel']
        );

        $subject = 'Cash Receipt List '.$from->toDateString().' to '.$to->toDateString();
        $message = trim((string) ($data['message'] ?? '')) ?: $subject;
        return $this->deliver($delivery, $link, $data, $subject, $message);
    }

    private function shareData(Request $request): array
    {
        $data = $request->validate([
            'channel' => ['required', 'in:email,sms,whatsapp,link'],
            'recipient' => ['nullable', 'string', 'max:190'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        if (in_array($data['channel'], ['email', 'sms'], true) && trim((string) ($data['recipient'] ?? '')) === '') {
            throw ValidationException::withMessages(['recipient' => 'Recipient is required for '.$data['channel'].'.']);
        }
        if ($data['channel'] === 'email' && !filter_var($data['recipient'], FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['recipient' => 'Enter a valid email address.']);
        }

        return $data;
    }

    private function deliver(ReportDeliveryService $delivery, $link, array $data, string $subject, string $message)
    {
        if ($data['channel'] === 'email') {
            $delivery->sendEmail($data['recipient'], $subject, $message, $link);
        } elseif ($data['channel'] === 'sms') {
            $delivery->sendSms($data['recipient'], $message, $link);
        } elseif ($data['channel'] === 'whatsapp') {
            return response()->json([
                'ok' => true,
                'url' => $delivery->whatsappUrl((string) ($data['recipient'] ?? ''), $message, $link),
                'download_url' => $delivery->publicUrl($link),
            ]);
        }

        return response()->json([
            'ok' => true,
            'download_url' => $delivery->publicUrl($link),
            'message' => $data['channel'] === 'link' ? 'Downloadable link created.' : ucfirst($data['channel']).' sent successfully.',
        ]);
    }

    private function fileSafe(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_-]+/', '_', $value) ?: 'receipt';
    }
}

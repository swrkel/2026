<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerReferenceService;

/**
 * Task 8046 - the QR Action popup and its four actions.
 *
 * Spec: "QR Action (When clicking, a pop up to show with below details and the
 * functionalities) - Print, PDF, WhatsApp, EMail".
 *
 * Split out of CustomerReferenceController because these four actions bring in
 * their own dependencies - a PDF writer, the mailer, the customer's contact
 * details - that the list/CRUD screens have no use for. Keeping them apart
 * means the list page cannot break because of a mail configuration problem.
 */
class CustomerReferenceQrController extends Controller
{
    protected CustomerReferenceService $service;

    protected CustomerPermissionService $permissionService;

    public function __construct(
        CustomerReferenceService $service,
        CustomerPermissionService $permissionService
    ) {
        $this->service = $service;
        $this->permissionService = $permissionService;
    }

    protected function businessId(Request $request): int
    {
        return (int) ($request->session()->get('business.id') ?: $request->session()->get('user.business_id'));
    }

    /**
     * The QR popup body: the code plus the four action buttons.
     */
    public function modal(Request $request, int $id)
    {
        $this->permissionService->authorize('view');

        $businessId = $this->businessId($request);
        $data = $this->service->viewModel($businessId, $id);
        $data['contact'] = $this->contactDetails($businessId, (int) $data['reference']->customer_id);
        $data['pdfAvailable'] = $this->pdfDriver() !== null;

        return view('customers::customer_references.partials.qr_modal', $data);
    }

    /**
     * Printable page. Opens in a new tab and triggers the print dialog.
     */
    public function print(Request $request, int $id)
    {
        $this->permissionService->authorize('view');

        $data = $this->service->viewModel($this->businessId($request), $id);
        $data['autoPrint'] = true;

        return view('customers::customer_references.qr_print', $data);
    }

    /**
     * Download the QR as a PDF.
     *
     * The host application's PDF package is detected rather than assumed. If
     * none is installed the user is sent to the print view with a note telling
     * them to use their browser's "Save as PDF" - a working path rather than a
     * dead button.
     */
    public function pdf(Request $request, int $id)
    {
        $this->permissionService->authorize('view');

        $data = $this->service->viewModel($this->businessId($request), $id);
        $driver = $this->pdfDriver();

        /*
         * A PDF writer rasterises the HTML without running JavaScript, so the
         * browser-side QR fallback cannot help here. If the QR could not be
         * rendered server-side, generating a PDF would produce a document with
         * an empty box where the code should be. The print view is used
         * instead, where the browser renders the QR and "Save as PDF" gives the
         * user a correct document.
         */
        if ($driver === null || empty($data['qr_svg'])) {
            $data['autoPrint'] = true;
            $data['pdfFallbackNotice'] = true;

            return view('customers::customer_references.qr_print', $data);
        }

        try {
            $html = view('customers::customer_references.qr_print', array_merge($data, ['autoPrint' => false]))->render();
            $filename = $this->pdfFilename($data);

            if ($driver === 'dompdf-facade') {
                return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->download($filename);
            }

            if ($driver === 'dompdf-facade-legacy') {
                return \Barryvdh\DomPDF\Facade::loadHTML($html)->download($filename);
            }

            if ($driver === 'snappy') {
                return \Barryvdh\Snappy\Facades\SnappyPdf::loadHTML($html)->download($filename);
            }
        } catch (\Throwable $e) {
            Log::error('Customer Reference QR PDF generation failed', [
                'id' => $id,
                'driver' => $driver,
                'message' => $e->getMessage(),
            ]);
        }

        $data['autoPrint'] = true;
        $data['pdfFallbackNotice'] = true;

        return view('customers::customer_references.qr_print', $data);
    }

    /**
     * Build the WhatsApp share link.
     *
     * Returns a wa.me URL for the browser to open rather than sending server
     * side. Sending directly would need a WhatsApp Business API account and
     * per-tenant credentials, which this module does not have and cannot
     * assume. The customer's WhatsApp number is pre-filled when the contact
     * record has one.
     */
    public function whatsapp(Request $request, int $id): JsonResponse
    {
        $this->permissionService->authorize('view');

        $businessId = $this->businessId($request);
        $data = $this->service->viewModel($businessId, $id);
        $contact = $this->contactDetails($businessId, (int) $data['reference']->customer_id);

        $number = preg_replace('/[^0-9]/', '', (string) ($request->input('number') ?: $contact['whatsapp'] ?: $contact['mobile']));

        $message = $this->shareMessage($data);

        $url = 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
        if (empty($number)) {
            // No number anywhere: still open WhatsApp with the message ready so
            // the user can pick a recipient by hand.
            $url = 'https://wa.me/?text=' . rawurlencode($message);
        }

        return response()->json([
            'success' => true,
            'url' => $url,
            'has_number' => ! empty($number),
        ]);
    }

    /**
     * Email the QR to the customer.
     */
    public function email(Request $request, int $id): JsonResponse
    {
        $this->permissionService->authorize('view');

        $businessId = $this->businessId($request);
        $data = $this->service->viewModel($businessId, $id);
        $contact = $this->contactDetails($businessId, (int) $data['reference']->customer_id);

        $to = trim((string) ($request->input('email') ?: $contact['email']));

        $validator = validator(['email' => $to], ['email' => 'required|email']);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => 'No valid email address is available for this customer. Enter one and try again.',
            ], 422);
        }

        try {
            $subject = $data['reference']->documentTitle() . ' QR - ' . $data['reference']->reference_no;
            $body = view('customers::customer_references.qr_email', array_merge($data, [
                'customEmail' => $to,
            ]))->render();

            Mail::html($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });

            return response()->json([
                'success' => true,
                'msg' => 'QR code emailed to ' . $to . '.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Customer Reference QR email failed', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'Unable to send the email. Check the mail settings for this business.',
            ], 500);
        }
    }

    /**
     * Plain-text message used for WhatsApp sharing.
     *
     * The QR payload itself is included so the recipient gets the details even
     * if they never scan the image.
     */
    protected function shareMessage(array $data): string
    {
        $lines = [];
        // "Customer Vehicle" when the reference is a vehicle, so the recipient
        // sees what the code is for before reading the detail lines.
        $lines[] = $data['reference']->documentTitle() . ' QR';
        $lines[] = '';
        $lines[] = (string) $data['qr_payload'];

        return implode("\n", $lines);
    }

    protected function pdfFilename(array $data): string
    {
        $safe = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) $data['reference']->reference_no);
        $prefix = $data['reference']->is_vehicle ? 'customer-vehicle' : 'customer-reference';

        return $prefix . '-' . ($safe ?: $data['reference']->id) . '.pdf';
    }

    /**
     * Detect the host application's PDF package.
     */
    protected function pdfDriver(): ?string
    {
        $configured = config('customers.pdf_driver');
        if (! empty($configured)) {
            return (string) $configured;
        }

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return 'dompdf-facade';
        }

        // dompdf v0.8 and earlier exposed the facade at a different path.
        if (class_exists(\Barryvdh\DomPDF\Facade::class)) {
            return 'dompdf-facade-legacy';
        }

        if (class_exists(\Barryvdh\Snappy\Facades\SnappyPdf::class)) {
            return 'snappy';
        }

        return null;
    }

    /**
     * Pull the customer's email / mobile / WhatsApp number.
     *
     * Column presence is checked because `contacts` varies across tenant schema
     * versions and a missing column would otherwise fail the query.
     *
     * @return array{email: string, mobile: string, whatsapp: string}
     */
    protected function contactDetails(int $businessId, int $customerId): array
    {
        $blank = ['email' => '', 'mobile' => '', 'whatsapp' => ''];

        if (! Schema::hasTable('contacts')) {
            return $blank;
        }

        $columns = ['id'];
        foreach (['email', 'mobile', 'whatsapp_number'] as $column) {
            if (Schema::hasColumn('contacts', $column)) {
                $columns[] = $column;
            }
        }

        $row = DB::table('contacts')
            ->where('business_id', $businessId)
            ->where('id', $customerId)
            ->select($columns)
            ->first();

        if (! $row) {
            return $blank;
        }

        return [
            'email' => (string) ($row->email ?? ''),
            'mobile' => (string) ($row->mobile ?? ''),
            'whatsapp' => (string) ($row->whatsapp_number ?? ''),
        ];
    }
}

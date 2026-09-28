<?php
namespace Modules\ManagementReport\Services\Delivery;

use Illuminate\Support\Facades\Mail;
use Modules\ManagementReport\Entities\ReportRun;

class EmailDeliveryService
{
    protected $pdf;

    public function __construct(PdfService $pdf)
    {
        $this->pdf = $pdf;
    }

    public function send(ReportRun $run, array $recipients, $body, $attachPdf = true)
    {
        foreach ($recipients as $recipient) {
            Mail::raw($body, function ($mail) use ($run, $recipient, $attachPdf) {
                $mail->to($recipient)->subject($run->report_title . ' - ' . $run->period_start);
                if ($attachPdf) {
                    $mail->attachData($this->pdf->binary($run), $this->pdf->filename($run), ['mime' => 'application/pdf']);
                }
            });
        }
        return ['status' => 'sent', 'provider' => 'laravel_mail'];
    }
}

<?php

namespace App\Mail;

use App\Services\Documents\GlobalPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Backward-compatible invoice mail using the global PDF service.
 */
class SendInvoice extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $title;
    public $logo;

    public function __construct($data, $title = 'Invoice', $logo = null)
    {
        $this->data = $data;
        $this->title = $title ?: 'Invoice';
        $this->logo = $logo;
    }

    public function build()
    {
        $html = view('sale_pos.receipts.shipping_print_receipt')
            ->with([
                'data' => $this->data,
                'title' => $this->title,
                'logo' => $this->logo,
            ])
            ->render();

        $pdfContent = app(GlobalPdfService::class)->binary(
            $html,
            [
                'format' => 'A5',
                'orientation' => 'L',
                'allow_unsafe_image_resizing' => true,
            ],
            [
                'business_id' => data_get($this->data, 'business_id'),
                'business_name' => data_get($this->data, 'business.name') ?: data_get($this->data, 'business_name'),
                'location_id' => data_get($this->data, 'location_id') ?: data_get($this->data, 'business_location_id'),
                'business_location' => data_get($this->data, 'location.name')
                    ?: data_get($this->data, 'business_location.name')
                    ?: (is_scalar(data_get($this->data, 'business_location')) ? data_get($this->data, 'business_location') : null),
                'page_title' => $this->title,
                'date_range' => data_get($this->data, 'date_range') ?: data_get($this->data, 'transaction_date'),
                'page_no' => 1,
            ]
        );

        return $this->subject($this->title)
            ->html('<p>Please find the document attached.</p>')
            ->attachData($pdfContent, 'invoice.pdf', ['mime' => 'application/pdf']);
    }
}

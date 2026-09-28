{{--
    Task 8046 - the QR Action popup.

    Spec: "QR Action (When clicking, a pop up to show with below details and the
    functionalities) - Print, PDF, WhatsApp, EMail".

    The QR image is rendered server-side when a QR library is available. When it
    is not, the container is left empty and carries data-qr-payload; the runtime
    script draws the code in the browser from that payload. Either way the user
    sees a QR, and Print and PDF both work because both are driven from a page
    that is already open.
--}}
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    <h4 class="modal-title">
        <i class="fa fa-qrcode"></i> QR Action
    </h4>
</div>

<div class="modal-body">

    <div class="alert alert-danger" id="cus_ref_qr_error" style="display: none;"></div>
    <div class="alert alert-success" id="cus_ref_qr_success" style="display: none;"></div>

    <div class="cus-ref-qr-holder"
         id="cus_ref_qr_holder"
         data-qr-payload="{{ $qr_payload }}">
        @if(! empty($qr_svg))
            {!! $qr_svg !!}
        @else
            <div class="text-muted" id="cus_ref_qr_placeholder">
                <i class="fa fa-spinner fa-spin"></i> Rendering QR code&hellip;
            </div>
        @endif
    </div>

    <table class="table table-bordered" style="margin-top: 10px;">
        <tbody>
            <tr>
                <th style="width: 40%;">Customer Name</th>
                <td>{{ $customer_name }}</td>
            </tr>
            <tr>
                <th>{{ $reference->referenceLabel() }}</th>
                <td>{{ $reference->reference_no }}</td>
            </tr>
            @if($reference->is_vehicle)
                <tr>
                    <th>Fuel Type</th>
                    <td>{{ $fuel_type_label }}</td>
                </tr>
            @endif
            <tr>
                <th>Status</th>
                <td>
                    @if($reference->is_active)
                        <span class="label label-success">Active</span>
                    @else
                        <span class="label label-default">Inactive</span>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    {{--
        Email and WhatsApp destinations are pre-filled from the contact record
        but stay editable, so a reference can be sent to a driver or a site
        contact without editing the customer master first.
    --}}
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="cus_ref_qr_email">Email to</label>
                <input type="email" class="form-control" id="cus_ref_qr_email"
                       value="{{ $contact['email'] }}" placeholder="customer@example.com">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="cus_ref_qr_whatsapp">WhatsApp number</label>
                <input type="text" class="form-control" id="cus_ref_qr_whatsapp"
                       value="{{ $contact['whatsapp'] ?: $contact['mobile'] }}"
                       placeholder="Include the country code">
            </div>
        </div>
    </div>

    @unless($pdfAvailable)
        <p class="help-block">
            <i class="fa fa-info-circle"></i>
            No PDF library was detected in this application, so <strong>PDF</strong> opens the print view
            where you can choose &ldquo;Save as PDF&rdquo;.
        </p>
    @endunless

</div>

<div class="modal-footer">
    <div class="btn-group pull-left" role="group">
        <a href="{{ route('customers.customer_references.qr.print', $reference->id) }}"
           target="_blank" rel="noopener" class="btn btn-default">
            <i class="fa fa-print"></i> Print
        </a>
        <a href="{{ route('customers.customer_references.qr.pdf', $reference->id) }}"
           target="_blank" rel="noopener" class="btn btn-default">
            <i class="fa fa-file-pdf-o"></i> PDF
        </a>
        <button type="button" class="btn btn-default" id="cus_ref_qr_whatsapp_button"
                data-id="{{ $reference->id }}">
            <i class="fa fa-whatsapp"></i> WhatsApp
        </button>
        <button type="button" class="btn btn-default" id="cus_ref_qr_email_button"
                data-id="{{ $reference->id }}">
            <i class="fa fa-envelope"></i> Email
        </button>
    </div>

    <button type="button" class="btn btn-primary" data-dismiss="modal">@lang('messages.close')</button>
</div>

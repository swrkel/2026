{{--
    Task 8046 - the read-only View popup from the Action column.
--}}
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    <h4 class="modal-title">
        <i class="fa fa-eye"></i> Customer Reference
    </h4>
</div>

<div class="modal-body">

    <table class="table table-bordered">
        <tbody>
            <tr>
                <th style="width: 40%;">Date &amp; Time</th>
                <td>{{ optional($reference->reference_datetime)->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <th>Customer</th>
                <td>{{ $customer_name }}</td>
            </tr>
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
            <tr>
                <th>Is a Vehicle</th>
                <td>{{ $reference->is_vehicle ? 'Yes' : 'No' }}</td>
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
                <th>Added By</th>
                <td>{{ $added_by_name }}</td>
            </tr>
            <tr>
                <th>Added On</th>
                <td>{{ optional($reference->created_at)->format('d/m/Y H:i') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="cus-ref-qr-holder" data-qr-payload="{{ $qr_payload }}">
        @if(! empty($qr_svg))
            {!! $qr_svg !!}
        @else
            <div class="text-muted">
                <i class="fa fa-spinner fa-spin"></i> Rendering QR code&hellip;
            </div>
        @endif
    </div>

</div>

<div class="modal-footer">
    <a href="{{ route('customers.customer_references.qr.print', $reference->id) }}"
       target="_blank" rel="noopener" class="btn btn-default pull-left">
        <i class="fa fa-print"></i> Print
    </a>
    <button type="button" class="btn btn-primary" data-dismiss="modal">@lang('messages.close')</button>
</div>

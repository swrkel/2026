<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('membership::lang.view_signature')</h4>
        </div>

        <div class="modal-body">
            <table class="table table-bordered">
                <tr>
                    <th width="30%">@lang('membership::lang.signature')</th>
                    <td>
                        @if($signature->signature_path && file_exists(public_path('uploads/' . $signature->signature_path)))
                            <img src="{{ asset('uploads/' . $signature->signature_path) }}" 
                                 style="max-width: 300px; max-height: 150px;" 
                                 alt="Signature">
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.added_by')</th>
                    <td>{{ optional($signature->createdBy)->username ?? '-' }}</td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.date_time')</th>
                    <td>{{ $signature->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            </table>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>


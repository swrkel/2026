<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close membership-signature-modal-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('membership::lang.authorized_signature')</h4>
        </div>
        <div class="modal-body">
            <table class="table table-bordered">
                <tr>
                    <th>@lang('membership::lang.date_time')</th>
                    <td>{{ optional($signature->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.status')</th>
                    <td>
                        @if($signature->is_active)
                            <span class="label label-success">@lang('membership::lang.active')</span>
                        @else
                            <span class="label label-danger">@lang('membership::lang.inactive')</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.added_by')</th>
                    <td>
                        @if($signature->createdBy)
                            {{ trim(($signature->createdBy->first_name ?? '') . ' ' . ($signature->createdBy->last_name ?? '')) ?: ($signature->createdBy->username ?? '-') }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.signature')</th>
                    <td>
                        @if($signature->signature_path && file_exists(public_path('uploads/' . $signature->signature_path)))
                            @php $ext = strtolower(pathinfo($signature->signature_path, PATHINFO_EXTENSION)); @endphp
                            @if(in_array($ext, ['jpg','jpeg','png','gif','webp','bmp','tiff','tif']))
                                <img src="{{ asset('uploads/' . $signature->signature_path) }}" style="max-width:300px; max-height:150px;" alt="Signature">
                            @else
                                <a href="{{ asset('uploads/' . $signature->signature_path) }}" target="_blank" class="btn btn-default btn-sm">
                                    <i class="fa fa-file"></i> View {{ strtoupper($ext) }}
                                </a>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                </tr>
            </table>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default membership-signature-modal-close" data-dismiss="modal" data-bs-dismiss="modal">
                @lang('messages.close')
            </button>
        </div>
    </div>
</div>

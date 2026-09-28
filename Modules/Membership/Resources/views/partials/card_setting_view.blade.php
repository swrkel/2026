<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('membership::lang.view_card_setting')</h4>
        </div>

        <div class="modal-body">
            <table class="table table-bordered">
                <tr>
                    <th width="30%">@lang('membership::lang.length') (mm)</th>
                    <td>{{ $cardSetting->length }}</td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.width') (mm)</th>
                    <td>{{ $cardSetting->width }}</td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.size_details')</th>
                    <td>{{ $cardSetting->length }} mm × {{ $cardSetting->width }} mm</td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.card_sample')</th>
                    <td>
                        @php
                            $l = (float) $cardSetting->length;
                            $w = (float) $cardSetting->width;
                            $maxPx = 120;
                            $maxMm = $l > 0 && $w > 0 ? max($l, $w) : 1;
                            $wPx = round($maxPx * ($w / $maxMm));
                            $hPx = round($maxPx * ($l / $maxMm));
                        @endphp
                        @if($l > 0 && $w > 0)
                            <div class="border rounded p-2 bg-light d-inline-block">
                                <div style="border:2px solid #333; background:#fff; width:{{ $wPx }}px; height:{{ $hPx }}px; display:inline-block;"></div>
                                <div class="small text-muted mt-1">{{ $cardSetting->length }} mm × {{ $cardSetting->width }} mm</div>
                            </div>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.added_by')</th>
                    <td>{{ optional($cardSetting->createdBy)->username ?? '-' }}</td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.date_time')</th>
                    <td>{{ $cardSetting->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            </table>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>


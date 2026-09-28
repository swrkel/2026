<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close membership-card-setting-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title" id="membershipCardSettingModalLabel">@lang('membership::lang.view_card_setting')</h4>
        </div>
        <div class="modal-body">
            <table class="table table-bordered">
                <tr>
                    <th width="30%">@lang('membership::lang.length') (mm)</th>
                    <td class="text-right">{{ number_format((float) $cardSetting->length, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.width') (mm)</th>
                    <td class="text-right">{{ number_format((float) $cardSetting->width, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.size_details')</th>
                    <td>{{ number_format((float) $cardSetting->length, 2, '.', ',') }} mm × {{ number_format((float) $cardSetting->width, 2, '.', ',') }} mm</td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.card_sample')</th>
                    <td>
                        @php
                            $l = (float) $cardSetting->length;
                            $w = (float) $cardSetting->width;
                            $maxPx = 120;
                            $maxMm = ($l > 0 && $w > 0) ? max($l, $w) : 1;
                            $wPx = round($maxPx * ($w / $maxMm));
                            $hPx = round($maxPx * ($l / $maxMm));
                        @endphp
                        @if($l > 0 && $w > 0)
                            <div style="padding:10px; background:#f5f5f5; border:1px solid #ddd; border-radius:4px; display:inline-block;">
                                <div style="border:2px solid #333; background:#fff; width:{{ $wPx }}px; height:{{ $hPx }}px; display:inline-block;"></div>
                                <div class="small text-muted" style="margin-top:4px;">{{ number_format($l, 2, '.', ',') }} mm × {{ number_format($w, 2, '.', ',') }} mm</div>
                            </div>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.date_time')</th>
                    <td>{{ optional($cardSetting->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            </table>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default membership-card-setting-close" data-dismiss="modal" data-bs-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>

@if(!empty($data))
    <div class="row">
        @foreach($data as $key => $value)
            <div class="col-md-4 col-sm-6">
                <div class="form-group">
                    <label>{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                    <div class="form-control" style="height:auto;min-height:34px;background:#f8f8f8;word-break:break-word;">
                        @if(is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1')
                            {{ in_array($value, [true, 1, '1'], true) ? 'Yes' : 'No' }}
                        @elseif(is_array($value) || is_object($value))
                            {{ json_encode($value) }}
                        @else
                            {{ $value === null || $value === '' ? '—' : $value }}
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="alert alert-info" style="margin-bottom:0;">No saved settings are available for this business.</div>
@endif

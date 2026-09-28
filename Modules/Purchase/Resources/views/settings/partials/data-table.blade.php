@if(!empty($rows))
    @php
        $columns = array_values(array_unique(array_merge(...array_map('array_keys', $rows))));
    @endphp
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-condensed">
            <thead><tr>
                @foreach($columns as $column)
                    <th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                @endforeach
            </tr></thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        @foreach($columns as $column)
                            @php $value = $row[$column] ?? null; @endphp
                            <td>
                                @if(is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1')
                                    {{ in_array($value, [true, 1, '1'], true) ? 'Yes' : 'No' }}
                                @elseif(is_array($value) || is_object($value))
                                    {{ json_encode($value) }}
                                @else
                                    {{ $value === null || $value === '' ? '—' : $value }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info" style="margin-bottom:0;">No saved data is available for this business in the related table.</div>
@endif

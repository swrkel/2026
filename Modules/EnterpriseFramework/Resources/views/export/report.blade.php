<div class="efw-export-report">
    <h3>{{ $title ?? 'Enterprise Report' }}</h3>
    <p>{{ $branch ?? 'Consolidated' }} | {{ $date_range ?? '' }}</p>
    @if(!empty($rows))
        <table class="table table-bordered table-condensed">
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @endif
</div>

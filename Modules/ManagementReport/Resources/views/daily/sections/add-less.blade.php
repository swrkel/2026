<div class="mgmt-two-column">
    <div>
        <h4 class="mgmt-subheading mgmt-add-heading">ADD</h4>
        <table class="mgmt-report-table">
            <tbody>@foreach($data['add'] as $row)<tr><td>{{ $row['label'] }}</td><td class="num">{{ number_format($row['amount'], 2) }}</td></tr>@endforeach</tbody>
            <tfoot><tr><th>Total Add</th><th class="num">{{ number_format($data['add_total'], 2) }}</th></tr></tfoot>
        </table>
    </div>
    <div>
        <h4 class="mgmt-subheading mgmt-less-heading">LESS</h4>
        <table class="mgmt-report-table">
            <tbody>@foreach($data['less'] as $row)<tr><td>{{ $row['label'] }}</td><td class="num">{{ number_format($row['amount'], 2) }}</td></tr>@endforeach</tbody>
            <tfoot><tr><th>Total Less</th><th class="num">{{ number_format($data['less_total'], 2) }}</th></tr></tfoot>
        </table>
    </div>
</div>

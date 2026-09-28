@if(empty($rows) || (method_exists($rows, 'count') && $rows->count() == 0))
    <div class="empty-state"><i class="fa fa-folder-open-o"></i><br>{{ $empty ?? 'No records found.' }}</div>
@else
    <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Reference</th><th>Details</th></tr></thead><tbody>
    @foreach($rows as $row)
        <tr>
            <td>{{ $row->created_at ?? $row->date ?? $row->prescription_date ?? $row->result_date ?? '' }}</td>
            <td>{{ $row->reference_no ?? $row->invoice_no ?? $row->id ?? '' }}</td>
            <td>{{ $row->title ?? $row->description ?? $row->notes ?? $row->status ?? '' }}</td>
        </tr>
    @endforeach
    </tbody></table></div>
@endif

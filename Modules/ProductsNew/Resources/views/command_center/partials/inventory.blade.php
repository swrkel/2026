<table class="table table-bordered productsnew-table"><tbody>
@foreach($workspace['inventory'] as $key => $value)<tr><th>{{ ucwords(str_replace('_',' ', $key)) }}</th><td>{{ $value ?: '0' }}</td></tr>@endforeach
</tbody></table>

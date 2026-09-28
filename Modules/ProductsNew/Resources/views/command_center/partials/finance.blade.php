<table class="table table-bordered productsnew-table"><tbody>
@foreach($workspace['finance'] as $key => $value)<tr><th>{{ ucwords(str_replace('_',' ', $key)) }}</th><td>{{ is_numeric($value) ? number_format($value,4) : $value }}</td></tr>@endforeach
</tbody></table>

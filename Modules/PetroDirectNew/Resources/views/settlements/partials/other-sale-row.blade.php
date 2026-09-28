<tr>
    <td><input name="other_sales[{{ $i }}][reference_no]" class="form-control" value="{{ $row['reference_no'] ?? '' }}"></td>
    <td><select name="other_sales[{{ $i }}][contact_id]" class="form-control"><option value="">Walk-in / Select</option>@foreach($contacts as $contact)<option value="{{ $contact->id }}" @selected(($row['contact_id'] ?? null)==$contact->id)>{{ $contact->name }}</option>@endforeach</select></td>
    <td><select name="other_sales[{{ $i }}][lines][0][product_id]" class="form-control"><option value="">Select Product</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(data_get($row,'lines.0.product_id')==$product->id)>{{ $product->name }}</option>@endforeach</select></td>
    <td><input type="number" step="0.0001" name="other_sales[{{ $i }}][lines][0][qty]" class="form-control" value="{{ data_get($row,'lines.0.qty',1) }}"></td>
    <td><input type="number" step="0.0001" name="other_sales[{{ $i }}][lines][0][unit_price]" class="form-control" value="{{ data_get($row,'lines.0.unit_price',0) }}"></td>
    <td><button type="button" class="btn btn-danger pdn-remove">×</button></td>
</tr>

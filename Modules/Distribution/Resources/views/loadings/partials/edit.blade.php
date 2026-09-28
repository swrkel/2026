<div class="modal-dialog modal-xl">
    <div class="modal-content">
        <form method="POST" action="{{ route('distribution.loadings.update', $loading->id) }}" id="edit_loading_form">
            @csrf

            <div class="modal-header">
                <h4 class="modal-title">Edit Product Loading</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Date:</label>
                            @if(!empty($show_date_picker))
                                <input type="date" name="date_time" class="form-control" 
                                    value="{{ date('Y-m-d', strtotime($loading->date_time)) }}" required>
                            @elseif(!empty($auto_date_time))
                                <input type="text" name="date_time" class="form-control" 
                                    value="{{ $loading->date_time }}" readonly>
                            @else
                                <input type="date" name="date_time" class="form-control" 
                                    value="{{ date('Y-m-d', strtotime($loading->date_time)) }}" required>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Sales Rep:</label>
                            <select name="sales_rep_id" class="form-control" required>
                                @foreach($salesReps as $id => $name)
                                    <option value="{{ $id }}" {{ $loading->sales_rep_id == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Vehicle:</label>
                            <select name="vehicle_id" class="form-control">
                                <option value="">-- Select --</option>
                                @foreach($vehicles as $id => $type)
                                    <option value="{{ $id }}" {{ $loading->vehicle_id == $id ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Product Category:</label>
                            <select name="product_category_id" class="form-control">
                                <option value="">All</option>
                                @foreach($categories as $id => $name)
                                    <option value="{{ $id }}" {{ $loading->product_category_id == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Product Sub Category:</label>
                            <select name="product_sub_category_id" class="form-control" id="edit_product_sub_category_id">
                                <option value="">All</option>
                                @foreach($subcategories ?? [] as $id => $name)
                                    <option value="{{ $id }}" {{ $loading->product_sub_category_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>@lang( 'distribution::lang.index' )</th>
                            <th>@lang( 'distribution::lang.product' )</th>
                            <th>@lang( 'distribution::lang.available_qty' )</th>
                            <th>@lang( 'distribution::lang.vehicle_balance_qty' )</th>
                            <th>@lang( 'distribution::lang.requested_qty' )</th>
                            <th>@lang( 'distribution::lang.issued_qty' )</th>
                            <th>@lang( 'distribution::lang.unit_sale_price' )</th>
                            <th>@lang( 'distribution::lang.total_in_sale_price' )</th>
                            <th><i class="fa fa-trash"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($loading->lines as $i => $line)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $line->product?->name ?? '-' }}</td>
                            <td>{{ number_format((float) ($line->available_qty ?? 0), 2) }}</td>
                             <td>
                                <input type="number" step="0.01"
                                       name="vehicle_balance_qty[]"
                                       value="{{ number_format((float) $line->vehicle_balance_qty, 2, '.', '') }}"
                                       class="form-control">
                            </td>
                            <td>
                                <input type="number" step="0.01"
                                       name="requested_qty[]"
                                       value="{{ number_format((float) $line->requested_qty, 2, '.', '') }}"
                                       class="form-control">
                            </td>
                            <td>
                                <input type="number" step="0.01"
                                       name="issued_qty[]"
                                       value="{{ number_format((float) $line->issued_qty, 2, '.', '') }}"
                                       class="form-control edit_issued_qty">
                            </td>
                            <td>
                                <input type="number" step="0.01"
                                       name="sale_price[]"
                                       value="{{ number_format((float) $line->sale_price, 2, '.', '') }}"
                                       class="form-control edit_sale_price">
                            </td>
                            <td class="line_total">{{ number_format((float) $line->line_total, 2) }}</td>
                            <td>
                                <button type="button" class="btn btn-danger btn-xs remove_row" title="Remove Product"><i class="fa fa-times"></i></button>
                            </td>

                            <input type="hidden" name="product_ids[]" value="{{ $line->product_id }}">
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="7" class="text-right"><strong>Total Sale Amount:</strong></th>
                            <th class="loading_total_sale">{{ number_format((float) $loading->total_sale_price, 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-primary">Update</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>



<script>
(function distributionLoadingEditSubcategoryFix(){
    var $modal = $('#edit_product_sub_category_id').closest('.modal');
    var $category = $('select[name="product_category_id"]');
    var $sub = $('#edit_product_sub_category_id');
    if ($.fn.select2) { $sub.select2({ width:'100%', dropdownParent: $modal.length ? $modal : $(document.body) }); }
    $category.off('change.loadingSubcategory').on('change.loadingSubcategory', function(){
        $.get('{{ route('distribution.loadings.subcategories') }}', {category_id: $(this).val()}, function(rows){
            var selected = $sub.val();
            $sub.empty().append('<option value="">All</option>');
            $.each(rows || [], function(i, row){ $sub.append('<option value="'+row.id+'">'+row.name+'</option>'); });
            if ($sub.find('option[value="'+selected+'"]').length) { $sub.val(selected); }
            $sub.trigger('change');
        });
    });
})();
</script>

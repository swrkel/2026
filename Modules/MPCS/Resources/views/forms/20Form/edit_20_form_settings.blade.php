<style>
    .single-cat {
        padding: 5px 20px;
        background-color: orange;
        margin-bottom: 4px;
        min-width: max-content;
        text-align: center;
    }

    .btn-remove {
        background-color: #dc3545;
        color: white;
        padding: 5px 10px;
        border: none;
        cursor: pointer;
        border-radius: 3px;
    }

    .select2-container--default .select2-selection--multiple {
        min-height: 200px !important;
        max-height: 200px !important;
        overflow-y: auto !important;
        padding-top: 5px !important;
    }

    .section-divider {
        border: 0;
        border-top: 1px solid #dee2e6;
        margin: 15px 0;
    }

    .products-table {
        max-height: 250px;
        overflow-y: auto;
    }

    .table-container thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        text-align: center;
        vertical-align: middle;
    }

    #productTable {
        width: 100%;
        border-collapse: collapse;
    }

    #productTable th,
    #productTable td {
        padding: 4px 8px !important;
        font-size: 13px;
        vertical-align: middle;
    }

    #productTable td.text-center,
    #productTable th.text-center {
        text-align: center;
    }

    .table-container thead th {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 2;
    }
</style>
<div class="modal-dialog" role="document" style="width: 80%;">
    <div class="modal-content">
        {!! Form::open(['url' => action([\Modules\MPCS\Http\Controllers\F20FormController::class, 'mpcs20Update'], [$settings->id]), 'method' => 'post', 'id' => 'update_21c_form_settings' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Edit 20 Form Settings</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12">
                <div class="row">
                    <!-- Date and Time -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Opening Date</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="datepicker" name="date"
                                       data-date-format="yyyy/mm/dd" readonly value="{{$settings->opening_date}}"/>
                                <div class="input-group-addon">
                                    <i class="fa fa-calendar-o"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Date and Time -->
                    <div class="col-md-6">

                    </div>
                </div>
                <br>

                <!-- Form Starting Number -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Form Type <span class="" aria-required="true"></span></label>
                        <label>@lang('mpcs::lang.form_starting_number') .<span class="required"
                                                                               aria-required="true"></span></label>
                        <input type="hidden" name="starting_number" class="form-control"
                               value="{{ $settings->starting_number }}" required>
                    </div>
                </div>

                <!-- Ref Previous Form Number -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Total Sale <span class="required" aria-required="true">*</span></label>
                        <input type="text" name="total_sale" class="form-control" value="{{ $settings->total_sale }}"
                               required>
                    </div>
                </div>

                <!-- Receipt Section Previous Day Amount -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Cash Sale <span class="required" aria-required="true">*</span></label>
                        <input type="text" name="cash_sale" class="form-control" value="{{ $settings->cash_sale }}"
                               required>
                    </div>
                </div>

                <!-- Receipt Section Opening Stock Amount -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Credit Sale <span class="required" aria-required="true">*</span></label>
                        <input type="text" name="credit_sale" class="form-control" value="{{ $settings->credit_sale }}"
                               required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="edit_category">Select Sub Category</label>
                        <select id="edit_category" name="category[]" multiple class="form-control select2">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="edit_product">Select Products</label>
                        <select id="edit_product" name="product[]" multiple class="form-control select2">
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-success" id="add_products_btn" style="float: right">
                            <i class="fa fa-plus"></i> Add
                        </button>
                    </div>
                </div>
                <hr class="section-divider">
                <div class="row products-table" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <table class="table table-bordered table-striped" id="productTable">
                            <thead class="table-header">
                            <tr>
                                <th style="width: 5%;">Index</th>
                                <th style="width: 30%;">Product Sub Category</th>
                                <th style="width: 50%;">Product</th>
                                <th style="width: 15%;">Action</th>
                            </tr>
                            </thead>
                            <tbody id="products_table_body">
                            </tbody>
                        </table>
                        <p id="empty_message" style="text-align: center; color: #999;">No products added yet</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" id="close_21c_modal"
                    data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
    $(document).ready(function () {
        const masterCategories = @json($categories);
        const masterProducts = @json($products);

        $('#edit_product').select2({
            placeholder: 'Search and select products',
            allowClear: true,
            width: '100%',
            closeOnSelect: false
        });

        $('#edit_category').select2({
            placeholder: 'Search and select category',
            allowClear: true,
            width: '100%',
            closeOnSelect: false
        });

        const existingCategoryIds = "{{ $settings->category ?? '' }}".split(',').filter(Boolean);
        const existingProductIds = "{{ $settings->product ?? '' }}".split(',').filter(Boolean);

        $('#edit_category').val(existingCategoryIds).trigger('change');
        $('#edit_product').val(existingProductIds).trigger('change');

        const tableBody = $('#products_table_body');
        tableBody.empty();
        existingProductIds.forEach(pid => {
            const prod = masterProducts.find(p=>String(p.id)===pid);
            if(!prod) return;
            const cat = masterCategories.find(c=>String(c.id)===String(prod.sub_category_id));
            const catName = cat ? cat.name : '';
            const index = tableBody.find('tr').length+1;
            const row = `
        <tr data-product-id="${prod.id}" data-subcategory-id="${prod.sub_category_id}">
            <td class="text-center">${index}</td>
            <td>${catName}</td>
            <td>${prod.name}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger remove-product-btn">Remove</button>
                <input type="hidden" name="product_ids[]" value="${prod.id}">
            </td>
        </tr>`;
            tableBody.append(row);
        });

        $('#edit_category').on('change', function () {
            const selectedCatIds = $(this).val() || [];
            const filteredProducts = masterProducts.filter(p=>selectedCatIds.includes(String(p.sub_category_id)));
            const selectProd = $('#edit_product');
            selectProd.empty();
            filteredProducts.forEach(p=>selectProd.append(`<option value="${p.id}">${p.name}</option>`));
            selectProd.trigger('change.select2');
            $('#product-help-text').toggle(selectedCatIds.length===0);
        });

        $('#add_products_btn').on('click', function () {
            const selectedProducts = $('#edit_product').val()||[];
            if(selectedProducts.length===0){ toastr.warning('Please select at least one product'); return;}
            selectedProducts.forEach(pid=>{
                if(tableBody.find(`tr[data-product-id="${pid}"]`).length>0) return;
                const prod = masterProducts.find(p=>String(p.id)===pid);
                if(!prod) return;
                const cat = masterCategories.find(c=>String(c.id)===String(prod.sub_category_id));
                const catName = cat ? cat.name : '';
                const index = tableBody.find('tr').length+1;
                const row = `<tr data-product-id="${prod.id}" data-subcategory-id="${prod.sub_category_id}">
                <td class="text-center">${index}</td>
                <td>${catName}</td>
                <td>${prod.name}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-product-btn">Remove</button>
                    <input type="hidden" name="product_ids[]" value="${prod.id}">
                </td>
            </tr>`;
                tableBody.append(row);
            });
        });

        $(document).on('click','.remove-product-btn',function(){
            $(this).closest('tr').remove();
            updateTableIndex();
        });
    });

    function updateTableIndex(){
        $('#products_table_body tr').each(function(i){ $(this).find('td:first').text(i+1); });
    }
</script>

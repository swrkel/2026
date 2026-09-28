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
        {!! Form::open(['url' => '#', 'method' => 'post', 'id' => 'add_21c_form_settings' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Add 20 Form Settings</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12" style="padding-left: 0; padding-right: 0"><br/>
                <div class="row">
                    <!-- Date and Time -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Opening Date</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="datepicker" name="date"
                                       data-date-format="yyyy/mm/dd" readonly/>
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

                <!-- Form Starting Number -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.form_type') <span class="" aria-required="true"></span></label>
                        <label>@lang('mpcs::lang.form_starting_number') .<span class="required"
                                                                               aria-required="true"></span></label>
                        <input type="hidden" name="starting_number" class="form-control" value="00">
                    </div>
                </div>

                <!-- Ref Previous Form Number -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.total_sale') <span class="required"
                                                                    aria-required="true">*</span></label>
                        <input type="text" name="total_sale" class="form-control" required>
                    </div>
                </div>

                <!-- Receipt Section Previous Day Amount -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.cash_sale') <span class="required"
                                                                   aria-required="true">*</span></label>
                        <input type="text" name="cash_sale" class="form-control" required>
                    </div>
                </div>

                <!-- Receipt Section Opening Stock Amount -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.credit_sale') <span class="required"
                                                                     aria-required="true">*</span></label>
                        <input type="text" name="credit_sale" class="form-control" required>
                    </div>
                </div>


                <div class="col-md-6">
                    <div class="form-group">
                        <label for="product">@lang('mpcs::lang.select_sub_category')</label>
                        <select name="category[]" id="category" class="form-control select2" multiple="multiple">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="product">@lang('mpcs::lang.select_products')</label>
                        <select name="product[]" id="product" class="form-control select2" multiple="multiple">
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

        <div class="modal-footer" style="margin-top: 10px">
            <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" id="close_21c_modal"
                    data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
    if (typeof hiddenCategories === 'undefined') {
        var hiddenCategories = new Set();
    }

    var masterData = {
        categories: @json($categories),
        products: @json($products)
    };

    $(document).ready(function () {
        $('#datepicker').datepicker('setDate', new Date());

        $('#product').select2({
            placeholder: 'Search and select products',
            allowClear: true,
            width: '100%',
            closeOnSelect: false
        });

        $('#category').select2({
            placeholder: 'Search and select category',
            allowClear: true,
            width: '100%',
            closeOnSelect: false
        });

        $('#category').on('change', function () {
            updateProductDropdown();
        });

        $('#product').on('select2:unselect', handleProductUnselect);

        $('#add_products_btn').on('click', function () {
            addProductsToTable();
        });

        $(document).on('click', '.btn-delete-row', function () {
            $(this).closest('tr').remove();
            updateTableIndices();
            updateEmptyMessage();
        });

        $('#category, #product').on('select2:open', function() {
            const searchField = $('.select2-search__field');

            searchField.off('keydown.ctrlA').on('keydown.ctrlA', function(e) {
                if (e.ctrlKey && (e.key === 'a' || e.key === 'A')) {
                    e.preventDefault();
                    const allOptions = $('#category option').map(function() {
                        return $(this).val();
                    }).get();
                    $('#category').val(allOptions).trigger('change.select2');
                    updateProductDropdown();
                }
            });
        });
    });

    function updateProductDropdown() {
        const selectedSubCategories = $('#category').val() || [];
        const productSelect = $('#product');

        if (selectedSubCategories.length === 0) {
            $('#product').empty().prop('disabled', true).trigger('change');
            $('#product-help-text').show();
            return;
        }
        productSelect.empty();

        const filteredProducts = masterData.products.filter(prod =>
            selectedSubCategories.includes(String(prod.sub_category_id))
        );
        productSelect.empty().prop('disabled', false);

        if (filteredProducts.length > 0) {
            filteredProducts.forEach(prod => {
                productSelect.append(`<option value="${prod.id}">${prod.name}</option>`);
            });
        } else {
            productSelect.append('<option disabled>(No products available)</option>');
        }

        // Refresh select2
        const productIds = filteredProducts.map(p => p.id);
        productSelect.val(productIds).trigger('change.select2');
    }

    function handleProductUnselect(e) {
        const removedProductId = e.params.data.id;
        const removedProduct = masterData.products.find(p => String(p.id) === String(removedProductId));

        if (!removedProduct) return;

        const remainingSelected = $('#product').val() || [];
        const hasSameSubcategory = remainingSelected.some(pid => {
            const prod = masterData.products.find(p => String(p.id) === String(pid));
            return prod && String(prod.subcategory_id) === String(removedProduct.subcategory_id);
        });

        if (!hasSameSubcategory) {
            let categories = $('#category').val() || [];
            categories = categories.filter(cid => String(cid) !== String(removedProduct.subcategory_id));
            $('#category').val(categories).trigger('change.select2');
        }
    }

    function addProductsToTable() {
        const selectedProducts = $('#product').val() || [];

        if (selectedProducts.length === 0) {
            toastr.warning('Please select at least one product.');
            return;
        }

        const addedProducts = [];

        selectedProducts.forEach(productId => {
            const product = masterData.products.find(p => String(p.id) === String(productId));
            if (!product) return;

            const subcategory = masterData.categories.find(c => String(c.id) === String(product.sub_category_id));
            const subcategoryName = subcategory ? subcategory.name : '';

            addedProducts.push({
                subcategory_id: product.sub_category_id,
                subcategory_name: subcategoryName,
                product_id: product.id,
                product_name: product.name
            });
        });

        renderTable(addedProducts);

        toastr.success(`${addedProducts.length} product(s) added successfully`);
    }

    function renderTable(products) {
        const tableBody = $('#products_table_body');
        if (tableBody.length === 0) return;

        products.forEach(prod => {
            const exists = tableBody.find(`tr[data-product-id="${prod.product_id}"]`).length > 0;
            if (exists) return;

            const index = tableBody.find('tr').length + 1;
            const row = `
           <tr data-product-id="${prod.product_id}" data-subcategory-id="${prod.subcategory_id}">
                <td class="text-center">${index}</td>
                <td>${prod.subcategory_name}</td>
                <td>${prod.product_name}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-product-btn">Remove</button>
                    <input type="hidden" name="product_ids[]" value="${prod.product_id}">
                </td>
            </tr>
        `;
            tableBody.append(row);
        });

        updateTableIndex();
    }

    function updateTableIndex() {
        $('#productTable tbody tr').each(function (i) {
            $(this).find('td:first').text(i + 1);
        });
    }

    $(document).on('click', '.remove-product-btn', function () {
        const row = $(this).closest('tr');
        row.remove();

        $('#products_table_body tr').each(function(i) {
            $(this).find('td:first').text(i + 1);
        });
    });
</script>

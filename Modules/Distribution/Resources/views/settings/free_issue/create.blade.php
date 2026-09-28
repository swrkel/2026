<div class="modal-dialog modal-lg" style="width: 90%; max-width: 1200px;">
    <div class="modal-content">

        {!! Form::open(['route' => 'free-issues.store', 'id' => 'freeIssueForm', 'novalidate' => 'novalidate']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">
                Free Issue
                <span class="pull-right" style="margin-right: 20px; font-weight: bold; padding: 5px 15px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;">
                    Form No: {{ $form_no ?? '-' }}
                </span>
            </h4>
        </div>

        <div class="modal-body" style="padding: 20px 25px;">

            {{-- ROW 1: 4 Fields (Date & Time, Category, Subcategory, Products) --}}
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>Date & Time:*</label>
                        @if (!empty($auto_date_time))
                            <input type="text" name="date_time" class="form-control"
                                value="{{ date('Y-m-d H:i:s') }}" readonly required>
                        @else
                            <input type="datetime-local" name="date_time" class="form-control"
                                value="{{ now()->format('Y-m-d\TH:i') }}" readonly required>
                        @endif
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        {!! Form::label('catagories_id', 'Product Categories:*') !!}
                        {!! Form::select('catagories_id[]', $categories, null, [
                            'class' => 'form-control select2',
                            'multiple',
                            'id' => 'categories',
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        {!! Form::label('subcatagories_id', 'Sub Categories:*') !!}
                        {!! Form::select('subcatagories_id[]', [], null, [
                            'class' => 'form-control select2',
                            'multiple',
                            'id' => 'subcategories',
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        {!! Form::label('product_id', 'Products:*') !!}
                        {!! Form::select('product_id[]', [], null, [
                            'class' => 'form-control select2',
                            'multiple',
                            'id' => 'products',
                        ]) !!}
                    </div>
                </div>
            </div>

            {{-- Unit & Quantity Type in One Row --}}
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        {!! Form::label('unit_id', 'Unit:*') !!}
                        {!! Form::select('unit_id', [], null, [
                            'class' => 'form-control select2',
                            'id' => 'unit',
                            'placeholder' => 'Select unit...',
                        ]) !!}
                    </div>
                </div>
                <div class="col-sm-6 col-md-9">
                    <div class="form-group">
                        <label>Quantity Type:*</label>
                        <div class="radio-inline" style="margin-left: 15px;">
                            <label>
                                <input type="radio" name="qty_type" value="single" id="single_qty_radio" checked>
                                Single Qty
                            </label>
                        </div>
                        <div class="radio-inline" style="margin-left: 20px;">
                            <label>
                                <input type="radio" name="qty_type" value="range" id="range_qty_radio">
                                Qty Range
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ROW 2: Qty Fields (Dynamic based on type) --}}
            <div class="row">
                <div class="col-sm-6 col-md-2">
                    <div class="form-group">
                        {!! Form::label('qty_from', 'For Every Qty:*', ['id' => 'qty_from_label']) !!}
                        {!! Form::number('qty_from', null, ['class' => 'form-control', 'min' => 0, 'id' => 'qty_from_input']) !!}
                    </div>
                </div>
                <div class="col-sm-6 col-md-2" id="qty_till_container">
                    <div class="form-group">
                        {!! Form::label('qty_till', 'Qty Till:*', ['id' => 'qty_till_label']) !!}
                        {!! Form::number('qty_till', null, ['class' => 'form-control', 'min' => 0, 'id' => 'qty_till_input']) !!}
                    </div>
                </div>
                <div class="col-sm-6 col-md-2">
                    <div class="form-group">
                        {!! Form::label('qty_free', 'Free Qty:*') !!}
                        {!! Form::number('qty_free', null, ['class' => 'form-control', 'min' => 1, 'id' => 'qty_free_input']) !!}
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        {!! Form::label('date_since', 'Date Since:*') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            <input type="datetime-local" id="date_since" name="date_since_input" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        {!! Form::label('date_till', 'Date Till:*') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            <input type="datetime-local" id="date_till" name="date_till_input" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ROW 3: Products for Free Quantities - full width --}}
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('free_product_id', 'Products for Free Quantities:') !!}
                        {!! Form::select('free_product_id[]', [], null, [
                            'class' => 'form-control select2',
                            'multiple',
                            'id' => 'free_products',
                            'placeholder' => 'Select products...',
                        ]) !!}
                    </div>
                </div>
            </div>

            {{-- ROW 4: Add Button (right-aligned) --}}
            <div class="row">
                <div class="col-md-12 text-right" style="margin-top: 10px; margin-bottom: 15px;">
                    <button type="button" class="btn btn-info" id="addDraftRow">
                        <i class="fa fa-plus"></i> Add
                    </button>
                </div>
            </div>

            <hr style="margin: 15px 0;">

            {{-- Draft Table with Unit Column --}}
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="draftTable" style="font-size: 13px;">
                    <thead>
                        <tr>
                            <th style="width: 10%;">Categories</th>
                            <th style="width: 10%;">Subcategory</th>
                            <th style="width: 8%;">Unit</th>
                            <th style="width: 12%;">Products</th>
                            <th style="width: 12%;">Date Since</th>
                            <th style="width: 12%;">Date Till</th>
                            <th style="width: 8%;">Qty From</th>
                            <th style="width: 8%;">Qty Till</th>
                            <th style="width: 8%;">Free Qty</th>
                            <th style="width: 12%;">Free Products</th>
                            <th style="width: 5%;">Delete</th>
                        </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary" id="saveButton">
                <i class="fa fa-save"></i> Save
            </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">
                Close
            </button>
        </div>

        {!! Form::close() !!}
    </div>
</div>

<!-- Make sure Toastr is included FIRST -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
$(document).ready(function() {
    
    // Toastr configuration
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "5000",
        "extendedTimeOut": "1000"
    };

    // Initialize Select2
    $('.select2').select2({
        width: '100%'
    });

    /* CATEGORY → SUBCATEGORY */
    $('#categories').on('change', function() {
        let ids = $(this).val() || [];
        $('#subcategories').empty().trigger('change');
        $('#products, #free_products').empty().trigger('change');
        $('#unit').empty().trigger('change');

        if (ids.length === 0) {
            $('#subcategories').append(new Option('No categories selected', '')).trigger('change');
            $('#products, #free_products').append(new Option('No categories selected', '')).trigger('change');
            $('#unit').append(new Option('Select unit...', '')).trigger('change');
            return;
        }

        $('#subcategories').append(new Option('Loading...', '')).trigger('change');

        $.get('/distribution/subcategories/' + ids.join(','), function(res) {
            $('#subcategories').empty();
            $.each(res, function(id, name) {
                $('#subcategories').append(new Option(name, id));
            });
            $('#subcategories').trigger('change');
        }).fail(function() {
            toastr.error('Failed to load subcategories');
        });
    });

    /* SUBCATEGORY → PRODUCTS (both main products and free products) */
    $('#subcategories').on('change', function() {
        let ids = $(this).val() || [];
        $('#products, #free_products').empty().trigger('change');
        $('#unit').empty().trigger('change');

        if (ids.length === 0) {
            $('#products, #free_products').append(new Option('No subcategories selected', '')).trigger('change');
            $('#unit').append(new Option('Select unit...', '')).trigger('change');
            return;
        }

        $('#products, #free_products').append(new Option('Loading...', '')).trigger('change');

        $.get('/distribution/products', { subcategories: ids }, function(res) {
            $('#products, #free_products').empty();
            $.each(res, function(id, name) {
                $('#products, #free_products').append(new Option(name, id));
            });
            $('#products, #free_products').trigger('change');
        }).fail(function() {
            toastr.error('Failed to load products');
        });
    });

    /* When product is selected, load its unit */
    $('#products').on('change', function() {
        let productId = $(this).val();
        let $unitSelect = $('#unit');

        $unitSelect.empty();

        if (!productId || productId.length === 0) {
            $unitSelect.append(new Option('Select unit...', '')).trigger('change');
            return;
        }

        let firstProductId = productId[0];
        $unitSelect.append(new Option('Loading...', '')).trigger('change');

        $.get('/distribution/get-product-unit/' + firstProductId, function(res) {
            $unitSelect.empty();
            if (res.units && typeof res.units === 'object' && Object.keys(res.units).length > 0) {
                $.each(res.units, function(id, name) {
                    $unitSelect.append(new Option(name, id));
                });
                // Auto-select first unit if available
                let firstUnitId = Object.keys(res.units)[0];
                $unitSelect.val(firstUnitId).trigger('change');
            } else {
                $unitSelect.append(new Option('No unit available', ''));
                toastr.warning('This product has no units assigned');
            }
            $unitSelect.trigger('change');
        }).fail(function(xhr) {
            $unitSelect.empty();
            $unitSelect.append(new Option('Error loading unit', ''));
            $unitSelect.trigger('change');
            toastr.error('Failed to load unit information');
        });
    });

    /* Qty Type Radio Button Handler */
    function updateQtyFields() {
        let qtyType = $('input[name="qty_type"]:checked').val();
        let $qtyFromLabel = $('#qty_from_label');
        let $qtyTillContainer = $('#qty_till_container');
        let $qtyTillInput = $('#qty_till_input');
        let $qtyFromInput = $('#qty_from_input');

        if (qtyType === 'single') {
            $qtyFromLabel.text('For Every Qty:*');
            $qtyTillContainer.hide();
            $qtyTillInput.prop('disabled', true);
            $qtyTillInput.val('');
            $qtyTillInput.removeAttr('required');
            $qtyFromInput.attr('required', true);
        } else {
            $qtyFromLabel.text('Qty From:*');
            $qtyTillContainer.show();
            $qtyTillInput.prop('disabled', false);
            $qtyFromInput.attr('required', true);
            $qtyTillInput.attr('required', true);
        }
    }

    $('input[name="qty_type"]').on('change', function() {
        updateQtyFields();
    });
    updateQtyFields();

    /* ADD ROW TO DRAFT TABLE */
    $('#addDraftRow').on('click', function() {
        let categories = $('#categories option:selected');
        let subcategories = $('#subcategories option:selected');
        let products = $('#products option:selected');
        let freeProducts = $('#free_products option:selected');
        let unitId = $('#unit').val();
        let unitName = $('#unit option:selected').text() || '-';

        let qtyType = $('input[name="qty_type"]:checked').val();
        let qty_from = $('#qty_from_input').val();
        let qty_till = $('#qty_till_input').val();
        let qty_free = $('#qty_free_input').val();
        let date_since = $('#date_since').val();
        let date_till = $('#date_till').val();

        // Validation
        if (categories.length === 0) {
            toastr.error('Please select a category.');
            return;
        }

        if (subcategories.length === 0) {
            toastr.error('Please select a subcategory.');
            return;
        }

        if (products.length === 0) {
            toastr.error('Please select at least one product.');
            return;
        }

        if (!unitId || unitId === '') {
            toastr.error('Please select a unit for the product.');
            $('#unit').focus();
            return;
        }

        if (freeProducts.length === 0) {
            toastr.error('Please select at least one free product.');
            return;
        }

        if (!date_since || !date_till) {
            toastr.error('Please select Date Since and Date Till.');
            return;
        }

        if (!qty_free || qty_free <= 0) {
            toastr.error('Please fill in Free Qty (must be greater than 0).');
            return;
        }

        let fromNum = parseFloat(qty_from);
        let tillNum = parseFloat(qty_till);

        if (qtyType === 'single') {
            if (!qty_from || fromNum <= 0) {
                toastr.error('Please enter a valid "For Every Qty" value.');
                return;
            }
            qty_till = '';
        } else {
            if (!qty_from || !qty_till) {
                toastr.error('Please fill in Qty From and Qty Till.');
                return;
            }
            if (fromNum > tillNum) {
                toastr.error('Qty From cannot be greater than Qty Till.');
                return;
            }
        }

        let catId = categories.length > 0 ? categories[0].value : '';
        let catName = categories.length > 0 ? categories[0].text : '';
        let subId = subcategories.length > 0 ? subcategories[0].value : '';
        let subName = subcategories.length > 0 ? subcategories[0].text : '';

        let freeProdsValue = freeProducts.map(function() {
            return $(this).val();
        }).get();

        let freeProdsJson = JSON.stringify(freeProdsValue);
        let freeProdsText = freeProducts.map(function() {
            return $(this).text();
        }).get().join(', ');

        products.each(function() {
            let productId = $(this).val();
            let productName = $(this).text();

            let rowHtml = `
            <tr>
                <td>${catName}<input type="hidden" name="cat[]" value="${catId}">
                <td>${subName}<input type="hidden" name="sub[]" value="${subId}">
                <td>${unitName}<input type="hidden" name="unit_ids[]" value="${unitId}">
                <td>${productName}<input type="hidden" name="prod[]" value="${productId}">
                <td>${date_since}<input type="hidden" name="date_sinces[]" value="${date_since}">
                <td>${date_till}<input type="hidden" name="date_tills[]" value="${date_till}">
                <td>${qty_from}<input type="hidden" name="qty_froms[]" value="${qty_from}">
                <td>${qty_till || ''}<input type="hidden" name="qty_tills[]" value="${qty_till || ''}">
                <td>${qty_free}<input type="hidden" name="qty_frees[]" value="${qty_free}">
                <td>${freeProdsText}<input type="hidden" name="free_products[]" value='${freeProdsJson}'>
                <td class="text-center"><button type="button" class="btn btn-danger btn-xs removeRow"><i class="fa fa-times"></i></button><input type="hidden" name="qty_types[]" value="${qtyType}"></td>
            </tr>
            `;
            $('#draftTable tbody').append(rowHtml);
        });

        // Clear form fields for next entry
        $('#products').val(null).trigger('change');
        $('#free_products').val(null).trigger('change');
        $('#unit').val(null).trigger('change');
        $('#qty_from_input').val('');
        $('#qty_till_input').val('');
        $('#qty_free_input').val('');
        $('#date_since').val('');
        $('#date_till').val('');

        $('#single_qty_radio').prop('checked', true);
        updateQtyFields();
        
        toastr.success('Row added successfully!');
    });

    /* REMOVE ROW */
    $(document).on('click', '.removeRow', function() {
        $(this).closest('tr').remove();
        toastr.info('Row removed');
    });

    /* FORM SUBMIT WITH AJAX */
    $('#freeIssueForm').on('submit', function(e) {
        e.preventDefault();
        
        let rowCount = $('#draftTable tbody tr').length;

        if (rowCount === 0) {
            toastr.error('Please add at least one free issue rule before saving.');
            return false;
        }

        // Validate each row
        let isValid = true;
        let errorMsg = '';
        
        $('#draftTable tbody tr').each(function(index) {
            let qtyType = $(this).find('input[name="qty_types[]"]').val();
            let qtyFrom = parseFloat($(this).find('input[name="qty_froms[]"]').val());
            let qtyTill = $(this).find('input[name="qty_tills[]"]').val();
            let qtyFree = parseFloat($(this).find('input[name="qty_frees[]"]').val());
            let unitId = $(this).find('input[name="unit_ids[]"]').val();

            if (!unitId || unitId === '') {
                errorMsg = `Row ${index + 1}: Unit is required.`;
                isValid = false;
                return false;
            }

            if (qtyType === 'single') {
                if (!qtyFrom || qtyFrom <= 0) {
                    errorMsg = `Row ${index + 1}: "For Every Qty" must be greater than 0.`;
                    isValid = false;
                    return false;
                }
                if (!qtyFree || qtyFree <= 0) {
                    errorMsg = `Row ${index + 1}: Free Qty must be greater than 0.`;
                    isValid = false;
                    return false;
                }
            } else {
                if (!qtyFrom || !qtyTill) {
                    errorMsg = `Row ${index + 1}: Both Qty From and Qty Till are required.`;
                    isValid = false;
                    return false;
                }
                let fromNum = parseFloat(qtyFrom);
                let tillNum = parseFloat(qtyTill);
                if (fromNum > tillNum) {
                    errorMsg = `Row ${index + 1}: Qty From cannot be greater than Qty Till.`;
                    isValid = false;
                    return false;
                }
                if (!qtyFree || qtyFree <= 0) {
                    errorMsg = `Row ${index + 1}: Free Qty must be greater than 0.`;
                    isValid = false;
                    return false;
                }
            }
        });

        if (!isValid) {
            toastr.error(errorMsg);
            return false;
        }

        // Disable submit button and show loading
        let submitBtn = $('#saveButton');
        let originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        // Submit via AJAX
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Free Issues saved successfully!');
                    setTimeout(function() {
                        window.location.href = response.redirect || '{{ route("distribution.free-issues.index") }}';
                    }, 1500);
                } else {
                    toastr.error(response.message || 'An error occurred while saving.');
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html(originalText);
                
                let errorMsg = 'An error occurred while saving.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    let errors = xhr.responseJSON.errors;
                    errorMsg = Array.isArray(errors) ? errors.join(' ') : JSON.stringify(errors);
                } else if (xhr.status === 500) {
                    errorMsg = 'Server error. Please check logs.';
                }
                
                toastr.error(errorMsg);
                console.error('Save error:', xhr);
            }
        });

        return false;
    });
    
});
</script>
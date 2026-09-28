<div class="modal-dialog modal-lg" style="width: 90%; max-width: 1200px;">
    <div class="modal-content">

        {!! Form::open(['url' => route('distribution.free-issues.update', $mainIssue->id), 'method' => 'put', 'id' => 'freeIssueForm', 'novalidate' => 'novalidate']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">
                Edit Free Issue
                <span class="pull-right" style="margin-right: 20px; font-weight: bold; padding: 5px 15px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;">
                    Form No: {{ $mainIssue->form_no ?? '-' }}
                </span>
            </h4>
        </div>

        <div class="modal-body" style="padding: 20px 25px;">

            {{-- ROW 1: 4 Fields (Date & Time, Category, Subcategory, Products) --}}
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>Date & Time:*</label>
                        @if(!empty($auto_date_time))
                            <input type="text" name="date_time" class="form-control"
                                value="{{ $mainIssue->date_time ? \Carbon\Carbon::parse($mainIssue->date_time)->format('Y-m-d H:i:s') : (isset($mainIssue->created_at) ? $mainIssue->created_at->format('Y-m-d H:i:s') : date('Y-m-d H:i:s')) }}" readonly required>
                        @else
                            <input type="datetime-local" name="date_time" class="form-control"
                                value="{{ $mainIssue->date_time ? \Carbon\Carbon::parse($mainIssue->date_time)->format('Y-m-d\TH:i') : (isset($mainIssue->created_at) ? $mainIssue->created_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" readonly required>
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
                                <input type="radio" name="qty_type" value="single" id="single_qty_radio">
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
                            <input type="datetime-local" id="date_since" name="date_since_input" class="form-control"
                                value="{{ $mainIssue->date_since ? \Carbon\Carbon::parse($mainIssue->date_since)->format('Y-m-d\TH:i') : '' }}">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        {!! Form::label('date_till', 'Date Till:*') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            <input type="datetime-local" id="date_till" name="date_till_input" class="form-control"
                                value="{{ $mainIssue->date_till ? \Carbon\Carbon::parse($mainIssue->date_till)->format('Y-m-d\TH:i') : '' }}">
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
                    <tbody>
                        @foreach($freeIssues as $issue)
                            @php
                                $catText  = $issue->category->name ?? 'None';
                                $subText  = $issue->subcategory->name ?? 'None';
                                $unitText = $issue->unit ? $issue->unit->actual_name : '-';
                                $unitId   = $issue->unit_id;
                                $prodText = optional(\Modules\Distribution\Entities\Core\Product::find($issue->product_name))->name ?: '-';
                                $freeProdsJson = json_encode($issue->free_products ?? []);
                                $freeProdsText = collect((array) ($issue->free_products ?? []))
                                    ->map(fn($id) => optional(\Modules\Distribution\Entities\Core\Product::find($id))->name)
                                    ->filter()->implode(', ') ?: '-';
                                $catVal  = $issue->product_category;
                                $subVal  = $issue->product_subcategory;
                                $prodVal = $issue->product_name;
                                
                                // Determine qty_type based on qty_till value
                                $qtyType = (!empty($issue->qty_till) && $issue->qty_till > 0) ? 'range' : 'single';
                            @endphp
                            <tr>
                                <td>{{ $catText }}<input type="hidden" name="cat[]" value="{{ $catVal }}"></td>
                                <td>{{ $subText }}<input type="hidden" name="sub[]" value="{{ $subVal }}"></td>
                                <td>
                                    {{ $unitText }}
                                    <input type="hidden" name="unit_ids[]" value="{{ $unitId }}">
                                </td>
                                <td>
                                    {{ $prodText }}
                                    <input type="hidden" name="prod[]" value="{{ $prodVal }}">
                                </td>
                                <td>
                                    {{ $issue->date_since ? \Carbon\Carbon::parse($issue->date_since)->format('Y-m-d H:i') : '' }}
                                    <input type="hidden" name="date_sinces[]" value="{{ $issue->date_since ? \Carbon\Carbon::parse($issue->date_since)->format('Y-m-d\TH:i') : '' }}">
                                </td>
                                <td>
                                    {{ $issue->date_till ? \Carbon\Carbon::parse($issue->date_till)->format('Y-m-d H:i') : '' }}
                                    <input type="hidden" name="date_tills[]" value="{{ $issue->date_till ? \Carbon\Carbon::parse($issue->date_till)->format('Y-m-d\TH:i') : '' }}">
                                </td>
                                <td>{{ $issue->qty_from }}<input type="hidden" name="qty_froms[]" value="{{ $issue->qty_from }}"></td>
                                <td>{{ $issue->qty_till }}<input type="hidden" name="qty_tills[]" value="{{ $issue->qty_till }}"></td>
                                <td>{{ $issue->free_qty }}<input type="hidden" name="qty_frees[]" value="{{ $issue->free_qty }}"></td>
                                <td>
                                    {{ $freeProdsText }}
                                    <input type="hidden" name="free_products[]" value='{{ $freeProdsJson }}'>
                                </td>
                                <td>
                                    <input type="hidden" name="qty_types[]" value="{{ $qtyType }}">
                                    <button type="button" class="btn btn-danger btn-xs removeRow">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> Save
            </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">
                Close
            </button>
        </div>

        {!! Form::close() !!}
    </div>
</div>

<script>
$(document).ready(function() {
    // ========== FIX: Handle modal focus properly ==========
    $(document).on('shown.bs.modal', '.view_modal', function() {
        // Remove aria-hidden attribute when modal is shown
        $(this).removeAttr('aria-hidden');
        
        // Remove focus from any element that might be causing the warning
        if (document.activeElement) {
            document.activeElement.blur();
        }
        
        // Move focus to a safe element (the categories select)
        setTimeout(function() {
            if ($('#categories').length) {
                $('#categories').focus();
            } else if ($('#products').length) {
                $('#products').focus();
            } else {
                $('.modal-content').focus();
            }
        }, 50);
    });
    
    $(document).on('hidden.bs.modal', '.view_modal', function() {
        // Restore aria-hidden when modal closes
        $(this).attr('aria-hidden', 'true');
    });
    
    // Initialize select2 with proper dropdown parent for modals
    $('.select2').select2({
        width: '100%',
        dropdownParent: $('.view_modal .modal-content')
    });

    /* Qty Type Radio Button Handler */
    function updateQtyFields() {
        let qtyType = $('input[name="qty_type"]:checked').val();
        let $qtyFromLabel = $('#qty_from_label');
        let $qtyTillContainer = $('#qty_till_container');
        let $qtyTillInput = $('#qty_till_input');
        let $qtyFromInput = $('#qty_from_input');

        if (qtyType === 'single') {
            // Single Qty mode
            $qtyFromLabel.text('For Every Qty:*');
            $qtyTillContainer.hide();
            $qtyTillInput.prop('disabled', true);
            $qtyTillInput.val('');

            // Remove required attribute from qty_till
            $qtyTillInput.removeAttr('required');
            // Ensure qty_from is required
            $qtyFromInput.attr('required', true);
        } else {
            // Range Qty mode
            $qtyFromLabel.text('Qty From:*');
            $qtyTillContainer.show();
            $qtyTillInput.prop('disabled', false);

            // Add required attributes to both
            $qtyFromInput.attr('required', true);
            $qtyTillInput.attr('required', true);
        }
    }

    // Bind radio button change event
    $('input[name="qty_type"]').on('change', function() {
        updateQtyFields();
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
            console.error('Error loading subcategories');
            if (typeof toastr !== 'undefined') {
                toastr.error('Error loading subcategories');
            }
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
            console.error('Error loading products');
            if (typeof toastr !== 'undefined') {
                toastr.error('Error loading products');
            }
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

        // Get the first selected product
        let firstProductId = productId[0];
        $unitSelect.append(new Option('Loading...', '')).trigger('change');

        $.get('/distribution/get-product-unit/' + firstProductId, function(res) {
            $unitSelect.empty();
            if (res.units && Object.keys(res.units).length > 0) {
                $.each(res.units, function(id, name) {
                    $unitSelect.append(new Option(name, id));
                });
            } else {
                $unitSelect.append(new Option('No unit available', ''));
            }
            $unitSelect.trigger('change');
        }).fail(function() {
            $unitSelect.empty();
            $unitSelect.append(new Option('Error loading unit', ''));
            $unitSelect.trigger('change');
        });
    });

    /* PRE-POPULATE ALL FIELDS ON EDIT LOAD */
    @if(isset($freeIssues) && $freeIssues->count() > 0)
    @php $first = $freeIssues->first(); @endphp
    setTimeout(function() {
        let savedCatId   = '{{ $first->product_category }}';
        let savedSubId   = '{{ $first->product_subcategory }}';
        let savedProdId  = '{{ $first->product_name }}';
        let savedUnitId  = '{{ $first->unit_id }}';
        let savedFreeProds = @json((array) ($first->free_products ?? []));
        let savedQtyFrom = '{{ $first->qty_from }}';
        let savedQtyTill = '{{ $first->qty_till }}';
        let savedQtyFree = '{{ $first->free_qty }}';

        // Determine qty_type for radio selection
        let qtyType = (savedQtyTill && savedQtyTill != '0' && savedQtyTill != '' && parseFloat(savedQtyTill) > 0) ? 'range' : 'single';
        
        console.log('Qty Type determined:', qtyType, 'Qty Till:', savedQtyTill);
        
        // Set radio button based on qty_type
        if (qtyType === 'single') {
            $('#single_qty_radio').prop('checked', true);
        } else {
            $('#range_qty_radio').prop('checked', true);
        }
        
        // Update UI based on qty_type
        updateQtyFields();

        // Pre-fill Qty From, Qty Till, Free Qty
        $('#qty_from_input').val(savedQtyFrom);
        $('#qty_till_input').val(savedQtyTill);
        $('#qty_free_input').val(savedQtyFree);

        if (!savedCatId) return;

        // Step 1: Set category
        $('#categories').val([savedCatId]).trigger('change.select2');

        // Step 2: Load subcategories via AJAX
        $.get('/distribution/subcategories/' + savedCatId, function(res) {
            $('#subcategories').empty();
            $.each(res, function(id, name) {
                $('#subcategories').append(new Option(name, id));
            });
            if (savedSubId) {
                $('#subcategories').val([savedSubId]).trigger('change.select2');
            }

            // Step 3: Load products via AJAX
            $.get('/distribution/products', { subcategories: [savedSubId] }, function(res2) {
                $('#products').empty();
                $('#free_products').empty();
                $.each(res2, function(id, name) {
                    $('#products').append(new Option(name, id));
                    $('#free_products').append(new Option(name, id));
                });

                if (savedProdId) {
                    $('#products').val([savedProdId]).trigger('change.select2');
                    
                    // Step 4: Load unit for the product
                    $.get('/distribution/get-product-unit/' + savedProdId, function(unitRes) {
                        $('#unit').empty();
                        if (unitRes.units && Object.keys(unitRes.units).length > 0) {
                            $.each(unitRes.units, function(id, name) {
                                $('#unit').append(new Option(name, id));
                            });
                            if (savedUnitId) {
                                $('#unit').val(savedUnitId).trigger('change.select2');
                            }
                        } else {
                            $('#unit').append(new Option('No unit available', ''));
                        }
                    });
                }

                if (savedFreeProds && savedFreeProds.length > 0) {
                    $('#free_products').val(savedFreeProds).trigger('change.select2');
                }
            });
        });
    }, 200);
    @endif

    /* ADD ROW TO DRAFT TABLE */
    $('#addDraftRow').on('click', function() {
        // Get selected data
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
        if (products.length === 0) {
            alert('Please select at least one product.');
            return;
        }

        if (!unitId || unitId === '') {
            alert('Please select a unit for the product.');
            $('#unit').focus();
            return;
        }

        if (freeProducts.length === 0) {
            alert('Please select at least one free product.');
            return;
        }

        if (!date_since || !date_till) {
            alert('Please select Date Since and Date Till.');
            return;
        }

        if (!qty_free || parseFloat(qty_free) <= 0) {
            alert('Please fill in Free Qty (must be greater than 0).');
            return;
        }

        // Validate based on qty type
        let fromNum = parseFloat(qty_from);
        let tillNum = parseFloat(qty_till);

        if (qtyType === 'single') {
            // Single Qty mode: Only qty_from is required
            if (!qty_from || fromNum <= 0) {
                alert('Please enter a valid "For Every Qty" value.');
                return;
            }
            // Clear qty_till for single mode
            qty_till = '';
        } else {
            // Range Qty mode: Both qty_from and qty_till are required
            if (!qty_from || !qty_till) {
                alert('Please fill in Qty From and Qty Till.');
                return;
            }
            if (fromNum > tillNum) {
                alert('Qty From cannot be greater than Qty Till.');
                return;
            }
        }

        // Get the first selected category and subcategory
        let catId = categories.length > 0 ? categories[0].value : '';
        let catName = categories.length > 0 ? categories[0].text : '';
        let subId = subcategories.length > 0 ? subcategories[0].value : '';
        let subName = subcategories.length > 0 ? subcategories[0].text : '';

        // Get free products as array of IDs
        let freeProdsValue = freeProducts.map(function() {
            return $(this).val();
        }).get();

        // Convert to JSON properly
        let freeProdsJson = JSON.stringify(freeProdsValue);
        let freeProdsText = freeProducts.map(function() {
            return $(this).text();
        }).get().join(', ');

        // Create ONE ROW PER PRODUCT
        products.each(function() {
            let productId = $(this).val();
            let productName = $(this).text();

            let rowHtml = `
            <tr>
                <td style="vertical-align: middle;">${escapeHtml(catName)}<input type="hidden" name="cat[]" value="${escapeHtml(catId)}"></td>
                <td style="vertical-align: middle;">${escapeHtml(subName)}<input type="hidden" name="sub[]" value="${escapeHtml(subId)}"></td>
                <td style="vertical-align: middle;">${escapeHtml(unitName)}<input type="hidden" name="unit_ids[]" value="${escapeHtml(unitId)}"></td>
                <td style="vertical-align: middle;">${escapeHtml(productName)}<input type="hidden" name="prod[]" value="${escapeHtml(productId)}"></td>
                <td style="vertical-align: middle;">${escapeHtml(date_since)}<input type="hidden" name="date_sinces[]" value="${escapeHtml(date_since)}"></td>
                <td style="vertical-align: middle;">${escapeHtml(date_till)}<input type="hidden" name="date_tills[]" value="${escapeHtml(date_till)}"></td>
                <td style="vertical-align: middle;">${escapeHtml(qty_from)}<input type="hidden" name="qty_froms[]" value="${escapeHtml(qty_from)}"></td>
                <td style="vertical-align: middle;">${escapeHtml(qty_till || '')}<input type="hidden" name="qty_tills[]" value="${escapeHtml(qty_till || '')}"></td>
                <td style="vertical-align: middle;">${escapeHtml(qty_free)}<input type="hidden" name="qty_frees[]" value="${escapeHtml(qty_free)}"></td>
                <td style="vertical-align: middle;">${escapeHtml(freeProdsText)}<input type="hidden" name="free_products[]" value='${escapeHtml(freeProdsJson)}'></td>
                <td style="vertical-align: middle; text-align: center;"><input type="hidden" name="qty_types[]" value="${escapeHtml(qtyType)}"><button type="button" class="btn btn-danger btn-xs removeRow"><i class="fa fa-times"></i></button></td>
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

        // Reset to Single Qty mode by default
        $('#single_qty_radio').prop('checked', true);
        updateQtyFields();
        
        if (typeof toastr !== 'undefined') {
            toastr.success('Row added successfully!');
        }
        
        // Prevent default behavior
        return false;
    });

    /* Helper function to escape HTML */
    function escapeHtml(str) {
        if (!str) return '';
        return str.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /* REMOVE ROW */
    $(document).on('click', '.removeRow', function() {
        $(this).closest('tr').remove();
        if (typeof toastr !== 'undefined') {
            toastr.info('Row removed');
        }
    });

    /* BEFORE FORM SUBMIT - Validate draft table has rows and data integrity */
    $('#freeIssueForm').on('submit', function(e) {
        let rowCount = $('#draftTable tbody tr').length;

        if (rowCount === 0) {
            e.preventDefault();
            alert('Please add at least one free issue rule before saving.');
            return false;
        }

        // Validate based on qty_type for each row
        let isValid = true;
        $('#draftTable tbody tr').each(function() {
            let qtyType = $(this).find('input[name="qty_types[]"]').val();
            let qtyFrom = parseFloat($(this).find('input[name="qty_froms[]"]').val());
            let qtyTill = $(this).find('input[name="qty_tills[]"]').val();
            let qtyFree = parseFloat($(this).find('input[name="qty_frees[]"]').val());

            if (qtyType === 'single') {
                // Single Qty: Only validate qty_from exists
                if (!qtyFrom || qtyFrom <= 0) {
                    alert('Single Qty rule: "For Every Qty" must be greater than 0.');
                    isValid = false;
                    return false;
                }
                if (!qtyFree || qtyFree <= 0) {
                    alert('Single Qty rule: Free Qty must be greater than 0.');
                    isValid = false;
                    return false;
                }
            } else {
                // Range Qty: Validate both qty_from and qty_till
                if (!qtyFrom || !qtyTill) {
                    alert('Range Qty rule: Both Qty From and Qty Till are required.');
                    isValid = false;
                    return false;
                }
                let fromNum = parseFloat(qtyFrom);
                let tillNum = parseFloat(qtyTill);
                if (fromNum > tillNum) {
                    alert('Range Qty rule: Qty From cannot be greater than Qty Till.');
                    isValid = false;
                    return false;
                }
                if (!qtyFree || qtyFree <= 0) {
                    alert('Range Qty rule: Free Qty must be greater than 0.');
                    isValid = false;
                    return false;
                }
            }
        });

        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Show saving indicator
        let submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        
        // Allow form to submit after validation passes
        return true;
    });
});
</script>
<!-- Main content -->
<section class="content">
    <!-- date and time Ref form number added on table -->
    <div class="row">
        <div class="col-md-3 text-red">
            {{-- <b>@lang('mpcs::lang.date'): <span class="15a_from_date">{{$openingDate}}</span> </b> --}}
            <b>System Entered Date: <span class="15a_from_date">{{$openingDate}}</span> </b>
        </div>
        <div class="col-md-3 text-red">
            <b>@lang('mpcs::lang.ref_previous_form_number'):
                <span class="15a_from_no">
                    {{$refPreviousFormNumber}}
                </span>
            </b>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <h5 style="font-weight: bold;">@lang('mpcs::lang.user_added'):
                    <span class="15a_from_name">
                    {{$name}}
                    </span>
                </h5>
            </div>
        </div>
    </div>
    <!-- end date and time Ref form -->
    <div class="row">
        <div class="box-tools pull-right" style="margin: 14px 20px 14px 0; display: flex; flex-direction: column; gap: 10px; align-items: flex-end;">
            @php
                $header = $row->created_by ?? 0;
            @endphp
            <button type="button"
                    class="btn btn-primary btn-modal  @if (auth()->user()->can('superadmin') || auth()->user()->id === $header) left @endif"
                    data-href="{{ action('\Modules\MPCS\Http\Controllers\F15FormController@get15FormSetting') }}"
                    data-container=".form_f15_modal" id="form_f15_modal_button" @if(!empty($form15Header)) disabled @endif>
                <i class="fa fa-plus"></i> @lang('mpcs::lang.add_15_form_settings')</button>
            <button type="button" class="btn btn-success" id="select_product_categories_btn" style="width: 250px;">
                <i class="fa fa-th-list"></i> Select the Product Categories to show
            </button>
            <button type="button" class="btn btn-info" id="selected_product_categories_btn" style="width: 250px;">
                <i class="fa fa-check-square-o"></i> Selected Product Categories
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">

            @component('components.widget', ['class' => 'box-primary'])
                <div class="col-md-12">
                    <div class="box-body" style="margin-top: 20px;">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="msg"></div>
                                <div class="table-responsive">
                                    <table id="form_15_settings_table" class="table table-striped table-bordered"
                                           width="100%">
                                        <thead>
                                        <tr>
                                            <th>Actions</th>
                                            <th>System Entered Date</th>
                                            <th>Description</th>
                                            <th>Value</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- Modal for adding form settings -->
    <div class="modal fade form_15_settings_modal" id="form_15_settings_modal" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel"></div>
    <!-- Modal for updating form settings -->
    <div class="modal fade update_form_15_settings_modal" id="update_form_15_settings_modal" tabindex="-1"
         role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal form_f15_modal" tabindex="-1" role="dialog" id="form_f15_modal"
         aria-labelledby="gridSystemModalLabel"></div>

</section>


<!-- JavaScript -->
<script type="text/javascript">
    $(document).ready(function () {
        const form15Labels = {
            1: 'Form Starting Number',
            2: 'Ref Previous Form Number',
            3: 'Store Purchases Amount Up to Previous Day',
            4: 'Total (No 17) Up to Previous Day',
            5: 'Opening Stock Up to Previous Day',
            6: 'Grand Total Up to Previous Day',
            7: 'Cash Sales Up to Previous Day',
            8: 'Card Sales Up to Previous Day',
            9: 'Credit Sales Up to Previous Day',
            10: 'Total (No 31) Up to Previous Day',
            11: 'Balance Stock in Sale Price Up to Previous Day',
            12: 'Grand Total Again'
        };

        function getForm15Label(form15_label_id) {
            return form15Labels[form15_label_id] || null;
        }

        // Ambil nomor Form Number dari f15_form_id
        const startingNumberLabel =
                @if (auth()->user()->can('user'))
                    'Form Number : - ';
        @else
            'Form Number : ' + '{{ !empty($headers->id) ? $headers->id : 'N/A' }}';
        @endif

        var form_15_settings_table = $('#form_15_settings_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ action('\Modules\MPCS\Http\Controllers\F15FormController@mpcs15FormSettings') }}",
                type: 'GET',
                dataSrc: function (json) {
                    return json.data.filter(function (row) {
                        return form15Labels[row.form15_label_id] !== undefined;
                    });
                },
                error: function (xhr, error, thrown) {
                    console.log('Ajax error:', xhr.status, error, thrown);
                    alert('Error loading data: ' + thrown);
                }
            },
            columns: [
                {
                    data: 'action',
                    name: 'action',
                    render: function (data) {
                        return data;
                    },
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    width: '10%'
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function (data) {
                        return new Date(data)
                            .toLocaleDateString();
                    },
                    className: 'text-left',
                    width: '20%'
                },
                {
                    data: 'form15_label_id',
                    name: 'form15_label_id',
                    render: function (data) {
                        return form15Labels[data];
                    },
                    className: 'text-left',
                    width: '35%'
                },
                {
                    data: 'rupees',
                    name: 'rupees',
                    render: function (data) {
                        return parseFloat(data).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");

                    },
                    className: 'text-center',
                    width: '35%'
                }
            ],
            columnDefs: [{
                targets: '_all',
                className: 'text-left'
            }],
            paging: false,
            lengthChange: false
        });
        // Menampilkan F 15 Form Starting Number di atas tabel
        $('#form_15_settings_table').before('<div class="form-group" style="display:none;"><label>' + startingNumberLabel +
            '</label></div>');


        // Submit Update Form 15 Settings
        $(document).on('submit', 'form#update_15_form_settings', function (e) {
            e.preventDefault();
            $(this).find('button[type="submit"]').attr('disabled', true);
            var data = $(this).serialize();

            $.ajax({
                method: $(this).attr('method'),
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                success: function (result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                        form_15_settings_table.ajax.reload();
                        $('#update_form_15_settings_modal').modal('hide');
                        let setting = result.setting;
                        if (setting.form15_label_id == 2) {
                            $('.15a_from_no').text(setting.rupees);
                        }
                    } else {
                        toastr.error(result.msg);
                    }
                    $('button[type="submit"]').attr('disabled', false);
                },
                error: function (xhr, status, error) {
                    toastr.error(error);
                    $('button[type="submit"]').attr('disabled', false);
                }
            });
        });

        $(document).on('submit', 'form#add_15_form_settings', function (e) {
            e.preventDefault();
            $(this).find('button[type="submit"]').attr('disabled', true);
            var data = $(this).serialize();
            var openingDate = $('input[name="dated_at"]').val();
            var refPreviousFormNumber = $('input[name="rupees[2]"]').val();

            $.ajax({
                method: $(this).attr('method'),
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                success: function (result) {
                    if (result.success == true) {
                        toastr.success(result.msg);
                        form_15_settings_table.ajax.reload();
                        $('#form_f15_modal').modal('hide');
                        $('.15a_from_date').text(openingDate);
                        $('.15a_from_no').text(refPreviousFormNumber);
                        $('.15a_from_name').text(@json(auth()->user()->username));
                        $('#form_f15_modal_button').attr('disabled', true);
                    } else {
                        toastr.error(result.msg);
                    }
                    $('button[type="submit"]').attr('disabled', false);
                },
                error: function (xhr, status, error) {
                    toastr.error(error);
                    $('button[type="submit"]').attr('disabled', false);
                }
            });
        });
    });

    $(document).ready(function() {
        // F15 Categories Feature Modals and JS Handlers
        let allCategoriesMaster = [];
        let selectedCategoriesMaster = [];
        let f15CategorySelectionsTable = null;

        window.renderCategoryLists = function() {
            let allFilter = $('#all_categories_filter').val().toLowerCase();
            let $allList = $('#all_categories_list').empty();
            allCategoriesMaster.forEach(cat => {
                if (cat.name.toLowerCase().includes(allFilter)) {
                    $allList.append(`<option value="${cat.id}">${cat.name}</option>`);
                }
            });

            let selectedFilter = $('#selected_categories_filter').val().toLowerCase();
            let $selectedList = $('#selected_categories_list').empty();
            selectedCategoriesMaster.forEach(cat => {
                if (cat.name.toLowerCase().includes(selectedFilter)) {
                    $selectedList.append(`<option value="${cat.id}">${cat.name}</option>`);
                }
            });
        }

        window.loadCategoriesModal = function(selectionId = null) {
            $.ajax({
                url: "{{ action('\Modules\MPCS\Http\Controllers\F15FormController@getF15Categories') }}",
                type: 'GET',
                data: { id: selectionId },
                success: function(response) {
                    $('#category_selection_id').val(response.selection_id);
                    let selectedIds = (response.selected_ids || []).map(id => parseInt(id));

                    allCategoriesMaster = [];
                    selectedCategoriesMaster = [];

                    response.all_categories.forEach(cat => {
                        if (selectedIds.includes(parseInt(cat.id))) {
                            selectedCategoriesMaster.push(cat);
                        } else {
                            allCategoriesMaster.push(cat);
                        }
                    });

                    allCategoriesMaster.sort((a, b) => a.name.localeCompare(b.name));
                    selectedCategoriesMaster.sort((a, b) => a.name.localeCompare(b.name));

                    $('#all_categories_filter').val('');
                    $('#selected_categories_filter').val('');

                    renderCategoryLists();
                    $('#f15_categories_modal').modal('show');
                },
                error: function(xhr, status, error) {
                    toastr.error('Failed to load product categories: ' + error);
                }
            });
        }

        $(document).on('click', '#select_product_categories_btn', function() {
            loadCategoriesModal();
        });

        $(document).on('keyup', '#all_categories_filter, #selected_categories_filter', function() {
            renderCategoryLists();
        });

        $(document).on('click', '#btn_move_right', function() {
            let selectedIds = $('#all_categories_list').val() || [];
            if (selectedIds.length === 0) return;

            selectedIds.forEach(id => {
                let idx = allCategoriesMaster.findIndex(cat => cat.id == id);
                if (idx !== -1) {
                    selectedCategoriesMaster.push(allCategoriesMaster[idx]);
                    allCategoriesMaster.splice(idx, 1);
                }
            });

            selectedCategoriesMaster.sort((a, b) => a.name.localeCompare(b.name));
            renderCategoryLists();
        });

        $(document).on('click', '#btn_move_left', function() {
            let selectedIds = $('#selected_categories_list').val() || [];
            if (selectedIds.length === 0) return;

            selectedIds.forEach(id => {
                let idx = selectedCategoriesMaster.findIndex(cat => cat.id == id);
                if (idx !== -1) {
                    allCategoriesMaster.push(selectedCategoriesMaster[idx]);
                    selectedCategoriesMaster.splice(idx, 1);
                }
            });

            allCategoriesMaster.sort((a, b) => a.name.localeCompare(b.name));
            renderCategoryLists();
        });

        // Double click shortcut to move items
        $(document).on('dblclick', '#all_categories_list option', function() {
            let id = $(this).val();
            let idx = allCategoriesMaster.findIndex(cat => cat.id == id);
            if (idx !== -1) {
                selectedCategoriesMaster.push(allCategoriesMaster[idx]);
                allCategoriesMaster.splice(idx, 1);
                selectedCategoriesMaster.sort((a, b) => a.name.localeCompare(b.name));
                renderCategoryLists();
            }
        });

        $(document).on('dblclick', '#selected_categories_list option', function() {
            let id = $(this).val();
            let idx = selectedCategoriesMaster.findIndex(cat => cat.id == id);
            if (idx !== -1) {
                allCategoriesMaster.push(selectedCategoriesMaster[idx]);
                selectedCategoriesMaster.splice(idx, 1);
                allCategoriesMaster.sort((a, b) => a.name.localeCompare(b.name));
                renderCategoryLists();
            }
        });

        $(document).on('click', '#btn_save_f15_categories', function() {
            let categoryIds = selectedCategoriesMaster.map(cat => cat.id);
            let selectionId = $('#category_selection_id').val();

            $(this).attr('disabled', true);
            $.ajax({
                url: "{{ action('\Modules\MPCS\Http\Controllers\F15FormController@saveF15Categories') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    category_ids: categoryIds,
                    selection_id: selectionId
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg);
                        $('#f15_categories_modal').modal('hide');
                        if (f15CategorySelectionsTable) {
                            f15CategorySelectionsTable.ajax.reload();
                        }
                        if (typeof get_previous_value_15 === 'function') {
                            get_previous_value_15();
                        }
                    } else {
                        toastr.error(response.msg);
                    }
                    $('#btn_save_f15_categories').attr('disabled', false);
                },
                error: function(xhr, status, error) {
                    toastr.error('Failed to save selections: ' + error);
                    $('#btn_save_f15_categories').attr('disabled', false);
                }
            });
        });

        $(document).on('click', '#selected_product_categories_btn', function() {
            $('#f15_selected_categories_log_modal').modal('show');
            if (!f15CategorySelectionsTable) {
                f15CategorySelectionsTable = $('#f15_category_selections_table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ action('\Modules\MPCS\Http\Controllers\F15FormController@getF15CategorySelections') }}",
                        type: 'GET',
                        error: function(xhr, error, thrown) {
                            toastr.error('Failed to load history selections: ' + thrown);
                        }
                    },
                    columns: [
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' },
                        { data: 'created_at', name: 'created_at' },
                        { data: 'selected_categories', name: 'selected_categories', orderable: false },
                        { data: 'username', name: 'username' }
                    ]
                });
            } else {
                f15CategorySelectionsTable.ajax.reload();
            }
        });

        $(document).on('click', '.edit-category-selection', function() {
            let selectionId = $(this).data('id');
            $('#f15_selected_categories_log_modal').modal('hide');
            loadCategoriesModal(selectionId);
        });
    });

    // Function to delete Form Setting
    function deleteFormSetting(button) {
        var url = button.getAttribute('data-href');

        if (confirm("Are you sure you want to delete this setting?")) {
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success(data.msg);
                        button.closest('tr').remove();
                    } else {
                        toastr.error(data.msg);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error("Something went wrong.");
                });
        }
    }
</script>

<!-- Modal for Select the Product Categories to show -->
<div class="modal fade" id="f15_categories_modal" tabindex="-1" role="dialog" aria-labelledby="f15CategoriesModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 8px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background-color: #3c8dbc; color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 0.8;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="f15CategoriesModalLabel" style="font-weight: bold;">F 15 Form - Select Product Categories</h4>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <input type="hidden" id="category_selection_id" value="">
                <div class="row" style="display: flex; align-items: center; justify-content: space-between;">
                    <!-- Left Box: All Product Categories -->
                    <div class="col-md-5">
                        <label style="font-weight: bold; margin-bottom: 8px; display: block; font-size: 14px;">All Product Categories</label>
                        <div class="form-group" style="margin-bottom: 10px;">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-search"></i></span>
                                <input type="text" id="all_categories_filter" class="form-control" placeholder="Type & Auto Filter">
                            </div>
                        </div>
                        <div style="border: 1px solid #ccc; border-radius: 4px; overflow: hidden;">
                            <select id="all_categories_list" class="form-control" multiple style="height: 300px; border: none; font-size: 13px; padding: 5px;">
                            </select>
                        </div>
                    </div>
                    
                    <!-- Middle: Move Buttons -->
                    <div class="col-md-2 text-center" style="display: flex; flex-direction: column; gap: 15px; align-items: center; justify-content: center; margin-top: 50px;">
                        <button type="button" id="btn_move_right" class="btn btn-default" style="padding: 10px 20px; font-size: 24px; border: none; background: transparent; transition: transform 0.2s;">
                            <span style="color: #28a745; font-weight: bold; font-size: 50px; line-height: 1;">➔</span>
                        </button>
                        <button type="button" id="btn_move_left" class="btn btn-default" style="padding: 10px 20px; font-size: 24px; border: none; background: transparent; transition: transform 0.2s;">
                            <span style="color: #dc3545; font-weight: bold; font-size: 50px; line-height: 1;">←</span>
                        </button>
                    </div>
                    
                    <!-- Right Box: Selected Product Categories -->
                    <div class="col-md-5">
                        <label style="font-weight: bold; margin-bottom: 8px; display: block; font-size: 14px;">Selected Product Categories</label>
                        <div class="form-group" style="margin-bottom: 10px;">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-search"></i></span>
                                <input type="text" id="selected_categories_filter" class="form-control" placeholder="Type & Auto Filter">
                            </div>
                        </div>
                        <div style="border: 1px solid #ccc; border-radius: 4px; overflow: hidden;">
                            <select id="selected_categories_list" class="form-control" multiple style="height: 300px; border: none; font-size: 13px; padding: 5px;">
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background-color: #f9f9f9; border-top: 1px solid #eee;">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" id="btn_save_f15_categories" class="btn btn-primary" style="font-weight: bold; padding: 6px 20px;">Save Selection</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Selected Product Categories Log -->
<div class="modal fade" id="f15_selected_categories_log_modal" tabindex="-1" role="dialog" aria-labelledby="f15SelectedCategoriesLogModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 8px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background-color: #3c8dbc; color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 0.8;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="f15SelectedCategoriesLogModalLabel" style="font-weight: bold;">Selected Product Categories</h4>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <div class="table-responsive">
                    <table id="f15_category_selections_table" class="table table-striped table-bordered" width="100%">
                        <thead>
                            <tr>
                                <th>Edit</th>
                                <th>Date & Time</th>
                                <th>Selected Product Categories</th>
                                <th>Last entered / Edited By</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer" style="background-color: #f9f9f9; border-top: 1px solid #eee;">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

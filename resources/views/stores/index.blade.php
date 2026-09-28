@extends('layouts.app')
@section('title', __('store.store_list'))

@section('content')

<style>
    /* /stores compact column sizing - display only; no data logic changes. */
    #store_table th:nth-child(1),
    #store_table td:nth-child(1) {
        width: 8% !important;
        max-width: 95px;
    }

    #store_table th:nth-child(2),
    #store_table td:nth-child(2) {
        width: 12% !important;
        max-width: 150px;
    }

    #store_table th:nth-child(3),
    #store_table td:nth-child(3) {
        width: 14% !important;
        max-width: 180px;
    }

    /* Address, Contact, Stock, Status and Action use the same base width. */
    #store_table th:nth-child(4),
    #store_table td:nth-child(4),
    #store_table th:nth-child(5),
    #store_table td:nth-child(5),
    #store_table th:nth-child(7),
    #store_table td:nth-child(7),
    #store_table th:nth-child(8),
    #store_table td:nth-child(8) {
        width: 9% !important;
        max-width: 120px;
        text-align: center;
    }

    /* Stock starts at the same width as Address, but can grow for longer values.
       JS updates --store-stock-width after every DataTable draw, capped to keep
       the full table inside the page viewport. */
    #store_table th:nth-child(6),
    #store_table td:nth-child(6) {
        width: var(--store-stock-width, 9%) !important;
        max-width: none;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
        text-align: center;
    }

    /* Keep the whole stores table within the available screen width. */
    .store-table-fit {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    #store_table_wrapper,
    #store_table {
        width: 100% !important;
        max-width: 100% !important;
    }

    #store_table {
        table-layout: fixed;
    }

    #store_table th,
    #store_table td {
        box-sizing: border-box;
        padding-left: 5px !important;
        padding-right: 5px !important;
        vertical-align: middle;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    #store_table th:nth-child(5),
    #store_table td:nth-child(5),
    #store_table th:nth-child(7),
    #store_table td:nth-child(7),
    #store_table th:nth-child(8),
    #store_table td:nth-child(8) {
        white-space: normal;
    }

    #store_table .store-address-btn {
        min-width: 72px;
        padding: 4px 10px;
        white-space: nowrap;
    }

    #store_address_modal .modal-dialog {
        max-width: 620px;
    }

    #store_address_modal .store-address-content {
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        word-break: break-word;
        line-height: 1.6;
        margin: 0;
        padding: 6px 2px;
    }
</style>

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('store.store_list')</h1>
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['id' => 'location_id', 'class' =>
                    'form-control select2', 'style' => 'width:100%']); !!}
                </div>
            </div>
            @endcomponent
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'store.all_Store' )])
        @can('store.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-primary btn-modal pull-right"
                    data-href="{{action('StoreController@create')}}" data-container=".store_modal">
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
            @endslot
        @endcan
        @can('store.view')
            <div class="table-responsive store-table-fit">
                <table class="table table-bordered table-striped" id="store_table">
                    <thead>
                        <tr>
                            <th>@lang('store.location_id')</th>
                            <th>@lang('store.location_name')</th>
                            <th>@lang('store.name')</th>
                            <th>@lang('store.address')</th>
                            <th>@lang('store.contact')</th>
                            <th>@lang('store.stock')</th>
                            <th>@lang('store.status')</th>
                            <th>@lang('store.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade store_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade" id="store_address_modal" tabindex="-1" role="dialog" aria-labelledby="storeAddressModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="storeAddressModalLabel">@lang('store.address')</h4>
                </div>
                <div class="modal-body">
                    <p class="store-address-content" id="store_address_content"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                </div>
            </div>
        </div>
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')
<script>
    $('#location_id').change(function () {
        store_table.ajax.reload();
    });
    //employee list
    store_table = $('#store_table').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        ajax: {
            url: '{{action("StoreController@index")}}',
            data: function (d) {
                d.location_id = $('#location_id').val();
            }
        },
        columns: [
            { data: 'location_id', name: 'business_locations.location_id' },
            { data: 'location_name', name: 'business_locations.name' },
            { data: 'name', name: 'name' },
            {
                data: 'address',
                name: 'address',
                orderable: false,
                render: function(data, type, row) {
                    if (type !== 'display') {
                        return data || '';
                    }

                    var address = $.trim(data == null ? '' : String(data));
                    var hoverText = address || 'No address available';
                    var safeAddress = $('<div>').text(address).html()
                        .replace(/\"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                    var safeHoverText = $('<div>').text(hoverText).html()
                        .replace(/\"/g, '&quot;')
                        .replace(/'/g, '&#39;');

                    return '<button type="button" class="btn btn-info btn-xs store-address-btn" ' +
                        'data-address="' + safeAddress + '" ' +
                        'title="' + safeHoverText + '" ' +
                        'data-toggle="tooltip" data-placement="top">Address</button>';
                }
            },
            { data: 'contact_number', name: 'contact_number' },
            { data: 'stock', name: 'stock' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action' },
        ],
        fnDrawCallback: function (oSettings) {
            $('#store_table [data-toggle="tooltip"]').tooltip({
                container: 'body',
                trigger: 'hover'
            });

            adjustStoreStockColumnWidth();
        },
    });

    /**
     * Keep Stock at the same base width as Address (9%).
     * When current-page Stock text is longer, increase only that column,
     * up to 18%, so the table still stays inside the screen.
     */
    function adjustStoreStockColumnWidth() {
        var maxChars = 0;

        $('#store_table tbody tr').each(function() {
            var text = $.trim($(this).find('td:eq(5)').text());
            maxChars = Math.max(maxChars, text.length);
        });

        var stockWidth = 9;
        if (maxChars > 12) {
            stockWidth = Math.min(18, 9 + Math.ceil((maxChars - 12) / 4));
        }

        $('#store_table').css('--store-stock-width', stockWidth + '%');
    }

    $(document).on('click', '.store-address-btn', function() {
        var address = $(this).attr('data-address') || '';
        $('#store_address_content').text(address || 'No address available');
        $('#store_address_modal').modal('show');
    });

    $(document).on('click', 'a.delete_store', function(e) {
        e.preventDefault();
        swal({
            title: LANG.sure,
            text: 'This store will be deleted.',
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                var data = $(this).serialize();

                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success === true) {
                            toastr.success(result.msg);
                            store_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });
    $('#filter_business').select2();


    // Store Edit modal. Keep the handler Store-specific; the previous code
    // accidentally bound to #unit_edit_form, so Store edits fell back to a
    // full-page PUT submission and could be intercepted by location middleware.
    $(document).on('click', 'button.edit_store_button', function(e) {
        e.preventDefault();
        var href = $(this).data('href');

        $('div.edit_modal').load(href, function(response, status, xhr) {
            if (status === 'error') {
                toastr.error('Unable to open Store edit form' + (xhr && xhr.status ? ' (' + xhr.status + ')' : '') + '.');
                return;
            }

            $(this).modal('show');
            initStoreLocationSelect($(this));
        });
    });

    // The Add modal is loaded by the normal .btn-modal engine. Initialise the
    // Store Location Select2 inside the modal only, avoiding the page filter
    // #location_id and the modal Location field interfering with each other.
    $(document).on('shown.bs.modal', '.store_modal, .edit_modal', function() {
        initStoreLocationSelect($(this));
    });

    function initStoreLocationSelect($modal) {
        var $select = $modal.find('select.store-location-select');
        if (!$select.length) {
            return;
        }

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        $select.select2({
            width: '100%',
            dropdownParent: $modal
        });
    }

    // Fast Add/Edit save. Use one delegated handler because both forms are
    // loaded dynamically. Laravel Collective supplies _method=PUT on Edit, so
    // POST + serialized form data remains compatible with StoreController@update.
    $(document).on('submit.storeCrud', '#store_create_form, #store_edit_form', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        var $form = $(this);
        var $modal = $form.closest('.modal');
        var $saveButton = $form.find('button[type="submit"]');

        if ($form.data('saving')) {
            return;
        }

        var locationId = $form.find('select[name="location_id"]').val();
        if (!locationId) {
            toastr.error('Please select a Location.');
            return;
        }

        $form.data('saving', true);
        $saveButton.prop('disabled', true);

        $.ajax({
            method: 'POST',
            url: $form.attr('action'),
            dataType: 'json',
            data: $form.serialize(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).done(function(result) {
            if (result && result.success == true) {
                $modal.modal('hide');
                toastr.success(result.msg || 'Store saved successfully.');
                store_table.ajax.reload(null, false);
            } else {
                toastr.error((result && result.msg) ? result.msg : 'Unable to save Store.');
            }
        }).fail(function(xhr) {
            var message = 'Unable to save Store.';

            if (xhr && xhr.responseJSON) {
                if (xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON.msg) {
                    message = xhr.responseJSON.msg;
                }
            } else if (xhr && xhr.status === 403) {
                message = 'Unauthorized access to this location.';
            }

            toastr.error(message);
        }).always(function() {
            $form.data('saving', false);
            $saveButton.prop('disabled', false);
        });
    });

    
</script>
@endsection
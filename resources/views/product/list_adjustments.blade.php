@extends('layouts.app')
@section('title', __('product.list_adjustments'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('product.list_adjustments')
        <small>@lang('product.manage_adjustments')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="settlement_tabs">
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#list-adjusted-opening-stock" data-toggle="tab">
                    <strong>@lang('product.list_adjusted_opening_stock')</strong>
                </a>
            </li>
            <li>
                <a href="#list-adjusted-product-code" data-toggle="tab">
                    <strong>@lang('product.list_adjusted_product_code')</strong>
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- First Tab: Adjusted Opening Stock -->
            <div class="tab-pane active" id="list-adjusted-opening-stock">
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="adjusted_opening_stocks_table">
                            <thead>
                                <tr>
                                    <th>@lang('product.system_date_time')</th>
                                    <th>@lang('product.transaction_date')</th>
                                    <th>@lang('product.product_category')</th>
                                    <th>@lang('product.product_subcategory')</th>
                                    <th>@lang('product.product_name')</th>
                                    <th>@lang('product.product_sku')</th>
                                    <th>@lang('product.original_quantity')</th>
                                    <th>@lang('product.adjusted_quantity')</th>
                                    <th>@lang('lang_v1.note')</th>
                                    <th>@lang('product.adjusted_by')</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTable will populate this -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Second Tab: Adjusted Product Code -->
            <div class="tab-pane" id="list-adjusted-product-code">
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="adjusted_product_code_table">
                            <thead>
                                <tr>
                                    <th>@lang('product.system_date_time')</th>
                                    <th>@lang('product.transaction_date')</th>
                                    <th>@lang('product.product_category')</th>
                                    <th>@lang('product.product_subcategory')</th>
                                    <th>@lang('product.product_name')</th>
                                    <th>@lang('product.product_sku')</th>
                                    <th>@lang('product.original_code')</th>
                                    <th>@lang('product.adjusted_code')</th>
                                    <th>@lang('lang_v1.note')</th>
                                    <th>@lang('product.adjusted_by')</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTable will populate this -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Note Modal (Reused for both tabs) -->
<div class="modal fade" id="noteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">@lang('lang_v1.note')</h4>
            </div>
            <div class="modal-body">
                <p id="noteContent"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    console.log('Document ready, initializing tables...');
    
    // Small delay to ensure everything is loaded
    setTimeout(function() {
        console.log('Initializing DataTables now...');
        
        // DESTROY ANY EXISTING DATATABLES FIRST
        if ($.fn.DataTable.isDataTable('#adjusted_opening_stocks_table')) {
            $('#adjusted_opening_stocks_table').DataTable().destroy();
        }
        if ($.fn.DataTable.isDataTable('#adjusted_product_code_table')) {
            $('#adjusted_product_code_table').DataTable().destroy();
        }
        
        // Clear any existing data
        $('#adjusted_opening_stocks_table tbody').empty();
        $('#adjusted_product_code_table tbody').empty();
        
        // ========== FIRST TAB: Adjusted Opening Stock DataTable ==========
        var openingStockUrl = '{{ route("products.getAdjustedOpeningStocks") }}';
        console.log('Opening Stock AJAX URL:', openingStockUrl);
        
        var adjusted_stocks_table = $('#adjusted_opening_stocks_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: openingStockUrl,
                type: 'GET',
                error: function(xhr, status, error) {
                    console.error('Opening Stock Table AJAX error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    $('#adjusted_opening_stocks_table tbody').html('<tr><td colspan="10" class="text-center text-danger">Error loading data. Please check console.</td></tr>');
                }
            },
            columns: [
                { data: 'system_date_time', name: 'system_date_time' },
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'product_category', name: 'product_category' },
                { data: 'product_subcategory', name: 'product_subcategory' },
                { data: 'product_name', name: 'product_name' },
                { data: 'product_sku', name: 'product_sku' },
                { data: 'original_quantity', name: 'original_quantity' },
                { data: 'adjusted_quantity', name: 'adjusted_quantity' },
                { data: 'note_button', name: 'note_button', orderable: false, searchable: false },
                { data: 'adjusted_by', name: 'adjusted_by' }
            ],
            order: [[0, 'desc']],
            language: {
                processing: '<i class="fa fa-spinner fa-spin"></i> Loading...',
                zeroRecords: 'No adjustments found',
                infoEmpty: 'No records available',
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: 'Next',
                    previous: 'Previous'
                }
            },
            initComplete: function(settings, json) {
                console.log('Opening Stock Table initialization complete');
                console.log('Records loaded:', json ? json.recordsTotal : 0);
            }
        });

        // ========== SECOND TAB: Adjusted Product Code DataTable ==========
        var productCodeUrl = '{{ route("products.getAdjustedProductCodes") }}';
        console.log('Product Code AJAX URL:', productCodeUrl);
        
        var adjusted_code_table = $('#adjusted_product_code_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: productCodeUrl,
                type: 'GET',
                error: function(xhr, status, error) {
                    console.error('Product Code Table AJAX error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    $('#adjusted_product_code_table tbody').html('<tr><td colspan="10" class="text-center text-danger">Error loading data. Please check console.</td></tr>');
                }
            },
            columns: [
                { data: 'system_date_time', name: 'system_date_time' },
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'product_category', name: 'product_category' },
                { data: 'product_subcategory', name: 'product_subcategory' },
                { data: 'product_name', name: 'product_name' },
                { data: 'product_sku', name: 'product_sku' },
                { data: 'original_code', name: 'original_code' },
                { data: 'adjusted_code', name: 'adjusted_code' },
                { data: 'note_button', name: 'note_button', orderable: false, searchable: false },
                { data: 'adjusted_by', name: 'adjusted_by' }
            ],
            order: [[0, 'desc']],
            language: {
                processing: '<i class="fa fa-spinner fa-spin"></i> Loading...',
                zeroRecords: 'No product code adjustments found',
                infoEmpty: 'No records available',
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: 'Next',
                    previous: 'Previous'
                }
            },
            initComplete: function(settings, json) {
                console.log('Product Code Table initialization complete');
                console.log('Records loaded:', json ? json.recordsTotal : 0);
            }
        });

        // ========== TAB SWITCHING: Reload tables when tab changes ==========
        $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            var target = $(e.target).attr('href');
            
            if (target === '#list-adjusted-opening-stock') {
                console.log('Switched to Opening Stock tab, reloading...');
                adjusted_stocks_table.ajax.reload(null, false);
            } else if (target === '#list-adjusted-product-code') {
                console.log('Switched to Product Code tab, reloading...');
                adjusted_code_table.ajax.reload(null, false);
            }
        });
        
    }, 500); // 500ms delay
});

// Global function to show note modal (works for both tabs)
function showNoteModal(note) {
    console.log('Opening note modal with note:', note);
    $('#noteContent').text(note);
    $('#noteModal').modal('show');
}
</script>
@endsection
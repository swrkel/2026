@extends('layouts.app')
@section('title', __('mpcs::lang.F17_form'))

@section('content')
<style>
    @media print {
        #form_17_view_table_wrapper .dataTables_length,
        #form_17_view_table_wrapper .dataTables_filter,
        #form_17_view_table_wrapper .dataTables_info,
        #form_17_view_table_wrapper .dataTables_paginate {
            display: none !important;
        }
        #form_17_view_table {
            width: 100% !important;
            font-size: 9px !important;
        }
        #form_17_view_table th,
        #form_17_view_table td {
            padding: 3px !important;
            white-space: normal !important;
        }
    }
</style>
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('mpcs::lang.f17_from') . ' - ' . __('messages.view')])
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="form_17_view_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('mpcs::lang.index')</th>
                    <th>@lang('mpcs::lang.product_code')</th>
                    <th>@lang('mpcs::lang.product')</th>
                    <th>@lang('mpcs::lang.current_stock')</th>
                    <th>@lang('mpcs::lang.unit_price')</th>
                    <th>@lang('mpcs::lang.select_mode')</th>
                    <th>@lang('mpcs::lang.new_price')</th>
                    <th>@lang('mpcs::lang.unit_price_difference')</th>
                    <th>@lang('mpcs::lang.price_changed_loss')</th>
                    <th>@lang('mpcs::lang.price_changed_gain')</th>
                    <th>@lang('mpcs::lang.signature')</th>
                    <th>@lang('mpcs::lang.page_no')</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function () {
        var f17AutoPrint = {{ request()->get('print') == 1 ? 'true' : 'false' }};
        var f17Printed = false;

        $('#form_17_view_table').DataTable({
            processing: true,
            serverSide: true,
            pageLength: f17AutoPrint ? -1 : 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            ajax: {
                url: '/mpcs/F17/{{ $id }}'
            },
            columns: [
                { data: 'DT_Row_Index', name: 'DT_Row_Index', orderable: false, searchable: false },
                { data: 'sku', name: 'products.sku' },
                { data: 'product', name: 'products.name' },
                { data: 'current_stock', name: 'current_stock' },
                { data: 'unit_price', name: 'unit_price' },
                { data: 'select_mode', name: 'select_mode' },
                { data: 'new_price', name: 'new_price' },
                { data: 'unit_price_difference', name: 'unit_price_difference' },
                { data: 'price_changed_loss', name: 'price_changed_loss' },
                { data: 'price_changed_gain', name: 'price_changed_gain' },
                { data: 'signature', name: 'signature', orderable: false, searchable: false },
                { data: 'page_no', name: 'page_no' }
            ],
            columnDefs: [
                { width: 20, targets: 6 }
            ],
            initComplete: function () {
                if (f17AutoPrint && !f17Printed) {
                    f17Printed = true;
                    window.setTimeout(function () {
                        window.focus();
                        window.print();
                    }, 350);
                }
            }
        });
    });
</script>
@endsection

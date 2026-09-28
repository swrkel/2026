@extends('layouts.app')
@section('title', 'Categories')

@section('content')


<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang( 'category.manage_your_categories' )</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang( 'category.categories' )</a></li>
                    <li><span>@lang( 'category.manage_your_categories' )</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content main-content-inner">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'category.manage_your_categories' )])
        @can('category.create')
            @slot('tool')
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-primary btn-modal category_modal_trigger" id="category_add_btn" 
                    data-href="{{action('CategoryController@create')}}" 
                    data-container=".category_modal">
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                </div>
            @endslot
        @endcan
        @can('category.view')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="all_category_table" >
                    <thead>
                        <tr>
                            <th style="width:25px">@lang( 'category.category' )</th>
                            <th>@lang( 'category.code' )</th>
                            <th style="width:25px">@lang( 'category.sub_category' )</th>
                            <th>@lang( 'category.sub_cat_code' )</th>
                            <th style="width:50px">@lang( 'category.cogs' )</th>
                            <th style="width:50px">@lang( 'category.sales_accounts' )</th>
                            <th>@lang( 'category.vat_based_on' )</th>
                            <th style="width:150px">@lang( 'category.apply_vat_on' )</th>
                            <th>@lang( 'category.vat_exempted' )</th>
                            <!--<th>@lang( 'category.price_reduction_acc' )</th>
                            <th>@lang( 'category.price_increment_acc' )</th>
                            <th>@lang( 'category.remaining_stock_adjusts' )</th>-->
                            <th class="notexport">@lang( 'messages.action' )</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade category_modal" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')
    <style>
        /* IS1623 category modal dropdown hardening */
        /* IS1626: keep native category/account selects visible, but do NOT force Select2 original selects visible.
           Forcing all .form-control selects to display:block was creating broken/duplicate dropdowns,
           especially the Parent Category select inside the Add Category modal. */
        .category_modal select.category-native-select {
            width: 100% !important;
            height: 34px !important;
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            position: relative !important;
            z-index: 1061 !important;
            background: #fff !important;
            color: #555 !important;
        }
        .category_modal select.select2-hidden-accessible {
            display: none !important;
        }
        .category_modal .select2-container { width: 100% !important; }
        .select2-container--open { z-index: 999999 !important; }
    </style>
    <script>
        $(document).on('click', '#category_add_btn, .category_modal_trigger', function(e) {
            e.preventDefault();
            var href = $(this).data('href') || $(this).attr('href');
            var container = $(this).data('container') || '.category_modal';
            if (!href) { return; }
            $.ajax({
                url: href,
                dataType: 'html',
                success: function(result) {
                    $(container).html(result).modal('show');
                    $(container).off('shown.bs.modal.categoryDropdownFix').on('shown.bs.modal.categoryDropdownFix', function () {
                        var $modal = $(this);
                        $modal.find('select.category-native-select').each(function(){
                            if ($(this).data('select2')) { $(this).select2('destroy'); }
                            $(this).css({'width':'100%', 'display':'block'});
                        });
                        $modal.find('select.select2').not('.category-native-select').each(function(){
                            if ($(this).data('select2')) { $(this).select2('destroy'); }
                            $(this).select2({ dropdownParent: $modal, width: '100%' });
                        });
                    });
                },
                error: function() {
                    toastr.error((typeof LANG !== 'undefined' && LANG.something_went_wrong) ? LANG.something_went_wrong : 'Something went wrong');
                }
            });
        });

        $(document).ready(function(){
            category_table = $('#all_category_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '/categories',
                   
                },
                columns: [
                    { data: 'category_name', name: 'name' },
                    { data: 'category_short_code', name: 'short_code' },
                    { data: 'sub_category_name', name: 'name' },
                    { data: 'sub_category_short_code', name: 'short_code' },
                    { data: 'cogs', name: 'cogs' },
                    { data: 'sales_accounts', name: 'sales_accounts' },
                    { data: 'vat_based_on', name: 'vat_based_on' },
                    { data: 'apply_vat_on', name: 'apply_vat_on' },
                    { data: 'vat_exempted', name: 'vat_exempted' },
                    //{ data: 'decr_accounts', name: 'decr_accounts' },
                    //{ data: 'incr_accounts', name: 'incr_accounts' },
                    //{ data: 'remaining_stock_adjusts', name: 'remaining_stock_adjusts' },
                    { data: 'action', name: 'action' },
                ],
                @include('layouts.partials.datatable_export_button')
              
            });
        })
    </script>
@endsection
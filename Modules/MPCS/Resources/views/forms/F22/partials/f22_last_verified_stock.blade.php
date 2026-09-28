<style>
    /*
     * IS2044: room for the 12 columns on this table.
     *
     * A minimum width so they are not squeezed, headings that wrap instead of
     * forcing each column to the width of its longest word, and a visible
     * scrollbar so it is clear more columns exist to the right.
     */
    #form_22_last_verified_table {
        width: auto !important;
        min-width: 1500px !important;
        max-width: none !important;
        table-layout: auto !important;
    }

    #form_22_last_verified_table th {
        white-space: normal;
        vertical-align: middle;
        font-size: 12px;
        padding: 6px 5px;
    }

    #form_22_last_verified_table td {
        font-size: 12px;
        padding: 6px 5px;
        white-space: nowrap;
    }

    .dataTables_scrollBody { scrollbar-width: thin; }
    .dataTables_scrollBody::-webkit-scrollbar { height: 10px; }
    .dataTables_scrollBody::-webkit-scrollbar-thumb {
        background: #c1c9d2;
        border-radius: 5px;
    }
</style>
<!-- Main content -->
<section class="content">
    {!! Form::open(['action' => '\Modules\MPCS\Http\Controllers\F22FormController@printF22Form', 'method' =>
    'post', 'id' =>
    'lf_f22_form']) !!}

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
            <div class="row">
                <div class="col-md-3">
                </div>
                <div class="col-md-3 pull-right">
                    <button type="submit" name="submit_type" id="lf_f22_print" value="print"
                        class="btn btn-primary pull-right">@lang('mpcs::lang.print')</button>
                </div>
            </div>
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-4"></div>
                    <div class="col-md-5">
                        <div class="text-center">
                            <h5 style="font-weight: bold;">{{request()->session()->get('business.name')}} <br>
                                <span class="lf_f22_location_name">@lang('petro::lang.all')</span></h5>
                                <input type="hidden" name="f22_location_name" id="lf_f22_location_name" value="All">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center pull-left">
                                <h5 style="font-weight: bold;" class="text-red">
                                    @lang('mpcs::lang.f22_form_no') : {{$last_form_no}}</h5>
                                    <input type="hidden" name="F22_from_no" id="lf_f22_form_no" value="{{$last_form_no}}">
                        </div>
                    </div>
                </div>
                <div class="row" style="margin-top: 20px;">
                    <div class="table-responsive">
                        {{-- IS2044: the inline width:100% was removed. With scrollX enabled it
                                 fights the layout DataTables computes and forces the
                                 12 columns back inside the container, which is what
                                 clipped them. --}}
                        <table class="table table-bordered table-striped" id="form_22_last_verified_table">
                            <thead>
                                <tr>
                                    <th>@lang('mpcs::lang.index_no')</th>
                                    <th>@lang('mpcs::lang.date')</th>
                                    <th>@lang('mpcs::lang.code')</th>
                                    <th>@lang('mpcs::lang.product')</th>
                                    <th>@lang('mpcs::lang.current_stock')</th>
                                    <th>@lang('mpcs::lang.stock_count')</th>
                                    <th>@lang('mpcs::lang.unit_purchase_price')</th>
                                    <th>@lang('mpcs::lang.total_purchase_price')</th>
                                    <th>@lang('mpcs::lang.unit_sale_price')</th>
                                    <th>@lang('mpcs::lang.total_sale_price')</th>
                                    <th>@lang('mpcs::lang.qty_difference')</th>
                               
                                </tr>
                            </thead>
                            <tfoot class="bg-gray">
                                <tr>
                                    <td class="text-red text-bold" colspan="7">@lang('mpcs::lang.total_this_page')</td>
                                    <td class="text-red text-bold text-right" id="lf_footer_total_purchase_price"></td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold text-right" id="lf_footer_total_sale_price"></td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr>
                                    <td class="text-red text-bold" colspan="7">@lang('mpcs::lang.total_previous_page')
                                    </td>
                                    <td class="text-red text-bold text-right" id="lf_pre_total_purchase_price"></td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold text-right" id="lf_pre_total_sale_price"></td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr>
                                    <td class="text-red text-bold" colspan="7">@lang('mpcs::lang.grand_total')</td>
                                    <td class="text-red text-bold text-right" id="lf_grand_total_purchase_price"></td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold text-right" id="lf_grand_total_sale_price"></td>
                                    <td>&nbsp;</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Pumps & Meters Section -->
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <h3 style="color:red;">Pumps & Meters</h3>
                        <div class="lf-pumps-container"></div>
                    </div>
                </div>
                
                <input type="hidden" name="purchase_price1" id="lf_purchase_price1" value="">
                <input type="hidden" name="sales_price1" id="lf_sales_price1" value="">
                <input type="hidden" name="purchase_price2" id="lf_purchase_price2" value="">
                <input type="hidden" name="sales_price2" id="lf_sales_price2" value="">
                <input type="hidden" name="purchase_price3" id="lf_purchase_price3" value="">
                <input type="hidden" name="sales_price3" id="lf_sales_price3" value="">
            </div>

            @endcomponent
        </div>
    </div>
    {!! Form::close() !!}
</section>
<!-- /.content -->

<style>
    .pump-section table {
        display: block;
        overflow-x: auto;
    }

    .lf-pumps-container {
        display: flex;
        gap: 10px;
        align-items: stretch;
    }

    .no-wrap {
        white-space: nowrap;
    }

    .column-50 {
        width: 25%;
    }

    .pump-section {
        flex: 1;
        border-right: 3px solid skyblue;
        padding-right: 10px;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    
    /* Print styles for pumps section */
    @media print {
        .lf-pumps-container {
            display: flex !important;
            page-break-inside: avoid;
        }
        
        .pump-section {
            page-break-inside: avoid;
        }
        
        .pump-section table {
            display: table !important;
        }
    }
</style>
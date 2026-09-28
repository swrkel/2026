<style>
    /*
     * IS2044: room for the 9 columns on this table.
     *
     * A minimum width so they are not squeezed, headings that wrap instead of
     * forcing each column to the width of its longest word, and a visible
     * scrollbar so it is clear more columns exist to the right.
     */
    #form_f22_list_table {
        width: auto !important;
        min-width: 1200px !important;
        max-width: none !important;
        table-layout: auto !important;
    }

    #form_f22_list_table th {
        white-space: normal;
        vertical-align: middle;
        font-size: 12px;
        padding: 6px 5px;
    }

    #form_f22_list_table td {
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
   
    <div class="row">
        <div class="col-md-12">
            {{-- @component('components.filters', ['title' => __('report.filters')])

            <div class="col-md-3" id="location_filter">
                <div class="form-group">
                    {!! Form::label('f22_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('f22_location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3" id="location_filter">
                <div class="form-group">
                    {!! Form::label('f22_product_id', __('mpcs::lang.product') . ':') !!}
                    {!! Form::select('f22_product_id', $products, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>


            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('type', __('mpcs::lang.form_no') . ':') !!}
                    {!! Form::text('F22_from_no', $F22_from_no, ['class' => 'form-control', 'readonly']) !!}
                </div>
            </div>


            @endcomponent --}}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
  
            <div class="col-md-12">
                <div class="row" style="margin-top: 20px;">
                    <div class="table-responsive">
                        <div id="f22_header_notice"></div>
                        {{-- IS2044: the inline width:100% was removed. With scrollX enabled it
                                 fights the layout DataTables computes and forces the
                                 9 columns back inside the container, which is what
                                 clipped them. --}}
                        <table class="table table-bordered table-striped" id="form_f22_list_table">
                            <thead>
                                <tr>
                                    <th>@lang('mpcs::lang.date_and_time')</th>
                                    <th>@lang('mpcs::lang.location')</th>
                                    <th>@lang('mpcs::lang.form_no')</th>
                                    <th>@lang('mpcs::lang.stock_adjustment_no')</th>
                                    <th>@lang('mpcs::lang.total_stock_loss_purchase')</th>                                  
                                    <th>@lang('mpcs::lang.total_stock_loss_sales')</th>                                    
                                    <th>@lang('mpcs::lang.user')</th>
                                    <th>@lang('mpcs::lang.action')</th>
                                </tr>
                            </thead>
                            
                        </table>
                    </div>
                </div>
              
            </div>

            @endcomponent
        </div>
    </div>
  
</section>
<!-- /.content -->
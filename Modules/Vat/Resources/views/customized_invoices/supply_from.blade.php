

<!-- Main content -->
<section class="content main-content-inner">
    
    @component('components.widget', ['class' => 'box-primary'])
    @slot('tool')
    
    <div class="box-tools pull-right">
        <a href="{{action('\Modules\Vat\Http\Controllers\VatSupplyFromController@create')}}" class="btn btn-primary">
            <i class="fa fa-plus"></i> @lang('messages.add')</a>
    </div>
    
    @endslot
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="supply_from_table" style="width: 100%">
            <thead>
                <tr>
                    <th>@lang('vat::lang.supply_from')</th>
                    <th>@lang('vat::lang.status')</th>
                    <th>@lang('vat::lang.created_by')</th>
                    <th>@lang('vat::lang.date_time')</th>
                    <th>@lang('lang_v1.action')</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->

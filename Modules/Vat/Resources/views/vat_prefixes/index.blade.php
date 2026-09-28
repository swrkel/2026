

<!-- Main content -->
<section class="content main-content-inner">
    
    @component('components.widget', ['class' => 'box-primary'])
    @slot('tool')
    
    <div class="box-tools pull-right">
        <a href="{{action('\Modules\Vat\Http\Controllers\VatPrefixController@create')}}" class="btn btn-primary vat-btn-modal add_fuel_tank"
            data-href="{{action('\Modules\Vat\Http\Controllers\VatPrefixController@create')}}"
            data-container=".fuel_tank_modal">
            <i class="fa fa-plus"></i> @lang('messages.add')</a>
    </div>
    
    @endslot
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="prefixes_table" style="width: 100%">
            <thead>
                <tr>
                    <th>@lang('vat::lang.prefix')</th>
                    <th>@lang('vat::lang.starting_no')</th>
                    <th>@lang('vat::lang.created_by')</th>
                    <th>@lang('lang_v1.action')</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->

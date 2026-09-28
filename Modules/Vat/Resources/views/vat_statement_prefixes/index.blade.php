

<!-- Main content -->
<section class="content">
    
    @component('components.widget', ['class' => 'box-primary'])
    @slot('tool')
    
    <div class="box-tools pull-right">
        <button type="button" data-href="{{action('\Modules\Vat\Http\Controllers\VatStatementPrefixController@create')}}" data-container=".fuel_tank_modal" class="btn btn-primary vat-ajax-modal-trigger">
            <i class="fa fa-plus"></i> @lang('messages.add')</button>
    </div>
    
    @endslot
    <div class="vat-action-table-wrap">
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

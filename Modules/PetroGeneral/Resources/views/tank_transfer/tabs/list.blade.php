@component('components.widget', ['class' => 'box-primary'])
@slot('tool')
<div class="box-tools pull-right"><button type="button" class="btn btn-primary btn-modal" data-href="{{ action('\Modules\PetroGeneral\Http\Controllers\TankTransferController@create') }}" data-container=".pg_transfer_modal"><i class="fa fa-plus"></i> @lang('messages.add')</button></div>
@endslot
<div class="table-responsive"><table class="table table-bordered table-striped" id="pg_tank_transfers_table"><thead><tr><th>@lang('petrogeneral::lang.date')</th><th>@lang('petrogeneral::lang.location')</th><th>@lang('petrogeneral::lang.transfer_no')</th><th>@lang('petrogeneral::lang.from_tank')</th><th>@lang('petrogeneral::lang.from_qty')</th><th>@lang('petrogeneral::lang.to_tank')</th><th>@lang('petrogeneral::lang.to_qty')</th><th>@lang('petrogeneral::lang.product')</th><th>@lang('petrogeneral::lang.transfer_qty')</th><th>@lang('petrogeneral::lang.user_added')</th></tr></thead></table></div>
@endcomponent
<div class="modal fade pg_transfer_modal" role="dialog"></div>

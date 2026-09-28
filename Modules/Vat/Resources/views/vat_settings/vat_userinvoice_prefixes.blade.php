

<!-- Main content -->
<section class="content main-content-inner">
    
    @component('components.widget', ['class' => 'box-primary'])
    @slot('tool')
    
    <div class="box-tools pull-right">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#vat_user_prefix_add_modal">
            <i class="fa fa-plus"></i> @lang('messages.add')</button>
    </div>
    
    @endslot
    <div class="table-responsive vat-user-prefix-table-wrap">
        <table class="table table-bordered table-striped" id="userinvoice_prefixes_table" style="width: 100%">
            <thead>
                <tr>
                    <th>@lang('vat::lang.date_time')</th>
                    <th>@lang('vat::lang.location')</th>
                    <th>@lang('vat::lang.user')</th>
                    <th>@lang('vat::lang.vat_invoice_prefix')</th>
                    <th>@lang('vat::lang.vat_invoice2_prefix')</th>
                    <th>@lang('vat::lang.created_by')</th>
                    <th>@lang('lang_v1.action')</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

</section>
<!-- /.content -->

{{-- S664: un-clips the Action menu so Edit and Delete are visible. --}}
@include('vat::partials.vat_prefix_action_menu')

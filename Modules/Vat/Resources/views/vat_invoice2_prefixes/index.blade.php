<!-- Main content -->
<section class="content main-content-inner">
    <style>
        .vat-action-dropdown-fix .dropdown-menu {
            z-index: 99999 !important;
        }

        .vat-action-dropdown-fix.table-responsive,
        .vat-action-dropdown-fix .dataTables_wrapper {
            overflow: visible !important;
        }
    </style>

    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools pull-right">
                <button type="button"
                        id="add_vat_invoice2_prefix"
                        data-href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatInvoice2PrefixController@create') }}"
                        class="btn btn-primary vat-invoice2-prefix-modal-trigger"
                        aria-controls="vatInvoice2PrefixModal">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </button>
            </div>
        @endslot

        <div class="table-responsive vat-action-dropdown-fix">
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

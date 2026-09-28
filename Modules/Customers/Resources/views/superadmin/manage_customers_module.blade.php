{{--
    CUS319 - Customers Module Super Admin Manage partial

    Include this partial from Super Admin -> All Business -> Manage when rendering
    the Customers Module section. It uses the Customers module registry so the
    Manage page and the System Customers Module menu remain synchronized.
--}}
@php
    $customersInputs = \Modules\Customers\Services\CustomerModulePageRegistry::inputs($manage_module_enable ?? []);
@endphp

<div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
    <div class="card-header text-center">
        <h4>@lang('customers::lang.customers_module')</h4>
        <hr>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-sm-3">
                <div class="checkbox">
                    <label class="flex-label search_label">
                        {!! Form::hidden('customers_module', 0) !!}
                        {!! Form::checkbox('customers_module', 1, !empty($customersInputs['customers_module']['checked']), ['class' => 'input-icheck-red ch_select', 'id' => 'customers_module']) !!}
                        @lang('customers::lang.customers_module')
                    </label>
                </div>
            </div>
        </div>

        <div class="row" style="margin-top: 10px;">
            <div class="col-sm-12">
                <label>@lang('customers::lang.customers_module_individual_pages')</label>
                <div style="margin-bottom: 10px;" class="row">
                    <div class="col-sm-4" style="margin-bottom:8px;">
                        <input type="text" class="form-control input-sm" id="customers_module_permission_search" placeholder="Search Customers permissions/pages..." autocomplete="off">
                    </div>
                    <div class="col-sm-8">
                    <button type="button" class="btn btn-primary btn-sm" id="customers_module_select_all">
                        <i class="fa fa-check-square-o"></i> @lang('customers::lang.select_all')
                    </button>
                    <button type="button" class="btn btn-default btn-sm" id="customers_module_deselect_all">
                        <i class="fa fa-square-o"></i> @lang('customers::lang.deselect_all')
                    </button>
                    </div>
                </div>
            </div>

            @foreach($customersInputs as $key => $input)
                @continue(!empty($input['root']))
                <div class="col-sm-3 customers-module-permission-row" data-customers-permission-text="{{ strtolower($input['label'].' '.$key) }}">
                    <div class="checkbox">
                        <label class="flex-label search_label">
                            {!! Form::hidden($key, 0) !!}
                            {!! Form::checkbox($key, 1, !empty($input['checked']), ['class' => 'input-icheck-red ch_select customers-module-page-checkbox', 'id' => $key]) !!}
                            {{ $input['label'] }}
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@push('javascript')
<script>
(function ($) {
    function customersSetChecked($boxes, checked) {
        $boxes.prop('checked', checked);
        if ($.fn.iCheck) {
            $boxes.iCheck(checked ? 'check' : 'uncheck');
        }
    }

    $(document).on('click', '#customers_module_select_all', function () {
        customersSetChecked($('.customers-module-page-checkbox'), true);
        customersSetChecked($('#customers_module'), true);
    });

    $(document).on('click', '#customers_module_deselect_all', function () {
        customersSetChecked($('.customers-module-page-checkbox'), false);
    });

    $(document).on('input keyup change', '#customers_module_permission_search', function () {
        var term = String($(this).val() || '').toLowerCase().trim();
        $('.customers-module-permission-row').each(function () {
            var haystack = String($(this).data('customers-permission-text') || '').toLowerCase();
            $(this).toggle(term === '' || haystack.indexOf(term) !== -1);
        });
    });

    $(document).on('ifChecked change', '.customers-module-page-checkbox', function () {
        if ($('.customers-module-page-checkbox:checked').length > 0) {
            customersSetChecked($('#customers_module'), true);
        }
    });
})(jQuery);
</script>
@endpush

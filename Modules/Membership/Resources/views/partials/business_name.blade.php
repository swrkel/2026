<div class="box box-solid membership-business-name-box">
    <div class="box-header">
        <h3 class="box-title">{{ __('membership::lang.Business_name') }}</h3>
        <div class="box-tools pull-right">
            @can('add_membership_settings')
                <button type="button"
                        class="btn btn-primary membership-business-name-ajax"
                        id="open_add_business_name_modal"
                        data-href="{{ url('/membership/setting/business-names/create') }}"
                        data-container=".business_name_modal">
                    <i class="fa fa-plus"></i> {{ __('messages.add') }}
                </button>
            @endcan
        </div>
    </div>

    <div class="box-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="business_names_table" style="width: 100%">
                <thead>
                    <tr>
                        <th>@lang('messages.action')</th>
                        <th>@lang('membership::lang.business_type')</th>
                        <th>@lang('membership::lang.Business_name')</th>
                        <th>@lang('membership::lang.added_by')</th>
                        <th>@lang('membership::lang.date_time')</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade business_name_modal" tabindex="-1" role="dialog" aria-hidden="true"></div>

<script>
    window.membershipBusinessNameConfig = {
        listUrl: @json(url('/membership/setting/business-name/data')),
        createUrl: @json(url('/membership/setting/business-names/create')),
        deleteUrlBase: @json(url('/membership/setting/business-names')),
        csrfToken: @json(csrf_token()),
        langSomethingWrong: @json(__('messages.something_went_wrong')),
        langRemove: @json(__('messages.remove')),
        langBusinessName: @json(__('membership::lang.Business_name')),
        langEnterBusinessName: @json(__('membership::lang.enter_business_name')),
        selectors: {
            table: '#business_names_table',
            addButton: '#open_add_business_name_modal',
            ajaxModal: '.business_name_modal',
            addForm: 'form#add_business_name_form',
            editForm: 'form#edit_business_name_form'
        }
    };
</script>
{{-- IS1618: handled by public/js/membership/membership_settings_is1618.js --}}

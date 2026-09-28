<div class="table-responsive">
    <table class="table table-bordered table-striped" id="history_table">
        <thead>
            <tr>
                <th>@lang('lang_v1.date_and_time')</th>
                <th>@lang('vat::lang.original_option_status')</th>
                <th>@lang('vat::lang.changed_to_option')</th>
                <th>@lang('vat::lang.changed_by')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($history as $h)
            <tr>
                <td>{{ @format_datetime($h->created_at) }}</td>
                <td>
                    <span class="label {{ $h->original_status == 'Active' ? 'label-success' : 'label-danger' }}">
                        {{ $h->original_status == 'Active' ? __('vat::lang.active') : __('vat::lang.inactive') }}
                    </span>
                </td>
                <td>
                    <span class="label {{ $h->changed_status == 'Active' ? 'label-success' : 'label-danger' }}">
                        {{ $h->changed_status == 'Active' ? __('vat::lang.active') : __('vat::lang.inactive') }}
                    </span>
                </td>
                <td>{{ $h->changed_by_user->user_full_name }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

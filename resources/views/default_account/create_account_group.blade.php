<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('DefaultAccountGroupController@store'), 'method' => 'post', 'id' => 'account_group_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('lang_v1.add_account_group')</h4>
        </div>
        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('account_group_name_group', __('lang_v1.name') . ':*') !!}
                {!! Form::text('name', null, ['class' => 'form-control', 'required', 'id' => 'account_group_name_group', 'placeholder' => __('lang_v1.name')]) !!}
            </div>

            <div class="form-group">
                {!! Form::label('account_type_id_group', __('account.account_type') . ':*') !!}
                <select name="account_type_id" id="account_type_id_group" class="form-control select2" required style="width: 100%;">
                    <option value="">@lang('messages.please_select')</option>
                    @foreach($account_types as $account_type)
                        <optgroup label="{{ $account_type->name }}">
                            <option value="{{ $account_type->id }}">{{ $account_type->name }}</option>
                            @foreach($account_type->sub_types as $sub_type)
                                <option value="{{ $sub_type->id }}">{{ $sub_type->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                {!! Form::label('note_group', __('lang_v1.note') . ':') !!}
                {!! Form::textarea('note', null, ['class' => 'form-control', 'id' => 'note_group', 'rows' => 3]) !!}
            </div>

            <div class="form-group">
                {!! Form::label('show_status', __('lang_v1.show_in_financial_report') . ':') !!}
                {!! Form::select('show_status', [0 => __('messages.no'), 1 => __('messages.yes')], 0, ['class' => 'form-control select2', 'id' => 'show_status', 'style' => 'width:100%;']) !!}
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" id="save_account_group_btn">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        // IS1541 root fix: Select2 must be attached to the actual modal that
        // loaded this form. Some pages use .default_account_model, while other
        // Super Admin pages use different modal classes. A wrong dropdownParent
        // makes the Account Type dropdown appear but prevents selecting values.
        var $modal = $('#account_group_form').closest('.modal');
        if (!$modal.length) {
            $modal = $('#account_group_form').closest('.modal-dialog').parent();
        }
        if (!$modal.length) {
            $modal = $(document.body);
        }

        $('#account_type_id_group, #show_status').each(function () {
            var $select = $(this);
            if ($select.data('select2')) {
                $select.select2('destroy');
            }
            $select.select2({
                dropdownParent: $modal,
                width: '100%',
                placeholder: LANG && LANG.please_select ? LANG.please_select : 'Please Select',
                allowClear: true
            });
        });
    });
</script>

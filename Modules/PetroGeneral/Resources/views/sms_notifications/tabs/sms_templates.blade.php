<div class="tab-pane {{ $active_tab === 'sms_templates' ? 'active' : '' }}" id="pg_sms_templates">
    {!! Form::open([
        'url' => action('\\Modules\\PetroGeneral\\Http\\Controllers\\PetroNotificationTemplateController@store'),
        'method' => 'post'
    ]) !!}

    <div class="row no-print">
        <div class="col-md-12">
            @include('petrogeneral::notification_template.partials.sms', [
                'templates' => $sms_notifications,
                'idPrefix' => 'pg_sms_'
            ])
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-danger btn-big">@lang('messages.save')</button>
        </div>
    </div>

    {!! Form::close() !!}
</div>

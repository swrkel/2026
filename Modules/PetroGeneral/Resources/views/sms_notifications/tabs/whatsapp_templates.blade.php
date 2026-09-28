<div class="tab-pane {{ $active_tab === 'whatsapp_templates' ? 'active' : '' }}" id="pg_whatsapp_templates">
    {!! Form::open([
        'url' => action('\\Modules\\PetroGeneral\\Http\\Controllers\\PetroWhatsAppTemplateController@store'),
        'method' => 'post'
    ]) !!}

    <div class="row no-print">
        <div class="col-md-12">
            @include('petrogeneral::whatsapp_template.partials.sms', [
                'templates' => $whatsapp_notifications,
                'idPrefix' => 'pg_whatsapp_'
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

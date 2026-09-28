<ul class="nav nav-tabs" role="tablist">
    <li class="{{ $active_tab === 'sms_templates' ? 'active' : '' }}">
        <a href="#pg_sms_templates" data-toggle="tab" aria-expanded="{{ $active_tab === 'sms_templates' ? 'true' : 'false' }}">
            @lang('petrogeneral::lang.sms_templates')
        </a>
    </li>
    <li class="{{ $active_tab === 'whatsapp_templates' ? 'active' : '' }}">
        <a href="#pg_whatsapp_templates" data-toggle="tab" aria-expanded="{{ $active_tab === 'whatsapp_templates' ? 'true' : 'false' }}">
            @lang('petrogeneral::lang.whatsapp_templates')
        </a>
    </li>
</ul>

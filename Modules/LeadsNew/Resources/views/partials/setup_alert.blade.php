@if(!empty($missingTables ?? []))
    <div class="ln-setup-alert">
        <div class="ln-setup-icon"><i class="fa fa-database"></i></div>
        <div>
            <strong>{{ __('leadsnew::messages.database_setup_pending_title') }}</strong><br>
            <span>{{ __('leadsnew::messages.database_setup_pending_clean') }}</span>
        </div>
    </div>
@endif
<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => url('/superadmin/business/' . $business->id . '/save-sidebar-modules'), 'method' => 'post', 'id' => 'manage_sidebar_modules_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Manage Side Bar - {{ $business->name }}</h4>
        </div>
        <div class="modal-body">
            <div class="alert alert-info" style="font-weight:600;">
                Select the globally available modules or main sections allowed for this business. Only checked items will appear in its sidebar, and unchecked items are also blocked from direct URL access. An installed module is not offered here until its exact module status is explicitly enabled globally; missing/disabled modules are never auto-enabled. The normal Manage page remains the child page/tab permission control.
            </div>
            <div class="row" style="margin-bottom:15px;">
                <div class="col-md-8">
                    <input type="text" class="form-control" id="sidebar_module_filter" placeholder="Search modules...">
                </div>
                <div class="col-md-4 text-right">
                    <button type="button" class="btn btn-default" id="sidebar_select_all">Select All</button>
                    <button type="button" class="btn btn-default" id="sidebar_clear_all">Clear All</button>
                </div>
            </div>
            <div class="row" id="sidebar_modules_grid">
                @foreach($sidebarModules as $moduleKey => $moduleName)
                    @php
                        // Backward-compatible during rolling deployments: an old
                        // controller may not yet provide sidebarModuleStates.
                        $moduleIsEnabled = isset($sidebarModuleStates) && array_key_exists($moduleKey, $sidebarModuleStates)
                            ? !empty($sidebarModuleStates[$moduleKey])
                            : in_array($moduleKey, $enabledModules ?? [], true);
                    @endphp
                    <div class="col-md-4 sidebar-module-item" data-search="{{ strtolower((string) $moduleName . ' ' . (string) $moduleKey) }}">
                        <label class="well well-sm" style="display:block; cursor:pointer; min-height:52px;">
                            <input type="checkbox" name="enabled_modules[]" value="{{ $moduleKey }}" {{ $moduleIsEnabled ? 'checked' : '' }}>
                            <strong style="margin-left:8px;">{{ $moduleName }}</strong>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
<script>
(function($){
    $('#sidebar_module_filter').off('input.sidebarfilter').on('input.sidebarfilter', function(){
        var term = String($(this).val() || '').toLowerCase();
        $('#sidebar_modules_grid .sidebar-module-item').each(function(){
            $(this).toggle(String($(this).data('search') || '').indexOf(term) !== -1);
        });
    });
    $('#sidebar_select_all').off('click.sidebarselect').on('click.sidebarselect', function(){
        $('#sidebar_modules_grid .sidebar-module-item:visible input[type="checkbox"]').prop('checked', true);
    });
    $('#sidebar_clear_all').off('click.sidebarclear').on('click.sidebarclear', function(){
        $('#sidebar_modules_grid .sidebar-module-item:visible input[type="checkbox"]').prop('checked', false);
    });
    $('#manage_sidebar_modules_form').off('submit.sidebarsave').on('submit.sidebarsave', function(e){
        e.preventDefault();
        var form = $(this);
        var btn = form.find('button[type="submit"]');
        btn.prop('disabled', true).text('Saving...');
        $.ajax({
            method: 'POST',
            url: form.attr('action'),
            data: form.serialize(),
            success: function(result){
                $('.view_modal').modal('hide');
                if (typeof toastr !== 'undefined') {
                    toastr.success((result && result.msg) ? result.msg : 'Manage Side Bar saved successfully.');
                }
            },
            error: function(xhr){
                var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                var message = response && (response.msg || response.message)
                    ? (response.msg || response.message)
                    : 'Not saved. Please check the log.';
                if (typeof toastr !== 'undefined') { toastr.error(message); }
                else { alert(message); }
            },
            complete: function(){ btn.prop('disabled', false).text('Save'); }
        });
    });
})(jQuery);
</script>

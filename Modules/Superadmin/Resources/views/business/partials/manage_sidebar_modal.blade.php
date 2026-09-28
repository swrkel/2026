<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('\Modules\Superadmin\Http\Controllers\BusinessController@saveSidebarModules', [$business->id]), 'method' => 'post', 'id' => 'manage_sidebar_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Manage Side Bar - {{ $business->name }}</h4>
        </div>
        <div class="modal-body">
            <div class="alert alert-info" style="margin-bottom: 15px;">
                Select the modules or main sections allowed for this business. Only checked items will appear in its sidebar, and unchecked items are also blocked from direct URL access. The normal Manage page remains the child page/tab permission control.
            </div>
            <div class="row" style="margin-bottom: 12px;">
                <div class="col-md-8">
                    <input type="text" class="form-control" id="sidebar_module_search" placeholder="Search modules...">
                </div>
                <div class="col-md-4 text-right">
                    <button type="button" class="btn btn-default btn-sm" id="sidebar_select_all">Select All</button>
                    <button type="button" class="btn btn-default btn-sm" id="sidebar_clear_all">Clear All</button>
                </div>
            </div>
            <div class="row">
                @foreach($modules as $module)
                    <div class="col-md-4 sidebar-module-row" data-label="{{ strtolower($module['label']) }} {{ strtolower($module['key']) }}" style="margin-bottom: 8px;">
                        <label style="font-weight: normal; display: block; border: 1px solid #ddd; padding: 8px; border-radius: 4px; min-height: 42px;">
                            {!! Form::checkbox('enabled_modules[]', $module['key'], $module['enabled'], ['class' => 'input-icheck sidebar-module-checkbox']) !!}
                            <span style="margin-left: 6px;">{{ $module['label'] }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>
<script>
(function(){
    if ($.fn.iCheck) {
        $('.sidebar-module-checkbox').iCheck({checkboxClass: 'icheckbox_square-blue'});
    }
    $('#sidebar_select_all').on('click', function(){
        if ($.fn.iCheck) { $('.sidebar-module-checkbox').iCheck('check'); } else { $('.sidebar-module-checkbox').prop('checked', true); }
    });
    $('#sidebar_clear_all').on('click', function(){
        if ($.fn.iCheck) { $('.sidebar-module-checkbox').iCheck('uncheck'); } else { $('.sidebar-module-checkbox').prop('checked', false); }
    });
    $('#sidebar_module_search').on('keyup', function(){
        var value = ($(this).val() || '').toLowerCase();
        $('.sidebar-module-row').each(function(){
            $(this).toggle($(this).data('label').indexOf(value) !== -1);
        });
    });
})();
</script>

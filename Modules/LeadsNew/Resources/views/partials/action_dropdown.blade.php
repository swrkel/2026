<div class="btn-group">
    <button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        @lang('messages.action') <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right" role="menu">
        @can('leads_new.view')<li><a href="{{ $view_url ?? '#' }}">@lang('messages.view')</a></li>@endcan
        @can('leads_new.update')<li><a href="{{ $edit_url ?? '#' }}">@lang('messages.edit')</a></li>@endcan
        @can('leads_new.create')<li><a href="{{ $duplicate_url ?? '#' }}">@lang('leadsnew::messages.duplicate')</a></li>@endcan
        @can('leads_new.delete')<li><a href="{{ $delete_url ?? '#' }}" class="text-danger leads-new-delete">@lang('messages.delete')</a></li>@endcan
    </ul>
</div>

{{-- Row actions. Editing only while the shift is open. --}}
<div class="btn-group">
    <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown">
        @lang('messages.actions') <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-left" role="menu">
        @if ((int) $row->shift_status === 0)
            <li>
                <a href="{{ route('sw.daily-cards.edit', $row->id) }}" class="btn-modal"
                    data-container=".sw_daily_cards_modal">
                    <i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')
                </a>
            </li>
            <li>
                <a href="{{ route('sw.daily-cards.destroy', $row->id) }}"
                    onclick="return confirm('{{ __('sw::lang.confirm_delete_entry') }}')">
                    <i class="fa fa-trash"></i> @lang('messages.delete')
                </a>
            </li>
        @else
            <li class="disabled">
                <a href="#"><i class="fa fa-lock"></i> @lang('sw::lang.shift_closed')</a>
            </li>
        @endif
    </ul>
</div>

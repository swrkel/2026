{{-- Row actions.

     Editing is refused once anything has been recovered or paid: the ledger
     entries from that recovery are already posted, and changing the amount
     afterwards would leave the record describing a figure that no longer
     exists. --}}
<div class="btn-group">
    <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown">
        @lang('messages.actions') <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-left" role="menu">
        @if ((float) ($row->settled_amount ?? 0) > 0)
            <li class="disabled">
                <a href="#"><i class="fa fa-lock"></i> @lang('sw::lang.partly_settled')</a>
            </li>
        @else
            <li>
                <a href="{{ route('sw.shortage-excess.edit', $row->id) }}" class="btn-modal"
                    data-container=".sw_shortage_excess_modal">
                    <i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')
                </a>
            </li>
            <li>
                <a href="{{ route('sw.shortage-excess.destroy', $row->id) }}"
                    onclick="return confirm('{{ __('sw::lang.confirm_delete_entry') }}')">
                    <i class="fa fa-trash"></i> @lang('messages.delete')
                </a>
            </li>
        @endif
    </ul>
</div>

{{-- Row actions for a shift. What is offered depends on its status. --}}
<div class="btn-group">
    <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown">
        @lang('messages.actions') <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-left" role="menu">

        <li>
            <a href="{{ route('sw.shifts.assignment', $row->id) }}" class="btn-modal"
                data-container=".sw_shift_modal">
                <i class="fa fa-users"></i> @lang('sw::lang.assign_operators')
            </a>
        </li>

        @if ((int) ($row->effective_status ?? \Modules\SW\Entities\Shift::normalizeStatusValue($row->status, $row->closed_at ?? null)) === \Modules\SW\Entities\Shift::STATUS_OPEN)
            <li>
                <a href="{{ route('sw.shifts.edit', $row->id) }}" class="btn-modal"
                    data-container=".sw_shift_modal">
                    <i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')
                </a>
            </li>
            <li class="divider"></li>
            <li>
                <a href="{{ route('sw.shifts.close', $row->id) }}"
                    onclick="event.preventDefault(); if (confirm('{{ __('sw::lang.confirm_close_shift') }}')) { document.getElementById('sw-close-{{ $row->id }}').submit(); }">
                    <i class="fa fa-lock"></i> @lang('sw::lang.close_shift')
                </a>
                <form id="sw-close-{{ $row->id }}" action="{{ route('sw.shifts.close', $row->id) }}"
                    method="POST" style="display:none">
                    @csrf
                </form>
            </li>
        @elseif ((int) ($row->effective_status ?? \Modules\SW\Entities\Shift::normalizeStatusValue($row->status, $row->closed_at ?? null)) === \Modules\SW\Entities\Shift::STATUS_CLOSED)
            <li>
                <a href="{{ route('sw.shifts.reopen.form', $row->id) }}" class="btn-modal"
                    data-container=".sw_shift_modal">
                    <i class="fa fa-unlock"></i> @lang('sw::lang.reopen_shift')
                </a>
            </li>
        @else
            <li class="disabled">
                <a href="#"><i class="fa fa-check"></i> @lang('sw::lang.settled_status')</a>
            </li>
        @endif

    </ul>
</div>

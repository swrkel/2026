<div class="btn-group sw-shift-actions">
    <button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        @lang('messages.action') <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-left sw-shift-action-menu" role="menu">
        <li>
            <a href="{{ route('sw.list-shifts.show', $shiftId) }}">
                <i class="fa fa-eye"></i> View
            </a>
        </li>
        <li>
            <a href="{{ route('sw.list-shifts.print', $shiftId) }}" target="_blank">
                <i class="fa fa-print"></i> Print
            </a>
        </li>
    </ul>
</div>

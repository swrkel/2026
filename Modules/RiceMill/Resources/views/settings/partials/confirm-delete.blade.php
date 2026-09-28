@if(!empty($disabledReason))
    <button type="button" class="rcm-action-link is-disabled" title="{{ $disabledReason }}" disabled><i class="fa fa-trash"></i> Delete</button>
@else
    <form method="post" action="{{ $deleteRoute }}" onsubmit="return confirm('Delete this setting? This action cannot be undone.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="rcm-action-link danger"><i class="fa fa-trash"></i> Delete</button>
    </form>
@endif

<div class="cheq-actions">
    <div class="cheq-action-menu">
        <button type="button" class="cheq-action-toggle"><i class="fa fa-cog"></i> Action <i class="fa fa-angle-down"></i></button>
        <div class="cheq-action-items">
            @isset($edit)
                <a class="cheq-action-edit" href="{{ $edit }}"><i class="fa fa-edit"></i> Edit</a>
            @endisset
            @isset($print)
                <a class="cheq-action-print" href="{{ $print }}" target="_blank"><i class="fa fa-print"></i> Print</a>
            @endisset
            @isset($delete)
                <form action="{{ $delete }}" method="post" onsubmit="return confirm('Delete this record?')">
                    @csrf
                    @method('DELETE')
                    <button class="cheq-action-delete" type="submit"><i class="fa fa-times"></i> Delete</button>
                </form>
            @endisset
            @if(!isset($edit) && !isset($print) && !isset($delete))
                <button type="button" class="cheq-action-default"><i class="fa fa-info-circle"></i> No Actions</button>
            @endif
        </div>
    </div>
</div>

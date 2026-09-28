<div class="dropdown">
    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="actionMenu{{ $row->id }}" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa fa-ellipsis-v"></i>
    </button>
    <ul class="dropdown-menu" aria-labelledby="actionMenu{{ $row->id }}">
        <li><a class="dropdown-item" href="{{ route('distribution.loadings.show', $row->id) }}">View</a></li>
        <li><a class="dropdown-item" href="{{ route('distribution.loadings.edit', $row->id) }}">Edit</a></li>
        <li><a class="dropdown-item" href="{{ route('distribution.loadings.print', $row->id) }}" target="_blank">Print</a></li>
    </ul>
</div>

<div class="btn-group expnew-action-menu">
    <button type="button"
            class="btn btn-primary btn-sm dropdown-toggle expnew-action-button"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false">
        Action <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right">
        <li><a href="{{ route('expensesnew.categories.edit', $c->id) }}"><i class="fa fa-pencil"></i> Edit</a></li>
        <li><a href="{{ route('expensesnew.categories.show', $c->id) }}"><i class="fa fa-eye"></i> View</a></li>
        <li class="divider"></li>
        <li>
            <a href="#"
               class="expnew-delete expnew-delete-link"
               data-url="{{ route('expensesnew.categories.destroy', $c->id) }}">
                <i class="fa fa-trash"></i> Delete
            </a>
        </li>
    </ul>
</div>

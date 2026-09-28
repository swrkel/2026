<div class="btn-group">
    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">Action <span class="caret"></span></button>
    <ul class="dropdown-menu dropdown-menu-right" role="menu">
        <li><a href="{{ route('beautysaloons.customers.show', $row->id) }}"><i class="fa fa-eye"></i> View</a></li>
        <li><a href="{{ route('beautysaloons.customers.edit', $row->id) }}"><i class="fa fa-edit"></i> Edit</a></li>
    </ul>
</div>

{{--
    Task 8046 - the Action column on the List Customer Reference page.

    Spec: View, an Active/Inactive toggle, and QR Action. Edit and Delete are
    included alongside them because the spec asks for the entered details to
    stay editable and deletable after they are saved.

    Both toggle and delete are rendered as buttons driving AJAX rather than
    links, so neither can be triggered by a crawler or a prefetch, and both are
    hidden outright when the user lacks the permission.
--}}
<div class="btn-group">
    <button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        @lang('customers::lang.actions') <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-left" role="menu">

        <li>
            <a href="#" class="cus-ref-view" data-id="{{ $row->id }}">
                <i class="fa fa-eye"></i> @lang('messages.view')
            </a>
        </li>

        <li>
            <a href="#" class="cus-ref-qr" data-id="{{ $row->id }}">
                <i class="fa fa-qrcode"></i> QR Action
            </a>
        </li>

        @if($canEdit)
            <li class="divider"></li>

            <li>
                <a href="#" class="cus-ref-toggle-status"
                   data-id="{{ $row->id }}"
                   data-active="{{ $row->is_active ? 1 : 0 }}">
                    @if($row->is_active)
                        <i class="fa fa-toggle-on text-green"></i> Set Inactive
                    @else
                        <i class="fa fa-toggle-off text-muted"></i> Set Active
                    @endif
                </a>
            </li>

            <li>
                <a href="#" class="cus-ref-edit" data-id="{{ $row->id }}">
                    <i class="fa fa-edit"></i> @lang('messages.edit')
                </a>
            </li>
        @endif

        @if($canDelete)
            <li>
                <a href="#" class="cus-ref-delete" data-id="{{ $row->id }}">
                    <i class="fa fa-trash"></i> @lang('messages.delete')
                </a>
            </li>
        @endif

    </ul>
</div>

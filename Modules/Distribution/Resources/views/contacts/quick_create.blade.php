{{--
    Distribution-owned quick customer/contact create include.
    DIST-309 centralises the old main-system contact.create dependency here so
    sales order and invoice pages no longer call main views directly.
    The existing ERP modal is still used as a compatibility fallback until the
    full Distribution customer form is separated in the customer/contact stage.
--}}
@if(View::exists('distribution::contacts.partials.create_modal'))
    @include('distribution::contacts.partials.create_modal', ['quick_add' => $quick_add ?? true])
@elseif(View::exists('contact.create'))
    @include('contact.create', ['quick_add' => $quick_add ?? true])
@endif

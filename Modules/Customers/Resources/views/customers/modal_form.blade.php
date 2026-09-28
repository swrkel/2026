{{--
    MA-002 (S-622): the customer form WITHOUT the application layout.

    The Edit and Add screens open in a popup, and the controller was returning
    customers.edit / customers.create - both of which extend the app layout.
    So the modal was being handed an ENTIRE PAGE - the whole document and all 71
    script tags from the layout, jQuery among them.

    Injecting that into the open page re-evaluates jQuery, which REPLACES the
    global $ and discards every plugin already registered against it. The
    console showed the consequence exactly:

        Uncaught TypeError: $(...).daterangepicker is not a function
            at S.globalEval  (jquery ajax)

    daterangepicker had been wiped. And because that error is thrown while the
    injected script is being evaluated, EVERY LINE AFTER IT IS ABANDONED -
    including the select2 initialisation. That is why the dropdowns were dead,
    and why no amount of changing the select2 code made any difference: the
    code was never reached.

    This view renders the form alone. No layout, no document wrapper, no scripts other
    than the form's own. The plugins already loaded on the customers page stay
    intact and do the work.
--}}
<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                {{ $customer ? __('customers::lang.edit_customer') : __('customers::lang.add_customer') }}
            </h4>
        </div>

        @if ($customer)
            {!! Form::model($customer, [
                'route' => ['customers.update', $customer->id],
                'method' => 'put',
                'id' => 'customers_module_edit_form',
                'files' => true,
                'novalidate' => 'novalidate',
            ]) !!}
        @else
            {!! Form::open([
                'route' => 'customers.store',
                'method' => 'post',
                'id' => 'customers_module_add_form',
                'files' => true,
                'novalidate' => 'novalidate',
            ]) !!}
        @endif

        <div class="modal-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Please fix the following errors:</strong>
                    <ul style="margin-bottom:0;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('customers::customers.partials.form', ['customer' => $customer])
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i>
                {{ $customer ? 'Update Customer' : 'Save Customer' }}
            </button>
        </div>

        {!! Form::close() !!}
    </div>
</div>

{{--
    Only the form's own scripts. The page underneath already has jQuery,
    select2, daterangepicker and the rest loaded and working.
--}}
@include('customers::customers.partials.form_scripts')

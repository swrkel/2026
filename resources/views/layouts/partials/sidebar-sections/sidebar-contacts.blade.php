{{-- Contacts Menu --}}

@if ($contact_module && ($contact_supplier || $contact_customer))
    @if (($user && $user->can('supplier.view')) || ($user && $user->can('customer.view')))

        <li class="nav-item {{ in_array($request->segment(1), ['contacts', 'customer-group', 'contact-group', 'List_product_bind', 'product_bind', 'customer-reference', 'customer-statement', 'outstanding-received-report']) ? 'active active-sub' : '' }}">

            <a class="nav-link collapsed"
               href="#"
               data-toggle="collapse"
               data-target="#contacts-menu"
               aria-expanded="true"
               aria-controls="contacts-menu">
                <i class="ti-id-badge"></i>
                <span>@lang('contact.contacts')</span>
            </a>

            <div id="contacts-menu"
                 class="collapse {{ in_array($request->segment(1), ['contacts', 'customer-group', 'contact-group', 'List_product_bind', 'product_bind', 'customer-reference', 'customer-statement', 'outstanding-received-report']) ? 'show' : '' }}"
                 aria-labelledby="headingPages"
                 data-parent="#accordionSidebar">

                <div class="bg-white py-2 collapse-inner rounded">

                    <h6 class="collapse-header">@lang('contact.contacts'):</h6>

                    @if ($contact_supplier)
                        @can('supplier.view')
                            <a class="collapse-item {{ $request->input('type') == 'supplier' ? 'active' : '' }}"
                               href="{{ action('ContactController@index', ['type' => 'supplier']) }}">
                                @lang('report.supplier')
                            </a>
                        @endcan
                    @endif

                    @if ($contact_customer)
                        @can('customer.view')
                            <a class="collapse-item {{ $request->input('type') == 'customer' ? 'active' : '' }}"
                               href="{{ action('ContactController@index', ['type' => 'customer']) }}">
                                @lang('report.customer')
                            </a>
                        @endcan
                    @endif

                    @if ($contact_settings)
                        <a class="collapse-item {{ $request->segment(1) == 'contacts' ? 'active' : '' }}"
                           href="{{ action('ContactController@settings') }}">
                            @lang('contact.settings')
                        </a>
                    @endif

                    @if ($contact_list_supplier_map_products)
                        <a class="collapse-item {{ $request->segment(1) == 'List_product_bind' ? 'active' : '' }}"
                           href="{{ action('SupplierMappingController@index') }}">
                            @lang('lang_v1.list_supplier_map_products')
                        </a>
                    @endif

                    @if ($contact_add_supplier_products)
                        <a class="collapse-item {{ $request->segment(1) == 'product_bind' ? 'active' : '' }}"
                           href="{{ action('SupplierMappingController@addMapping') }}">
                            @lang('lang_v1.add_supplier_map_products')
                        </a>
                    @endif

                    @if ($contact_group_customer)
                        <a class="collapse-item {{ $request->segment(1) == 'contact-group' ? 'active' : '' }}"
                           href="{{ action('ContactGroupController@index') }}">
                            @lang('lang_v1.contact_groups')
                        </a>
                    @endif

                    @if ($import_contact && !$property_module && $contact_customer)
                        @if (($user && $user->can('supplier.create')) || ($user && $user->can('customer.create')))
                            <a class="collapse-item {{ $request->segment(1) == 'contacts' && $request->segment(2) == 'import' ? 'active' : '' }}"
                               href="{{ action('ContactController@getImportContacts') }}">
                                @lang('lang_v1.import_contacts')
                            </a>
                        @endif
                    @endif

                    @if ($customer_reference)
                        <a class="collapse-item {{ $request->segment(1) == 'customer-reference' ? 'active' : '' }}"
                           href="{{ action('CustomerReferenceController@index') }}">
                            @lang('lang_v1.customer_reference')
                        </a>
                    @endif

                    @if ($contact_customer)

                        @if ($manual_bill)
                            <a class="collapse-item {{ $request->segment(1) == 'manual-bill' ? 'active' : '' }}"
                               href="{{ action([\App\Http\Controllers\ManualBillController::class, 'index']) }}">
                                Manual Bill
                            </a>
                        @endif

                        @if ($customer_statement)
                            <a class="collapse-item {{ $request->segment(1) == 'customer-statement' ? 'active' : '' }}"
                               href="{{ action('CustomerStatementController@index') }}">
                                @lang('contact.customer_statements')
                            </a>
                        @endif

                        @if ($customer_statement_pmt)
                            <a class="collapse-item {{ $request->segment(1) == 'customer-statement' ? 'active' : '' }}"
                               href="{{ url('customer-statement/get-statement-list-pmts') }}">
                                @lang('contact.customer_statements_with_payment')
                            </a>
                        @endif

                        @if ($customer_payment)
                            <a class="collapse-item {{ $request->segment(1) == 'customer-payment-simple' ? 'active' : '' }}"
                               href="{{ action('CustomerPaymentController@index') }}">
                                @lang('lang_v1.customer_payments')
                            </a>
                        @endif

                    @endif

                    @if ($outstanding_received)
                        @if ($user && $user->can('payment_received.view'))
                            <a class="collapse-item {{ $request->segment(1) == 'outstanding-received-report' ? 'active' : '' }}"
                               href="{{ url('/contacts/outstanding-received-report') }}">
                                @lang('lang_v1.outstanding_received')
                            </a>
                        @endif
                    @endif

                    @if ($contact_import_opening_balalnces)
                        @php
                            /*
                             * Customer opening-balance import is owned by the standalone
                             * Customers module. Do not resolve the removed legacy
                             * ContactController action because it causes the complete
                             * sidebar (and every page using it) to fail with HTTP 500.
                             */
                            $contactImportBalanceUrl = \Illuminate\Support\Facades\Route::has('customers.import_balance')
                                ? route('customers.import_balance')
                                : url('/customers/master-data/opening-balances');
                        @endphp
                        <a class="collapse-item {{ $request->is('customers/import-balance*') || $request->is('customers/master-data/opening-balances*') ? 'active' : '' }}"
                           href="{{ $contactImportBalanceUrl }}">
                            @lang('lang_v1.import_contacts_balance')
                        </a>
                    @endif

                    @if ($contact_supplier)
                        @if ($issue_payment_detail)
                            <a class="collapse-item {{ $request->segment(1) == 'issued-payment-details' ? 'active' : '' }}"
                               href="{{ action('ContactController@getIssuedPaymentDetails') }}">
                                @lang('lang_v1.issued_payment_details')
                            </a>
                        @endif
                    @endif

                    @if ($contact_returned_cheque_details)
                        <a class="collapse-item {{ $request->segment(1) == 'returned-cheque-details' ? 'active' : '' }}"
                           href="{{ action('ContactController@getReturnedCheques') }}">
                            @lang('sale.returned_cheques_details')
                        </a>
                    @endif

                    <a class="collapse-item {{ $request->segment(1) == 'contact-user-activity' ? 'active' : '' }}"
                       href="{{ action('CustomerStatementController@getUserActivityReport') }}">
                        @lang('lang_v1.contact_module_user_activity')
                    </a>

                </div>
            </div>
        </li>

    @endif
@endif
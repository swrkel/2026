@php
    $isEdit = isset($supplier) && !empty($supplier->id);
@endphp

<div class="supplier-form-wrapper" data-supplier-form>
    <ul class="nav nav-tabs supplier-form-tabs" role="tablist">
        <li class="active"><a href="#supplier-basic-tab" data-supplier-form-tab role="tab" aria-controls="supplier-basic-tab" aria-selected="true">@lang('suppliers::lang.basic_details')</a></li>
        <li><a href="#supplier-contact-tab" data-supplier-form-tab role="tab" aria-controls="supplier-contact-tab" aria-selected="false">@lang('suppliers::lang.contact_details')</a></li>
        <li><a href="#supplier-address-tab" data-supplier-form-tab role="tab" aria-controls="supplier-address-tab" aria-selected="false">@lang('suppliers::lang.address_details')</a></li>
        <li><a href="#supplier-finance-tab" data-supplier-form-tab role="tab" aria-controls="supplier-finance-tab" aria-selected="false">@lang('suppliers::lang.finance_details')</a></li>
        <li><a href="#supplier-custom-tab" data-supplier-form-tab role="tab" aria-controls="supplier-custom-tab" aria-selected="false">@lang('suppliers::lang.custom_fields')</a></li>
    </ul>

    <div class="tab-content supplier-form-tab-content">
        <div class="tab-pane active" id="supplier-basic-tab">
            @include('suppliers::suppliers.form-tabs.basic')
        </div>
        <div class="tab-pane" id="supplier-contact-tab">
            @include('suppliers::suppliers.form-tabs.contact')
        </div>
        <div class="tab-pane" id="supplier-address-tab">
            @include('suppliers::suppliers.form-tabs.address')
        </div>
        <div class="tab-pane" id="supplier-finance-tab">
            @include('suppliers::suppliers.form-tabs.finance')
        </div>
        <div class="tab-pane" id="supplier-custom-tab">
            @include('suppliers::suppliers.form-tabs.custom')
        </div>
    </div>

    @include('suppliers::suppliers.form-tabs.actions')
</div>

@once
    <script>
            (function () {
                'use strict';

                function activateSupplierFormTab(wrapper, link) {
                    var selector = link.getAttribute('href');

                    if (!selector || selector.charAt(0) !== '#') {
                        return;
                    }

                    var target = wrapper.querySelector(selector);

                    if (!target) {
                        return;
                    }

                    wrapper.querySelectorAll('.supplier-form-tabs > li').forEach(function (item) {
                        item.classList.remove('active');
                    });

                    wrapper.querySelectorAll('.supplier-form-tabs [data-supplier-form-tab]').forEach(function (tabLink) {
                        tabLink.setAttribute('aria-selected', 'false');
                        tabLink.setAttribute('aria-expanded', 'false');
                    });

                    wrapper.querySelectorAll('.supplier-form-tab-content > .tab-pane').forEach(function (pane) {
                        pane.classList.remove('active', 'in');
                        pane.style.display = 'none';
                        pane.setAttribute('aria-hidden', 'true');
                    });

                    var listItem = link.closest('li');
                    if (listItem) {
                        listItem.classList.add('active');
                    }

                    link.setAttribute('aria-selected', 'true');
                    link.setAttribute('aria-expanded', 'true');
                    target.classList.add('active', 'in');
                    target.style.display = 'block';
                    target.setAttribute('aria-hidden', 'false');
                }

                function initialiseSupplierFormTabs() {
                    document.querySelectorAll('[data-supplier-form]').forEach(function (wrapper) {
                        if (wrapper.getAttribute('data-supplier-tabs-ready') === '1') {
                            return;
                        }

                        wrapper.setAttribute('data-supplier-tabs-ready', '1');

                        wrapper.querySelectorAll('.supplier-form-tabs [data-supplier-form-tab]').forEach(function (link) {
                            // Capture phase keeps the form tabs independent from any
                            // global tab/colour engines installed by the host layout.
                            link.addEventListener('click', function (event) {
                                event.preventDefault();
                                event.stopImmediatePropagation();
                                activateSupplierFormTab(wrapper, link);
                            }, true);
                        });

                        var activeLink = wrapper.querySelector('.supplier-form-tabs > li.active [data-supplier-form-tab]')
                            || wrapper.querySelector('.supplier-form-tabs [data-supplier-form-tab]');

                        if (activeLink) {
                            activateSupplierFormTab(wrapper, activeLink);
                        }
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initialiseSupplierFormTabs);
                } else {
                    initialiseSupplierFormTabs();
                }
            })();
    </script>
@endonce

{{--
|--------------------------------------------------------------------------
| Loan Sidebar Wrapper
|--------------------------------------------------------------------------
| Keeps Loan navigation inside Modules/Loan only.
| This wrapper must not create duplicate Loan ERP links.
| It only loads the Loan module sidebar partial.
|--------------------------------------------------------------------------
--}}

@if (!empty($loan_module))
    @can('loan_module.access')
        @includeIf('loan::layouts.partials.sidebar')
    @endcan
@endif

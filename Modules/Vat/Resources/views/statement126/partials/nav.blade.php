 <div class="row">
     <div class="col-md-12">
         <div class="settlement_tabs">
             <ul class="nav nav-tabs">
                 <li class="@if (request()->tab == 'add') active @endif">
                     <a href="{{ action('\Modules\Vat\Http\Controllers\VatStatement126Controller@create') }}?tab=add">
                         <i class="fa fa-file-text-o"></i> <strong>@lang('vat::lang.add_126_statement')</strong>
                     </a>
                 </li>

                 <li class="@if (request()->tab == 'list') active @endif">
                     <a href="{{ action('\Modules\Vat\Http\Controllers\VatStatement126Controller@index') }}?tab=list">
                         <i class="fa fa-list"></i> <strong>@lang('vat::lang.list_126_statement')</strong>
                     </a>
                 </li>

                 <li class="@if (request()->tab == 'products_sold') active @endif">
                     <a
                         href="{{ action('\Modules\Vat\Http\Controllers\VatStatement126Controller@productsSold') }}?tab=products_sold">
                         <i class="fa fa-shopping-cart"></i> <strong>@lang('vat::lang.vat_products_sold')-126</strong>
                     </a>
                 </li>

                 <li class="@if (request()->tab == 'invoice_setting') active @endif">
                     <a
                         href="{{ action('\Modules\Vat\Http\Controllers\VatStatement126Controller@invoicesSetting') }}?tab=invoice_setting">
                         <i class="fa fa-cog"></i> <strong>@lang('vat::lang.list_vat_invoice_setting')</strong>
                     </a>
                 </li>

                 <li class="@if (request()->tab == 'prefixes') active @endif">
                     <a
                         href="{{ action('\Modules\Vat\Http\Controllers\VatStatement126PrefixController@index') }}?tab=prefixes">
                         <i class="fa fa-code"></i> <strong>@lang('vat::lang.prefix_and_starting_nos')</strong>
                     </a>
                 </li>

                 <li class="@if (request()->tab == 'assign_prefix') active @endif">
                     <a
                         href="{{ action('\Modules\Vat\Http\Controllers\VatStatement126Controller@assignPrefix') }}?tab=assign_prefix">
                         <i class="fa fa-user-plus"></i> <strong>Assign Prefix to Users</strong>
                     </a>
                 </li>

             </ul>
         </div>
     </div>
 </div>

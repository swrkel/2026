 <!-- Main content -->
 <section class="content">
     @component('components.widget', [
         'class' => 'box-primary',
         'title' => __('subscription::lang.invoice_prefixes'),
     ])
     @endcomponent
     @component('components.widget', ['class' => 'box-primary'])
         @slot('tool')
             <div class="box-tools pull-right">
                 <button class="btn btn-primary add-invoice-prefix"
                     data-href="{{ action('\Modules\Subscription\Http\Controllers\SubscriptionInvoicePrefixController@create') }}">
                     <i class="fa fa-plus"></i> @lang('messages.add')
                 </button>
             </div>
         @endslot

         <table class="table table-bordered" id="subscription_invoice_prefix_table"  style="width:100%;">
             <thead>
                 <tr>
                     <th>@lang('messages.action')</th>
                     <th>@lang('subscription::lang.date')</th>
                     <th>@lang('subscription::lang.user')</th>
                     <th>@lang('subscription::lang.prefix')</th>
                     <th>@lang('subscription::lang.current_number')</th>
                 </tr>
             </thead>
         </table>
     @endcomponent
 </section>

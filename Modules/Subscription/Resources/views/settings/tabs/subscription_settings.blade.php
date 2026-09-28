 <!-- Main content -->
 <section class="content">
     @component('components.widget', ['class' => 'box-primary', 'title' => __('subscription::lang.subscription_settings')])
     @endcomponent
     @component('components.widget', ['class' => 'box-primary'])
         @slot('tool')
             <div class="box-tools pull-right">
                 <button type="button" class="btn btn-primary" id="add_fleet_btn"
                     data-href="{{ action('\Modules\Subscription\Http\Controllers\SubscriptionSettingController@create') }}">
                     <i class="fa fa-plus"></i> @lang('subscription::lang.add')
                 </button>
             </div>
         @endslot

         <div class="table-responsive">
             <table class="table table-striped table-bordered" id="subscription_settings_table" style="width:100%;">
                 <thead>
                     <tr>
                         <th>@lang('messages.action')</th>
                         <th>@lang('subscription::lang.date')</th>
                         <th>@lang('subscription::lang.product')</th>
                         <th>@lang('subscription::lang.base_amount')</th>
                         <th>@lang('subscription::lang.subscription_amount')</th>
                         <th>@lang('subscription::lang.subscription_cycle')</th>
                         <th>@lang('subscription::lang.created_by')</th>
                     </tr>
                 </thead>
             </table>
         </div>
     @endcomponent
 </section>
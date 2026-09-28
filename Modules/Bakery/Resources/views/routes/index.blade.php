<!-- Main content -->
<section class="content">
  @component('components.widget', ['class' => 'box-primary', 'title' => __('bakery::lang.all_your_routes_trips')])
  @slot('tool')
  <div class="box-tools ">
    <button type="button" class="btn  btn-primary btn-modal pull-right"
      data-href="{{action('\Modules\Bakery\Http\Controllers\RouteController@create')}}"
      data-container=".view_modal">
      <i class="fa fa-plus"></i> @lang('messages.add')</button>

  </div>
  @endslot
  <div class="table-responsive">
    <table class="table table-bordered table-striped" id="route_table" style="width: 100%;">
      <thead>
        <tr>
          <th class="notexport">@lang('messages.action')</th>
          <th>@lang('fleet::lang.date')</th>
          <th>@lang('bakery::lang.route_trip_name')</th>
          <th>@lang('fleet::lang.added_by')</th>
        </tr>
      </thead>
    </table>
  </div>
  @endcomponent
</section>
<!-- /.content -->

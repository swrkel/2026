<section class="content">
    @component('distribution::components.widget', ['class' => 'box-primary', 'title' => 'User To Routes'])
        <div class="row">
            <div class="col-md-12">
                <button type="button" class="btn btn-primary pull-right" id="add-user-route-btn">
                    <i class="fa fa-plus"></i> Add User to Route
                </button>
                <p class="text-muted">Manage mapping between Sales Representatives and Routes.</p>
            </div>
        </div>
    @endcomponent

    @component('distribution::components.filters', ['title' => __('report.filters')])
        <div class="row" id="user_routes_filter_form">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Sales Rep</label>
                    {!! Form::select('sales_rep_filter', $sales_reps, null, ['class' => 'form-control select2', 'placeholder' => 'All']) !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Status</label>
                    {!! Form::select('status_filter', ['active' => 'Active', 'inactive' => 'Inactive'], null, ['class' => 'form-control select2', 'placeholder' => 'All']) !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Route</label>
                    {!! Form::select('route_filter', $routes, null, ['class' => 'form-control select2', 'placeholder' => 'All']) !!}
                </div>
            </div>
        </div>
    @endcomponent

    @component('distribution::components.widget', ['class' => 'box-primary', 'title' => 'Mapped User Routes'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="user_routes_table">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Date Time</th>
                        <th>Sales Rep</th>
                        <th>Routes Mapped</th>
                        <th>Status</th>
                        <th>User Added</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grouped_maps as $map)
                        <tr data-sales-rep-id="{{ $map->sales_rep_id }}"
                            data-status="{{ $map->status }}"
                            data-route-ids="{{ implode(',', $map->route_ids) }}">
                            <td>
                                <button type="button" class="btn btn-xs btn-primary edit-map-btn"
                                    data-sales-rep-id="{{ $map->sales_rep_id }}"
                                    data-route-ids='@json($map->route_ids)'>
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('distribution.route_user_maps.destroy_by_sales_rep', $map->sales_rep_id) }}" style="display:inline;" onsubmit="return confirm('Delete this mapping?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                </form>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($map->created_at)->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <div>{{ $map->sales_rep_name }}</div>
                                <div style="margin-top: 5px;">
                                    <form method="POST" action="{{ route('distribution.route_user_maps.toggle_status_by_sales_rep', $map->sales_rep_id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-warning">Change Status</button>
                                    </form>
                                </div>
                            </td>
                            <td>
                                <ul class="mapped-routes-list" style="margin:0; padding-left:18px;">
                                    @forelse($map->routes_mapped as $route_name)
                                        <li>{{ $route_name }}</li>
                                    @empty
                                        <li>-</li>
                                    @endforelse
                                </ul>
                            </td>
                            <td>
                                <span class="label label-{{ $map->status === 'active' ? 'success' : 'default' }}">{{ ucfirst($map->status) }}</span>
                            </td>
                            <td>
                                <div><strong>{{ $map->added_by_user ?: '-' }}</strong></div>
                                @if(($map->status_changed_user_name || $map->changed_user_name) && $map->last_status_from && $map->last_status_to && $map->status_changed_at)
                                    <div style="font-size: 11px; color: #555; margin-top: 5px;">
                                        Status Changed by the User {{ $map->status_changed_user_name ?: $map->changed_user_name }}, from {{ ucfirst($map->last_status_from) }} Status to {{ ucfirst($map->last_status_to) }} Status on {{ \Carbon\Carbon::parse($map->status_changed_at)->format('Y-m-d H:i:s') }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No data available in table</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcomponent
</section>

<div class="modal fade contains_select2" id="addUserRouteMapModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('distribution.route_user_maps.store') }}">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">Add User To Routes</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Date & Time</label>
                        <input type="text" class="form-control" value="{{ now()->format('Y-m-d H:i:s') }}" readonly>
                    </div>
                    <div class="form-group">
                        <label>Location *</label>
                        {!! Form::select('location_id', $business_locations ?? [], $default_location_id ?? null, ['class' => 'form-control select2', 'required', 'id' => 'location_id']) !!}
                    </div>
                    <div class="form-group">
                        <label>Sales Rep *</label>
                        {!! Form::select('sales_rep_id', $sales_reps, null, ['class' => 'form-control select2', 'placeholder' => 'Please Select', 'required', 'id' => 'sales_rep_id']) !!}
                    </div>
                    <div class="form-group">
                        <label>Routes *</label>
                        <div class="input-group">
                            {!! Form::select('temp_route_ids[]', $routes, null, ['class' => 'form-control select2', 'id' => 'add_temp_route_ids', 'multiple']) !!}
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-primary" id="add_route_btn_add_modal">Add</button>
                            </span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Added Routes</label>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="add_routes_table">
                                <thead>
                                    <tr>
                                        <th>Route Name</th>
                                        <th style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="no-routes-row">
                                        <td colspan="2" class="text-center">No routes added yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade contains_select2" id="editUserRouteMapModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="editUserRouteMapForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">Edit User To Routes</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Location *</label>
                        {!! Form::select('location_id', $business_locations ?? [], null, ['class' => 'form-control select2', 'id' => 'edit_location_id', 'disabled']) !!}
                    </div>
                    <div class="form-group">
                        <label>Sales Rep *</label>
                        {!! Form::select('sales_rep_id_readonly', $sales_reps, null, ['class' => 'form-control select2', 'id' => 'edit_sales_rep_id', 'disabled']) !!}
                    </div>
                    <div class="form-group">
                        <label>Routes *</label>
                        <div class="input-group">
                            {!! Form::select('temp_route_ids[]', $routes, null, ['class' => 'form-control select2', 'id' => 'edit_temp_route_ids', 'multiple']) !!}
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-primary" id="add_route_btn_edit_modal">Add</button>
                            </span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Added Routes</label>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="edit_routes_table">
                                <thead>
                                    <tr>
                                        <th>Route Name</th>
                                        <th style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="no-routes-row">
                                        <td colspan="2" class="text-center">No routes added yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(function() {
        $('.select2').select2({
            width: '100%'
        });

        function applyUserRoutesFilters() {
            var salesRepId = $('select[name="sales_rep_filter"]').val() || '';
            var status = $('select[name="status_filter"]').val() || '';
            var routeId = $('select[name="route_filter"]').val() || '';

            $('#user_routes_table tbody tr').each(function() {
                var $row = $(this);
                var rowSalesRepId = String($row.data('sales-rep-id') || '');
                var rowStatus = String($row.data('status') || '');
                var rowRouteIds = String($row.data('route-ids') || '').split(',');

                var matchesSalesRep = !salesRepId || rowSalesRepId === String(salesRepId);
                var matchesStatus = !status || rowStatus === String(status);
                var matchesRoute = !routeId || rowRouteIds.indexOf(String(routeId)) !== -1;

                $row.toggle(matchesSalesRep && matchesStatus && matchesRoute);
            });
        }

        $('#user_routes_filter_form').on('change', 'select', applyUserRoutesFilters);

        function addRouteToTable($table, routeId, routeName) {
            if (!routeId) return;
            if ($table.find('input[value="' + routeId + '"]').length > 0) {
                return; // already added
            }
            $table.find('.no-routes-row').remove();
            var rowHtml = '<tr>' +
                '<td>' + routeName + '<input type="hidden" name="route_ids[]" value="' + routeId + '"></td>' +
                '<td><button type="button" class="btn btn-danger btn-xs remove-route-row-btn">Delete</button></td>' +
                '</tr>';
            $table.find('tbody').append(rowHtml);
        }

        $(document).on('click', '.remove-route-row-btn', function() {
            var $tbody = $(this).closest('tbody');
            $(this).closest('tr').remove();
            if ($tbody.find('tr').length === 0) {
                $tbody.append('<tr class="no-routes-row"><td colspan="2" class="text-center">No routes added yet.</td></tr>');
            }
        });

        $('#add_route_btn_add_modal').on('click', function() {
            var selectedIds = $('#add_temp_route_ids').val() || [];
            selectedIds.forEach(function(routeId) {
                var routeName = $('#add_temp_route_ids option[value="' + routeId + '"]').text();
                addRouteToTable($('#add_routes_table'), routeId, routeName);
            });
            $('#add_temp_route_ids').val(null).trigger('change');
        });

        $('#add_route_btn_edit_modal').on('click', function() {
            var selectedIds = $('#edit_temp_route_ids').val() || [];
            selectedIds.forEach(function(routeId) {
                var routeName = $('#edit_temp_route_ids option[value="' + routeId + '"]').text();
                addRouteToTable($('#edit_routes_table'), routeId, routeName);
            });
            $('#edit_temp_route_ids').val(null).trigger('change');
        });

        $(document).on('click', '.edit-map-btn', function() {
            var salesRepId = $(this).data('sales-rep-id');
            var routeIds = $(this).data('route-ids') || [];
            $('#edit_sales_rep_id').val(String(salesRepId)).trigger('change');
            
            var $editTable = $('#edit_routes_table');
            $editTable.find('tbody').empty();
            if (routeIds.length === 0) {
                $editTable.find('tbody').append('<tr class="no-routes-row"><td colspan="2" class="text-center">No routes added yet.</td></tr>');
            } else {
                routeIds.forEach(function(routeId) {
                    var routeName = $('#edit_temp_route_ids option[value="' + routeId + '"]').text() || ('Route ID: ' + routeId);
                    addRouteToTable($editTable, routeId, routeName);
                });
            }

            var actionUrl = "{{ route('distribution.route_user_maps.update_by_sales_rep', ':sales_rep_id') }}".replace(':sales_rep_id', salesRepId);
            $('#editUserRouteMapForm').attr('action', actionUrl);
            $('#editUserRouteMapModal').modal('show');
        });

        $(document).on('submit', '#addUserRouteMapModal form, #editUserRouteMapModal form', function(e) {
            var $form = $(this);
            if ($form.find('input[name="route_ids[]"]').length === 0) {
                e.preventDefault();
                alert('Please add at least one route to the table.');
                return false;
            }
        });

        $(document).on('click', '#add-user-route-btn', function() {
            $('#addUserRouteMapModal').modal('show');
        });

        $(document).on('shown.bs.modal', '#addUserRouteMapModal', function() {
            var $modal = $(this);
            $modal.find('.select2').each(function() {
                var $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $select.select2({
                    dropdownParent: $modal,
                    width: '100%',
                    minimumResultsForSearch: 0
                });
            });
        });

        $(document).on('shown.bs.modal', '#editUserRouteMapModal', function() {
            var $modal = $(this);
            $modal.find('.select2').each(function() {
                var $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $select.select2({
                    dropdownParent: $modal,
                    width: '100%',
                    minimumResultsForSearch: 0
                });
            });
        });
    });
</script>

<style>
    .select2-results__options {
        max-height: 60px !important;
    }
</style>


@component('components.widget', ['class' => '', 'title' => 'Referred to'])

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="referred_to_date_time">Date & Time</label>
            <input type="text" id="referred_to_date_time" class="form-control" value="{{ now()->format('m/d/Y h:i A') }}" readonly>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="referred_to_department">Department</label>
            <select name="department" id="referred_to_department" class="form-control select2" style="width: 100%;">
                <option value="">Select Department</option>
                @foreach(($referred_departments ?? []) as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="referred_to_designation">Designations</label>
            <select id="referred_to_designation" class="form-control select2" style="width: 100%;">
                <option value="">Select Designation</option>
                @foreach(($user_designations ?? []) as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="referred_to_officer_name">Officer Name</label>
            <input type="text" id="referred_to_officer_name" class="form-control" placeholder="Officer Name">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="referred_to_status">Status</label>
            <input type="text" id="referred_to_status" class="form-control" value="Active" readonly>
        </div>
    </div>
    <div class="col-md-2" style="padding-top: 22px">
        <button type="button" class="btn btn-primary" id="save_referred_to">Save</button>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="referred_to_filter_department">Department</label>
            <select id="referred_to_filter_department" class="form-control select2" style="width: 100%;">
                <option value="">All</option>
                @foreach(($referred_departments ?? []) as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="referred_to_filter_designation">Designation</label>
            <select id="referred_to_filter_designation" class="form-control select2" style="width: 100%;">
                <option value="">All</option>
                @foreach(($user_designations ?? []) as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="referred_to_filter_status">Status</label>
            <select id="referred_to_filter_status" class="form-control select2" style="width: 100%;">
                <option value="">All</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="document_referred_to_table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Date & Time</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Officer Name</th>
                    <th>Status</th>
                    <th>Added User</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="referred_to_status_modal" tabindex="-1" role="dialog" aria-labelledby="referredToStatusModalLabel">
    <div class="modal-dialog" role="document" style="width: 60%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="referredToStatusModalLabel">Change Status</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status_change_department">Department</label>
                            <input type="text" id="status_change_department" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status_change_designation">Designation</label>
                            <input type="text" id="status_change_designation" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status_change_officer_name">Officer Name</label>
                            <input type="text" id="status_change_officer_name" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status_change_current">Current Status</label>
                            <input type="text" id="status_change_current" class="form-control" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status_change_to">Change Status To</label>
                            <select id="status_change_to" class="form-control select2" style="width: 100%;">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="referred_to_status_history_table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Changed By</th>
                                    <th>Changed Status From</th>
                                    <th>Changed Status To</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="save_referred_to_status_change">Save</button>
            </div>
        </div>
    </div>
</div>

@endcomponent
{{-- 
<script>
    $(document).ready(function () {
        var selectedReferredToId = null;

        function loadDesignationOptions(targetSelector, selectedValue) {
            $.ajax({
                url: '/DocManagement/document_designations_by_department',
                method: 'GET',
                data: {
                    department: $('#referred_to_department').val()
                },
                success: function (response) {
                    var $target = $(targetSelector);
                    var currentValue = selectedValue || '';

                    $target.empty().append('<option value="">Select Designation</option>');
                    $.each(response, function (index, designation) {
                        $target.append('<option value="' + designation + '">' + designation + '</option>');
                    });

                    $target.val(currentValue).trigger('change');
                }
            });
        }

        function loadReferredToTableData() {
            $.ajax({
                url: '/DocManagement/document_referred_to_gets',
                method: 'GET',
                data: {
                    department: $('#referred_to_filter_department').val(),
                    designation: $('#referred_to_filter_designation').val(),
                    status: $('#referred_to_filter_status').val()
                },
                success: function (response) {
                    updateReferredToTable(response);
                },
                error: function (xhr, status, error) {
                    console.error('Error loading referred to data:', error);
                }
            });
        }

        function updateReferredToTable(data) {
            var tableBody = $('#document_referred_to_table tbody');
            tableBody.empty();

            for (var i = 0; i < data.length; i++) {
                var row = '<tr>' +
                    '<td><button type="button" class="btn btn-xs btn-primary open-status-modal" data-id="' + data[i].id + '" data-department="' + (data[i].department || '') + '" data-designation="' + (data[i].designation || '') + '" data-officer-name="' + (data[i].officer_name || '') + '" data-status="' + (data[i].status || 'Active') + '">Change Status</button></td>' +
                    '<td>' + (data[i].date_time || '') + '</td>' +
                    '<td>' + (data[i].department || '') + '</td>' +
                    '<td>' + (data[i].designation || '') + '</td>' +
                    '<td>' + (data[i].officer_name || '') + '</td>' +
                    '<td>' + (data[i].status || '') + '</td>' +
                    '<td>' + (data[i].user || '') + '</td>' +
                    '</tr>';
                tableBody.append(row);
            }
        }

        function loadReferredToStatusHistory(id) {
            $.ajax({
                url: '/DocManagement/document_referred_to_status_history_gets',
                method: 'GET',
                data: { id: id },
                success: function (response) {
                    var tableBody = $('#referred_to_status_history_table tbody');
                    tableBody.empty();

                    for (var i = 0; i < response.length; i++) {
                        var row = '<tr>' +
                            '<td>' + (response[i].date_time || '') + '</td>' +
                            '<td>' + (response[i].changed_by || '') + '</td>' +
                            '<td>' + (response[i].status_from || '') + '</td>' +
                            '<td>' + (response[i].status_to || '') + '</td>' +
                            '</tr>';
                        tableBody.append(row);
                    }
                }
            });
        }

        $('#referred_to_department').on('change', function () {
            loadDesignationOptions('#referred_to_designation');
        });

        $('#save_referred_to').on('click', function () {
            var $button = $(this);
            var department = $('#referred_to_department').val();
            var designation = $('#referred_to_designation').val();
            var officerName = $('#referred_to_officer_name').val().trim();

            if (!department || !designation || !officerName) {
                toastr.error('Department, Designation and Officer Name are required');
                return;
            }

            $button.prop('disabled', true);

            $.ajax({
                url: '/DocManagement/store_referred_to',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    date_time: $('#referred_to_date_time').val(),
                    department: department,
                    designation: designation,
                    officer_name: officerName,
                    status: $('#referred_to_status').val()
                },
                success: function (response) {
                    loadReferredToTableData();
                    $('#referred_to_department').val('').trigger('change');
                    $('#referred_to_designation').empty().append('<option value="">Select Designation</option>').trigger('change');
                    $('#referred_to_officer_name').val('');

                    if (response.success) {
                        toastr.success(response.msg || 'Saved Successfully');
                    } else {
                        toastr.error(response.msg || 'Failed to save referred to');
                    }
                },
                error: function () {
                    toastr.error('Failed to save referred to');
                },
                complete: function () {
                    $button.prop('disabled', false);
                }
            });
        });

        $('#referred_to_filter_department, #referred_to_filter_designation, #referred_to_filter_status').on('change', function () {
            loadReferredToTableData();
        });

        $(document).on('click', '.open-status-modal', function () {
            selectedReferredToId = $(this).data('id');
            $('#status_change_department').val($(this).data('department'));
            $('#status_change_designation').val($(this).data('designation'));
            $('#status_change_officer_name').val($(this).data('officer-name'));
            $('#status_change_current').val($(this).data('status'));
            $('#status_change_to').val($(this).data('status')).trigger('change');
            loadReferredToStatusHistory(selectedReferredToId);
            $('#referred_to_status_modal').modal('show');
        });

        $('#save_referred_to_status_change').on('click', function () {
            var $button = $(this);
            if (!selectedReferredToId) {
                return;
            }

            $button.prop('disabled', true);

            $.ajax({
                url: '/DocManagement/update_referred_to_status',
                method: 'GET',
                data: {
                    id: selectedReferredToId,
                    status: $('#status_change_to').val()
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.msg || 'Status changed successfully');
                        $('#status_change_current').val($('#status_change_to').val());
                        loadReferredToTableData();
                        loadReferredToStatusHistory(selectedReferredToId);
                    } else {
                        toastr.error(response.msg || 'Failed to change status');
                    }
                },
                error: function () {
                    toastr.error('Failed to change status');
                },
                complete: function () {
                    $button.prop('disabled', false);
                }
            });
        });

        loadReferredToTableData();
    });
</script> --}}

@component('components.widget', ['class' => '', 'title' => 'Department'])

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="department_name">Department</label>
            <input type="text" name="department_name" id="department_name" class="form-control" placeholder="Department">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="department_description">Description</label>
            <input type="text" name="department_description" id="department_description" class="form-control" placeholder="Description">
        </div>
    </div>
    <div class="col-md-3" style="padding-top: 22px">
        <button type="button" class="btn btn-primary" id="save_department">Save</button>
    </div>
</div>

<div class="row">
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="department_table" style="width:100%!important">
            <thead>
                <tr>
                    <th width="20%">Date Added</th>
                    <th width="25%">Department</th>
                    <th width="35%">Description</th>
                    <th width="25%">Added User</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
@endcomponent

{{-- <script>
    $(document).ready(function() {
        loadDepartmentTableData();

        function loadDepartmentTableData() {
            $.ajax({
                url: '/DocManagement/document_department_gets',
                method: 'GET',
                success: function(response) {
                    updateDepartmentTable(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error loading department data:', error);
                }
            });
        }

        function updateDepartmentTable(data) {
            var tableBody = $('#department_table tbody');
            tableBody.empty();

            for (var i = 0; i < data.length; i++) {
                var row = '<tr>' +
                    '<td>' + (data[i].created_at || '') + '</td>' +
                    '<td>' + (data[i].department || '') + '</td>' +
                    '<td>' + (data[i].description || '') + '</td>' +
                    '</tr>';

                tableBody.append(row);
            }
        }

        $('#save_department').on('click', function() {
            var $button = $(this);
            var department = $.trim($('#department_name').val());
            var description = $.trim($('#department_description').val());

            if (!department) {
                toastr.error('Department is required');
                return;
            }

            $button.prop('disabled', true);

            $.ajax({
                url: '/DocManagement/store_department',
                method: 'GET',
                data: {
                    department: department,
                    description: description
                },
                success: function(response) {
                    loadDepartmentTableData();

                    if (response.success) {
                        $('#department_name').val('');
                        $('#department_description').val('');
                        toastr.success(response.msg || 'Department saved successfully');
                    } else {
                        toastr.error(response.msg || 'Department already exists');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error saving department:', error);
                    toastr.error('Failed to save Department');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });
    });
</script> --}}

@component('components.widget', ['class' => '', 'title' => 'Doc Status'])

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="doc_status_name">Status</label>
            <input type="text" name="doc_status_name" id="doc_status_name" class="form-control" placeholder="Status">
        </div>
    </div>
    <div class="col-md-3" style="padding-top: 22px">
        <button type="button" class="btn btn-primary" id="save_doc_status">Save</button>
    </div>
</div>

<div class="row">
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="doc_status_table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Added By</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@endcomponent
{{-- 
<script>
    $(document).ready(function () {
        loadDocStatusTableData();

        function loadDocStatusTableData() {
            $.ajax({
                url: '/DocManagement/document_status_gets',
                method: 'GET',
                success: function (response) {
                    updateDocStatusTable(response);
                },
                error: function (xhr, status, error) {
                    console.error('Error loading doc status data:', error);
                }
            });
        }

        function updateDocStatusTable(data) {
            var tableBody = $('#doc_status_table tbody');
            tableBody.empty();

            for (var i = 0; i < data.length; i++) {
                var row = '<tr>' +
                    '<td>' + (data[i].date_time || '') + '</td>' +
                    '<td>' + (data[i].status || '') + '</td>' +
                    '<td>' + (data[i].user || '') + '</td>' +
                    '</tr>';
                tableBody.append(row);
            }
        }

        $('#save_doc_status').on('click', function () {
            var $button = $(this);
            var statusName = $.trim($('#doc_status_name').val());

            if (!statusName) {
                toastr.error('Status is required');
                return;
            }

            $button.prop('disabled', true);

            $.ajax({
                url: '/DocManagement/store_doc_status',
                method: 'GET',
                data: {
                    status_name: statusName
                },
                success: function (response) {
                    loadDocStatusTableData();

                    if (response.success) {
                        $('#doc_status_name').val('');
                        toastr.success(response.msg || 'Doc Status saved successfully');
                    } else {
                        toastr.error(response.msg || 'Failed to save Doc Status');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error saving doc status:', error);
                    toastr.error('Failed to save Doc Status');
                },
                complete: function () {
                    $button.prop('disabled', false);
                }
            });
        });
    });
</script> --}}

@component('components.widget', ['class' => '', 'title' => 'Designation'])

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="designation_department_id">Department</label>
            <select name="designation_department_id" id="designation_department_id" class="form-control select2" style="width:100%">
                <option value="">Select Department</option>
                @foreach(($referred_departments ?? []) as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="designation_name">Designation</label>
            <input type="text" name="designation_name" id="designation_name" class="form-control" placeholder="Designation">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="designation_description">Description</label>
            <input type="text" name="designation_description" id="designation_description" class="form-control" placeholder="Description">
        </div>
    </div>
    <div class="col-md-3" style="padding-top: 22px">
        <button type="button" class="btn btn-primary" id="save_designation">Save</button>
    </div>
</div>

<div class="row">
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="designation_table" style="width:100%!important">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Description</th>
                    <th>Added User</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endcomponent

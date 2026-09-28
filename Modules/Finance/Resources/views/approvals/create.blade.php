@extends('layouts.app')

@section('title', 'Create Finance Approval Workflow')

@section('content')

<section class="content-header">
    <h1>
        Create Finance Approval Workflow
    </h1>
</section>

<section class="content">

    <form method="POST"
          action="{{ route('finance.approvals.store') }}">

        @csrf

        <div class="box box-primary">

            <div class="box-header with-border">

                <h3 class="box-title">
                    Workflow Details
                </h3>

            </div>

            <div class="box-body">

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Workflow Name *</label>

                            <input type="text"
                                   name="workflow_name"
                                   class="form-control"
                                   required>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Branch</label>

                            <select name="location_id"
                                    class="form-control">

                                <option value="">
                                    All Branches
                                </option>

                                @foreach($locations as $id => $name)

                                    <option value="{{ $id }}">
                                        {{ $name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Module *</label>

                            <input type="text"
                                   name="module"
                                   class="form-control"
                                   placeholder="Example: Journals"
                                   required>

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Approval Type *</label>

                            <input type="text"
                                   name="approval_type"
                                   class="form-control"
                                   placeholder="Example: Expense Approval"
                                   required>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Minimum Amount</label>

                            <input type="number"
                                   step="0.01"
                                   name="minimum_amount"
                                   class="form-control"
                                   value="0">

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Maximum Amount</label>

                            <input type="number"
                                   step="0.01"
                                   name="maximum_amount"
                                   class="form-control"
                                   value="0">

                        </div>

                    </div>

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Approval Levels *</label>

                            <select name="approval_levels"
                                    id="approval_levels"
                                    class="form-control"
                                    onchange="generateLevels()"
                                    required>

                                <option value="1">1 Level</option>
                                <option value="2">2 Levels</option>
                                <option value="3">3 Levels</option>
                                <option value="4">4 Levels</option>
                                <option value="5">5 Levels</option>

                            </select>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="box box-success">

            <div class="box-header with-border">

                <h3 class="box-title">
                    Approval Levels
                </h3>

            </div>

            <div class="box-body">

                <div id="approval-levels-container">

                </div>

            </div>

        </div>

        <div class="box-footer">

            <button type="submit"
                    class="btn btn-primary">

                <i class="fa fa-save"></i>
                Save Workflow

            </button>

        </div>

    </form>

</section>

@endsection

@section('javascript')

<script>

    function generateLevels()
    {
        let levels = document.getElementById('approval_levels').value;

        let container = document.getElementById('approval-levels-container');

        container.innerHTML = '';

        for (let i = 1; i <= levels; i++) {

            container.innerHTML += `

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Level ${i} Role</label>

                            <input type="text"
                                   name="role_name_${i}"
                                   class="form-control"
                                   placeholder="Example: Finance Manager"
                                   required>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Approval Limit</label>

                            <input type="number"
                                   step="0.01"
                                   name="approval_limit_${i}"
                                   class="form-control"
                                   value="0">

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Escalation Hours</label>

                            <input type="number"
                                   name="escalation_hours_${i}"
                                   class="form-control"
                                   value="24">

                        </div>

                    </div>

                </div>

            `;
        }
    }

    generateLevels();

</script>

@endsection
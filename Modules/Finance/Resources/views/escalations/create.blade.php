@extends('layouts.app')

@section('title', 'Create Finance Escalation')

@section('content')

<section class="content-header">
    <h1>
        Create Finance Escalation
    </h1>
</section>

<section class="content">

    <form method="POST"
          action="{{ route('finance.escalations.store') }}">

        @csrf

        <div class="box box-danger">

            <div class="box-header with-border">

                <h3 class="box-title">
                    Escalation Details
                </h3>

            </div>

            <div class="box-body">

                <div class="row">

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
                                   placeholder="Example: Treasury"
                                   required>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Escalation Type *</label>

                            <input type="text"
                                   name="escalation_type"
                                   class="form-control"
                                   placeholder="Example: Approval Delay"
                                   required>

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-3">

                        <div class="form-group">

                            <label>Severity *</label>

                            <select name="severity"
                                    class="form-control"
                                    required>

                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>

                            </select>

                        </div>

                    </div>

                    <div class="col-md-9">

                        <div class="form-group">

                            <label>Subject *</label>

                            <input type="text"
                                   name="subject"
                                   class="form-control"
                                   required>

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-12">

                        <div class="form-group">

                            <label>Description</label>

                            <textarea name="description"
                                      class="form-control"
                                      rows="5"></textarea>

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Reference Type</label>

                            <input type="text"
                                   name="reference_type"
                                   class="form-control"
                                   placeholder="Example: journal_entries">

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Reference ID</label>

                            <input type="number"
                                   name="reference_id"
                                   class="form-control">

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            <label>Assign To</label>

                            <select name="assigned_to"
                                    class="form-control">

                                <option value="">
                                    Select User
                                </option>

                                @foreach($users as $id => $user)

                                    <option value="{{ $id }}">
                                        {{ $user }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>

            </div>

            <div class="box-footer">

                <button type="submit"
                        class="btn btn-danger">

                    <i class="fa fa-save"></i>
                    Create Escalation

                </button>

            </div>

        </div>

    </form>

</section>

@endsection
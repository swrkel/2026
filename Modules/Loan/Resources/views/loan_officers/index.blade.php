@extends('layouts.app')
@section('title', 'Loan Officers')

@section('content')
<section class="content-header">
    <div class="erp-so-card erp-so-header">
        <div>
            <h3><i class="fa fa-user-tie"></i> Loan Officers</h3>
            <p><i class="fa fa-cog"></i> Loan Module / Settings</p>
            <p><i class="fa fa-info-circle"></i> Active officers will be available in Add/Edit Loan Customer.</p>
        </div>
        <div class="erp-so-badge">
            <span>Total Officers</span>
            <strong>{{ $officers->total() }}</strong>
        </div>
    </div>
</section>

<section class="content">
    <div class="erp-so-shell">
        <div class="row">
            <div class="col-md-5">
                <div class="erp-so-card">
                    <div class="erp-so-card-title"><i class="fa fa-plus"></i> Add Loan Officer</div>
                    {!! Form::open(['url' => route('loan.officers.store'), 'method' => 'post']) !!}
                        <div class="form-group row">
                            <label class="col-sm-4 control-label text-right">System User:</label>
                            <div class="col-sm-8">
                                {!! Form::select('user_id', ['' => 'Manual / Not Linked'] + ($users ?? []), null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 control-label text-right">Officer Name: <span class="erp-required">*</span></label>
                            <div class="col-sm-8">
                                {!! Form::text('name', null, ['class' => 'form-control', 'required']) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 control-label text-right">Email:</label>
                            <div class="col-sm-8">
                                {!! Form::text('email', null, ['class' => 'form-control']) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 control-label text-right">Mobile:</label>
                            <div class="col-sm-8">
                                {!! Form::text('mobile', null, ['class' => 'form-control']) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 control-label text-right">Status:</label>
                            <div class="col-sm-8">
                                {!! Form::select('status', ['active' => 'Active', 'inactive' => 'Inactive'], 'active', ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 control-label text-right">Notes:</label>
                            <div class="col-sm-8">
                                {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 3]) !!}
                            </div>
                        </div>
                        <div class="text-right">
                            <button type="submit" class="btn erp-btn-primary"><i class="fa fa-save"></i> Save Officer</button>
                        </div>
                    {!! Form::close() !!}
                </div>
            </div>
            <div class="col-md-7">
                <div class="erp-so-card">
                    <div class="erp-so-card-title"><i class="fa fa-list"></i> Loan Officer List</div>
                    <form method="get" action="{{ route('loan.officers.index') }}" class="row" style="margin-bottom: 15px;">
                        <div class="col-md-9">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search officer name, email or mobile">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn erp-btn-info btn-block"><i class="fa fa-search"></i> Search</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Status</th>
                                    <th style="width:100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($officers as $officer)
                                    <tr>
                                        <td>{{ $officer->name }}</td>
                                        <td>{{ $officer->email ?: '-' }}</td>
                                        <td>{{ $officer->mobile ?: '-' }}</td>
                                        <td><span class="label label-{{ $officer->status == 'active' ? 'success' : 'default' }}">{{ ucfirst($officer->status) }}</span></td>
                                        <td>
                                            {!! Form::open(['url' => route('loan.officers.destroy', $officer->id), 'method' => 'post', 'onsubmit' => "return confirm('Delete this loan officer?')"]) !!}
                                                <button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                                            {!! Form::close() !!}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center">No loan officers found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $officers->links() }}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
@parent
<script>
$(document).ready(function(){ if ($.fn.select2) { $('.select2').select2(); } });
</script>
@endsection

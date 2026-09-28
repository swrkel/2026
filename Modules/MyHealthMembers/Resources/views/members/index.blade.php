@extends('layouts.app')
@section('title', __('My Health Members'))

@section('content')
<section class="content-header">
    <h1>{{ __('My Health Members') }} <small>{{ __('Member Register') }}</small></h1>
</section>

<section class="content">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row">
        <div class="col-md-4 col-sm-6 col-xs-12">
            <div class="info-box"><span class="info-box-icon bg-aqua"><i class="fa fa-users"></i></span><div class="info-box-content"><span class="info-box-text">{{ __('Total Members') }}</span><span class="info-box-number">{{ number_format($summary['total'] ?? 0) }}</span></div></div>
        </div>
        <div class="col-md-4 col-sm-6 col-xs-12">
            <div class="info-box"><span class="info-box-icon bg-green"><i class="fa fa-check"></i></span><div class="info-box-content"><span class="info-box-text">{{ __('Active Members') }}</span><span class="info-box-number">{{ number_format($summary['active'] ?? 0) }}</span></div></div>
        </div>
        <div class="col-md-4 col-sm-6 col-xs-12">
            <div class="info-box"><span class="info-box-icon bg-yellow"><i class="fa fa-calendar"></i></span><div class="info-box-content"><span class="info-box-text">{{ __('New Today') }}</span><span class="info-box-number">{{ number_format($summary['today'] ?? 0) }}</span></div></div>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('Search & Filters') }}</h3>
        </div>
        <form method="GET" action="{{ route('myhealth.members.index') }}">
            <div class="box-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('Search') }}</label>
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Member Code, Name, Mobile, NIC, Passport">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ __('Gender') }}</label>
                            <select name="gender" class="form-control">
                                <option value="">{{ __('All') }}</option>
                                @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}" @selected(request('gender') === $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ __('Blood Group') }}</label>
                            <select name="blood_group" class="form-control">
                                <option value="">{{ __('All') }}</option>
                                @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $group)
                                    <option value="{{ $group }}" @selected(request('blood_group') === $group)>{{ $group }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ __('Status') }}</label>
                            <select name="status" class="form-control">
                                <option value="">{{ __('All') }}</option>
                                @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'deceased' => 'Deceased'] as $value => $label)
                                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ __('Registered From') }}</label>
                            <input type="date" name="registered_from" class="form-control" value="{{ request('registered_from') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ __('Registered To') }}</label>
                            <input type="date" name="registered_to" class="form-control" value="{{ request('registered_to') }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> {{ __('Search') }}</button>
                <a href="{{ route('myhealth.members.index') }}" class="btn btn-default">{{ __('Reset') }}</a>
                @if(\Illuminate\Support\Facades\Route::has('myhealth.members.create'))
                    <a href="{{ route('myhealth.members.create') }}" class="btn btn-success pull-right"><i class="fa fa-plus"></i> {{ __('Add My Health Member') }}</a>
                @endif
            </div>
        </form>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('Member Register') }}</h3>
        </div>
        <div class="box-body table-responsive no-padding">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Member Code') }}</th>
                        <th>{{ __('Photo') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Mobile') }}</th>
                        <th>{{ __('NIC / Passport') }}</th>
                        <th>{{ __('DOB') }}</th>
                        <th>{{ __('Gender') }}</th>
                        <th>{{ __('Blood Group') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Registered Date') }}</th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                        <tr>
                            <td><strong>{{ $member->myhealth_code }}</strong><br><small class="text-muted">{{ optional($member->login)->login_code }}</small></td>
                            <td>
                                @if(!empty($member->photo_path))
                                    <img src="{{ asset($member->photo_path) }}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                @else
                                    <span class="label label-default"><i class="fa fa-user"></i></span>
                                @endif
                            </td>
                            <td>{{ $member->name }}</td>
                            <td>{{ $member->mobile }}</td>
                            <td>{{ $member->nic_no ?: $member->passport_no }}</td>
                            <td>{{ optional($member->date_of_birth)->format('Y-m-d') }}</td>
                            <td>{{ ucfirst((string) $member->gender) }}</td>
                            <td>{{ $member->blood_group }}</td>
                            <td><span class="label label-{{ $member->status_label === 'Active' ? 'success' : 'default' }}">{{ $member->status_label }}</span></td>
                            <td>{{ optional($member->created_at)->format('Y-m-d') }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('myhealth.members.show', $member->id) }}" class="btn btn-xs btn-primary"><i class="fa fa-eye"></i> {{ __('View') }}</a>
                                    @if(\Illuminate\Support\Facades\Route::has('myhealth.members.edit'))
                                        <a href="{{ route('myhealth.members.edit', $member->id) }}" class="btn btn-xs btn-default"><i class="fa fa-edit"></i> {{ __('Edit') }}</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted">{{ __('No members found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="box-footer clearfix">
            <div class="pull-left" style="padding-top:8px;">{{ __('Total Records') }}: {{ $members->total() }}</div>
            <div class="pull-right">{{ $members->links() }}</div>
        </div>
    </div>
</section>
@endsection

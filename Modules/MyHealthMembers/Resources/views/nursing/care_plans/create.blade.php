@extends('layouts.app')
@section('title', $title ?? 'My Health Nursing')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'My Health Nursing' }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

@php($title = 'Add Care Plan')
<form method="POST" action="{{ route('myhealth.nursing.care_plans.store') }}">@csrf<div class="box box-primary"><div class="box-body"><div class="form-group"><label>Member *</label><select name="member_id" class="form-control" required><option value="">Select member</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->member_code ?? '' }} - {{ $member->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Nursing Diagnosis *</label><input name="nursing_diagnosis" class="form-control" required></div>
@foreach(['goals'=>'Goals','interventions'=>'Interventions','outcomes'=>'Outcomes','remarks'=>'Remarks'] as $name=>$label)<div class="form-group"><label>{ $label }</label><textarea name="{ $name }" class="form-control" rows="2"></textarea></div>@endforeach
<div class="form-group"><label>Review Date</label><input type="date" name="review_date" class="form-control"></div>
</div><div class="box-footer"><button class="btn btn-primary">Save Care Plan</button></div></div></form></section>
@endsection

@extends('layouts.app')
@section('title', 'My Health Registration')

@section('content')
<section class="content-header">
    <h1>My Health Member Registration</h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Register My Health Member</h3>
        </div>
        <form method="POST" action="{{ route('myhealth.public.register.store') }}" enctype="multipart/form-data" id="myhealth_public_register_form">
            @csrf
            <div class="box-body">
                @include('myhealthmembers.public.partials.registration_form')
            </div>
        </form>
    </div>
</section>
@endsection

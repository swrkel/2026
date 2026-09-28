@extends('layouts.app')
@section('title', 'My Health Dashboard')

@section('content')
<section class="content-header">
    <h1>My Health Dashboard</h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-body">
            <p><b>MyHealth Code:</b> {{ $member->myhealth_code }}</p>
            <p><b>Name:</b> {{ $member->name }}</p>
            <p><b>Mobile:</b> {{ $member->mobile }}</p>
            <p><b>Email:</b> {{ $member->email }}</p>

            <form method="POST" action="{{ route('myhealth.public.logout') }}">
                @csrf
                <button class="btn btn-danger">Logout</button>
            </form>
        </div>
    </div>
</section>
@endsection

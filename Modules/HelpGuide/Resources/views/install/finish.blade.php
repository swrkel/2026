@extends('helpguide::install.layouts.master', [
    'step_text' => 'Completed',
    'finish' => route('helpguide.dashboard'),
    'step' => 6
])

@section('content')
    <div class="alert alert-success">Congratulations! Installation has been successfully completed</div>
@endsection

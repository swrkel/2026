@extends('layouts.app')
@section('title', 'My Health Release Notes')
@section('content')
<section class="content-header"><h1>My Health <small>Enterprise Release Notes</small></h1></section>
<section class="content">
@include('myhealthmembers::certification._nav')
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">MYHEALTH_030 Enterprise Certification</h3></div>
    <div class="box-body">
        <p>This release adds the final production certification area for the My Health platform.</p>
        <ul>
            <li>Standalone architecture audit checklist.</li>
            <li>Security production checklist.</li>
            <li>Performance readiness checklist.</li>
            <li>End-to-end workflow validation checklist.</li>
            <li>Production readiness dashboard.</li>
            <li>Disaster Recovery section retained under the My Health module.</li>
        </ul>
        <p><strong>Final deployment command:</strong></p>
        <pre>php artisan optimize:clear</pre>
    </div>
</div>
</section>
@endsection

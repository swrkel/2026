@extends('layouts.app')
@section('title', __('distributionnew::lang.server_checklist'))

@section('content')
<section class="content-header disnew-pos-header"><h1>{{ __('distributionnew::lang.server_checklist') }}</h1></section>
<section class="content disnew-pos-page">
    <div class="box box-solid disnew-pos-card">
        <div class="box-body">
            <ol>
                <li>Replace the complete Modules/DistributionNew folder.</li>
                <li>Run master SQL or all Laravel migrations on each tenant database.</li>
                <li>Clear config, route, view and permission cache.</li>
                <li>Enable Distribution New permissions for the business/users.</li>
                <li>Open the Distribution New dashboard from the sidebar.</li>
                <li>Create a test sales order, loading, delivery, invoice, collection and settlement.</li>
                <li>Confirm SMS bridge logs are created without duplicating the SMS module.</li>
            </ol>
        </div>
    </div>
</section>
@endsection

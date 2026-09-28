@extends('layouts.app')
@section('title', 'ERP Experience Framework - V3 Forms & DataTables')
@section('content')
<section class="content-header"><h1>ERP Experience Framework <small>V3 Forms & DataTables</small></h1></section>
<section class="content">
    <x-erp.card title="Form Components">
        <x-erp.form-grid>
            <div class="exf-col-4"><x-erp.input name="sample_name" label="Text Input" placeholder="Sample input" /></div>
            <div class="exf-col-4"><x-erp.money name="sample_amount" label="Money" currency="Rs" /></div>
            <div class="exf-col-4"><x-erp.date name="sample_date" label="Date" /></div>
            <div class="exf-col-6"><x-erp.select name="sample_select" label="Select" :options="['1'=>'Option One','2'=>'Option Two']" /></div>
            <div class="exf-col-6"><x-erp.textarea name="sample_note" label="Textarea" /></div>
        </x-erp.form-grid>
    </x-erp.card>
    <x-erp.card title="Status & Badges">
        <x-erp.status value="draft" /> <x-erp.status value="pending" /> <x-erp.status value="approved" /> <x-erp.status value="completed" /> <x-erp.status value="rejected" />
        <br><br><x-erp.badge type="primary">Primary</x-erp.badge> <x-erp.badge type="success">Success</x-erp.badge> <x-erp.badge type="warning">Warning</x-erp.badge>
    </x-erp.card>
    <x-erp.card title="Empty State"><x-erp.empty-state title="No Data" message="This is the standard empty-state layout." /></x-erp.card>
</section>
@endsection

@extends('tailoring::layouts.app')
@section('page_title', 'Smart Edition Settings')
@section('tailoring_content')
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Smart Edition / Feature Visibility</h3></div>
    <div class="box-body">
        <p class="text-muted">Use Basic for small tailoring shops. Professional and Enterprise features remain hidden unless permissions/features are enabled.</p>
        <form method="POST" action="{{ route('tailoring.settings.smart-edition.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-4"><label>Edition</label><select name="edition" class="form-control"><option value="basic">Basic</option><option value="professional">Professional</option><option value="enterprise">Enterprise</option></select></div>
                <div class="col-md-4"><label>Business Type</label><select name="setup_wizard_answers[business_type]" class="form-control"><option value="small_tailor">Small Tailor</option><option value="boutique">Boutique</option><option value="uniform_shop">Uniform Shop</option><option value="factory">Factory</option></select></div>
                <div class="col-md-4"><label>Employees</label><select name="setup_wizard_answers[employees]" class="form-control"><option value="1">1</option><option value="2-5">2-5</option><option value="5-20">5-20</option><option value="20+">20+</option></select></div>
            </div>
            <hr>
            <label class="checkbox-inline"><input type="checkbox" name="enabled_features[]" value="inventory"> Inventory</label>
            <label class="checkbox-inline"><input type="checkbox" name="enabled_features[]" value="production"> Production</label>
            <label class="checkbox-inline"><input type="checkbox" name="enabled_features[]" value="production_planning"> Production Planning</label>
            <label class="checkbox-inline"><input type="checkbox" name="enabled_features[]" value="barcode_tracking"> Barcode</label>
            <label class="checkbox-inline"><input type="checkbox" name="enabled_features[]" value="qr_job_cards"> QR Job Cards</label>
            <label class="checkbox-inline"><input type="checkbox" name="enabled_features[]" value="factory_analytics"> Factory Analytics</label>
            <br><br><button class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</div>
@endsection

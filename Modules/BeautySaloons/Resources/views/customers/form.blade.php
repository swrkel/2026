<div class="row">
    <div class="col-md-3"><div class="form-group"><label>Customer Code</label><input type="text" name="customer_code" class="form-control" value="{{ old('customer_code', $customer->customer_code ?? '') }}"></div></div>
    <div class="col-md-3"><div class="form-group"><label>Full Name *</label><input type="text" name="full_name" class="form-control" required value="{{ old('full_name', $customer->full_name ?? '') }}"></div></div>
    <div class="col-md-3"><div class="form-group"><label>Mobile</label><input type="text" name="mobile" class="form-control" value="{{ old('mobile', $customer->mobile ?? '') }}"></div></div>
    <div class="col-md-3"><div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $customer->email ?? '') }}"></div></div>
    <div class="col-md-3"><div class="form-group"><label>Gender</label><select name="gender" class="form-control"><option value="">Please Select</option><option value="female">Female</option><option value="male">Male</option><option value="other">Other</option></select></div></div>
    <div class="col-md-3"><div class="form-group"><label>Date of Birth</label><input type="text" name="dob" class="form-control bs_datepicker" value="{{ old('dob', $customer->dob ?? '') }}"></div></div>
    <div class="col-md-3"><div class="form-group"><label>NIC No</label><input type="text" name="nic_no" class="form-control" value="{{ old('nic_no', $customer->nic_no ?? '') }}"></div></div>
    <div class="col-md-3"><div class="form-group"><label>Customer Type</label><select name="customer_type" class="form-control"><option value="regular">Regular</option><option value="vip">VIP</option><option value="bridal">Bridal</option><option value="corporate">Corporate</option></select></div></div>
    <div class="col-md-6"><div class="form-group"><label>Address</label><textarea name="address" class="form-control">{{ old('address', $customer->address ?? '') }}</textarea></div></div>
    <div class="col-md-3"><div class="form-group"><label>Skin Type</label><input type="text" name="skin_type" class="form-control" value="{{ old('skin_type', $customer->skin_type ?? '') }}"></div></div>
    <div class="col-md-3"><div class="form-group"><label>Hair Type</label><input type="text" name="hair_type" class="form-control" value="{{ old('hair_type', $customer->hair_type ?? '') }}"></div></div>
    <div class="col-md-6"><div class="form-group"><label>Allergies</label><textarea name="allergies" class="form-control">{{ old('allergies', $customer->allergies ?? '') }}</textarea></div></div>
    <div class="col-md-6"><div class="form-group"><label>Medical Notes</label><textarea name="medical_notes" class="form-control">{{ old('medical_notes', $customer->medical_notes ?? '') }}</textarea></div></div>
    <div class="col-md-3"><label><input type="checkbox" name="sms_enabled" value="1" checked> SMS Enabled</label></div>
    <div class="col-md-3"><label><input type="checkbox" name="email_enabled" value="1"> Email Enabled</label></div>
    <div class="col-md-3"><label><input type="checkbox" name="whatsapp_enabled" value="1"> WhatsApp Enabled</label></div>
    <div class="col-md-3"><label><input type="checkbox" name="loyalty_enabled" value="1" checked> Loyalty Enabled</label></div>
</div>

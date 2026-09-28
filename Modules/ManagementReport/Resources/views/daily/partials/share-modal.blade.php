<div class="mgmt-modal" id="mgmt-share-modal" hidden>
    <div class="mgmt-modal-backdrop" data-mgmt-close-share></div>
    <div class="mgmt-modal-dialog">
        <div class="mgmt-modal-header"><h3>Share Management Report</h3><button type="button" data-mgmt-close-share>&times;</button></div>
        <form id="mgmt-share-form" action="{{ route('managementreport.daily.share', $run) }}" method="POST">
            @csrf
            <div class="mgmt-field">
                <label>Channel</label>
                <div class="mgmt-channel-options">
                    <label><input type="radio" name="channel" value="sms" checked><span><i class="fa fa-commenting"></i> SMS</span></label>
                    <label><input type="radio" name="channel" value="email"><span><i class="fa fa-envelope"></i> Email</span></label>
                    <label><input type="radio" name="channel" value="whatsapp"><span><i class="fa fa-whatsapp"></i> WhatsApp</span></label>
                </div>
            </div>
            <div class="mgmt-field">
                <label>Recipients</label>
                <textarea id="mgmt-recipient-input" rows="3" placeholder="Enter one mobile number or email per line" required></textarea>
                <div id="mgmt-recipient-fields"></div>
                <small>Use one recipient per line. Up to 50 recipients.</small>
            </div>
            <div class="mgmt-field">
                <label>Message</label>
                <textarea name="message" rows="3">Daily Management Report for {{ \Carbon\Carbon::parse($run->period_start)->format('d M Y') }} is ready.</textarea>
            </div>
            <div class="mgmt-share-row">
                <div class="mgmt-field"><label>Link Expiry</label><select name="expiry_hours"><option value="24">24 hours</option><option value="72" selected>3 days</option><option value="168">7 days</option><option value="720">30 days</option></select></div>
                <label class="mgmt-checkbox"><input type="checkbox" name="attach_pdf" value="1" checked> Attach PDF to email</label>
            </div>
            <div id="mgmt-share-result"></div>
            <div class="mgmt-modal-footer">
                <button type="button" class="mgmt-btn" data-mgmt-close-share>Cancel</button>
                <button type="submit" class="mgmt-btn mgmt-btn-primary"><i class="fa fa-paper-plane"></i> Send / Prepare</button>
            </div>
        </form>
    </div>
</div>

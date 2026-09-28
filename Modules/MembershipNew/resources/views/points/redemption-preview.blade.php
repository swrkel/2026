<div class="mn-panel">
    <h3>Redemption Preview Widget</h3>
    <p>This standalone partial can be included by POS/Sales pages after integration.</p>
    <div class="mn-form-grid">
        <label>Member ID <input id="mn_member_id"></label>
        <label>Invoice Amount <input id="mn_invoice_amount"></label>
        <label>Point Value <input id="mn_point_value" value="1.0000"></label>
    </div>
    <button type="button" class="mn-btn mn-btn-warning" onclick="MembershipNew.previewRedemption()">Preview Redeem</button>
    <div id="mn_redemption_result"></div>
</div>

<div class="pos-cashier-hotbar">
    <div class="hotbar-left">
        <span class="hotkey-pill primary"><kbd>F2</kbd> Scan / Search</span>
        <span class="hotkey-pill"><kbd>F4</kbd> Customer</span>
        <span class="hotkey-pill warning"><kbd>F6</kbd> Hold</span>
        <span class="hotkey-pill"><kbd>F7</kbd> Resume</span>
        <span class="hotkey-pill success"><kbd>F8</kbd> Payment</span>
        <span class="hotkey-pill success"><kbd>F9</kbd> Checkout</span>
    </div>
    <div class="hotbar-right">
        <span class="pos-scan-status">Ready for scan</span>
        <button type="button" class="btn btn-default btn-sm" onclick="POSEnterpriseCashier.toggleHelp()"><i class="fa fa-keyboard-o"></i> Shortcuts</button>
    </div>
</div>
<div id="pos_shortcut_help" class="pos-shortcut-help ch-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title">Cashier Keyboard Shortcuts</h3>
            <div class="ch-card-subtitle">Designed for faster billing without leaving the POS workspace.</div>
        </div>
        <button type="button" class="btn btn-default btn-sm" onclick="POSEnterpriseCashier.toggleHelp()">Close</button>
    </div>
    <div class="ch-card-body">
        <div class="shortcut-grid">
            <div class="shortcut-item"><kbd>F2</kbd><span>Focus barcode/search</span></div>
            <div class="shortcut-item"><kbd>F4</kbd><span>Select customer</span></div>
            <div class="shortcut-item"><kbd>F6</kbd><span>Hold current bill</span></div>
            <div class="shortcut-item"><kbd>F7</kbd><span>Resume held bill</span></div>
            <div class="shortcut-item"><kbd>F8</kbd><span>Payment field</span></div>
            <div class="shortcut-item"><kbd>F9</kbd><span>Complete sale</span></div>
            <div class="shortcut-item"><kbd>F10</kbd><span>Show/hide shortcuts</span></div>
            <div class="shortcut-item"><kbd>Esc</kbd><span>Close help panel</span></div>
        </div>
    </div>
</div>

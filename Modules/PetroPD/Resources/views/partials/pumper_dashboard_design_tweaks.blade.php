<style>
/* S364 Pumper Dashboard design/font adjustments - design only */
.petropd-pumper-ui .btn,
.pumper-dashboard-tabs .btn,
.content-header .btn.pull-right,
.content-header a.btn,
a#dashboard_btn,
a#logout_btn,
a#update_passcode_btn,
.toggle-fullscreen {
    border-radius: 8px !important;
    padding: 11px 18px !important;
    min-height: 40px !important;
    font-size: 110% !important;
    font-weight: 700 !important;
    line-height: 1.15 !important;
}

.pd-pumper-top-tabs .btn,
.content-header > a.btn.pull-right,
.content-header .btn-modal,
.content-header .btn-info,
.content-header .btn-danger,
.content-header .btn-warning,
.content-header .btn-primary {
    transform: scale(1.15);
    transform-origin: right center;
    margin-left: 12px !important;
    margin-bottom: 8px !important;
}

.content-header h1,
.content-header h2,
.content-header h3,
.content-header h4,
.pumper-dashboard-heading,
.pumper-dashboard-shift,
.pumper-dashboard-welcome {
    font-size: 110% !important;
}

.pumper-login-card h1:first-child,
.pumper-login-welcome {
    text-align: center !important;
    width: 100% !important;
}

.pumper-login-card #key_pad button,
#key_pad button {
    font-size: 110% !important;
    font-weight: 700 !important;
}

.pumper-login-card input#passcode,
.pumper-login-card #check_password_btn,
#check_password_btn {
    font-size: 110% !important;
    min-height: 39px !important;
}

.pd-closed-pump-card,
.closed-pump-card,
.pump-card.closed,
.daily-pump-card.closed {
    background: #0f4ea0 !important;
    color: #fff !important;
    border-bottom: 5px solid #3b0066 !important;
}

.pd-closed-pump-card .closed,
.closed-pump-card .closed,
.pump-card.closed .closed,
.daily-pump-card.closed .closed,
.pump-status-closed {
    background: #4b006e !important;
    color: #fff !important;
    border-radius: 999px !important;
    padding: 3px 13px !important;
    font-weight: 800 !important;
}

.pd-pumper-back-btn,
a.back-btn,
.btn-back,
a[href*="dashboard"] .back {
    background: #0f4ea0 !important;
    color: #fff !important;
    border-radius: 8px !important;
    padding: 12px 20px !important;
    font-size: 110% !important;
    font-weight: 800 !important;
}

/* Other Sales calculator/keypad and green enter arrow */
.other-sales-keypad button,
#key_pad .btn-success,
.calculate_total,
#calculate_total_btn,
button[id="enter"] {
    font-size: 110% !important;
    font-weight: 800 !important;
}

#key_pad button:not(.btn-danger):not(.btn-success),
.calcBG button:not(.btn-danger):not(.btn-success) {
    background: #2f80d0 !important;
    color: #fff !important;
}

#key_pad .btn-success,
.calcBG .btn-success,
button[id="enter"] {
    background: #16a34a !important;
    color: #fff !important;
}

#key_pad .btn-danger,
.calcBG .btn-danger,
button[id="backspace"] {
    background: #ef4444 !important;
    color: #fff !important;
}

@media (max-width: 991px) {
    .content-header > a.btn.pull-right,
    .content-header .btn-modal,
    .content-header .btn-info,
    .content-header .btn-danger,
    .content-header .btn-warning,
    .content-header .btn-primary {
        transform: none;
        width: auto !important;
        display: inline-block !important;
        float: none !important;
    }
}
</style>

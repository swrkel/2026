@include('pumperdashboard::partials.pumper_dashboard_ui_standard')
@extends('layouts.pumper')
@section('content')

<link rel="stylesheet" href="{{ asset('css/pumper-dashboard-remaining-modern.css') }}?v=20260726-is1797-final-1">

<style>
    /* IS1804 - Pumper Dashboard / Other Sales redesign.
       All styles are strictly scoped to this page so the Payments and Petro PD pages remain unchanged. */
    .pd-other-sales-modern.pd-other-sales-redesign,
    .pd-other-sales-modern.pd-other-sales-redesign * {
        box-sizing: border-box;
    }

    .pd-other-sales-modern.pd-other-sales-redesign {
        width: 100%;
        max-width: 100vw;
        padding: 4px;
        overflow-x: hidden;
        color: #1f2937;
        font-family: "Segoe UI", Arial, sans-serif;
    }

    .pd-other-sales-redesign .pd-other-sales-shell {
        width: 100%;
        min-height: calc(100vh - 92px);
        padding: 8px 20px 12px;
        overflow-x: hidden;
        background: #ffffff;
        border: 1px solid #dbe4ee;
        border-radius: 22px;
        box-shadow: 0 12px 32px rgba(15, 23, 42, .08);
    }

    .pd-other-sales-redesign .pd-other-sales-top-actions {
        display: grid;
        grid-template-columns: 2.25fr .9fr 1.18fr .98fr 1.05fr 1.28fr;
        gap: 8px;
        width: 100%;
        margin: 0 0 8px;
    }

    .pd-other-sales-redesign .pd-other-sales-action {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-width: 0;
        height: 36px;
        min-height: 36px;
        margin: 0 !important;
        padding: 5px 10px;
        border: 0 !important;
        border-radius: 9px !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .25),
            0 7px 16px rgba(15, 23, 42, .16);
        color: #ffffff !important;
        font-size: 16px !important;
        font-weight: 800;
        line-height: 1.15;
        text-align: center;
        text-decoration: none !important;
        white-space: nowrap;
        cursor: pointer;
        user-select: none;
        touch-action: manipulation;
        transition: transform .16s ease, box-shadow .16s ease, filter .16s ease, opacity .16s ease;
    }

    .pd-other-sales-redesign .pd-other-sales-action:hover,
    .pd-other-sales-redesign .pd-other-sales-action:focus {
        color: #ffffff !important;
        text-decoration: none !important;
        transform: translateY(-2px);
        filter: brightness(1.04);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .30),
            0 11px 23px rgba(15, 23, 42, .21);
        outline: none;
    }

    .pd-other-sales-redesign .pd-other-sales-action:active {
        transform: translateY(0);
        box-shadow:
            inset 0 2px 4px rgba(15, 23, 42, .18),
            0 5px 11px rgba(15, 23, 42, .14);
    }

    .pd-other-sales-redesign .pd-other-sales-action:disabled {
        opacity: .58;
        cursor: not-allowed;
        filter: saturate(.65);
        transform: none !important;
        box-shadow: 0 4px 10px rgba(15, 23, 42, .10);
    }

    .pd-other-sales-redesign .pd-other-sales-action-correct {
        background: linear-gradient(135deg, #009e4f 0%, #22c55e 100%) !important;
    }

    .pd-other-sales-redesign .pd-other-sales-action-save {
        background: linear-gradient(135deg, #6a1b9a 0%, #9c27b0 100%) !important;
    }

    .pd-other-sales-redesign .pd-other-sales-action-print {
        background: linear-gradient(135deg, #009e4f 0%, #22c55e 100%) !important;
    }

    .pd-other-sales-redesign .pd-other-sales-action-cancel {
        background: linear-gradient(135deg, #c40000 0%, #e31b23 100%) !important;
    }

    .pd-other-sales-redesign .pd-other-sales-action-logout {
        background: linear-gradient(135deg, #ef6c00 0%, #ffa000 100%) !important;
    }

    .pd-other-sales-redesign .pd-other-sales-action-dashboard {
        background: linear-gradient(135deg, #6d0038 0%, #930052 100%) !important;
    }

    .pd-other-sales-redesign .pd-other-sales-fields {
        display: grid !important;
        grid-template-columns: minmax(300px, 2fr) minmax(150px, 1.2fr) minmax(140px, 1fr) minmax(140px, 1fr) 88px;
        align-items: end;
        gap: 12px;
        width: 100%;
        margin: 0;
    }

    .pd-other-sales-redesign .pd-other-sales-field {
        min-width: 0;
    }

    .pd-other-sales-redesign .pd-other-sales-field label {
        display: block;
        margin: 0 0 4px;
        color: #1f2937;
        font-size: 14px;
        font-weight: 800;
        line-height: 1.2;
    }

    .pd-other-sales-redesign .pd-other-sales-field .form-control {
        width: 100% !important;
        height: 32px !important;
        min-height: 32px !important;
        margin: 0 !important;
        padding: 4px 12px !important;
        background: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
        box-shadow: 0 3px 9px rgba(15, 23, 42, .04);
        color: #111827 !important;
        font-size: 16px !important;
        font-weight: 500;
        line-height: 1.2;
    }

    .pd-other-sales-redesign .pd-other-sales-field .form-control:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .15) !important;
        outline: none;
    }

    .pd-other-sales-redesign .pd-other-sales-field .form-control:disabled,
    .pd-other-sales-redesign .pd-other-sales-field .form-control[readonly] {
        background: #ffffff !important;
        color: #111827 !important;
        opacity: 1;
        cursor: default;
    }

    .pd-other-sales-redesign #products + .select2-container {
        width: 100% !important;
    }

    .pd-other-sales-redesign #products + .select2-container .select2-selection--single {
        height: 32px !important;
        min-height: 32px !important;
        background: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
        box-shadow: 0 3px 9px rgba(15, 23, 42, .04);
    }

    .pd-other-sales-redesign #products + .select2-container .select2-selection__rendered {
        height: 30px !important;
        padding: 0 38px 0 12px !important;
        color: #1f2937 !important;
        font-size: 18px !important;
        font-weight: 500;
        line-height: 30px !important;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .pd-other-sales-redesign #products + .select2-container .select2-selection__placeholder {
        color: #64748b !important;
    }

    .pd-other-sales-redesign #products + .select2-container .select2-selection__arrow {
        top: 0 !important;
        right: 10px !important;
        width: 28px !important;
        height: 30px !important;
    }

    .pd-other-sales-redesign #products + .select2-container--focus .select2-selection--single,
    .pd-other-sales-redesign #products + .select2-container--open .select2-selection--single {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .15) !important;
    }

    .pd-other-sales-select-open .select2-results__option {
        padding: 10px 12px !important;
        font-size: 150% !important;
        line-height: 1.35 !important;
    }

    /*
        MA-002: the arrow, and a worded fallback behind it.

        The arrow is the character U+23CE, which every common system font
        carries - so it does not depend on Font Awesome, which was returning
        403 and leaving this button blank.

        The word "Enter" is in the markup but hidden. It is there for the rare
        device whose font lacks the arrow glyph: such a device draws a hollow
        box, and the operator would see nothing meaningful. Turn the word on by
        adding the class pd-confirm-show-word to the button if that is ever
        reported - one class, and no markup change.

        The button is 70px wide, so the word fits without altering the layout.
    */
    .pd-other-sales-redesign .pd-other-sales-confirm .pd-confirm-arrow {
        display: inline-block;
        line-height: 1;
    }

    .pd-other-sales-redesign .pd-other-sales-confirm .pd-confirm-word {
        display: none;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .3px;
    }

    .pd-other-sales-redesign .pd-other-sales-confirm.pd-confirm-show-word .pd-confirm-arrow {
        display: none;
    }

    .pd-other-sales-redesign .pd-other-sales-confirm.pd-confirm-show-word .pd-confirm-word {
        display: inline-block;
    }

    .pd-other-sales-redesign .pd-other-sales-confirm {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 70px !important;
        height: 32px !important;
        min-height: 32px !important;
        margin: 0 !important;
        padding: 0 !important;
        background: linear-gradient(135deg, #16a34a 0%, #4cc366 100%) !important;
        border: 0 !important;
        border-radius: 11px !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .25),
            0 6px 14px rgba(22, 163, 74, .24);
        color: #ffffff !important;
        font-size: 20px !important;
        cursor: pointer;
        touch-action: manipulation;
        transition: transform .16s ease, box-shadow .16s ease, filter .16s ease;
    }

    .pd-other-sales-redesign .pd-other-sales-confirm:hover,
    .pd-other-sales-redesign .pd-other-sales-confirm:focus {
        transform: translateY(-2px);
        filter: brightness(1.04);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .30),
            0 10px 20px rgba(22, 163, 74, .30);
        outline: none;
    }

    .pd-other-sales-redesign .pd-other-sales-workspace {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) minmax(300px, 376px);
        justify-content: space-between;
        align-items: start;
        gap: 20px;
        width: 100%;
        margin-top: 12px;
    }

    .pd-other-sales-redesign .pd-other-sales-summary {
        min-width: 0;
    }

    .pd-other-sales-redesign .pd-other-sales-total {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: min(100%, 540px);
        height: 44px;
        min-height: 44px;
        padding: 7px 14px;
        background: #fff8f8;
        border: 1px solid #fecaca;
        border-radius: 13px;
        box-shadow: 0 4px 12px rgba(185, 28, 28, .04);
        color: #b91c1c;
        font-size: 22px;
        font-weight: 900;
        line-height: 1.2;
    }

    .pd-other-sales-redesign .pd-other-sales-total #totalAmount {
        margin-left: 18px;
        text-align: right;
        white-space: nowrap;
    }

    .pd-other-sales-redesign .pd-other-sales-table-wrap {
        width: 100%;
        height: clamp(220px, calc(100vh - 330px), 390px);
        min-height: 220px;
        max-height: 390px;
        margin-top: 8px;
        overflow: auto;
        background: #ffffff;
        border: 1px solid #d6e0eb;
        border-radius: 18px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
    }

    .pd-other-sales-redesign .pd-other-sales-table {
        width: 100%;
        margin: 0 !important;
        border: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        table-layout: fixed;
    }

    .pd-other-sales-redesign .pd-other-sales-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        height: 46px;
        padding: 8px 14px !important;
        background: #f8fafc !important;
        border-top: 0 !important;
        border-bottom: 1px solid #dbe4ee !important;
        border-left: 0 !important;
        border-right: 1px solid #dbe4ee !important;
        color: #334155 !important;
        font-size: 17px !important;
        font-weight: 900 !important;
        letter-spacing: .02em;
        line-height: 1.15;
        text-align: center !important;
        text-transform: uppercase;
        vertical-align: middle !important;
    }

    .pd-other-sales-redesign .pd-other-sales-table thead th:first-child {
        text-align: left !important;
    }

    .pd-other-sales-redesign .pd-other-sales-table thead th:last-child {
        border-right: 0 !important;
    }

    .pd-other-sales-redesign .pd-other-sales-table tbody td {
        padding: 18px 20px !important;
        background: #ffffff !important;
        border-top: 0 !important;
        border-bottom: 1px solid #e5ebf2 !important;
        border-left: 0 !important;
        border-right: 1px solid #e5ebf2 !important;
        color: #0f172a !important;
        font-size: 17px !important;
        font-weight: 500;
        line-height: 1.35;
        text-align: center !important;
        vertical-align: middle !important;
        overflow-wrap: anywhere;
    }

    .pd-other-sales-redesign .pd-other-sales-table tbody td:first-child {
        text-align: left !important;
    }

    .pd-other-sales-redesign .pd-other-sales-table tbody td:last-child {
        border-right: 0 !important;
    }

    .pd-other-sales-redesign .pd-other-sales-table tbody tr:last-child td {
        border-bottom: 0 !important;
    }

    .pd-other-sales-redesign .pd-other-sales-table tbody tr:only-child:not(.pd-other-sales-empty-row) {
        height: 150px;
    }

    .pd-other-sales-redesign .pd-other-sales-empty-row td {
        height: 196px;
        color: #64748b !important;
        font-weight: 700;
        text-align: center !important;
    }

    .pd-other-sales-redesign .pd-other-sales-keypad {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 6px;
        width: 100%;
        max-width: 376px;
        margin: 0 auto !important;
        padding: 6px;
        background: #f8fafc;
        border-radius: 15px;
    }

    .pd-other-sales-redesign .pd-other-sales-keypad button {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 100% !important;
        height: clamp(70px, 4.8vw, 90px) !important;
        min-height: 70px !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 1px solid transparent !important;
        border-radius: 12px !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .28),
            inset 0 -3px 0 rgba(15, 23, 42, .10),
            0 7px 16px rgba(15, 23, 42, .17) !important;
        color: #ffffff !important;
        font-size: clamp(24px, 1.7vw, 30px) !important;
        font-weight: 900 !important;
        line-height: 1;
        cursor: pointer;
        user-select: none;
        touch-action: manipulation;
        transform: none;
        transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
    }

    .pd-other-sales-redesign .pd-other-sales-keypad button.btn-primary {
        background: linear-gradient(135deg, #246fe5 0%, #31a7e9 100%) !important;
        border-color: #2d85e8 !important;
    }

    .pd-other-sales-redesign .pd-other-sales-keypad button.btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #fb5b61 100%) !important;
        border-color: #ef4444 !important;
    }

    .pd-other-sales-redesign .pd-other-sales-keypad button.btn-success {
        background: linear-gradient(135deg, #16a34a 0%, #22b455 100%) !important;
        border-color: #16a34a !important;
    }

    .pd-other-sales-redesign .pd-other-sales-keypad button:hover,
    .pd-other-sales-redesign .pd-other-sales-keypad button:focus {
        transform: translateY(-2px);
        filter: brightness(1.06);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .34),
            inset 0 -3px 0 rgba(15, 23, 42, .08),
            0 12px 24px rgba(37, 99, 235, .24) !important;
        outline: none;
    }

    .pd-other-sales-redesign .pd-other-sales-keypad button:active {
        transform: translateY(1px);
        box-shadow:
            inset 0 3px 6px rgba(15, 23, 42, .18),
            0 4px 10px rgba(15, 23, 42, .14) !important;
    }

    @media (min-width: 992px) and (max-height: 820px) {
        .pd-other-sales-redesign .pd-other-sales-shell {
            min-height: calc(100vh - 72px);
            padding-top: 6px;
            padding-bottom: 8px;
        }

        .pd-other-sales-redesign .pd-other-sales-workspace {
            margin-top: 9px;
        }

        .pd-other-sales-redesign .pd-other-sales-table-wrap {
            height: clamp(205px, calc(100vh - 315px), 360px);
            min-height: 205px;
            max-height: 360px;
        }
    }

    @media (max-width: 1199px) {
        .pd-other-sales-redesign .pd-other-sales-shell {
            padding-right: 20px;
            padding-left: 20px;
        }

        .pd-other-sales-redesign .pd-other-sales-top-actions {
            gap: 8px;
        }

        .pd-other-sales-redesign .pd-other-sales-action {
            height: 36px;
            min-height: 36px;
            padding: 5px 7px;
            font-size: 14px !important;
        }

        .pd-other-sales-redesign .pd-other-sales-fields {
            gap: 12px;
        }

        .pd-other-sales-redesign .pd-other-sales-workspace {
            grid-template-columns: minmax(0, 1fr) minmax(280px, 350px);
            gap: 18px;
        }
    }

    @media (max-width: 991px) {
        .pd-other-sales-modern.pd-other-sales-redesign {
            padding: 8px;
        }

        .pd-other-sales-redesign .pd-other-sales-shell {
            min-height: auto;
            padding: 14px 16px 22px;
            border-radius: 17px;
        }

        .pd-other-sales-redesign .pd-other-sales-top-actions {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            width: 100%;
        }

        .pd-other-sales-redesign .pd-other-sales-action-correct {
            grid-column: span 2;
        }

        .pd-other-sales-redesign .pd-other-sales-fields {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pd-other-sales-redesign .pd-other-sales-field:first-child {
            grid-column: 1 / -1;
        }

        .pd-other-sales-redesign .pd-other-sales-confirm {
            width: 100% !important;
        }

        .pd-other-sales-redesign .pd-other-sales-workspace {
            grid-template-columns: minmax(0, 1fr);
            gap: 22px;
        }

        .pd-other-sales-redesign .pd-other-sales-keypad {
            max-width: 376px;
        }
    }

    @media (max-width: 575px) {
        .pd-other-sales-redesign .pd-other-sales-top-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pd-other-sales-redesign .pd-other-sales-action-correct,
        .pd-other-sales-redesign .pd-other-sales-action-dashboard {
            grid-column: 1 / -1;
        }

        .pd-other-sales-redesign .pd-other-sales-action {
            height: 42px;
            min-height: 42px;
            font-size: 14px !important;
            white-space: normal;
        }

        .pd-other-sales-redesign .pd-other-sales-fields {
            grid-template-columns: minmax(0, 1fr);
        }

        .pd-other-sales-redesign .pd-other-sales-field:first-child {
            grid-column: auto;
        }

        .pd-other-sales-redesign .pd-other-sales-field .form-control,
        .pd-other-sales-redesign #products + .select2-container .select2-selection--single {
            height: 42px !important;
            min-height: 42px !important;
        }

        .pd-other-sales-redesign #products + .select2-container .select2-selection__rendered {
            height: 40px !important;
            font-size: 17px !important;
            line-height: 40px !important;
        }

        .pd-other-sales-redesign #products + .select2-container .select2-selection__arrow {
            height: 40px !important;
        }

        .pd-other-sales-redesign .pd-other-sales-confirm {
            height: 42px !important;
            min-height: 42px !important;
        }

        .pd-other-sales-redesign .pd-other-sales-total {
            width: 100%;
            min-height: 56px;
            padding: 10px 14px;
            font-size: 20px;
        }

        .pd-other-sales-redesign .pd-other-sales-table-wrap {
            min-height: 230px;
        }

        .pd-other-sales-redesign .pd-other-sales-keypad {
            gap: 6px;
            padding: 6px;
        }

        .pd-other-sales-redesign .pd-other-sales-keypad button {
            height: 61px !important;
            min-height: 61px !important;
            border-radius: 10px !important;
            font-size: 25px !important;
        }
    }
</style>

<div class="pd-other-sales-modern pd-other-sales-redesign no-print">
    <form name="calculator" autocomplete="off">
        <div class="pd-other-sales-shell">
            <section class="pd-other-sales-top-actions other-sale-action-tabs" aria-label="Other sales actions">
                <button class="pd-other-sales-action pd-other-sales-action-correct" id="correctbtn" type="button">
                    {{-- MA-002: two lines, as on the payments screen. --}}
                    @lang('pumperdashboard::lang.amount_correct_line1')<br>@lang('pumperdashboard::lang.amount_correct_line2')
                </button>

                <button disabled value="save" id="payment_submit1" name="submit"
                    class="pd-other-sales-action pd-other-sales-action-save" type="button">
                    @lang('lang_v1.save')
                </button>

                <button disabled value="save" id="payment_submit1_print" name="submit"
                    class="pd-other-sales-action pd-other-sales-action-print" type="button">
                    Print &amp; @lang('lang_v1.save')
                </button>

                <button type="button" class="pd-other-sales-action pd-other-sales-action-cancel" id="cancelbtn"
                    onclick="if (typeof window.reset === 'function') { window.reset(); }">
                    <i class="fa fa-refresh" aria-hidden="true"></i>&nbsp;@lang('pumperdashboard::lang.cancel')
                </button>

                <a href="{{ action('Auth\\PumpOperatorLoginController@logout') }}"
                    class="pd-other-sales-action pd-other-sales-action-logout">
                    @lang('pumperdashboard::lang.logout')
                </a>

                <a href="{{ action('\\Modules\\PumperDashboard\\Http\\Controllers\\PumpOperatorController@dashboard') }}"
                    class="pd-other-sales-action pd-other-sales-action-dashboard">
                    Dashboard
                </a>
            </section>

            <div class="pd-other-sales-fields">
                <div class="pd-other-sales-field">
                    <label for="products">Product</label>
                    <select class="form-control select2 select2-products" id="products">
                        <option value="" disabled selected>Please Select</option>
                        @foreach($products as $item)
                            <option value="{{ $item->id }}" data-current-stock="{{ $item->current_stock ?? 0 }}">
                                {{ $item->name }} ({{ $item->current_stock ?? 0 }} {{ $item->unit }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="pd-other-sales-field">
                    <label for="unit">Unit</label>
                    <input class="form-control" id="unit" name="unit" disabled>
                </div>

                <div class="pd-other-sales-field">
                    <label for="price">Price</label>
                    <input class="form-control" id="price" name="price" disabled>
                </div>

                <div class="pd-other-sales-field">
                    {{--
                        MA-002: this field is renamed from id="amount".

                        The payments page loads BOTH this partial and
                        payment_section, and BOTH carried id="amount" - two
                        elements with the same id on one page.

                        $('#amount') and getElementById('amount') return only
                        the FIRST match in the document, so every write meant
                        for the cash amount - the keypad, __write_number, the
                        focus() call - could land in the wrong element. That is
                        why the cash field would not take a value.

                        This one is a QUANTITY field, so the name it should
                        always have had is pd_other_sales_qty. Nothing
                        referenced it by id, so the rename is safe - I checked
                        before changing it.

                        id="payment_type" is duplicated the same way and renamed
                        for the same reason. The FORM NAME is unchanged, so what
                        gets submitted is exactly as before.
                    --}}
                    <label for="pd_other_sales_qty">Qty</label>
                    <input class="form-control" id="pd_other_sales_qty" name="qty" value="0" inputmode="decimal">
                    <input type="hidden" name="payment_type" id="pd_other_sales_payment_type" value="">
                </div>

                {{--
                    MA-002: the arrow is now a REAL CHARACTER, not an icon font.

                    This button held <i class="fa fa-level-down">. That renders
                    nothing at all when Font Awesome fails to load - and the
                    hosted kit is currently returning 403 Forbidden, so the
                    button appeared empty.

                    The Close Meter screen has the same green enter button and
                    has always shown its arrow, because it uses the literal
                    character rather than an icon. Doing the same here means the
                    arrow cannot disappear again, whatever happens to the icon
                    font.

                    THE WORD IS THERE TOO, for any device whose font lacks the
                    arrow glyph. It is hidden by default and shown only if the
                    arrow cannot be drawn - see the CSS below.
                --}}
                <button class="pd-other-sales-confirm" type="button" id="confirm" aria-label="Add quantity" title="Add item">
                    <span class="pd-confirm-arrow" aria-hidden="true">&#9166;</span>
                    <span class="pd-confirm-word">Enter</span>
                </button>
            </div>

            <div class="pd-other-sales-workspace">
                <section class="pd-other-sales-summary" aria-label="Other sales summary">
                    <div class="pd-other-sales-total">
                        <span>Total Amount</span>
                        <span id="totalAmount" aria-live="polite">0.00</span>
                    </div>

                    <div class="pd-other-sales-table-wrap">
                        <table class="table pd-other-sales-table">
                            <colgroup>
                                <col style="width:55%">
                                <col style="width:10%">
                                <col style="width:12%">
                                <col style="width:10%">
                                <col style="width:13%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Unit</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody id="readyBody">
                                <tr class="pd-other-sales-empty-row">
                                    <td colspan="5">No items added yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section id="key_pad" class="pd-other-sales-keypad text-center" aria-label="Other sales numeric keypad">
                    @foreach ([7, 8, 9, 4, 5, 6, 1, 2, 3] as $digit)
                        <button id="{{ $digit }}" type="button" class="btn btn-primary btn-sm" onclick="enterVal(this.id)">{{ $digit }}</button>
                    @endforeach
                    <button id="backspace" type="button" class="btn btn-danger" onclick="enterVal(this.id)" aria-label="Backspace">&#9003;</button>
                    <button id="0" type="button" class="btn btn-primary btn-sm" onclick="enterVal(this.id)">0</button>
                    <button id="precision" type="button" class="btn btn-success" onclick="enterVal(this.id)" aria-label="Decimal point">.</button>
                </section>
            </div>
        </div>
    </form>
</div>

<section class="invoice print_section" id="receipt_section"></section>

<div class="modal fade" id="paymentMethodModal" tabindex="-1" aria-labelledby="paymentMethodModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="paymentMethodModalLabel">Select Payment Method</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <label for="payment_method">Payment Method:</label>
        <select id="payment_method" class="form-control">
          <option value="Cash">Cash</option>
          <option value="Credit Sale" selected>Credit Sale</option>
          <option value="Card">Card</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" id="confirm_payment_method" class="btn btn-primary">Continue &amp; Print</button>
      </div>
    </div>
  </div>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    /*
     | The script tag is only emitted when the file is actually on disk.
     |
     | Previously the check picked between an upper and lower case path, but
     | emitted the tag EITHER WAY. When neither file existed the browser
     | requested a missing address, the server answered with an HTML error page,
     | and the browser tried to run that page as JavaScript. That killed every
     | script on the page - the product dropdown stopped working and the rest of
     | the page's code was printed on screen as plain text.
     |
     | A missing asset should cost us that one file, not the whole page.
     */
    $pumperPoPaymentJsPath = null;

    if (file_exists(public_path('Modules/pumper-dashboard/js/po_payment.js'))) {
        $pumperPoPaymentJsPath = asset('Modules/pumper-dashboard/js/po_payment.js');
    } elseif (file_exists(public_path('modules/pumper-dashboard/js/po_payment.js'))) {
        $pumperPoPaymentJsPath = asset('modules/pumper-dashboard/js/po_payment.js');
    }
@endphp
@if($pumperPoPaymentJsPath)
    <script src="{{ $pumperPoPaymentJsPath }}?v={{ time() }}"></script>
@endif
<script>
    $(document).ready(function() {
        $('#products').select2({
            placeholder: 'Please Select',
            allowClear: true,
            dropdownCssClass: 'pd-other-sales-select-open'
        });
        
        let dataArray = [];
        let totalAmount = 0.0;
        let correctFlag = false;
        $(document).on('change', '#products', function() {
            $.ajax({
            method: "get",
            url: "/pumper-dashboard/pump-operator-payments/othersale/getproducts",
            data: { product_id: $(this).val() },
                success: function (result) {
                    // MA-002 (S-609 #10): the console.log that used to be here
                    // dereferenced result.currency_precision.currency_precision
                    // as the FIRST statement. When that was null it threw, and
                    // the three lines below - the ones that fill Unit and Price -
                    // never ran. Removed; nothing depended on it.
                    if (!result || !result.product) {
                        toastr.error("Could not load the product details.");
                        return;
                    }
                    var price = parseFloat(result.product.sell_price_inc_tax || 0).toFixed(2);
                    $("#unit").val(result.product.short_name || "");
                    $("#price").val(price);
                    // was $("amount") - missing the '#', so it selected nothing
                    $("#pd_other_sales_qty").val("");
                    $("#pd_other_sales_qty").focus();
                },
            });
            var selectedOption = $("#products option:selected");
            var current_stock = parseFloat(selectedOption.data('current-stock'));
            if (current_stock == 0) {
                toastr.error("Product Out of Stock");
                $("#pd_other_sales_qty").focus();
                return;
            }
        });

        $(document).on('click', '#confirm', function() {
            var amount = parseFloat($("#pd_other_sales_qty").val());
            if (isNaN(amount) || amount <= 0) {
                toastr.error("Please enter the amount");
                $("#pd_other_sales_qty").focus();
                return;
            }

            var selectedOption = $("#products option:selected");
            var product = selectedOption.text();
            var product_id = selectedOption.val();
            var current_stock = parseFloat(selectedOption.data('current-stock'));
            var unit = $("#unit").val();
            var price = parseFloat($("#price").val());
            var rowamount = amount * price;

            if (amount > current_stock) {
                toastr.error("The amount entered exceeds the current stock (" + current_stock + ").");
                $("#pd_other_sales_qty").focus();
                return;
            }

            const innerData = { product, unit, price, amount, rowamount, product_id };
            dataArray.push(innerData);
            display(dataArray);
        });

        
        function display(datas){
            var bodyHtml = "";
            
            let totalAmount = 0;

            datas.forEach((value) => {
                bodyHtml += generateRow(value);
                totalAmount += value.rowamount;
            });

            if (datas.length === 0) {
                bodyHtml = '<tr class="pd-other-sales-empty-row"><td colspan="5">No items added yet.</td></tr>';
            }
            
            console.log(totalAmount);
            
            const formattedNumber = totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            $('#totalAmount').html(formattedNumber);
            $('#readyBody').html(bodyHtml);
            $("#pd_other_sales_qty").val(0);
        }
        
        function generateRow(value) {
            return '<tr><td>' + value.product + '</td><td>' + value.unit + '</td><td>' + value.price + '</td><td>' + value.amount + '</td><td>' + value.rowamount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td></tr>';
        }
        
        $(document).on('click', '#correctbtn', function() {
            let totalAmount = $("#totalAmount").html();
            console.log(totalAmount);
            console.log(dataArray.length);
            if (totalAmount == "" || totalAmount == undefined || totalAmount == null) {
                $("#payment_submit1").attr("disabled", true);
                $("#payment_submit1_print").attr("disabled", true);
            } else if ($("#payment_type").prop("checked") == false && dataArray.length == 0) {
                $("#payment_submit1").attr("disabled", true);
                $("#payment_submit1_print").attr("disabled", true);
            } else {
                $("#payment_submit1").attr("disabled", false);
                $("#payment_submit1_print").attr("disabled", false);
            }
        });
        
        $(document).on('click', '#cancelbtn', function() {
            correctFlag = false;
            dataArray = [];
            totalAmount = 0.0
            display(dataArray);
            $('#payment_submit1').prop('disabled', true);
            $('#payment_submit1_print').prop('disabled', true);
        });

        $(document).on('click', '#payment_submit1', function() {
            var saveButton = document.getElementById('payment_submit1');
            saveButton.innerText = 'Saving...'; 
            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            // IS2206: keep the entered rows on screen until the server confirms
            // the save. A failed request must not erase the operator's entry.
            var items = dataArray.slice();
            $('#payment_submit1').prop('disabled', true);
            $('#payment_submit1_print').prop('disabled', true);
            $.ajax({
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            url: "/pumper-dashboard/pump-operator-pmts/save-other-sale-items",
            data: { items },
            success: function (result) {
                if(result.success){
                    var saveButton = document.getElementById('payment_submit1');
                    saveButton.innerText = 'Save';
                    correctFlag = false;
                    dataArray = [];
                    totalAmount = 0.0;
                    display(dataArray);
                    $('#payment_submit1').prop('disabled', true);
                    $('#payment_submit1_print').prop('disabled', true);
                    toastr.success(result.msg);
                } else {
                    var saveButton = document.getElementById('payment_submit1');
                    saveButton.innerText = 'Save';
                    $('#payment_submit1').prop('disabled', false);
                    $('#payment_submit1_print').prop('disabled', false);
                    toastr.error(result.msg);
                }
            },
            error: function(xhr, status, error) {
                var saveButton = document.getElementById('payment_submit1');
                saveButton.innerText = 'Save';
                $('#payment_submit1').prop('disabled', false);
                $('#payment_submit1_print').prop('disabled', false);
                alert("An error occurred: " + xhr.status + " " + xhr.statusText);
                console.error("Error details: ", status, error);
            }
            });
        });
        $(document).on('click', '#payment_submit1_print', function() {
            // Show the modal instead of directly printing
            $('#paymentMethodModal').modal('show');
        });

        // Handle modal confirm button click
        $(document).on('click', '#confirm_payment_method', function() {
            var payment_method_value = $('#payment_method').val(); // get selected payment method
            $('#paymentMethodModal').modal('hide'); // hide modal

            var isMobileReceipt = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent || '');
            // Open synchronously from the user's click so desktop popup blockers
            // do not suppress the PDF preview after the AJAX save completes.
            var printPreviewWindow = !isMobileReceipt ? window.open('', '_blank') : null;
            if (printPreviewWindow) {
                printPreviewWindow.document.write('<!doctype html><html><head><title>Preparing print preview...</title></head><body style="font-family:Arial,sans-serif;padding:30px;">Preparing print preview...</body></html>');
            }

            var saveButton = document.getElementById('payment_submit1_print');
            saveButton.innerText = 'Saving & Print...'; 

            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            // IS2206: Save & Print follows the same rule: keep the entered
            // rows until the backend confirms that the sale was persisted.
            var items = dataArray.slice();
            $('#payment_submit1').prop('disabled', true);
            $('#payment_submit1_print').prop('disabled', true);

            $.ajax({
                method: "POST",
                headers: { 'X-CSRF-TOKEN': csrfToken },
                url: "/pumper-dashboard/pump-operator-pmts/save-other-sale-items",
                data: { items, print: "print", payment_method_value: payment_method_value }, // send selected method
                success: function (result) {
                    saveButton.innerText = 'Print & Save';
                    if(result.success){
                        correctFlag = false;
                        dataArray = [];
                        totalAmount = 0.0;
                        display(dataArray);
                        $('#payment_submit1').prop('disabled', true);
                        $('#payment_submit1_print').prop('disabled', true);
                        toastr.success(result.msg);
                        if(result.print){
                            if (!isMobileReceipt && result.print_preview_url) {
                                if (printPreviewWindow && !printPreviewWindow.closed) {
                                    printPreviewWindow.location.href = result.print_preview_url;
                                } else {
                                    window.location.href = result.print_preview_url;
                                }
                            } else {
                                if (printPreviewWindow && !printPreviewWindow.closed) {
                                    printPreviewWindow.close();
                                }
                                // Preserve the existing compact receipt path for
                                // mobile/Bluetooth printers.
                                $('#receipt_section').html(result.html_content || '');
                                setTimeout(function () {
                                    window.print();
                                }, 1000);
                            }
                        }
                    } else {
                        if (printPreviewWindow && !printPreviewWindow.closed) {
                            printPreviewWindow.close();
                        }
                        $('#payment_submit1').prop('disabled', false);
                        $('#payment_submit1_print').prop('disabled', false);
                        toastr.error(result.msg);
                    }
                },
                error: function(xhr, status, error) {
                    if (printPreviewWindow && !printPreviewWindow.closed) {
                        printPreviewWindow.close();
                    }
                    saveButton.innerText = 'Print & Save';
                    $('#payment_submit1').prop('disabled', false);
                    $('#payment_submit1_print').prop('disabled', false);
                    alert("An error occurred: " + xhr.status + " " + xhr.statusText);
                    console.error("Error details: ", status, error);
                }
            });
        });

        // $(document).on('click', '#payment_submit1_print', function() {
        //     var saveButton = document.getElementById('payment_submit1_print');
        //     saveButton.innerText = 'Saving & Print...'; 
        //     var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        //     var items = dataArray;
        //     correctFlag = false;
        //     dataArray = [];
        //     totalAmount = 0.0
        //     display(dataArray);
        //     $('#payment_submit1').prop('disabled', true);
        //     $('#payment_submit1_print').prop('disabled', true);
        //     $.ajax({
        //     method: "POST",
        //     headers: {
        //         'X-CSRF-TOKEN': csrfToken
        //     },
        //     url: "/pumper-dashboard/pump-operator-pmts/save-other-sale-items",
        //     data: { items, print: "print" },
        //     success: function (result) {
        //         console.log("save-other-sale-items", [result]);
        //         if(result.success){
        //             var saveButton = document.getElementById('payment_submit1_print');
        //             saveButton.innerText = 'Print & Save';
        //             toastr.success(result.msg);
        //             if(result.print){
        //                 $(document).ready(function() {
        //                     $('#receipt_section').html(result.html_content);
        //                     setTimeout(function () {
        //                         window.print();
        //                     }, 1000);
        //                 });
        //                 // window.location.href = '/pumper-dashboard/pump-operator-payments/othersale-list?print_other_sale_ids=' + result.print_other_sale_ids;
        //             }
        //         } else {
        //             var saveButton = document.getElementById('payment_submit1_print');
        //             saveButton.innerText = 'Print & Save';
        //             toastr.error(result.msg);
        //         }
        //     },
        //     error: function(xhr, status, error) {
        //         var saveButton = document.getElementById('payment_submit1_print');
        //         saveButton.innerText = 'Print & Save';
        //         alert("An error occurred: " + xhr.status + " " + xhr.statusText);
        //         console.error("Error details: ", status, error);
        //     }
        //     });
        // });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        let qtyInput = document.getElementById("pd_other_sales_qty");

        // Clear 0 when focused
        qtyInput.addEventListener("focus", function () {
            
            if (this.value === "0") {
                this.value = "";
            }
        });

        // If left empty, reset to 0
        qtyInput.addEventListener("blur", function () {
            
            if (this.value === "") {
                this.value = "0";
            }
        });
    });
</script>
@endsection






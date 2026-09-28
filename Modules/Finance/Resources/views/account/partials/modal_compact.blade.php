@php
    /*
     |--------------------------------------------------------------------------
     | S-627 #1/#2a/#3a: one compact size for every Finance deposit/transfer form
     |--------------------------------------------------------------------------
     |
     | The brief asks for each pop-up to be 15% smaller "so that the entire form
     | is visible on the screen without requiring the user to scroll".
     |
     | S-611 already took a first 15% off the Cash/Card form, but it did it
     | inside deposit.blade.php only, so:
     |
     |   * the Cheque Deposit form never actually got it - its rule was written
     |     against `.cheque_deposit_model`, a class that does not exist on the
     |     container (it is `.account_model`), and the dialog also carried an
     |     inline `style="width: 60%"` that a stylesheet rule cannot beat
     |     without !important;
     |   * the Transfer form was never touched at all.
     |
     | So the numbers below are the second 15% for Cash/Card and the first real
     | one for Cheque Deposit and Transfer, and they live in ONE file so the
     | three forms cannot drift apart again.
     |
     | WHY NOT `transform: scale(.85)`
     |   It blurs text on non-retina screens and it detaches the date-picker
     |   pop-up from its field, because the picker positions itself in untransformed
     |   page coordinates. The size therefore comes off the things that actually
     |   consume vertical space: dialog margin, padding, control height and type.
     |
     | The <style> block is injected with the modal body, so it is removed again
     | as soon as the container loads a different form. It cannot leak into the
     | other `.account_model` modals (Edit Account, Notes, Image).
     */
    $compactWidth    = $compactWidth ?? '72%';
    $compactMinWidth = $compactMinWidth ?? '0px';
@endphp
<style>
    .account_model .modal-dialog {
        width: {{ $compactWidth }} !important;
        max-width: 96vw !important;
        min-width: {{ $compactMinWidth }};
        margin: 14px auto !important;
    }

    .account_model .modal-header,
    .account_model .modal-footer {
        padding: 8px 13px !important;
    }

    .account_model .modal-title {
        font-size: 15px;
        font-weight: 700;
    }

    .account_model .modal-body {
        padding: 10px 13px !important;
    }

    .account_model .modal-body hr {
        margin: 8px 0 !important;
    }

    .account_model .form-group {
        margin-bottom: 7px !important;
    }

    .account_model .modal-body label {
        font-size: 11px;
        margin-bottom: 2px;
        font-weight: 700;
    }

    .account_model .modal-body .form-control,
    .account_model .modal-body .select2-selection--single {
        height: 26px;
        padding: 3px 8px;
        font-size: 11.5px;
    }

    /* Textareas must keep growing with their rows attribute. */
    .account_model .modal-body textarea.form-control {
        height: auto;
        line-height: 1.35;
    }

    .account_model .modal-body .select2-selection__rendered {
        line-height: 18px;
        font-size: 11.5px;
    }

    .account_model .modal-body .select2-selection__arrow {
        height: 24px;
    }

    .account_model .modal-body .input-group-addon {
        padding: 3px 8px;
        font-size: 11.5px;
    }

    .account_model .modal-body table th,
    .account_model .modal-body table td {
        padding: 4px 6px !important;
        font-size: 11.5px;
    }

    .account_model .modal-body .checkbox,
    .account_model .modal-body .radio {
        margin-top: 4px;
        margin-bottom: 4px;
    }

    .account_model .modal-footer .btn,
    .account_model .modal-body .btn {
        padding: 5px 13px;
        font-size: 12.5px;
    }

    /* The file input is the tallest control on the form once everything else
       has been brought down, so it gets the same treatment. */
    .account_model .modal-body input[type="file"] {
        font-size: 11.5px;
        padding: 2px 0;
    }

    /*
     * Short screens (laptops at 768px and 720px of usable height) are the ones
     * that were scrolling in the first place, so they get a further squeeze
     * rather than a scrollbar.
     */
    @media (max-height: 800px) {
        .account_model .modal-dialog {
            margin: 8px auto !important;
        }

        .account_model .modal-body {
            padding: 7px 12px !important;
        }

        .account_model .form-group {
            margin-bottom: 5px !important;
        }

        .account_model .modal-body .form-control,
        .account_model .modal-body .select2-selection--single {
            height: 24px;
            padding: 2px 8px;
        }

        .account_model .modal-body textarea.form-control {
            height: auto;
        }

        .account_model .modal-body hr {
            margin: 6px 0 !important;
        }
    }
</style>

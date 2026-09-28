{{--
 |------------------------------------------------------------------------------
 | Direct Settlement meter-sale scripts
 |------------------------------------------------------------------------------
 |
 | IS1939. This partial exists so that adding the Direct Settlement meter-sale
 | behaviour to a screen costs ONE line in that screen:
 |
 |     @include('petrodirect::settlement.partials.direct_settlement_scripts')
 |
 | placed anywhere AFTER app.js is loaded.
 |
 | WHY A PARTIAL RATHER THAN TWO SCRIPT TAGS
 |   Another developer is working in this module. Every line this change adds to
 |   a shared view is a line that can collide with theirs. One @include is the
 |   smallest possible footprint, merges cleanly, and can be read at a glance in
 |   a diff. Everything else lives in files nobody else is editing.
 |
 | WHAT IT DOES
 |   1. Declares that this page owns the Direct Settlement meter-sale handlers.
 |      The five class names those handlers bind to are shared by eight modules
 |      (PetroDirect, Petro, PetroGeneral, PetroPD, PumperDashboard,
 |      SettlementSW, Vat, EVCharging) and the handlers are delegated from
 |      document, so the script refuses to run without this flag.
 |
 |   2. Loads the single file that owns Add, Update, edit-form load, Cancel and
 |      Delete for Direct Settlement meter sales.
 |
 | app.js is deliberately NOT modified. Its older copies of these handlers are
 | still bound; the file below displaces them at runtime with .off(). That keeps
 | this whole change additive, so it cannot conflict with concurrent work in
 | app.js.
 --}}
<script>window.PETRODIRECT_DIRECT_SETTLEMENT = true;</script>
<script src="{{ route('petrodirect.assets.js', ['file' => 'direct-settlement-meter-sale.js']) }}?v={{ @filemtime(base_path('Modules/PetroDirect/Resources/assets/js/direct-settlement-meter-sale.js')) ?: 1 }}"></script>

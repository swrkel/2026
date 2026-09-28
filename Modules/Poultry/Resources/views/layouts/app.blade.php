{{--
    Poultry module layout.

    WHAT THIS USED TO BE

    A STANDALONE AdminLTE document - its own <html>, <head>, stylesheets,
    header, sidebar and sidebar-toggle. Rendering it produced TWO AdminLTE
    shells at once, which is why the Poultry menu appeared as an unstyled
    overlay on top of the real ERP sidebar, and why the page did not move when
    the sidebar was collapsed: Poultry's shell had no idea the core one existed.

    WHAT IT IS NOW

    It extends the core layout, like every other module. The module follows the
    application's conventions rather than carrying its own exception.

      - Sidebar collapse/expand comes from core. Poultry does not implement it,
        so it cannot drift from the rest of the ERP.
      - Poultry no longer draws a header or sidebar of its own. Navigation
        belongs in the main ERP sidebar, as for every other module.
      - Core yields 'css', 'content' and 'javascript', which are exactly the
        sections the 27 Poultry views already define. Those sections pass
        straight through, so NO view needed changing.

    The previous file's own comment recommended this change.
--}}

@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('modules/poultry/css/poultry.css') }}">
@endsection

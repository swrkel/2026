{{--
    The Actions menu clipping, fixed once for every SW table.

    WHAT WAS ACTUALLY CLIPPING IT

    Not `table-responsive`, which is what I assumed and scoped four separate
    fixes to. Walking up from an open menu showed the culprit:

        UL.dropdown-menu          overflow=visible
        DIV.btn-group             overflow=visible
        TD                        overflow=visible
        TR                        overflow=visible
        TBODY                     overflow=visible
        TABLE.table.dataTable     overflow=hidden   <-- here

    The TABLE element itself carries overflow:hidden, from the DataTables
    stylesheet. So rules aimed at .table-responsive never touched it, which is
    why the menu stayed clipped through four attempts.

    Fixed for SW's tables only. Overriding this estate-wide would take
    horizontal scrolling away from every table in the system to fix one
    dropdown.
--}}

@once
    @push('css')
    <style>
    .sw-table-wrap table.dataTable,
    .sw-table-wrap .table-responsive,
    .sw-table-wrap .dataTables_wrapper,
    .sw-table-wrap .dataTables_scrollBody {
        overflow: visible !important;
    }

    .sw-table-wrap .btn-group { position: static; }

    .sw-table-wrap .dropdown-menu {
        position: absolute;
        z-index: 1051;
        /* Downward. Flipping upward is what put the menu behind the export
           buttons and the search box. */
        top: auto;
        bottom: auto;
    }
    </style>
    @endpush
@endonce

<style>
    .f15-daily-page { padding: 12px 15px 30px; }
    .f15-toolbar {
        display: flex; justify-content: space-between; align-items: flex-end; gap: 16px;
        padding: 14px 16px; margin-bottom: 14px; background: #fff; border: 1px solid #e2e8f0;
        border-radius: 10px; box-shadow: 0 4px 16px rgba(15, 23, 42, .06);
    }
    .f15-toolbar-title h3 { margin: 0 0 3px; font-size: 20px; font-weight: 700; color: #172554; }
    .f15-toolbar-title span { color: #64748b; font-size: 13px; }
    .f15-toolbar-controls { display: flex; align-items: flex-end; gap: 9px; flex-wrap: wrap; }
    .f15-toolbar-controls .form-group { margin: 0; min-width: 185px; }
    .f15-toolbar-controls label { display: block; margin: 0 0 4px; font-size: 12px; color: #334155; }
    .f15-toolbar-controls .btn { height: 34px; white-space: nowrap; }
    .f15-reset-note { margin-bottom: 12px; padding: 10px 14px; border: 1px solid #f59e0b; background: #fffbeb; color: #92400e; border-radius: 8px; }
    /*
     * IS2029: the width reduction is applied to the SHEET, not the table.
     *
     * My first attempt narrowed only .f15-daily-table and left the sheet at
     * 1080px, so Notes, the three signature blocks and the Print button stayed
     * full width while the table shrank - leaving a ragged empty gutter down the
     * right of the page. Resizing one element in isolation was the mistake.
     *
     * ALL FIVE COLUMNS ARE NARROWER. None is widened.
     *
     * The reduction is applied to the SHEET, so the table, the Notes field and
     * the signature grid all shrink together and stay on the same two edges.
     * margin: 0 auto centres the sheet, giving equal space either side.
     *
     * 788px, not 773px: the 26px side padding is fixed, so it is the CONTENT box
     * that must shrink by 28.416%. 1028 x 0.71584 = 735.9, plus 52px of padding
     * = 788px.
     *
     * Two earlier attempts were wrong and are recorded so they are not repeated:
     *   - narrowing only .f15-daily-table left Notes and the signatures at full
     *     width, producing the ragged right edge that was reported;
     *   - making the table full page width and redistributing the column shares
     *     WIDENED four of the five columns, which is the opposite of the
     *     instruction.
     */
    .f15-report-sheet {
        max-width: 788px; margin: 0 auto; padding: 22px 26px 28px; background: #fff;
        border: 1px solid #d8dee9; box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
        box-sizing: border-box;
    }
    .f15-report-sheet[aria-busy="true"] { opacity: .62; pointer-events: none; }
    /*
     * IS2029: header rebalanced for the narrower sheet.
     *
     * It was "1fr 245px" with a matching 245px padding-left on the heading - a
     * way of visually centring the title across the full width by cancelling out
     * the meta column. On a 773px sheet that left the title roughly 230px to sit
     * in, so "MPCS Filling Station - Manana" wrapped awkwardly.
     *
     * Replaced with a three-column grid: an empty spacer, the heading, then the
     * meta block. The heading is now genuinely centred between two equal columns
     * rather than relying on a padding trick, so it stays centred at any sheet
     * width and has the full middle column to use.
     */
    .f15-report-header {
        display: grid;
        grid-template-columns: 165px 1fr 165px;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
    }
    .f15-report-spacer { min-height: 1px; }
    .f15-report-heading { text-align: center; padding-left: 0; }
    .f15-report-heading h2 { margin: 0 0 6px; font-size: 22px; font-weight: 500; color: #111827; }
    .f15-report-heading h3 { margin: 0; font-size: 20px; font-weight: 500; color: #1f2937; }
    .f15-report-meta { text-align: left; font-size: 12.5px; line-height: 1.65; }
    .f15-report-meta .btn { width: 100%; margin-bottom: 8px; padding: 6px 8px; font-size: 12.5px; }

    /* Below the three-column header's comfortable width, stack it so nothing is
       squeezed: heading first, meta beneath it. */
    @media (max-width: 700px) {
        .f15-report-header { grid-template-columns: 1fr; }
        .f15-report-meta { text-align: center; }
    }
    /*
     * IS2029: column widths reduced as requested -
     *   No            -20%   8.00% -> 6.400% of the original container
     *   Description   -40%  42.00% -> 25.200%
     *   Previous Day  -20%  16.66% -> 13.328%
     *   Today         -20%  16.66% -> 13.328%
     *   As of Today   -20%  16.66% -> 13.328%
     *                              total 71.584%
     *
     * The table itself has to shrink to 71.584%, and this is the part that is
     * easy to get wrong: with table-layout: fixed the column percentages are
     * relative to the TABLE, not the page. Narrowing them while the table stays
     * at width: 100% makes the browser scale them back up to fill it, and
     * nothing moves on screen.
     *
     * So the table is set to 71.584% of its container and each column is
     * re-expressed as a share of that narrower table (6.400 / 71.584 = 8.941%,
     * and so on). The result is exactly the requested reduction measured against
     * the original layout, and the shares still sum to 100%.
     */
    .f15-daily-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 13px; color: #111827; }
    .f15-daily-table .f15-col-no { width: 8.941%; }
    .f15-daily-table .f15-col-description { width: 35.203%; }
    .f15-daily-table .f15-col-amount { width: 18.619%; }

    /* Below the sheet's own width, use the space available so nothing is cut
       off on a laptop or tablet. */
    @media (max-width: 860px) {
        .f15-report-sheet { max-width: 100%; padding: 16px 14px 20px; }
    }
    .f15-daily-table th, .f15-daily-table td { border: 1px solid #94a3b8; padding: 6px 9px; vertical-align: middle; }
    .f15-daily-table th { background: #eef2f7; text-align: center; font-weight: 700; font-size: 13px; }
    .f15-section-row td { padding: 6px 12px; background: #f8fafc; color: #0284c7; font-size: 15px; font-weight: 700; text-align: left; }
    .f15-row-no { text-align: center; }
    .f15-description { text-align: left; }
    .f15-amount { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .f15-manual-input { height: 28px; padding: 3px 7px; text-align: right; font-size: 13px; min-width: 82px; }
    .f15-total-row td { font-weight: 700; background: #f8fafc; }
    .f15-grand-row td { font-weight: 800; background: #eaf2ff; }
    .f15-balance-row td { font-weight: 700; background: #fff7ed; }
    /*
     * IS2029: everything below the table is pinned to the same width as the
     * table.
     *
     * These are stated explicitly rather than left to inherit. The reported
     * problem was the Notes field and the signature blocks sitting wider than
     * the table, so relying on defaults here is what went wrong before.
     * box-sizing keeps the textarea's border inside the width instead of
     * pushing it a pixel or two past the table's right edge.
     */
    .f15-notes-block { margin-top: 13px; width: 100%; }
    .f15-notes-block label { display: block; margin-bottom: 4px; font-weight: 700; }
    .f15-notes-block textarea { width: 100%; box-sizing: border-box; resize: vertical; }
    .f15-signatures {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 22px;
        margin-top: 34px;
        width: 100%;
        box-sizing: border-box;
    }
    .f15-signature-card { text-align: center; min-width: 0; }
    .f15-signature-card > input[type="text"],
    .f15-signature-card input[type="date"] { max-width: 100%; box-sizing: border-box; }
    .f15-signature-card > input[type="text"] { border: 0; border-bottom: 1px solid #475569; border-radius: 0; box-shadow: none; text-align: center; margin-bottom: 5px; }
    .f15-signature-card strong { display: block; color: #172554; }
    .f15-signature-card label { margin-top: 22px; font-weight: 400; color: #475569; }
    .f15-signature-card input[type="date"] { border: 0; border-bottom: 1px dotted #64748b; background: transparent; padding: 2px 4px; }
    .f15-bottom-print { margin-top: 20px; text-align: center; }

    @media (max-width: 992px) {
        .f15-toolbar { align-items: stretch; flex-direction: column; }
        .f15-report-header { grid-template-columns: 1fr; }
        .f15-report-heading { padding-left: 0; }
        .f15-report-meta { margin-top: 12px; text-align: center; }
        .f15-report-meta .btn { width: auto; }
    }

    @media print {
        @page { size: A4 portrait; margin: 8mm; }
        /*
         * IS2009: the print preview was a blank page.
         *
         * The rule here used to be:
         *     body > *:not(.wrapper) { display: none !important; }
         *
         * That assumes the report's top-level ancestor is a direct child of
         * <body> carrying the class .wrapper. In this application the layout
         * nests the content deeper, so the element actually holding the report
         * was NOT matched by :not(.wrapper) - it was one of the "everything
         * else" nodes and got display:none, taking the whole report with it.
         * The browser then had nothing left to lay out, which is the blank
         * sheet in the ticket.
         *
         * Hiding by name is the fragile part: it breaks whenever the layout
         * changes. So the approach is inverted below - hide the page furniture
         * explicitly (header, sidebar, footer, .no-print), and let everything
         * else through. The report cannot be hidden by a rule that never
         * mentions it.
         */
        body { background: #fff !important; }

        /*
         * IS2029: the "hide everything, then re-show the sheet" isolation was
         * REVERTED here - it printed a blank page.
         *
         * The rule was:
         *     body * { visibility: hidden !important; }
         *     .f15-report-sheet, .f15-report-sheet * { visibility: visible !important; }
         *
         * On paper that produced nothing at all, the same failure as IS2009.
         * Whatever the precise cause in this theme, the lesson is the same one
         * that ticket recorded: a rule that hides the page wholesale and relies
         * on re-showing one branch is fragile here, because the report sits
         * several levels inside a shared layout.
         *
         * Back to hiding the page furniture BY NAME, below. That approach is
         * verbose and needs a new selector whenever the theme adds a widget, but
         * it cannot hide the report itself - the rules never mention it.
         */

        .main-header,
        .main-sidebar,
        .left-side,
        .content-header,
        .navbar,
        .sidebar,
        .sidebar-menu,
        .sidebar-toggle,
        .sidebar-mini,
        .control-sidebar,
        .control-sidebar-bg,
        .main-footer,
        footer,
        .no-print,
        .modal,
        .modal-backdrop,
        .toast,
        .breadcrumb,
        /*
         * IS2029: the tab strip printed as three lines of raw URLs -
         * "(/mpcs/F15-New#f15_form_tab)" and so on - across the top of the
         * sheet. Those are the page's own tabs; they are navigation, not part of
         * the document. Hidden here, and the a[href] rule below stops the
         * browser appending link targets to any other anchor.
         */
        .nav-tabs,
        .nav.nav-tabs,
        ul.nav-tabs,
        .f15-page-tab,
        /*
         * The floating accessibility / zoom widgets that sat over the table in
         * the reported print. They are fixed-position overlays, so without this
         * they land on top of the report on paper.
         */
        .fab,
        .fab-container,
        .floating-button,
        .float-btn,
        .zoom-controls,
        .accessibility-widget,
        [class*="floating-"],
        [id*="floating-"] {
            display: none !important;
        }

        /*
         * Never print link targets. Some themes add
         * a[href]:after { content: " (" attr(href) ")" } in their print CSS,
         * which is what turned the tabs into "(/mpcs/F15-New#f15_form_tab)".
         */
        a[href]:after,
        abbr[title]:after {
            content: "" !important;
        }

        /* Anything fixed to the viewport will otherwise be stamped onto every
           printed page. */
        body *[style*="position: fixed"],
        body *[style*="position:fixed"] {
            display: none !important;
        }

        /* The three print buttons are the only controls inside the sheet itself;
           the toolbar and bottom bar already sit in .no-print containers. Hiding
           them by id rather than by .btn avoids suppressing anything else in the
           report that happens to be button-styled. */
        #f15_daily_print,
        #f15_header_print,
        #f15_bottom_print {
            display: none !important;
        }

        /* Neutralise the layout's own offsets so the sheet starts at the margin
           rather than where the (now hidden) sidebar used to push it. */
        .content-wrapper,
        .right-side,
        .wrapper,
        .content,
        .box,
        .box-body,
        .f15-daily-page {
            margin: 0 !important;
            padding: 0 !important;
            min-height: 0 !important;
            background: #fff !important;
            width: auto !important;
            float: none !important;
            position: static !important;
        }
        .f15-report-sheet { max-width: none; margin: 0; padding: 0; border: 0; box-shadow: none; }
        /* IS2029: matches the three-column screen header. The old
           "1fr 170px" plus a 170px padding-left assumed two children; with the
           spacer present that combination pushed the title off centre. */
        .f15-report-header { grid-template-columns: 130px 1fr 130px; margin-bottom: 7px; }
        .f15-report-heading { padding-left: 0; }
        .f15-report-heading h2 { font-size: 19px; margin-bottom: 4px; font-weight: 700; }
        .f15-report-heading h3 { font-size: 16px; font-weight: 600; }

        /* The No. column only holds one or two digits; centring it stops the
           figures drifting against the Description column. */
        .f15-daily-table td:first-child,
        .f15-daily-table th:first-child { text-align: center; }
        .f15-report-meta { font-size: 11px; line-height: 1.45; }
        /*
         * IS2029: the printed sheet uses the full paper width.
         *
         * .f15-report-sheet is reset above, so the table, Notes and signatures
         * all span the page. The on-screen 788px reduction is a screen concern -
         * on paper there is no surrounding page furniture to make room for, and
         * a narrowed table would just leave a third of the sheet blank.
         */
        .f15-report-sheet {
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            box-shadow: none !important;
        }

        /*
         * IS2029: printed typography brought closer to the screen report.
         *
         * At 9.5px the sheet read as cramped next to the on-screen version. The
         * page has room for more: with the sidebar and tab strip now hidden, the
         * table has the full paper width, so the type can be larger and the rows
         * taller without spilling onto a second page.
         */
        .f15-daily-table { font-size: 11px; width: 100% !important; }
        .f15-daily-table th, .f15-daily-table td { padding: 5px 7px; }
        .f15-daily-table th { font-size: 11px; }
        .f15-section-row td { font-size: 11.5px; padding: 5px 8px; }

        /*
         * EVERYTHING PRINTS BLACK.
         *
         * The section headings are blue on screen (#0284c7), the report heading
         * dark navy, and the signature labels navy. In print those either waste
         * colour toner or come out as weak grey on a mono printer. A single
         * blanket rule is used rather than listing selectors, because the report
         * contains nested spans and inputs whose colours would otherwise slip
         * through - which is what happened before.
         */
        .f15-report-sheet,
        .f15-report-sheet * {
            color: #000 !important;
        }

        /*
         * NO SHADING ON PRINT.
         *
         * On screen the totals, grand total, balance and section rows are tinted
         * to make them scannable, and the header row is grey. On paper that
         * shading either eats toner or, with "background graphics" turned off in
         * the print dialog, prints inconsistently between browsers - so the same
         * document looks different depending on who printed it.
         *
         * Every fill is removed and the emphasis is carried by RULES and WEIGHT
         * instead, which print identically everywhere:
         *   - header row: a heavier bottom border
         *   - subtotal rows: a hairline above
         *   - grand total and balance rows: a double rule above, bold text
         *   - section headings: bold, letter-spaced, no fill
         *
         * print-color-adjust is set to `economy` so a browser cannot re-add the
         * screen fills, and the borders are forced to solid black for contrast.
         */
        .f15-daily-table,
        .f15-daily-table th,
        .f15-daily-table td,
        .f15-section-row td,
        .f15-total-row td,
        .f15-grand-row td,
        .f15-balance-row td {
            background: transparent !important;
            background-color: transparent !important;
            -webkit-print-color-adjust: economy;
            print-color-adjust: economy;
            color: #000 !important;
        }

        .f15-daily-table th,
        .f15-daily-table td { border: 1px solid #000 !important; }

        .f15-daily-table th {
            font-weight: 700;
            border-bottom: 2px solid #000 !important;
        }

        .f15-section-row td {
            font-weight: 700;
            letter-spacing: .04em;
            text-align: left;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
        }

        .f15-total-row td { font-weight: 700; border-top: 1.5px solid #000 !important; }

        .f15-grand-row td,
        .f15-balance-row td {
            font-weight: 800;
            border-top: 3px double #000 !important;
        }

        /*
         * Amount cells: the manual inputs are boxes on screen so they can be
         * typed into. On paper they are just figures, so the box, the shading
         * and the spin controls all come off and the value sits on the same
         * baseline as every other amount.
         */
        .f15-manual-input {
            border: 0 !important;
            padding: 0 !important;
            height: auto !important;
            font-size: 9.5px;
            background: transparent !important;
            box-shadow: none !important;
            text-align: right;
            width: 100%;
            -moz-appearance: textfield;
        }

        .f15-manual-input::-webkit-outer-spin-button,
        .f15-manual-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Keep a row from being split across two pages, and repeat the header
           row at the top of each page so a long report stays readable. */
        .f15-daily-table tr { page-break-inside: avoid; }
        .f15-daily-table thead { display: table-header-group; }

        .f15-notes-block { margin-top: 6px; }
        .f15-notes-block textarea {
            border: 1px solid #000 !important;
            height: 34px; min-height: 34px; font-size: 9px;
            background: transparent !important;
            resize: none;
        }

        /* The signature block should not be orphaned onto its own page. */
        .f15-signatures {
            gap: 14px;
            margin-top: 18px;
            font-size: 9.5px;
            page-break-inside: avoid;
        }
        .f15-signature-card > input[type="text"] {
            height: 22px; padding: 0; font-size: 9.5px;
            background: transparent !important;
            border: 0 !important;
            border-bottom: 1px solid #000 !important;
        }
        .f15-signature-card label { margin-top: 10px; }
        .f15-signature-card input[type="date"] {
            font-size: 9px;
            background: transparent !important;
            border: 0 !important;
            border-bottom: 1px dotted #000 !important;
        }

        /* The amber "F22 was saved on this date" banner is guidance for the
           person on screen, not part of the document. */
        .f15-reset-note { display: none !important; }
    }
</style>

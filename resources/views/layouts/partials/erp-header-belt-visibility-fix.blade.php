{{--
    ERP Experience Framework V5 - Header Belt Visibility/Consistency Fix
    Purpose:
    - Keep the top ERP header belt controls visible on every page using the main layout.
    - Some module pages override anchor/button/icon text colour, causing the header buttons to render
      as white boxes without visible text/icons. These scoped rules affect only the header belt.
--}}
<style>
/* =========================================================
   EXF V5 - ERP HEADER BELT VISIBILITY STANDARD
   Scope: Header belt only. Does not affect page tabs, forms, reports or module content.
========================================================= */
.header-area.no-print,
.header-area.no-print .row,
.header-area.no-print .my-div1,
.header-area.no-print .notification-area,
.header-area.no-print .notification-area > *,
.header-area.no-print .btn-group,
.header-area.no-print .dropdown,
.header-area.no-print .user-menu {
    visibility: visible !important;
    opacity: 1 !important;
}

.header-area.no-print .notification-area {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-end !important;
    flex-wrap: wrap !important;
    gap: 8px !important;
}

/* Make all header action buttons readable even when module/page CSS overrides .text-white/.btn-flat */
.header-area.no-print .notification-area a.btn,
.header-area.no-print .notification-area button.btn,
.header-area.no-print .notification-area .dropdown-toggle,
.header-area.no-print .notification-area .btn-group > .btn,
.header-area.no-print .notification-area li > a.btn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-height: 42px !important;
    height: 42px !important;
    padding: 8px 14px !important;
    border-radius: 12px !important;
    background: #ffffff !important;
    color: #1f2937 !important;
    border: 1px solid #e5e7eb !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06) !important;
    text-decoration: none !important;
    line-height: 1.2 !important;
    white-space: nowrap !important;
    font-weight: 600 !important;
}

.header-area.no-print .notification-area a.btn *,
.header-area.no-print .notification-area button.btn *,
.header-area.no-print .notification-area .dropdown-toggle *,
.header-area.no-print .notification-area .btn-group > .btn *,
.header-area.no-print .notification-area li > a.btn * {
    color: inherit !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.header-area.no-print .notification-area i,
.header-area.no-print .notification-area .fa,
.header-area.no-print .notification-area strong,
.header-area.no-print .notification-area span:not(.label):not(.notifications_count),
.header-area.no-print .notification-area .caret {
    color: inherit !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Keep the username/profile button in the approved blue gradient style */
.header-area.no-print .user-menu > a.dropdown-toggle,
.header-area.no-print .user-menu > a.dropdown-toggle.btn,
.header-area.no-print .notification-area .user-menu > a.dropdown-toggle {
    background: linear-gradient(135deg, #2563eb, #06b6d4) !important;
    color: #ffffff !important;
    border: none !important;
    min-height: 44px !important;
    height: 44px !important;
    padding: 8px 14px !important;
    border-radius: 14px !important;
    box-shadow: 0 6px 18px rgba(37, 99, 235, 0.25) !important;
}

.header-area.no-print .user-menu > a.dropdown-toggle *,
.header-area.no-print .notification-area .user-menu > a.dropdown-toggle * {
    color: #ffffff !important;
}

/* Dropdown readability */
.header-area.no-print .dropdown-menu,
.header-area.no-print .dropdown-menu * {
    visibility: visible !important;
    opacity: 1 !important;
}

.header-area.no-print .dropdown-menu li a {
    color: #1f2937 !important;
    background: transparent !important;
}

.header-area.no-print .dropdown-menu li a:hover {
    background: #f4f6f9 !important;
    color: #111827 !important;
}

/* Notification counter should remain visible and not inherit button text colour */
.header-area.no-print .notifications_count {
    color: #ffffff !important;
    background: #f59e0b !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Protect the header belt from module-level overflow clipping */
.header-area.no-print,
.header-area.no-print .row,
.header-area.no-print .my-div1,
.header-area.no-print .notification-area,
.header-area.no-print .btn-group,
.header-area.no-print .dropdown,
.header-area.no-print .user-menu {
    overflow: visible !important;
}

@media (max-width: 768px) {
    .header-area.no-print .notification-area {
        justify-content: center !important;
    }

    .header-area.no-print .notification-area a.btn,
    .header-area.no-print .notification-area button.btn,
    .header-area.no-print .notification-area .dropdown-toggle,
    .header-area.no-print .notification-area .btn-group > .btn {
        min-height: 38px !important;
        height: 38px !important;
        padding: 6px 10px !important;
    }
}
</style>

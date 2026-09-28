# Graphs - System Layout & Calibri Fix (12 Sep 2026)

## Changes
- Restored the ERP's normal `layouts.app` wrapper so the existing sidebar, header, footer and sidebar open/close behaviour are preserved.
- The Graphs page no longer creates its own `<html>`, `<head>` or `<body>` document.
- Graphs CSS is fully scoped under `.gr-app`; it does not style `html`, `body`, all links, all buttons, or all inputs globally.
- Graphs content uses Calibri (`Calibri, Segoe UI, Arial, sans-serif`) without changing the sidebar/global business font setting.
- Chart.js labels, legends and tooltips use Calibri on this page.
- Graphs loading state is applied to the Graphs root only, not to the global `<body>` element.

## Not changed
- Existing ERP layout/sidebar files.
- Existing global CSS or JavaScript.
- Graphs routes, data queries, stock calculations, sales calculations, permissions or database schema.

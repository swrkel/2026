# Graphs Design Cache Fix – 12 Sep 2026

## Cause
The standalone Graphs asset endpoint returned CSS/JS with `Cache-Control: public, max-age=86400`.
After replacing the Graphs module with the professional redesign, browsers could therefore keep the previous `graphs.css` and `graphs.js` for up to 24 hours because their URLs were unchanged.

## Fix
1. GraphsController now calculates an asset version from the module asset modification time.
2. The Blade view appends that version to CSS, Chart.js and Graphs JS URLs.
3. The asset endpoint now returns no-cache/no-store headers, so future Graphs UI updates become visible immediately.
4. All changes remain inside `Modules/Graphs`.

No database or core-system change is required.

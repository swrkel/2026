# Help Guide — English Content editor stuck in the left column

Super Admin / Help Guide Modules / Articles
`/superadmin/help-guide-modules/articles`

## Symptom

The English Content editor sits below the form fields in a narrow left-hand
column, with the right two thirds of the page empty. It should sit top-right,
taking the remaining width.

## What it was NOT

Both likely explanations were checked and eliminated first:

- **The layout was already deployed.** `hg-article-columns` is present in the
  live file with the 30% / 70% grid, along with earlier fixes for Summernote's
  width and the stacking breakpoint.
- **Summernote loads correctly.** `summernote-lite.min.js` and its CSS are
  present under `public/plugins/summernote/`, and the toolbar renders in the
  screenshot, so the editor is initialising.

The CSS was right all along. The markup never gave it two columns to lay out.

## Cause — one missing `</div>`

The inner two-column grid holding **Sort Order** and **Status** was opened but
never closed:

```html
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div class="hg-field"> ... Sort Order ... </div>
    <div class="hg-field"> ... Status ... </div>
    <!-- no closing tag -->
</div>{{-- /hg-article-side --}}   <-- actually closed the grid above
```

So the tag labelled as the end of `hg-article-side` closed that inner grid
instead. `hg-article-side` stayed open, and `hg-article-main` — the editor
column — was opened **inside** it.

`hg-article-columns` was therefore left with a **single child**. A CSS grid
places one child in the first column, so the fields and the editor both stacked
inside the 30% column and the 70% column rendered empty.

`hg-article-columns` was also left unclosed, so everything following the editor
was being absorbed into the grid.

## Why the div count looked fine

The file balanced at 18 `<div>` against 18 `</div>`, which is why this was not
obvious. A stray `</div>` after the form was silently compensating for the
missing one — the totals matched while the nesting was wrong.

So the fix is two changes, not one:

1. Add the missing closer to the Sort Order / Status grid.
2. Remove the now-surplus stray closer after the form.

Adding the first alone leaves the file at −1 and closes the card early.

## Fix

Added the missing `</div>`, removed the stray one.

Nesting now closes cleanly: `hg-article-side` ends, `hg-article-main` opens as
its sibling, and `hg-article-columns` closes at the end — two children, so the
existing 30/70 rule applies as written.

No CSS was changed. It did not need changing.

## Files changed (1)

```
Modules/HelpGuide/Resources/views/admin/articles.blade.php
```

## Deployment

```bash
cd /home/nivasa/public_html/Modules && unzip -o /path/to/HelpGuide_Article_Editor_Layout.zip
cd /home/nivasa/public_html && php artisan view:clear
```

`view:clear` is required — the broken markup is cached in compiled form.

## Testing

- [ ] Fields (Module, Title, Slug, Summary, Sort Order, Status) sit in the left column
- [ ] The English Content editor sits top-right, filling the remaining width
- [ ] Sort Order and Status remain side by side within the left column
- [ ] The editor toolbar wraps rather than overflowing
- [ ] Content below the grid renders outside it, not inside the columns
- [ ] Saving an article still works, in both add and edit
- [ ] Below roughly 820px the columns stack

# Management Report layout merge

This parcel uses the layout files supplied in `layouts(23).zip` as the source of truth.

Updated files:

- `resources/views/layouts/partials/sidebar.blade.php` — active sidebar used by `layouts/app.blade.php`.
- `resources/views/layouts/sidebar.blade.php` — compatibility sidebar copy.

The Management Report module resolver and menu were inserted without replacing or reverting any other latest layout changes.

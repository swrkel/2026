# Products New - Stage 002 SETTINGS CENTRE

This parcel extends the standalone Products New module with visible, independent settings pages.

## Added
- Settings navigation tabs
- Categories page: list/search/create
- Brands page: list/search/create
- Units page: list/search/create
- Variation templates page: list/search/create
- Settings lookup/write services
- Dedicated controllers for every setting section
- POS-aligned responsive UI styling
- Parcel SQL and updated master SQL

## Routes
- `/products-new/settings/categories`
- `/products-new/settings/brands`
- `/products-new/settings/units`
- `/products-new/settings/variations`

## Notes
The current Product module is not modified. These pages read/write existing tenant product master tables safely by `business_id`, while all new controllers/services/views/assets are inside `Modules/ProductsNew` and `public/modules/productsnew`.

# ATN-047 to ATN-054 — 8 Section Parcel

## ATN-047 — Visa Processing
- Full visa status workflow
- Status history and audit trail
- Approval, rejection, completion and cancellation states

## ATN-048 — Hotel Booking
- Hotel room types
- Date-wise room availability
- Cost and selling rates
- Availability validation

## ATN-049 — Travel Insurance
- Insurance providers
- Travel insurance policies
- Coverage and premium values
- Automatic policy numbering

## ATN-050 — Transfers
- Transfer route master
- Pickup and drop locations
- Distance, cost and sale pricing
- Vehicle-based pricing calculation

## ATN-051 — Tour Services
- Tour departure dates
- Capacity and booking counts
- Per-person cost and selling price
- Capacity validation

## ATN-052 — Ancillary Services
- Baggage, seat, meal and other add-ons
- Ancillary service master
- Booking and pricing
- Automatic ancillary booking numbering

## ATN-053 — Supplier Integration
- Supplier agreements by service type
- Effective dates
- Commission rates
- Priority-based supplier resolution

## ATN-054 — Service Bundles
- Bundled travel services
- Bundle items
- Bundle pricing and customer savings

## Installation
1. Merge over ATN-001 through ATN-046.
2. Add `Routes/travel-services-8.php` inside the authenticated AirlineTicketingNew route group.
3. Run the migration or `MASTER_ATN_047_TO_054_8_SECTION_PARCEL.sql`.
4. Assign the included permissions.
5. Publish the CSS/JS and merge the language file.
6. Run `php artisan optimize:clear`.

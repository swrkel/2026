# MembershipNew MEMNEW_015 - Database/Business Context Repair

Fixed:
- Prevents Membership New pages from silently using business_id = 0.
- Resolves active business from business.id, user.business_id, business_id, then authenticated user.business_id.
- Keeps Laravel active/default DB connection so central-DB businesses and tenant-DB businesses both remain supported.
- Adds missing MembershipNewMember::shareHolding relationship used by Members pages.
- Adds idempotent repair migration for the complete current mn_* schema.
- Adds database-name-free Master SQL repair file under SQL/MembershipNew.
- Makes the first three legacy migrations safe when tables already exist.

Current 500 addressed:
SQLSTATE[42S02] mn_members missing, plus business_id = 0.

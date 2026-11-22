# Programs and Assistance (Process 5.0) - setup

1. Import `database/schema_programs.sql` (after schema_base.sql and schema_farmers.sql). It adds
   `assistance_types`, `programs`, `program_beneficiaries`, `assistance_records` and seeds 11 assistance types.
2. Paste `style-additions.css` at the end of `public/assets/css/style.css` (detail lists, form footer, match rows).
3. Optional demo data (uses the profiles already in your registry): `php database/seed_programs_demo.php`
   Demo: Assistance Distribution > Record Distribution > Rice Seed Distribution 2026 > the first beneficiary >
   Certified Rice Seeds > Save  ->  "Duplicate Assistance Found" modal. Enter a reason to override.

## Rules to confirm with the MAO
- `assistance_types.duplicate_window_months` decides how long a repeat counts as a duplicate
  (seeds/fertilizer 6, livestock/gear/cash/insurance/livelihood 12, machinery/boat empty = any earlier record).
- Same CATEGORY under another program inside the window = "Possible duplicate".
- Only the Municipal Agriculturist creates/edits programs; technicians can add beneficiaries and record distributions.

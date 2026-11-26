Quick check after install:
  1. Import database/db_mao_system.sql, then run database/schema_modules.sql (phpMyAdmin > SQL).
  2. php tests/route_check.php /resources agriculturist   (repeat for /monitoring, /reports, /system, /map)

Manual checks:
- Register a farmer -> Resources > Farms > Add (enter latitude AND longitude) -> GIS Map shows it -> the profile's Farms tab lists it.
- Profile page > Farms/Fishery/Livestock tab > "+ Add" opens the form with that person already selected.
- Farms / Fishery: latitude without longitude -> error. Area 0 -> error.
- Fishery: Fishpond or Aquaculture unit without area -> error. Same registration no. as another asset -> error.
- Livestock: 0 heads -> error.
- Equipment: Held by "Individual farmer" without a farmer -> error; with an organization filled in -> error.
  Held by "LGU / MAO" with a farmer or organization -> error.
- System > Users: search and filters narrow the list. Deactivating a user logs them out on their next click.
- System > Backup: "Save copy on server" adds a file to storage/backups and lists it; "Download" saves a .sql file.
- System > Settings: reporting period end before start -> error. Season labels show in Production.

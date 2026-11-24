Quick check after install:
  mysql db_mao_system < database/schema_resources.sql
  sh tests/check_all_routes.sh      (add /fishery/1 /livestock/1 /equipment/1 after you add one record each)
Manual: register a farmer -> Farms > Add Farm (pin lat/long) -> Map Overview shows it -> farmer profile > Farms tab lists it.
Equipment: choose BOTH an organization and a farmer -> error; choose neither -> error.
Fishery: Fishpond without area -> error; duplicate registration no. -> error.

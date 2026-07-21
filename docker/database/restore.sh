#!/usr/bin/env bash
set -Eeuo pipefail

sqlcmd=/opt/mssql-tools18/bin/sqlcmd
connection=(
  "$sqlcmd"
  -S database
  -U sa
  -P "$MSSQL_SA_PASSWORD"
  -C
  -b
)

for _ in $(seq 1 120); do
  if "${connection[@]}" -Q "SELECT 1" >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

"${connection[@]}" -Q "SELECT 1" >/dev/null

shopt -s nullglob
backups=(
  /var/opt/mssql/backup/*.bak
  /var/opt/mssql/website-backup/*.bak
)
if ((${#backups[@]} == 0)); then
  existing_count="$(
    "${connection[@]}" -h -1 -W -Q \
      "SET NOCOUNT ON; SELECT COUNT(*) FROM sys.databases WHERE database_id > 4;"
  )"
  existing_count="${existing_count//[[:space:]]/}"
  if [[ ! "$existing_count" =~ ^[1-9][0-9]*$ ]]; then
    echo "No database backups or existing business databases were found." >&2
    exit 1
  fi
  echo "No backup files found; using $existing_count existing business databases."
fi

for backup in "${backups[@]}"; do
  database_name="$(basename "$backup" .bak)"
  database_name="${database_name//]/]]}"

  "${connection[@]}" -Q "
    IF DB_ID(N'${database_name}') IS NULL
    BEGIN
      PRINT N'Restoring ${database_name}';
      RESTORE DATABASE [${database_name}]
        FROM DISK = N'${backup}'
        WITH REPLACE, RECOVERY, STATS = 10;
    END
    ELSE
      PRINT N'Skipping existing database ${database_name}';

    IF EXISTS (
      SELECT 1 FROM sys.databases
      WHERE name = N'${database_name}' AND state_desc <> N'ONLINE'
    )
      THROW 51000, N'Database ${database_name} is not online', 1;
  "
done

"${connection[@]}" -i /usr/local/share/cabal/website-init.sql

"${connection[@]}" -Q "
  IF EXISTS (SELECT 1 FROM sys.servers WHERE name = N'adb01')
    EXEC master.dbo.sp_dropserver @server=N'adb01', @droplogins='droplogins';

  EXEC master.dbo.sp_addlinkedserver
    @server=N'adb01',
    @srvproduct=N'',
    @provider=N'MSOLEDBSQL',
    @datasrc=N'database',
    @provstr=N'TrustServerCertificate=yes';

  EXEC master.dbo.sp_addlinkedsrvlogin
    @rmtsrvname=N'adb01',
    @useself=N'True';

  EXEC master.dbo.sp_serveroption
    @server=N'adb01',
    @optname=N'data access',
    @optvalue=N'true';

  EXEC master.dbo.sp_serveroption
    @server=N'adb01',
    @optname=N'rpc out',
    @optvalue=N'true';
"

"${connection[@]}" -W -s '|' -Q "
  SET NOCOUNT ON;
  SELECT name, state_desc, compatibility_level
  FROM sys.databases
  WHERE database_id > 4
  ORDER BY name;
"

#!/usr/bin/env bash
set -Eeuo pipefail

for name in CABAL_PUBLIC_IP CLIENT_VERSION NORMAL_CLIENT_MAGIC_KEY MSSQL_PASSWORD; do
  value="${!name:-}"
  if [[ -z "$value" || "$value" == "CHANGE_ME" ]]; then
    echo "Missing required environment value: $name" >&2
    exit 2
  fi
done
[[ "$CLIENT_VERSION" =~ ^[0-9]+$ ]] || {
  echo "CLIENT_VERSION must be numeric" >&2
  exit 2
}
[[ "$NORMAL_CLIENT_MAGIC_KEY" =~ ^[0-9]+$ ]] || {
  echo "NORMAL_CLIENT_MAGIC_KEY must be numeric" >&2
  exit 2
}
[[ "${AUTO_START:-true}" == "true" || "${AUTO_START:-true}" == "false" ]] || {
  echo "AUTO_START must be true or false" >&2
  exit 2
}

if [[ ! -f /etc/cabal/LoginSvr_01.ini ]]; then
  cp -a /opt/cabal-seed/config/. /etc/cabal/
fi

mkdir -p /etc/cabal_etc/core /etc/cabal_etc/GMSHeartITS /var/log/cabal /var/log/supervisor
rm -f /var/run/supervisor.sock /var/run/supervisord.pid
rm -f /etc/cabal_etc/core/core.* /core.* 2>/dev/null || true

python3 - <<'PY'
import glob
import os
import re
import shutil
from datetime import datetime, timezone


backup_root = os.path.join(
    "/etc/cabal/.config-backups",
    datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ"),
)


def update(path, values):
    if not os.path.isfile(path):
        return
    with open(path, "r", encoding="utf-8", errors="surrogateescape") as handle:
        lines = handle.readlines()
    changed = False
    output = []
    for line in lines:
        replacement = line
        for key, value in values.items():
            if re.match(rf"^\s*{re.escape(key)}\s*=", line):
                replacement = f"{key}={value}\n"
                changed = changed or replacement != line
                break
        output.append(replacement)
    if changed:
        relative = path.removeprefix("/etc/cabal/").lstrip("/")
        backup = os.path.join(backup_root, relative)
        os.makedirs(os.path.dirname(backup), exist_ok=True)
        shutil.copy2(path, backup)
        temporary = path + ".tmp"
        with open(temporary, "w", encoding="utf-8", errors="surrogateescape") as handle:
            handle.writelines(output)
        os.replace(temporary, path)


public_ip = os.environ["CABAL_PUBLIC_IP"]
client_version = os.environ["CLIENT_VERSION"]
magic_key = os.environ["NORMAL_CLIENT_MAGIC_KEY"]
sql_host = os.environ.get("MSSQL_HOST", "database")
sql_port = os.environ.get("MSSQL_PORT", "1433")
sql_user = os.environ.get("MSSQL_USER", "sa")
sql_password = os.environ["MSSQL_PASSWORD"]

for path in glob.glob("/etc/cabal/WorldSvr*.ini"):
    update(path, {"IPAddress": public_ip, "AddrForClient": public_ip})

for path in glob.glob("/etc/cabal/LoginSvr*.ini"):
    update(path, {
        "client_version": client_version,
        "NormalClientMagicKey": magic_key,
        "Host": f"{public_ip}:3000",
    })

for pattern in (
    "/etc/cabal/*DBAgent*.ini",
    "/etc/cabal/PCBangDBAgent*.ini",
):
    for path in glob.glob(pattern):
        update(path, {"DBId": sql_user, "DBPwd": sql_password})

update("/etc/cabal_scripts/odbc/odbc.ini", {
    "Address": sql_host,
    "Port": sql_port,
})

rendered = (
    glob.glob("/etc/cabal/WorldSvr*.ini")
    + glob.glob("/etc/cabal/LoginSvr*.ini")
    + glob.glob("/etc/cabal/*DBAgent*.ini")
)
for path in rendered:
    with open(path, "r", encoding="utf-8", errors="surrogateescape") as handle:
        if re.search(r"\{\{[A-Z0-9_]+\}\}", handle.read()):
            raise RuntimeError(f"Unrendered configuration placeholder: {path}")

backup_parent = os.path.dirname(backup_root)
if os.path.isdir(backup_parent):
    snapshots = sorted(
        path for path in glob.glob(os.path.join(backup_parent, "*"))
        if os.path.isdir(path)
    )
    for stale in snapshots[:-10]:
        shutil.rmtree(stale)
PY

/etc/cabal_scripts/init.sh

/usr/bin/supervisord -c /etc/supervisord.conf &
supervisor_pid=$!

shutdown() {
  game-control stop --quiet || true
  kill -TERM "${supervisor_pid}" 2>/dev/null || true
  wait "${supervisor_pid}" 2>/dev/null || true
}
trap shutdown TERM INT

for _ in $(seq 1 60); do
  if /usr/bin/supervisorctl -c /etc/supervisord.conf pid >/dev/null 2>&1; then
    break
  fi
  sleep 1
done

if [[ "${AUTO_START:-true}" == "true" ]]; then
  game-control start
fi

wait "${supervisor_pid}"

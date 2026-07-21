#!/usr/bin/env bash
set -euo pipefail

supervisorctl -c /etc/supervisord.conf pid >/dev/null

if [[ "${AUTO_START:-true}" == "true" ]]; then
  while IFS= read -r service; do
    [[ -n "$service" && "$service" != \#* ]] || continue
    supervisorctl -c /etc/supervisord.conf status "$service" |
      grep -q ' RUNNING '
  done < /etc/cabal_structure/world_list
fi

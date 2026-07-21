#!/usr/bin/env bash
set -Eeuo pipefail

action="${1:-status}"
target="${2:-all}"
quiet=false
[[ "${2:-}" == "--quiet" || "${3:-}" == "--quiet" ]] && quiet=true

supervisor=(/usr/bin/supervisorctl -c /etc/supervisord.conf)
mapfile -t services < <(
  sed -e 's/#.*//' -e '/^[[:space:]]*$/d' /etc/cabal_structure/world_list
)

run_start() {
  local service="$1"
  if "${supervisor[@]}" status "$service" 2>/dev/null | grep -q 'RUNNING'; then
    return
  fi
  $quiet || printf 'Starting %s\n' "$service"
  "${supervisor[@]}" start "$service"
}

run_stop() {
  local service="$1"
  if ! "${supervisor[@]}" status "$service" 2>/dev/null | grep -q 'RUNNING'; then
    return
  fi
  $quiet || printf 'Stopping %s\n' "$service"
  "${supervisor[@]}" stop "$service"
}

case "$action" in
  start)
    if [[ "$target" == "all" || "$target" == "--quiet" ]]; then
      for service in "${services[@]}"; do run_start "$service"; done
    else
      run_start "$target"
    fi
    ;;
  stop)
    if [[ "$target" == "all" || "$target" == "--quiet" ]]; then
      mapfile -t running_services < <(
        "${supervisor[@]}" status |
          awk '$2 == "RUNNING" { print $1 }'
      )
      for ((index=${#running_services[@]}-1; index>=0; index--)); do
        run_stop "${running_services[$index]}"
      done
    else
      run_stop "$target"
    fi
    ;;
  restart)
    if [[ "$target" == "all" || "$target" == "--quiet" ]]; then
      mapfile -t running_before < <(
        "${supervisor[@]}" status |
          awk '$2 == "RUNNING" { print $1 }'
      )
      "$0" stop all
      "$0" start all
      for service in "${running_before[@]}"; do
        if [[ ! " ${services[*]} " =~ " ${service} " ]]; then
          run_start "$service"
        fi
      done
    else
      "$0" stop "$target"
      "$0" start "$target"
    fi
    ;;
  status)
    if [[ "$target" == "all" || "$target" == "--quiet" ]]; then
      "${supervisor[@]}" status "${services[@]}"
    else
      "${supervisor[@]}" status "$target"
    fi
    ;;
  *)
    echo "Usage: game-control {start|stop|restart|status} [all|service]" >&2
    exit 2
    ;;
esac

#!/bin/sh
set -eu

# ---------------------------
# Настройки
# ---------------------------
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
STORAGE_DIR="${SCRIPT_DIR}/storage"
mkdir -p "$STORAGE_DIR"

ARTISAN="${SCRIPT_DIR}/artisan"     # ожидаем artisan рядом со скриптом
PHP_BIN="${PHP_BIN:-php}"

DEFAULT_PHP_OPTIONS=$(cat <<'EOF'
--clear_cache
--clear
--import
EOF
)
PHP_OPTIONS="${PHP_OPTIONS:-$DEFAULT_PHP_OPTIONS}"
LF='
'

STOP_TIMEOUT="${STOP_TIMEOUT:-10}"             # секунд на мягкую остановку
START_CHECK_DELAY="${START_CHECK_DELAY:-1}"    # секунд подождать и проверить запуск
LOG_ROTATE_BYTES="${LOG_ROTATE_BYTES:-104857600}" # 100MB

# one source per line: code|artisan_command
SOURCES=$(cat <<'EOF'
waterway|worker:waterway-sync
infoflot|worker:infoflot-sync
volga|worker:volga-sync
germes|worker:germes-sync
gama|worker:gama-sync
EOF
)

# ---------------------------
# Утилиты
# ---------------------------
pid_file()  { echo "${STORAGE_DIR}/$1.pid"; }
meta_file() { echo "${STORAGE_DIR}/$1.meta"; }
log_file()  { echo "${STORAGE_DIR}/$1.log"; }

now() { date '+%Y-%m-%d %H:%M:%S'; }

file_size_bytes() {
  f="$1"
  if [ ! -f "$f" ]; then
    echo 0
    return
  fi
  size=$(stat -c%s "$f" 2>/dev/null || stat -f%z "$f" 2>/dev/null || echo 0)
  size=$(echo "$size" | tr -d '[:space:]')
  [ -n "$size" ] && [ "$size" -ge 0 ] 2>/dev/null && echo "$size" || echo 0
}

rotate_log_if_needed() {
  lf="$1"
  size="$(file_size_bytes "$lf")"
  if [ "$size" -ge "$LOG_ROTATE_BYTES" ]; then
    mv -f "$lf" "${lf}.$(date '+%Y%m%d_%H%M%S').$$.old"
  fi
}

is_running_pid() {
  _pid="${1:-}"
  [ -n "$_pid" ] && kill -0 "$_pid" 2>/dev/null
}

is_our_process() {
  _pid="${1:-}"
  _cmd="${2:-}"

  [ -n "$_pid" ] || return 1
  is_running_pid "$_pid" || return 1

  args="$(ps -p "$_pid" -o args= 2>/dev/null)"
  [ -n "$args" ] || return 1
  echo "$args" | grep -Fq "artisan" || return 1
  echo "$args" | grep -Fq "$_cmd"   || return 1

  return 0
}

LOCK_DIR="${STORAGE_DIR}/.lockdir"
acquire_lock() {
  if mkdir "$LOCK_DIR" 2>/dev/null; then
    echo $$ > "${LOCK_DIR}/pid"
    return 0
  fi

  if [ -f "${LOCK_DIR}/pid" ]; then
    lock_pid="$(cat "${LOCK_DIR}/pid" 2>/dev/null)"
    if [ -n "$lock_pid" ] && ! is_running_pid "$lock_pid"; then
      rm -rf "$LOCK_DIR"
      mkdir "$LOCK_DIR" 2>/dev/null || {
        echo "❌ Скрипт уже выполняется (lock удерживается)"
        exit 1
      }
      echo $$ > "${LOCK_DIR}/pid"
      return 0
    fi
  fi

  echo "⚠️ Скрипт уже выполняется (lock удерживается)."
  exit 1
}

release_lock() {
  rm -rf "$LOCK_DIR"
}

trap 'release_lock' INT TERM EXIT

require_deps() {
  command -v "$PHP_BIN" >/dev/null 2>&1 || { echo "❌ php не найден (PHP_BIN=$PHP_BIN)"; exit 1; }
  [ -f "$ARTISAN" ] || { echo "❌ artisan не найден по пути: $ARTISAN"; exit 1; }
}

# ---------------------------
# Операции
# ---------------------------
start_one() {
  code="$1"
  cmd="$2"

  pf="$(pid_file "$code")"
  mf="$(meta_file "$code")"
  lf="$(log_file "$code")"

  rotate_log_if_needed "$lf"

  if [ -f "$pf" ]; then
    old_pid="$(cat "$pf" 2>/dev/null)"
    if is_our_process "$old_pid" "$cmd"; then
      echo "⚠️ $code уже запущен (PID: $old_pid)"
      return 0
    fi
  fi

  start_time="$(now)"

  set -- "$PHP_BIN" "$ARTISAN" "$cmd"
  if [ -n "${PHP_OPTIONS:-}" ]; then
    while IFS= read -r opt || [ -n "$opt" ]; do
      [ -n "$opt" ] && set -- "$@" "$opt"
    done <<EOF
$PHP_OPTIONS
EOF
  fi
  "$@" >>"$lf" 2>&1 &
  new_pid=$!

  {
    echo "pid=$new_pid"
    echo "cmd=$cmd"
    echo "start_time=$start_time"
    echo "end_time="
    echo "status=running"
  } > "$mf"

  echo "$new_pid" > "$pf"

  sleep "$START_CHECK_DELAY"
  if ! is_our_process "$new_pid" "$cmd"; then
    echo "❌ $code не удержался в фоне. Проверь лог: $lf"
    rm -f "$pf"
    end_time="$(now)"
    {
      echo "pid=$new_pid"
      echo "cmd=$cmd"
      echo "start_time=$start_time"
      echo "end_time=$end_time"
      echo "status=failed"
    } > "$mf"
    return 1
  fi

  echo "✅ Запущен $code (PID: $new_pid) log: $lf"
}

stop_one() {
  code="$1"

  pf="$(pid_file "$code")"
  mf="$(meta_file "$code")"

  if [ ! -f "$pf" ]; then
    echo "⚪ $code не запускался (нет pid-файла)"
    return 0
  fi

  pid="$(cat "$pf" 2>/dev/null)"
  cmd="$(grep '^cmd=' "$mf" 2>/dev/null | cut -d= -f2-)"
  start_time="$(grep '^start_time=' "$mf" 2>/dev/null | cut -d= -f2-)"
  end_time="$(now)"

  if [ -z "$cmd" ]; then
    cmd="(unknown)"
  fi

  if ! is_running_pid "$pid"; then
    echo "🔴 $code уже не запущен (PID: $pid)"
    rm -f "$pf"
    {
      echo "pid=$pid"
      echo "cmd=$cmd"
      [ -n "$start_time" ] && echo "start_time=$start_time"
      echo "end_time=$end_time"
      echo "status=stopped"
    } > "$mf"
    return 0
  fi

  if ! is_our_process "$pid" "$cmd"; then
    echo "⚠️ $code: PID $pid не принадлежит ожидаемой команде, очищаю pid/meta"
    rm -f "$pf"
    {
      echo "pid=$pid"
      echo "cmd=$cmd"
      [ -n "$start_time" ] && echo "start_time=$start_time"
      echo "end_time=$end_time"
      echo "status=stale_pid"
    } > "$mf"
    return 0
  fi

  kill "$pid" 2>/dev/null

  i=0
  while is_running_pid "$pid" && [ "$i" -lt "$STOP_TIMEOUT" ]; do
    sleep 1
    i=$((i+1))
  done

  if is_running_pid "$pid"; then
    echo "⚠️ $code не остановился мягко, применяю SIGKILL (PID: $pid)"
    kill -9 "$pid" 2>/dev/null
    sleep 1
  fi

  rm -f "$pf"
  {
    echo "pid=$pid"
    echo "cmd=$cmd"
    [ -n "$start_time" ] && echo "start_time=$start_time"
    echo "end_time=$end_time"
    echo "status=stopped"
  } > "$mf"

  echo "✅ Остановлен $code (PID: $pid)"
}

restart_one() {
  code="$1"
  cmd="$2"
  stop_one "$code"
  sleep 1
  start_one "$code" "$cmd"
}

status_all() {
  echo "📊 Статус процессов:"
  echo ""

  while IFS='|' read -r code cmd; do
    pf="$(pid_file "$code")"
    mf="$(meta_file "$code")"
    lf="$(log_file "$code")"

    pid=""
    [ -f "$pf" ] && pid="$(cat "$pf" 2>/dev/null)"

    if is_our_process "$pid" "$cmd"; then
      st="🟢 запущен"
    elif [ -n "$pid" ]; then
      st="🔴 зависший PID-файл (PID $pid не наш процесс)"
    else
      st="⚪ не запускался"
    fi

    echo "  $code:"
    echo "    Статус: $st"
    [ -n "$pid" ] && echo "    PID: $pid"
    [ -f "$mf" ] && {
      start_time="$(grep '^start_time=' "$mf" 2>/dev/null | cut -d= -f2-)"
      end_time="$(grep '^end_time=' "$mf" 2>/dev/null | cut -d= -f2-)"
      [ -n "$start_time" ] && echo "    start_time: $start_time"
      [ -n "$end_time" ] && echo "    end_time:   $end_time"
    }
    echo "    log: $lf ($(file_size_bytes "$lf") bytes)"
    echo ""
  done <<EOF
$SOURCES
EOF
}

find_source_cmd() {
  code="$1"
  while IFS='|' read -r c cmd; do
    if [ "$c" = "$code" ]; then
      echo "$cmd"
      return 0
    fi
  done <<EOF
$SOURCES
EOF
}

# ---------------------------
# main
# ---------------------------
require_deps
acquire_lock

action="${1:-}"
target="${2:-}"

case "$action" in
  start)
    if [ -n "$target" ]; then
      cmd="$(find_source_cmd "$target")"
      [ -n "$cmd" ] || { echo "❌ Неизвестный сервис: $target"; exit 1; }
      start_one "$target" "$cmd"
    else
      echo "🚀 Запускаю синки..."
      while IFS='|' read -r code cmd; do
        start_one "$code" "$cmd"
      done <<EOF
$SOURCES
EOF
    fi
    ;;
  stop)
    if [ -n "$target" ]; then
      stop_one "$target"
    else
      echo "🛑 Останавливаю синки..."
      while IFS='|' read -r code cmd; do
        stop_one "$code"
      done <<EOF
$SOURCES
EOF
    fi
    ;;
  restart)
    if [ -n "$target" ]; then
      cmd="$(find_source_cmd "$target")"
      [ -n "$cmd" ] || { echo "❌ Неизвестный сервис: $target"; exit 1; }
      restart_one "$target" "$cmd"
    else
      echo "🔁 Рестарт всех синков..."
      while IFS='|' read -r code cmd; do
        restart_one "$code" "$cmd"
      done <<EOF
$SOURCES
EOF
    fi
    ;;
  status)
    status_all
    ;;
  *)
    echo "Использование: $0 {start|stop|restart|status} [service]"
    echo ""
    echo "Примеры:"
    echo "  $0 start"
    echo "  $0 start waterway"
    echo "  $0 stop volga"
    echo "  $0 restart"
    echo "  $0 status"
    exit 1
    ;;
esac
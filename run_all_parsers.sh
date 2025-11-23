#!/bin/bash
# Скрипт управления парсерами azimut74
# Использование: ./run_all_parsers.sh {start|status|stop}

set -euo pipefail

# Константы
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STATUS_FILE="${SCRIPT_DIR}/storage/parsers_status.json"
ARTISAN="${SCRIPT_DIR}/artisan"

# Определение парсеров
declare -A PARSERS=(
    ["volga"]="php ${ARTISAN} worker:volga-parse --clear"
    ["germes"]="php ${ARTISAN} worker:germes-parse --clear --clear_cache"
    ["gama"]="php ${ARTISAN} worker:gama-parse --clear --clear_cache"
    ["infoflot"]="php ${ARTISAN} worker:infoflot-parse --clear --clear_cache"
    ["waterway"]="php ${ARTISAN} worker:waterway-parse --clear --clear_cache"
)

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Функция логирования
log() {
    echo -e "[$(date +'%Y-%m-%d %H:%M:%S')] $*" >&2
}

# Функция проверки существования процесса
is_process_running() {
    local pid=$1
    if [ -z "$pid" ] || [ "$pid" = "null" ]; then
        return 1
    fi
    kill -0 "$pid" 2>/dev/null
}

# Функция чтения JSON файла
read_status_file() {
    if [ ! -f "$STATUS_FILE" ]; then
        echo "{}"
        return
    fi
    cat "$STATUS_FILE"
}

# Функция записи JSON файла
write_status_file() {
    local json_data="$1"
    # Создаем директорию storage если не существует
    mkdir -p "$(dirname "$STATUS_FILE")"
    echo "$json_data" > "$STATUS_FILE"
}

# Команда start
cmd_start() {
    log "${GREEN}Запуск всех парсеров...${NC}"
    
    # Проверяем, не запущены ли уже парсеры
    if [ -f "$STATUS_FILE" ]; then
        local existing_data
        existing_data=$(read_status_file)
        local running_count=0
        
    # Проверяем каждый парсер (упрощенный парсинг JSON)
    for parser_name in "${!PARSERS[@]}"; do
        # Ищем блок парсера в JSON
        local parser_json
        parser_json=$(echo "$existing_data" | sed -n "s/.*\"$parser_name\":{\([^}]*\)}.*/\1/p" || echo "")
        if [ -n "$parser_json" ]; then
            local pid
            pid=$(echo "$parser_json" | grep -o '"pid":[0-9]*' | grep -o '[0-9]*' || echo "")
            if [ -n "$pid" ] && is_process_running "$pid"; then
                running_count=$((running_count + 1))
            fi
        fi
    done
        
        if [ "$running_count" -gt 0 ]; then
            log "${YELLOW}Предупреждение: Найдено $running_count запущенных парсеров. Используйте 'stop' для остановки.${NC}"
            log "${YELLOW}Или используйте 'status' для проверки статуса.${NC}"
            return 1
        fi
    fi
    
    # Переходим в рабочую директорию
    cd "$SCRIPT_DIR" || exit 1
    
    # Создаем структуру JSON
    local started_at
    started_at=$(date '+%Y-%m-%d %H:%M:%S')
    local json_data="{"
    json_data+="\"started_at\":\"$started_at\","
    json_data+="\"parsers\":{"
    
    local first=true
    local pids=()
    
    # Запускаем каждый парсер
    for parser_name in "${!PARSERS[@]}"; do
        local cmd="${PARSERS[$parser_name]}"
        local parser_started_at
        parser_started_at=$(date '+%Y-%m-%d %H:%M:%S')
        
        log "${YELLOW}Запуск парсера: $parser_name${NC}"
        
        # Создаем директорию для логов если не существует
        mkdir -p "${SCRIPT_DIR}/storage/logs"
        
        # Запускаем процесс в фоне
        nohup bash -c "$cmd" > "${SCRIPT_DIR}/storage/logs/${parser_name}_parser.log" 2>&1 &
        local pid=$!
        pids+=("$pid")
        
        # Добавляем запятую если не первый элемент
        if [ "$first" = false ]; then
            json_data+=","
        fi
        first=false
        
        # Добавляем информацию о парсере в JSON
        json_data+="\"$parser_name\":{"
        json_data+="\"pid\":$pid,"
        json_data+="\"started_at\":\"$parser_started_at\","
        json_data+="\"command\":\"$cmd\","
        json_data+="\"status\":\"running\""
        json_data+="}"
        
        log "${GREEN}Парсер $parser_name запущен (PID: $pid)${NC}"
        
        # Небольшая задержка между запусками
        sleep 1
    done
    
    json_data+="}}"
    
    # Сохраняем JSON файл
    write_status_file "$json_data"
    
    log "${GREEN}Все парсеры запущены!${NC}"
    log "${GREEN}Используйте './run_all_parsers.sh status' для проверки статуса${NC}"
    
    return 0
}

# Команда status
cmd_status() {
    if [ ! -f "$STATUS_FILE" ]; then
        log "${YELLOW}Парсеры не запущены (файл статуса не найден)${NC}"
        return 1
    fi
    
    local status_data
    status_data=$(read_status_file)
    
    if [ "$status_data" = "{}" ]; then
        log "${YELLOW}Парсеры не запущены${NC}"
        return 1
    fi
    
    local started_at
    started_at=$(echo "$status_data" | grep -o '"started_at":"[^"]*"' | cut -d'"' -f4 || echo "неизвестно")
    
    log "${GREEN}=== Статус парсеров ===${NC}"
    log "Время запуска: $started_at"
    log ""
    
    local running_count=0
    local stopped_count=0
    
    # Проверяем каждый парсер
    for parser_name in "${!PARSERS[@]}"; do
        # Извлекаем данные парсера из JSON (упрощенный парсинг)
        # Ищем блок парсера: "parser_name":{...}
        local parser_json
        parser_json=$(echo "$status_data" | sed -n "s/.*\"$parser_name\":{\([^}]*\)}.*/\1/p" || echo "")
        
        if [ -z "$parser_json" ]; then
            continue
        fi
        
        local pid
        pid=$(echo "$parser_json" | grep -o '"pid":[0-9]*' | grep -o '[0-9]*' || echo "")
        local started
        started=$(echo "$parser_json" | grep -o '"started_at":"[^"]*"' | cut -d'"' -f4 || echo "неизвестно")
        local command
        command=$(echo "$parser_json" | grep -o '"command":"[^"]*"' | cut -d'"' -f4 || echo "")
        
        if is_process_running "$pid"; then
            running_count=$((running_count + 1))
            log "${GREEN}✓ $parser_name${NC} (PID: $pid) - ${GREEN}работает${NC}"
            log "  Запущен: $started"
        else
            stopped_count=$((stopped_count + 1))
            log "${RED}✗ $parser_name${NC} (PID: $pid) - ${RED}остановлен${NC}"
            log "  Запущен: $started"
        fi
    done
    
    log ""
    log "${GREEN}=== Итого ===${NC}"
    log "${GREEN}Работает: $running_count из ${#PARSERS[@]}${NC}"
    log "${RED}Остановлено: $stopped_count из ${#PARSERS[@]}${NC}"
    
    return 0
}

# Команда stop
cmd_stop() {
    if [ ! -f "$STATUS_FILE" ]; then
        log "${YELLOW}Парсеры не запущены (файл статуса не найден)${NC}"
        return 1
    fi
    
    local status_data
    status_data=$(read_status_file)
    
    if [ "$status_data" = "{}" ]; then
        log "${YELLOW}Парсеры не запущены${NC}"
        return 1
    fi
    
    log "${YELLOW}Остановка всех парсеров...${NC}"
    
    local stopped_count=0
    local not_found_count=0
    
    # Останавливаем каждый парсер
    for parser_name in "${!PARSERS[@]}"; do
        # Извлекаем данные парсера из JSON
        local parser_json
        parser_json=$(echo "$status_data" | sed -n "s/.*\"$parser_name\":{\([^}]*\)}.*/\1/p" || echo "")
        
        if [ -z "$parser_json" ]; then
            continue
        fi
        
        local pid
        pid=$(echo "$parser_json" | grep -o '"pid":[0-9]*' | grep -o '[0-9]*' || echo "")
        
        if [ -z "$pid" ]; then
            continue
        fi
        
        if is_process_running "$pid"; then
            log "${YELLOW}Остановка $parser_name (PID: $pid)...${NC}"
            if kill "$pid" 2>/dev/null; then
                # Ждем завершения процесса (максимум 10 секунд)
                local wait_count=0
                while is_process_running "$pid" && [ $wait_count -lt 10 ]; do
                    sleep 1
                    wait_count=$((wait_count + 1))
                done
                
                if is_process_running "$pid"; then
                    log "${RED}Процесс $parser_name (PID: $pid) не завершился, отправляем SIGKILL...${NC}"
                    kill -9 "$pid" 2>/dev/null || true
                fi
                
                stopped_count=$((stopped_count + 1))
                log "${GREEN}Парсер $parser_name остановлен${NC}"
            else
                log "${RED}Ошибка при остановке $parser_name (PID: $pid)${NC}"
            fi
        else
            not_found_count=$((not_found_count + 1))
            log "${YELLOW}Парсер $parser_name (PID: $pid) уже не работает${NC}"
        fi
    done
    
    # Удаляем файл статуса
    if [ -f "$STATUS_FILE" ]; then
        rm -f "$STATUS_FILE"
        log "${GREEN}Файл статуса удален${NC}"
    fi
    
    log "${GREEN}Остановлено парсеров: $stopped_count${NC}"
    if [ "$not_found_count" -gt 0 ]; then
        log "${YELLOW}Уже остановлено: $not_found_count${NC}"
    fi
    
    return 0
}

# Главная функция
main() {
    local command="${1:-}"
    
    case "$command" in
        start)
            cmd_start
            ;;
        status)
            cmd_status
            ;;
        stop)
            cmd_stop
            ;;
        *)
            echo "Использование: $0 {start|status|stop}"
            echo ""
            echo "Команды:"
            echo "  start   - Запустить все парсеры в фоне"
            echo "  status  - Показать статус работающих парсеров"
            echo "  stop    - Остановить все запущенные парсеры"
            exit 1
            ;;
    esac
}

# Запуск
main "$@"


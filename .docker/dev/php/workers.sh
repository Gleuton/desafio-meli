#!/bin/bash

# Cores para output
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}Starting Laravel Workers...${NC}"

# Iniciar queue:work
echo -e "${BLUE}[QUEUE]${NC} Starting queue:work..."
php artisan queue:work --tries=1 --timeout=0 &
QUEUE_PID=$!

# Iniciar schedule:work
echo -e "${BLUE}[SCHEDULER]${NC} Starting schedule:work..."
php artisan schedule:work &
SCHEDULER_PID=$!

# Função para limpar processos ao sair
cleanup() {
    echo -e "${RED}[WORKERS]${NC} Stopping workers..."
    kill $SCHEDULER_PID $QUEUE_PID 2>/dev/null
    exit 0
}

# Trap para garantir que os processos sejam mortos ao sair
trap cleanup SIGTERM SIGINT

# Aguardar ambos os processos
wait $SCHEDULER_PID $QUEUE_PID


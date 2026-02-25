# Desafio Mercado Livre - Integração de Anúncios

Este projeto é uma solução para o desafio de integração com a API do Mercado Livre (mock), focado na captura, processamento e armazenamento de anúncios de um vendedor específico.

## 🚀 Tecnologias e Arquitetura

O projeto foi construído utilizando:
- **PHP 8.5** & **Laravel 12**
- **MySQL 8.4** para persistência de dados
- **Docker & Docker Compose** para ambiente de desenvolvimento isolado
- **Mockoon CLI** para simulação das APIs do Mercado Livre e Auth

### Arquitetura
A aplicação segue princípios de **Clean Architecture**, dividida em:
- **Core/Application**: Casos de uso (`FetchSellerAdsUseCase`) que contêm a lógica de negócio.
- **Core/Infrastructure**: Implementações técnicas como Clientes HTTP (Guzzle), Repositórios (Eloquent) e Despachantes de Fila.
- **Jobs**: Processamento assíncrono dos detalhes de cada anúncio via `ProcessItemJob`.

---



## 🛠️ Como Executar (Docker)

Toda a aplicação deve ser executada via Docker para evitar conflitos de ambiente.

### 1. Clonar o repositório e configurar o ambiente
```bash
git clone git@github.com:Gleuton/desafio-meli.git
cd desafio-meli
cp .env.example .env
```
*Certifique-se de que as variáveis de banco de dados e Redis no `.env` apontem para os serviços do Docker (ex: `DB_HOST=desafio-db`, `REDIS_HOST=desafio-redis`).*

### 2. Subir os containers
```bash
docker compose -f docker-compose.dev.yml up -d
```
Este comando iniciará os seguintes serviços:
- `desafio_app`: Aplicação PHP (Laravel)
- `desafio_nginx`: Servidor Web (porta 8080)
- `desafio_db`: Banco de Dados MySQL
- `desafio_redis`: Gerenciador de Filas
- `desafio_mockoon`: Mock das APIs (porta 3001)

### 3. Setup Inicial (Método Rápido)
```bash
docker exec -it desafio_app composer run setup
```
Este comando executa automaticamente:
- Instalação das dependências do Composer
- Cópia do arquivo `.env.example` para `.env` (se não existir)
- Geração da chave da aplicação
- Execução das migrations

**⚠️ Importante:** O arquivo `workers.sh` precisa ter permissões de execução:
```bash
chmod +x .docker/dev/php/workers.sh
```

Após o setup, inicie os workers:
```bash
docker exec -it desafio_app composer run workers
```

### 4. Comandos do Composer Disponíveis

Para facilitar o desenvolvimento, foram criados scripts no `composer.json`:

#### `composer run setup`
Instala e configura todo o projeto do zero. Ideal para primeiro uso.

#### `composer run workers`
Inicia o **Scheduler** e **Queue Worker** em paralelo:
- `php artisan schedule:work` - Executa tarefas agendadas
- `php artisan queue:work` - Processa jobs da fila

```bash
docker exec -it desafio_app composer run workers
```

#### `composer run dev`
Inicia o servidor de desenvolvimento do Laravel:
```bash
docker exec -it desafio_app composer run dev
```

#### `composer run test`
Executa a suite de testes:
```bash
docker exec -it desafio_app composer run test
```

---

## 📦 Execução do Job de Anúncios

O sistema possui três formas de processar os anúncios:

### A. Execução Manual (Command)
Você pode disparar a busca de anúncios manualmente a qualquer momento via Artisan:
```bash
docker exec -it desafio_app php artisan meli:fetch-ads
```
**Opções disponíveis:**
- `--seller-id`: Define um ID de vendedor específico (Padrão: 252254392).
- `--limit`: Define a quantidade de anúncios a buscar (Padrão: 30).

### B. Execução Agendada (Scheduler Automático)
O Laravel está configurado para executar o comando `meli:fetch-ads` **automaticamente a cada 10 minutos**. 

**Para ativar o scheduler:**
```bash
docker exec -it desafio_app composer run workers
```

Este comando inicia:
- `php artisan schedule:work` - Executa tarefas agendadas automaticamente
- `php artisan queue:work` - Processa os jobs da fila

A configuração do scheduler está em `bootstrap/app.php` e inclui:
- Execução a cada 10 minutos
- Proteção contra execução simultânea (`withoutOverlapping()`)
- Execução apenas em um servidor (`onOneServer()`)

**Para visualizar os jobs agendados:**
```bash
docker exec -it desafio_app php artisan schedule:list
```
---

## 🔗 Consultar Anúncios

### Endpoint de Listagem
Para consultar os anúncios já processados e armazenados no banco de dados:
```bash
GET /api/v1/items
```

**Exemplo de requisição:**
```bash
curl -X GET http://localhost:8080/api/v1/items
```

**Resposta (exemplo):**
```json
[
    {
        "id": 133,
        "meli_id": "MLB009000011",
        "seller_id": "252254392",
        "title": "Cooler para Notebook Suporte Ergonômico",
        "status": "active",
        "processing_status": "pending",
        "created_at": "2026-01-22T12:15:00Z",
        "updated_at": "2026-01-28T09:30:00Z",
        "processed_at": "2026-02-24T17:57:43Z"
    },
    ...
]
```

### Filtros Disponíveis
- `?seller_id=252254392`: Filtrar por ID do vendedor
- `?status=completed`: Filtrar por status de processamento (pending, processing, completed, failed)
- `?limit=50`: Limitar a quantidade de resultados (Padrão: 30)
- `?page=1`: Paginação de resultados

---

## 🔍 Logs e Monitoramento

### Logs da Aplicação
Os logs de processamento (incluindo erros de token inválido) podem ser visualizados em:
```bash
tail -f storage/logs/laravel.log
```
Ou via Docker:
```bash
docker logs -f desafio_app
```

### Processamento de Filas
O serviço `desafio_queue` processa automaticamente os detalhes dos itens. Para ver o log do worker:
```bash
docker logs -f desafio_queue
```

---

## 🧪 Execução de Testes

O projeto utiliza o framework **Pest** para testes automatizados. Para rodar todos os testes dentro do ambiente Docker:

```bash
docker exec -it desafio_app php artisan test
```

---

## 📋 Regras de Negócio Implementadas
1. **Autenticação**: Uso obrigatório de access token via Meli-Auth.
2. **Resiliência**: Tratamento de tokens inválidos (`inactive_token`) com registro de log.
3. **Eficiência**: Busca inicial via API Search e detalhamento individual via API Items.
4. **Assincronismo**: Uso de filas para garantir que o job principal não fique travado.
5. **Idempotência**: Atualização de dados caso o anúncio já exista no banco.

---

## 🏗️ Arquitetura da Aplicação

### Divisão de Camadas

A aplicação está organizada em camadas bem definidas seguindo os princípios de **Clean Architecture**:

#### **Core/Application**
Responsável pela lógica de negócio pura, sem dependências de frameworks:
- **Contracts**: Interfaces que definem contratos (ex: `LoggerInterface`, `QueueDispatcherInterface`)
- **Exceptions**: Exceções de domínio específicas da aplicação
- **UseCases**: Casos de uso que orquestram a lógica de negócio (ex: `FetchSellerAdsUseCase`)

#### **Core/Infrastructure**
Implementações técnicas e acesso a recursos externos:
- **Http**: Clientes HTTP para comunicação com APIs externas (Meli Search, Items, Auth)
- **Logging**: Implementação de logging estruturado (`LaravelLogger`)
- **Persistence**: Repositórios que acessam o banco de dados (`ItemRepository`)
- **Queue**: Implementação de despacho de filas (`LaravelQueueDispatcher`)

#### **Jobs**
Processamento assíncrono de tarefas:
- `ProcessItemJob`: Job que processa itens individuais de forma assíncrona

#### **Console/Commands**
Entrada da aplicação via CLI:
- `FetchMeliAdsCommand`: Comando Artisan para disparar busca de anúncios manualmente ou via scheduler

#### **Tests**
Testes organizados por tipo:
- **Unit**: Testes isolados da lógica de negócio
- **Feature**: Testes de funcionalidade da aplicação (Jobs, Commands, Repositories)
- **Integration**: Testes que integram com APIs/serviços externos

### Fluxo de Dados

```
CLI/Scheduler
    ↓
FetchMeliAdsCommand (Console)
    ↓
FetchSellerAdsUseCase (Application)
    ↓
    ├─ MeliSearchClient (HTTP) → API Meli Search
    ├─ LoggerInterface (Logging) → Logs estruturados
    └─ QueueDispatcher (Queue) → Enfileira jobs
         ↓
    ProcessItemJob (Job async)
         ↓
         ├─ MeliItemsClient (HTTP) → API Meli Items
         ├─ Validação de dados (Exceptions)
         ├─ ItemRepository (Persistence) → Banco de dados
         └─ LoggerInterface (Logging) → Logs de processamento
```

### Princípios Aplicados

| Princípio | Implementação |
|-----------|---|
| **Single Responsibility** | Cada classe tem uma responsabilidade única |
| **Open/Closed** | Aberto para extensão via interfaces, fechado para modificação |
| **Dependency Inversion** | Dependências em abstrações (interfaces), não em classes concretas |
| **Interface Segregation** | Interfaces específicas e pequenas para cada contrato |
| **Separation of Concerns** | Lógica de negócio isolada da infraestrutura |

---

## 📚 Referência Rápida de Comandos

### Setup e Inicialização
```bash
# Subir containers Docker
docker compose -f docker-compose.dev.yml up -d

# Dar permissão de execução ao workers.sh (necessário apenas uma vez)
chmod +x .docker/dev/php/workers.sh

# Setup inicial (instalar dependências, migrations, etc)
docker exec -it desafio_app composer run setup

# Iniciar workers (scheduler + queue)
docker exec -it desafio_app composer run workers
```

### Comandos do Composer
```bash
# Setup completo do projeto
composer run setup

# Iniciar workers (scheduler + queue)
composer run workers

# Iniciar servidor de desenvolvimento
composer run dev

# Executar testes
composer run test
```

### Comandos Artisan
```bash
# Buscar anúncios manualmente
docker exec -it desafio_app php artisan meli:fetch-ads

# Listar jobs agendados
docker exec -it desafio_app php artisan schedule:list

# Ver logs em tempo real
docker exec -it desafio_app php artisan pail
```

### Monitoramento
```bash
# Ver logs da aplicação
docker logs -f desafio_app

# Ver logs do workers
docker logs -f desafio_workers

# Ver logs do Laravel
tail -f storage/logs/laravel.log
```

### API
```bash
# Listar todos os itens
curl http://localhost:8080/api/v1/items

# Filtrar por vendedor
curl http://localhost:8080/api/v1/items?seller_id=252254392

# Filtrar por status
curl http://localhost:8080/api/v1/items?status=completed

# Paginação
curl http://localhost:8080/api/v1/items?page=2&limit=50
```

---

## ❓ Troubleshooting

### Erro: "Permission denied" ao executar `composer run workers`

Se você encontrar um erro como:
```
sh: .docker/dev/php/workers.sh: Permission denied
```

**Solução:** O arquivo `workers.sh` precisa ter permissões de execução:
```bash
chmod +x .docker/dev/php/workers.sh
```

Depois execute novamente:
```bash
docker exec -it desafio_app composer run workers
```

### Workers não estão processando jobs

Verifique se:
1. O Redis está rodando: `docker ps | grep desafio_redis`
2. Os workers estão ativos: `docker logs -f desafio_app`
3. Existem jobs na fila: `docker exec -it desafio_app php artisan queue:monitor`

### Scheduler não está executando comandos

Certifique-se de que o `schedule:work` está rodando:
```bash
docker exec -it desafio_app composer run workers
```

Para verificar os jobs agendados:
```bash
docker exec -it desafio_app php artisan schedule:list
```

---


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
- `desafio_queue`: Worker para processar as filas automaticamente
- `desafio_mockoon`: Mock das APIs (porta 3001)

### 3. Instalar dependências e rodar Migrations
```bash
docker exec -it desafio_app composer install
docker exec -it desafio_app php artisan migrate
```

---

## 📦 Execução do Job de Anúncios

O sistema possui duas formas de processar os anúncios:

### A. Execução Manual (Command)
Você pode disparar a busca de anúncios manualmente a qualquer momento via Artisan:
```bash
docker exec -it desafio_app php artisan meli:fetch-ads
```
**Opções disponíveis:**
- `--seller-id`: Define um ID de vendedor específico (Padrão: 252254392).
- `--limit`: Define a quantidade de anúncios a buscar (Padrão: 30).

### B. Execução Agendada (Scheduler)
O Laravel está configurado para executar o comando `meli:fetch-ads` **automaticamente a cada 10 minutos**. A configuração está definida em `bootstrap/app.php` e inclui:
- Execução a cada 10 minutos
- Proteção contra execução simultânea (`withoutOverlapping()`)
- Execução apenas em um servidor (`onOneServer()`)

**Para Visualizar os Jobs agendados:**
```bash
docker exec -it desafio_app php artisan schedule:list
```
---

## 🔗 Consultar Anúncios

### Endpoint de Listagem
Para consultar os anúncios já processados e armazenados no banco de dados:
```bash
GET /api/items
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


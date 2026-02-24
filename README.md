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
O Laravel está configurado para executar este comando automaticamente **a cada 10 minutos**. No ambiente Docker, você pode simular o scheduler rodando:
```bash
docker exec -it desafio_app php artisan schedule:run
```

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

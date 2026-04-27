# MadNutz

Loja virtual de suplementos inspirada em [madnutz.com.br](https://madnutz.com.br), desenvolvida como clone funcional com foco em fidelidade visual e fluxo de compra completo.

## Objetivo

Replicar a experiência de compra da MadNutz — catálogo de produtos, kits configuráveis por sabor, kit personalizado com múltiplos produtos, área do cliente com autenticação por OTP, carrinho, checkout e painel administrativo.

## Stack

| Camada | Tecnologia |
| --- | --- |
| Backend | PHP 8.3 + Laravel 13 |
| Frontend | Vue 3 (Composition API / `<script setup>`) |
| Estilos | Tailwind CSS v4 (config via `@theme` no CSS, sem `tailwind.config.js`) |
| Build | Vite 8 + `laravel-vite-plugin` |
| Banco de dados | SQLite (dev) |
| Autenticação | Laravel Sanctum |
| Componentes UI | PrimeVue 4 + PrimeIcons |
| Roteamento SPA | Vue Router 4 |
| Pagamentos | B4You (gateway via API key) |

## Pré-requisitos

- PHP >= 8.3 com extensões `pdo_sqlite`, `mbstring`, `openssl`
- Composer
- Node.js >= 20 + npm

## Primeira execução

```bash
# 1. Clone e entre no diretório
git clone <repo-url> madnutz
cd madnutz

# 2. Instala dependências PHP + JS, cria .env, gera APP_KEY, roda migrations e build
composer run setup
```

O script `setup` faz tudo automaticamente. Se preferir passo a passo:

```bash
composer install

cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate

php artisan db:seed          # categorias, produtos, kits e admin

npm install
npm run build
```

### Credenciais do admin (após seed)

| Campo | Valor |
| --- | --- |
| E-mail | `admin@madnutz.com.br` |
| Senha | `madnutz@2026` |
| URL | `http://localhost:8000/admin` |

## Rodando em desenvolvimento

```bash
composer run dev
```

Sobe em paralelo:

- `php artisan serve` → [http://localhost:8000](http://localhost:8000)
- `npm run dev` → Vite HMR
- `php artisan queue:listen` → fila de jobs
- `php artisan pail` → log em tempo real

## Variáveis de ambiente

Copie `.env.example` para `.env` e ajuste conforme necessário. As mais importantes:

```dotenv
APP_URL=http://localhost:8000

# Banco (SQLite por padrão, sem configuração extra)
DB_CONNECTION=sqlite

# Gateway de pagamentos B4You
B4YOU_API_KEY=
B4YOU_WEBHOOK_SECRET=
VITE_B4YOU_API_KEY=
VITE_B4YOU_BASE_URL=
```

## Testes

```bash
composer run test
```

## Estrutura de rotas (SPA)

| Rota | Página |
| --- | --- |
| `/` | Home |
| `/categoria/:slug` | Categoria |
| `/busca` | Busca |
| `/produto/:slug` | Produto |
| `/carrinho` | Carrinho |
| `/checkout` | Checkout |
| `/kits/:slug/sabores` | Seletor de sabores do kit |
| `/kit-personalizado` | Kit personalizado |
| `/minha-conta` | Área do cliente |
| `/admin/*` | Painel administrativo |

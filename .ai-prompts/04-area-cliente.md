# Área do Cliente — Plano de Implementação

> Marcar `[x]` conforme cada item for concluído. Nunca apagar itens — só marcar.

---

## Visão Geral

Área autenticada para clientes finais (separada do admin). Login por **senha** ou **OTP via e-mail** — o cliente escolhe na tela de login. Design idêntico às páginas de kit: fundo escuro `#131313`, vermelho `#FF003C`, amarelo `#e6eb00`.

---

## FASE 1 — Backend: Banco de Dados

### Migrations
- [ ] `create_customers_table` — id, name, email (unique), phone (nullable), password (nullable), email_verified_at, timestamps
- [ ] `create_customer_otps_table` — id, email, code (hashed), expires_at, used_at (nullable), timestamps
- [ ] `create_customer_favorites_table` — id, customer_id, favoritable_type, favoritable_id, timestamps (unique: customer+type+id)
- [ ] `add_customer_id_to_orders_table` — customer_id nullable FK → customers

### Models
- [ ] `App\Models\Customer` — HasMany orders, HasMany favorites (morphTo), HasMany otps
- [ ] `App\Models\CustomerOtp` — BelongsTo customer (por email), scope: valid (não expirado, não usado)
- [ ] `App\Models\CustomerFavorite` — BelongsTo customer, morphTo favoritable (Product | Kit)

---

## FASE 2 — Backend: Autenticação

### Sanctum Guard
- [ ] Registrar guard `customers` em `config/auth.php` usando provider `customers` → model `Customer`
- [ ] Configurar Sanctum para aceitar tokens do guard `customers`

### CustomerAuthController (`/api/customer/auth/`)
- [ ] `POST /register` — cria conta com nome + email + senha (opcional). Retorna token.
- [ ] `POST /login-password` — email + senha → retorna token sanctum
- [ ] `POST /send-otp` — gera código 6 dígitos, salva hash em `customer_otps`, dispara e-mail. Rate limit: 1 por minuto por e-mail.
- [ ] `POST /verify-otp` — valida código (expira em 10 min), marca como usado, upsert customer se não existir, retorna token
- [ ] `GET /me` — retorna dados do cliente autenticado (guard customers)
- [ ] `POST /logout` — revoga token atual

### Middleware
- [ ] `CustomerAuth` middleware — verifica sanctum token do guard `customers`, retorna 401 se não autenticado

### OTP Mail
- [ ] `App\Mail\CustomerOtpMail` — Mailable com código
- [ ] View `resources/views/emails/customer-otp.blade.php` — e-mail simples com código grande

---

## FASE 3 — Backend: API do Cliente

### CustomerOrderController (`/api/customer/orders/`) — requer CustomerAuth
- [ ] `GET /` — lista pedidos do cliente autenticado (paginado, mais recentes primeiro)
- [ ] `GET /{id}` — detalhe de um pedido (valida que pertence ao cliente)
- [ ] `POST /{id}/repeat` — retorna itens do pedido formatados para o front adicionar ao carrinho

### CustomerFavoriteController (`/api/customer/favorites/`) — requer CustomerAuth
- [ ] `GET /` — lista favoritos (com product/kit carregado)
- [ ] `POST /toggle` — body: `{ type: 'product'|'kit', id }` — adiciona se não existe, remove se já existe. Retorna `{ favorited: bool }`

### CustomerProfileController (`/api/customer/profile/`) — requer CustomerAuth
- [ ] `PUT /` — atualiza name, phone, email (se mudar email → exige reconfirmação OTP)
- [ ] `PUT /password` — define ou troca senha (requer senha atual se já tinha)

### Registrar rotas em `routes/api.php`
- [ ] Grupo público: register, login-password, send-otp, verify-otp
- [ ] Grupo protegido (CustomerAuth): me, logout, orders, favorites, profile

---

## FASE 4 — Frontend: Fundação

### Store
- [ ] `resources/js/stores/customer.js` — token (localStorage), customer ref, isLoggedIn computed, funções: login(token, data), logout(), fetchMe()

### Layout
- [ ] `resources/js/layouts/CustomerLayout.vue`
  - Fundo `#131313` + glow vermelho
  - Header sticky: logo MadNutz + nav (Pedidos, Favoritos, Perfil) + botão Sair
  - Cores: texto branco, active `#FF003C`, hover `#e6eb00`
  - Mobile: menu hamburguer

### Router — novas rotas (dentro do CustomerLayout)
- [ ] `/minha-conta` → redirect para `/minha-conta/login` ou `/minha-conta/pedidos`
- [ ] `/minha-conta/login` → `CustomerLoginPage` (guest only)
- [ ] `/minha-conta/pedidos` → `CustomerOrdersPage` (requer auth)
- [ ] `/minha-conta/pedidos/:id` → `CustomerOrderDetailPage` (requer auth)
- [ ] `/minha-conta/favoritos` → `CustomerFavoritesPage` (requer auth)
- [ ] `/minha-conta/perfil` → `CustomerProfilePage` (requer auth)

### Composable
- [ ] `resources/js/composables/useCustomerApi.js` — axios com header Authorization do token do cliente

---

## FASE 5 — Frontend: Tela de Login / Registro

### `pages/customer/LoginPage.vue`
- [ ] Campo e-mail
- [ ] Toggle: **"Usar senha"** vs **"Receber código por e-mail"**
- [ ] Modo senha: campo password + botão entrar
- [ ] Modo OTP: botão "Enviar código" → input 6 dígitos (auto-avança entre campos) + botão confirmar
- [ ] Link "Criar conta" → modal/inline com nome + e-mail (+ senha opcional)
- [ ] Feedback de erro (credenciais inválidas, código expirado, etc.)
- [ ] Design: dark `#131313`, card `glass-dark`, botão `#FF003C`

---

## FASE 6 — Frontend: Dashboard e Pedidos

### `pages/customer/OrdersPage.vue`
- [ ] Lista de pedidos em cards: número, data, status badge (cores por status), valor total
- [ ] Botão "Repetir pedido" → chama `/repeat` e adiciona ao carrinho, redireciona para `/carrinho`
- [ ] Estado vazio: ilustração + CTA para a loja
- [ ] Paginação

### `pages/customer/OrderDetailPage.vue`
- [ ] Itens do pedido com thumbnail, nome, qty, preço
- [ ] Para kits: expandir sabores selecionados
- [ ] Timeline de status (Aguardando pagamento → Pago → Enviado → Entregue)
- [ ] Botão "Repetir este pedido"

---

## FASE 7 — Frontend: Favoritos

### `pages/customer/FavoritesPage.vue`
- [ ] Grid de produtos e kits favoritados (mesmo card da loja, com botão de remover)
- [ ] Botão "Ver produto" / "Escolher sabores"
- [ ] Estado vazio com CTA

### `components/FavoriteButton.vue`
- [ ] Ícone coração: vazio (não favoritado) → preenchido vermelho (favoritado)
- [ ] Chama `toggle` na API, atualiza estado local
- [ ] Se não logado: redireciona para `/minha-conta/login`
- [ ] Integrar no `ProductCard.vue` e nas páginas de kit

---

## FASE 8 — Frontend: Perfil

### `pages/customer/ProfilePage.vue`
- [ ] Form: nome, e-mail, telefone
- [ ] Seção senha: "Definir senha" (se nunca teve) / "Trocar senha"
- [ ] Botão sair (logout)

---

## FASE 9 — Integração com Pedidos

- [ ] No `POST /api/orders` (checkout existente): se vier header Authorization de customer → salvar `customer_id` no pedido
- [ ] `Order` model: `BelongsTo Customer` (nullable)
- [ ] Garantir que o cliente só veja os próprios pedidos

---

## Ordem de execução sugerida

```
Fase 1 → Fase 2 → Fase 3 → Fase 4 → Fase 5 → Fase 6 → Fase 7 → Fase 8 → Fase 9
```

Cada fase é independente o suficiente para testar antes de avançar.

---

## Notas técnicas

- OTP: código de 6 dígitos, bcrypt no banco, expira em **10 minutos**, máximo **3 tentativas** antes de invalidar
- Rate limit no `send-otp`: 1 disparo por minuto por e-mail (via Laravel throttle)
- Sanctum: usar `tokenCan` para separar tokens de customer vs admin se necessário
- `customer_favorites` usa `morphTo` para poder favoritar tanto `Product` quanto `Kit` sem tabelas separadas
- A senha em `customers` é **nullable** — cliente que só usa OTP nunca precisa definir senha

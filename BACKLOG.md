# MadNutz — Backlog

> Última atualização: 2026-04-17
> Stack: Laravel 13 + Vue 3 + Tailwind v4 + PostgreSQL

---

## Legenda
- `[ ]` Pendente
- `[~]` Em andamento
- `[x]` Concluído
- `[?]` Bloqueado / aguardando decisão

---

## SPRINT 1 — Base do backoffice e modelo de dados

### BD-01 — Refatorar banco de dados `[x]`
- Remover tabelas `categories`, `product_variants`
- Adaptar `products`: remover `category_id`, adicionar `size`
- Criar `kits` (id, name, slug, description, price, image, active, featured, sort_order)
- Criar `kit_products` (kit_id, product_id, quantity)
- Criar `orders` (id, customer_name, email, phone, address json, total, status, payment_ref, paid_at)
- Criar `order_items` (order_id, kit_id, kit_name_snapshot, price_snapshot, quantity)
- Criar `custom_kit_sessions` (id, session_id, items json, total_preview) — placeholder

### AUTH-01 — Admin: autenticação `[x]`
- Instalar Laravel Breeze (blade, sem Vue)
- Proteger rotas `/admin/*` com middleware `auth`
- Adicionar campo `is_admin` em users
- Middleware `AdminMiddleware` — bloqueia não-admins
- Seed: criar usuário admin padrão

### ADMIN-01 — Admin: CRUD de Produtos `[x]`
- Listagem com busca e paginação
- Criar/editar: nome, descrição, tamanho (50g/100g/etc.), preço de referência, imagem
- Upload de imagem (storage local)
- Soft delete / ativar-desativar
- **Nota:** preço é figurativo, não aparece na loja diretamente

### ADMIN-02 — Admin: CRUD de Kits `[x]`
- Listagem com drag-to-reorder (sort_order)
- Criar/editar kit:
  - Nome, descrição, preço de venda, imagem, ativo/destaque
  - Seleção de produtos: adicionar produto + quantidade (mín. 2 produtos)
  - Preview de itens do kit
- Validação: mínimo 2 produtos no kit

### ADMIN-03 — Admin: Pedidos `[x]`
- Listagem com filtros: status, data, busca (nome/email/ref pagamento)
- Detalhe: dados cliente, itens, valor, status de pagamento
- Ações: marcar enviado, cancelar
- **Status possíveis:** `pending` → `paid` → `shipped` → `cancelled`

---

## SPRINT 2 — Frontend loja

### FRONT-01 — Kits vindos do banco `[ ]`
- Substituir kits hardcoded em HomePage.vue pela API `/api/kits`
- Endpoint `GET /api/kits` — retorna kits ativos com produtos
- Endpoint `GET /api/kits/{slug}` — detalhe do kit

### FRONT-02 — Página de detalhe do kit `[ ]`
- Imagem, nome, descrição, composição (lista de produtos), preço
- Botão "QUERO ESSE" → adiciona ao carrinho
- Quantidade

### FRONT-03 — Kit personalizado `[ ]`
- Interface: usuário seleciona produtos disponíveis + quantidades
- Exibe prévia dos itens selecionados
- Preço: **placeholder** — lógica a definir com cliente
- Opções a discutir:
  - A) Soma preços individuais com desconto progressivo
  - B) Preço fixo por faixa de quantidade
  - C) Admin define preço base + adicional por produto

### FRONT-04 — Checkout `[ ]`
- Formulário: nome, e-mail, telefone, endereço (CEP auto-complete)
- Resumo do pedido (kits + quantidades + total)
- Criação do `order` no banco com status `pending`
- Redirecionamento para pagamento B4you

---

## SPRINT 3 — Pagamento e integrações

### PAY-01 — Integração B4you `[?]`
- **Bloqueado:** aguardando credenciais e documentação do cliente
- O que será feito:
  - Ao confirmar pedido → chamar API B4you → gerar link/sessão de pagamento
  - Redirecionar cliente para checkout B4you
  - Webhook de confirmação → atualizar `order.status` para `paid`
  - E-mail de confirmação ao cliente
- **Perguntas para o cliente:**
  - Qual o tipo de produto no B4you? (produto físico / digital)
  - B4you tem webhook de confirmação de pagamento?
  - Precisa de split de pagamento?

### PAY-02 — Regra de preço kit personalizado `[?]`
- **Bloqueado:** decisão do cliente
- Implementar lógica de preço após alinhamento

---

## SPRINT 4 — Melhorias e polimento

### UX-01 — Melhorias de UX `[ ]`
- Loading states em todas as páginas
- Toast notifications (item adicionado, pedido feito, erro)
- Animação marquee no hero (já existe, verificar mobile)
- Responsividade completa (mobile-first review)

### SEO-01 — SEO básico `[ ]`
- Meta tags dinâmicas por página (título, description, og:image)
- Sitemap
- Robots.txt

### PWA-01 — PWA `[ ]`
- manifest.json
- Service worker (cache de assets)
- Ícone de app

---

## Decisões pendentes com o cliente

| # | Questão | Impacto |
|---|---|---|
| D1 | Regra de preço do kit personalizado | FRONT-03, PAY-02 |
| D2 | Credenciais e documentação B4you | PAY-01 |
| D3 | Precisa de e-mail transacional? (qual provedor?) | PAY-01 |
| D4 | Entrega: como calcular frete? (Correios, fixo, grátis acima de X?) | FRONT-04 |
| D5 | Admin precisa de mais de 1 usuário? (roles?) | AUTH-01 |

---

## Estrutura de arquivos relevante

```
app/
  Http/Controllers/
    Admin/           ← controllers do backoffice (blade)
    Api/             ← controllers da API pública (JSON)
  Models/
    Product, Kit, KitProduct, Order, OrderItem
resources/
  views/admin/       ← blade views do backoffice
  js/
    pages/           ← Vue SPA (loja pública)
    layouts/
routes/
  web.php            ← SPA catch-all + /admin/*
  api.php            ← API pública /api/*
```

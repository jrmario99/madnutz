# Integração B4you — MadNutz

## O que é o B4you

B4you é uma plataforma brasileira de checkout e pagamentos online. Para ativar a integração no MadNutz basta inserir a API key nas variáveis de ambiente — toda a estrutura já está pronta.

---

## Como ativar

### 1. Obter as credenciais no painel B4you

1. Acesse sua conta em [b4you.com.br](https://b4you.com.br)
2. Vá em **Configurações → Integrações → API**
3. Copie a **API Key** e o **Webhook Secret**

### 2. Preencher o `.env`

```env
# Backend (Laravel)
B4YOU_API_KEY=sua_api_key_aqui
B4YOU_WEBHOOK_SECRET=seu_webhook_secret_aqui

# Frontend (Vite — deve começar com VITE_)
VITE_B4YOU_API_KEY=sua_api_key_aqui
VITE_B4YOU_BASE_URL=https://api.b4you.com.br/v1
```

> `VITE_B4YOU_BASE_URL` é opcional — o valor padrão já está configurado em `b4you.js`.

### 3. Reiniciar os serviços

```bash
# Recarregar config do Laravel
php artisan config:clear

# Recompilar o frontend
npm run build
```

Pronto. O sistema detecta automaticamente a presença da `VITE_B4YOU_API_KEY` e ativa o fluxo de pagamento.

---

## Fluxo de pagamento

```
Cliente preenche checkout
        ↓
POST /api/orders  →  Pedido criado no banco (status: pending)
        ↓
Frontend chama B4you API  →  Recebe checkout_url
        ↓
Cliente é redirecionado para pagar no B4you
        ↓
B4you chama POST /api/webhooks/b4you
        ↓
Pedido atualizado (status: paid / cancelled)
```

---

## Arquivos relevantes

| Arquivo | Responsabilidade |
|---|---|
| `resources/js/services/b4you.js` | Chama a API do B4you e retorna a URL de checkout |
| `resources/js/pages/CheckoutPage.vue` | Orquestra: cria o pedido e redireciona para o B4you |
| `app/Http/Controllers/Api/WebhookController.php` | Recebe eventos do B4you e atualiza o pedido |
| `app/Http/Controllers/Api/OrderController.php` | Cria o pedido no banco antes do pagamento |
| `config/services.php` | Configuração `b4you.api_key` e `b4you.webhook_secret` |
| `routes/api.php` | Rota `POST /api/webhooks/b4you` |

---

## Eventos do webhook tratados

| Evento B4you | Status do pedido |
|---|---|
| `payment.approved` | `paid` + grava `paid_at` e `payment_ref` |
| `payment.refunded` | `cancelled` |
| `payment.cancelled` | `cancelled` |
| Outros | Ignorado (log registrado) |

---

## Segurança do webhook

Quando `B4YOU_WEBHOOK_SECRET` estiver preenchido, o `WebhookController` valida a assinatura `X-B4You-Signature` de cada requisição antes de processar.

```php
// Validação automática em WebhookController::b4you()
$expected = hash_hmac('sha256', $request->getContent(), $secret);
if (!hash_equals($expected, $signature)) {
    return response()->json(['error' => 'Invalid signature'], 401);
}
```

---

## Sem API key (ambiente local / dev)

Se `VITE_B4YOU_API_KEY` estiver vazio, o checkout **não redireciona para o B4you** — o pedido é criado normalmente no banco e a tela de confirmação é exibida. Isso permite desenvolver e testar sem credenciais reais.

---

## Payload enviado ao B4you

```json
{
  "order_ref": "MN-ABC123",
  "amount": 4990,
  "customer": {
    "name": "João Silva",
    "email": "joao@email.com",
    "phone": "(85) 99999-9999"
  },
  "items": [
    { "title": "Kit Mad Started", "quantity": 1, "price": 4990 }
  ],
  "webhook_url": "https://seudominio.com.br/api/webhooks/b4you",
  "success_url": "https://seudominio.com.br/pedido-confirmado?ref=MN-ABC123",
  "cancel_url":  "https://seudominio.com.br/checkout"
}
```

> Valores monetários em **centavos** (sem vírgula).

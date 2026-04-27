/**
 * B4you payment integration
 *
 * To activate: set VITE_B4YOU_API_KEY in .env
 * Docs: https://docs.b4you.com.br
 */

const API_KEY  = import.meta.env.VITE_B4YOU_API_KEY  ?? '';
const BASE_URL = import.meta.env.VITE_B4YOU_BASE_URL ?? 'https://api.b4you.com.br/v1';

export const b4youEnabled = !!API_KEY;

/**
 * Create a B4you checkout session and return the redirect URL.
 *
 * @param {object} order   - Order returned by POST /api/orders
 * @param {object} customer - { name, email, phone }
 * @param {Array}  items   - Cart items [{ name, price, qty }]
 * @returns {Promise<string>} Checkout URL to redirect the user
 */
export async function createCheckout(order, customer, items) {
    const response = await fetch(`${BASE_URL}/checkout`, {
        method: 'POST',
        headers: {
            'Content-Type':  'application/json',
            'Authorization': `Bearer ${API_KEY}`,
        },
        body: JSON.stringify({
            order_ref:    order.order_number,
            amount:       Math.round(order.total * 100), // centavos
            customer: {
                name:  customer.name,
                email: customer.email,
                phone: customer.phone,
            },
            items: items.map(i => ({
                title:    i.name,
                quantity: i.qty,
                price:    Math.round(i.price * 100),
            })),
            // URL que o B4you chama ao confirmar pagamento
            webhook_url:  `${window.location.origin}/api/webhooks/b4you`,
            // Onde redirecionar após pagamento
            success_url:  `${window.location.origin}/pedido-confirmado?ref=${order.order_number}`,
            cancel_url:   `${window.location.origin}/checkout`,
        }),
    });

    if (!response.ok) {
        const err = await response.json().catch(() => ({}));
        throw new Error(err.message ?? 'Erro ao criar checkout B4you');
    }

    const data = await response.json();
    return data.checkout_url; // redirecionar para cá
}

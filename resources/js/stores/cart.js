import { ref, computed } from 'vue';

const items  = ref(JSON.parse(localStorage.getItem('cart') ?? '[]'));
const coupon = ref(JSON.parse(localStorage.getItem('cart_coupon') ?? 'null'));

function persist() {
    localStorage.setItem('cart', JSON.stringify(items.value));
}

function persistCoupon() {
    localStorage.setItem('cart_coupon', JSON.stringify(coupon.value));
}

export function useCart() {
    const count = computed(() => items.value.reduce((s, i) => s + i.qty, 0));
    const total = computed(() => items.value.reduce((s, i) => s + i.price * i.qty, 0));

    // Frete grátis quando todos os itens têm free_shipping !== false
    const freeShipping = computed(() =>
        items.value.length > 0 && items.value.every(i => i.free_shipping !== false)
    );
    const shipping = computed(() => freeShipping.value ? 0 : 9.99);

    const discount = computed(() => coupon.value?.discount ?? 0);

    const orderTotal = computed(() =>
        Math.max(0, total.value + shipping.value - discount.value)
    );

    function add(product, variant = null, qty = 1) {
        const key = `${product.id}-${variant?.id ?? 0}`;
        const existing = items.value.find(i => i.key === key);
        if (existing) {
            existing.qty += qty;
        } else {
            const price = product.price + (variant?.price_modifier ?? 0);
            items.value.push({
                key,
                product_id:  product.id,
                name:        product.name,
                brand:       product.brand,
                price,
                thumbnail:   product.thumbnail,
                variant:     variant?.value ?? null,
                qty,
                free_shipping: product.free_shipping ?? false,
                kit_products: product.kit_products ?? [],
            });
        }
        persist();
    }

    function remove(key) {
        items.value = items.value.filter(i => i.key !== key);
        persist();
    }

    function updateQty(key, qty) {
        const item = items.value.find(i => i.key === key);
        if (item) { item.qty = qty; persist(); }
    }

    function addCustomKit(customItems, totalPrice, qty = 1) {
        const key = `custom-${Date.now()}`;
        items.value.push({
            key,
            product_id:   null,
            name:         'Kit Personalizado',
            brand:        'MadNutz',
            price:        totalPrice,
            thumbnail:    null,
            variant:      null,
            qty,
            is_custom:    true,
            free_shipping: true,
            custom_items: customItems,
        });
        persist();
    }

    function addSlottedKit(kit, selectedItems, qty = 1) {
        const key = `kit-${kit.id}-${Date.now()}`;
        items.value.push({
            key,
            product_id:     null,
            name:           kit.name,
            brand:          'MadNutz',
            price:          kit.effective_price,
            thumbnail:      kit.image ?? null,
            variant:        null,
            qty,
            is_kit:         true,
            kit_id:         kit.id,
            kit_slug:       kit.slug,
            free_shipping:  kit.free_shipping ?? true,
            kit_selections: selectedItems,
        });
        persist();
    }

    function updateKitSelections(key, newSelections) {
        const item = items.value.find(i => i.key === key);
        if (item) { item.kit_selections = newSelections; persist(); }
    }

    function updateCustomKit(key, customItems, totalPrice) {
        const item = items.value.find(i => i.key === key);
        if (item) { item.custom_items = customItems; item.price = totalPrice; persist(); }
    }

    function addKitByName(name, price, qty, isCustom = false, customItems = null) {
        const key = `kit-repeat-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
        items.value.push({
            key,
            product_id:   null,
            name,
            brand:        'MadNutz',
            price,
            thumbnail:    null,
            variant:      null,
            qty,
            is_custom:    isCustom,
            free_shipping: true,
            custom_items: customItems ?? null,
        });
        persist();
    }

    async function applyCoupon(code) {
        const res = await fetch('/api/coupons/validate', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify({ code, order_total: total.value }),
        });

        const body = await res.json();

        if (!res.ok) {
            throw new Error(body.message ?? 'Cupom inválido.');
        }

        coupon.value = body;
        persistCoupon();
        return body;
    }

    function removeCoupon() {
        coupon.value = null;
        persistCoupon();
    }

    function clear() {
        items.value = [];
        coupon.value = null;
        persist();
        persistCoupon();
    }

    return {
        items, count, total, coupon, discount, shipping, freeShipping, orderTotal,
        add, addCustomKit, addSlottedKit, addKitByName,
        updateKitSelections, updateCustomKit,
        remove, updateQty, clear,
        applyCoupon, removeCoupon,
    };
}

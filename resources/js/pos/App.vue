<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, toRefs, watch } from 'vue';
import { createApi } from './api';
import { playSound, setSoundEnabled } from './sounds';

const props = defineProps({
    initialBootstrap: { type: Object, required: true },
    apiBase: { type: String, required: true },
    dashboardUrl: { type: String, required: true },
    locale: { type: String, default: 'en' },
});

const THEME_KEY = 'pos.theme';
const SOUND_KEY = 'pos.sound';

const api = createApi(props.apiBase);
const state = reactive({
    bootstrap: props.initialBootstrap,
    online: navigator.onLine,
    loading: false,
    error: '',
    success: '',
    search: '',
    categoryId: null,
    products: [],
    productsPage: 1,
    productsLastPage: 1,
    cart: [],
    customer: null,
    discountTotal: 0,
    notes: '',
    barcodeBuffer: '',
    checkoutBusy: false,
    theme: localStorage.getItem(THEME_KEY) === 'light' ? 'light' : 'dark',
    soundOn: localStorage.getItem(SOUND_KEY) !== '0',
});

const {
    bootstrap, online, loading, error, success, search, categoryId, products,
    productsPage, productsLastPage, cart, customer, discountTotal, notes, checkoutBusy,
    theme, soundOn,
} = toRefs(state);

const modal = reactive({
    openSession: false,
    payment: false,
    customer: false,
    cash: false,
    suspended: false,
    closeShift: false,
    receipt: false,
    variantPick: false,
});

const forms = reactive({
    open: { opening_balance: '0', opening_notes: '', device_name: '' },
    cash: { type: 'in', amount: '', reason: '', notes: '' },
    customerSearch: '',
    customerResults: [],
    quickCustomer: { name: '', phone: '' },
    paymentLines: [{ type: 'cash', amount: '', reference: '' }],
    close: { actual_cash: '0', actual_card: '0', actual_transfer: '0', actual_other: '0', closing_notes: '', difference_reason: '' },
    shiftSummary: null,
    receipt: null,
    suspendedList: [],
    variantProduct: null,
    selectedRegisterId: props.initialBootstrap?.register?.id || null,
});

const searchTimer = ref(null);
const barcodeTimer = ref(null);

const register = computed(() => state.bootstrap.register);
const session = computed(() => state.bootstrap.session);
const canSell = computed(() => session.value?.status === 'opened');
const cartCount = computed(() => state.cart.reduce((n, l) => n + Number(l.quantity || 0), 0));
const subtotal = computed(() => state.cart.reduce((sum, l) => sum + (Number(l.unit_price) * Number(l.quantity) - Number(l.discount || 0)), 0));
const taxTotal = computed(() => state.cart.reduce((sum, l) => sum + Number(l.tax || 0), 0));
const grandTotal = computed(() => Math.max(0, subtotal.value + taxTotal.value - Number(state.discountTotal || 0)));
const t = (en, ar) => (props.locale === 'ar' ? ar : en);

function money(n) {
    return Number(n || 0).toFixed(2);
}

function applyTheme(value) {
    state.theme = value === 'light' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', state.theme);
    localStorage.setItem(THEME_KEY, state.theme);
}

function toggleTheme() {
    applyTheme(state.theme === 'dark' ? 'light' : 'dark');
    beep('click');
}

function toggleSound() {
    state.soundOn = !state.soundOn;
    setSoundEnabled(state.soundOn);
    localStorage.setItem(SOUND_KEY, state.soundOn ? '1' : '0');
    if (state.soundOn) beep('click');
}

function beep(kind) {
    if (!state.soundOn) return;
    playSound(kind);
}

function setError(e) {
    state.error = e?.message || String(e);
    state.success = '';
    beep('error');
}

function clearFlash() {
    state.error = '';
    state.success = '';
}

function normalizeReceipt(result, fallbackTotal = '0.00') {
    const payload = result?.sale ? result : (result?.data || result || {});
    const sale = payload.sale || {};
    const invoice = payload.invoice || {};
    const id = sale.id || invoice.id || null;
    const total = sale.grand_total ?? invoice.grand_total ?? fallbackTotal;
    const number = sale.receipt_number || sale.document_number || (id ? `#${id}` : '—');
    const receiptUrl = payload.receipt_url
        || (id ? `${window.location.origin}/app/pos/receipt/${id}` : null);

    return {
        sale,
        invoice,
        change: payload.change ?? '0.00',
        paid_total: payload.paid_total ?? null,
        receipt_url: receiptUrl,
        display_number: number,
        display_total: total,
        display_change: payload.change ?? '0.00',
        sale_id: id,
    };
}

async function refreshBootstrap(registerId = null) {
    state.bootstrap = await api.bootstrap(registerId || forms.selectedRegisterId || register.value?.id);
    forms.selectedRegisterId = state.bootstrap.register?.id || null;
    if (!session.value || session.value.status !== 'opened') {
        modal.openSession = true;
    }
}

async function loadProducts(reset = false) {
    if (reset) {
        state.productsPage = 1;
        state.products = [];
    }
    state.loading = true;
    try {
        const page = await api.products({
            search: state.search || undefined,
            category_id: state.categoryId || undefined,
            page: state.productsPage,
            per_page: 24,
        });
        state.products = reset ? page.data : [...state.products, ...page.data];
        state.productsLastPage = page.last_page || 1;
    } catch (e) {
        setError(e);
    } finally {
        state.loading = false;
    }
}

function addProduct(product, variant = null) {
    if (!canSell.value) {
        modal.openSession = true;
        beep('error');
        return;
    }
    const activeVariants = product.variants || [];
    if (!variant && activeVariants.length > 1) {
        forms.variantProduct = product;
        modal.variantPick = true;
        beep('click');
        return;
    }
    if (!variant && activeVariants.length === 1) {
        variant = activeVariants[0];
    }
    const key = `${product.id}:${variant?.id || 0}`;
    const existing = state.cart.find((l) => l.key === key);
    if (existing) {
        existing.quantity = Number(existing.quantity) + 1;
        beep('add');
        return;
    }
    state.cart.push({
        key,
        product_id: product.id,
        product_variant_id: variant?.id || null,
        name: product.name + (variant?.sku ? ` (${variant.sku})` : ''),
        sku: variant?.sku || product.sku,
        unit_price: Number(variant?.price ?? product.price ?? 0),
        quantity: 1,
        discount: 0,
        tax: 0,
        notes: '',
        stock: variant?.quantity ?? product.quantity,
    });
    beep('add');
}

function changeQty(line, delta) {
    line.quantity = Math.max(1, Number(line.quantity) + delta);
    beep(delta > 0 ? 'add' : 'click');
}

function removeLine(line) {
    state.cart = state.cart.filter((l) => l.key !== line.key);
    beep('remove');
}

function clearCart() {
    state.cart = [];
    state.customer = null;
    state.discountTotal = 0;
    state.notes = '';
}

async function onBarcode(code) {
    clearFlash();
    try {
        const hits = await api.barcode(code);
        if (!hits.length) {
            setError(t('Product not found for barcode', 'لا يوجد منتج لهذا الباركود'));
            return;
        }
        beep('scan');
        if (hits.length === 1) {
            const p = hits[0];
            const variant = p.matched_variant_id
                ? (p.variants || []).find((v) => v.id === p.matched_variant_id)
                : null;
            addProduct(p, variant);
            state.success = t('Added to cart', 'تمت الإضافة إلى السلة');
            return;
        }
        forms.variantProduct = hits[0];
        modal.variantPick = true;
    } catch (e) {
        setError(e);
    }
}

function closeModals() {
    modal.payment = false;
    modal.customer = false;
    modal.cash = false;
    modal.suspended = false;
    modal.closeShift = false;
    modal.variantPick = false;
    modal.receipt = false;
}

function onKeydown(e) {
    if (e.key === 'F2') {
        e.preventDefault();
        document.getElementById('pos-search')?.focus();
        return;
    }
    if (e.key === 'F4') {
        e.preventDefault();
        modal.customer = true;
        return;
    }
    if (e.key === 'F8') {
        e.preventDefault();
        openPayment();
        return;
    }
    if (e.key === 'F9') {
        e.preventDefault();
        suspendSale();
        return;
    }
    if (e.key === 'Escape') {
        closeModals();
        return;
    }

    const tag = (e.target?.tagName || '').toLowerCase();
    if (['input', 'textarea', 'select'].includes(tag)) {
        return;
    }
    if (e.key === 'Enter' && state.barcodeBuffer) {
        const code = state.barcodeBuffer;
        state.barcodeBuffer = '';
        onBarcode(code);
        return;
    }
    if (e.key.length === 1) {
        state.barcodeBuffer += e.key;
        clearTimeout(barcodeTimer.value);
        barcodeTimer.value = setTimeout(() => { state.barcodeBuffer = ''; }, 120);
    }
}

async function openSession() {
    clearFlash();
    state.loading = true;
    try {
        await api.openSession({
            register_id: forms.selectedRegisterId || register.value.id,
            opening_balance: forms.open.opening_balance,
            opening_notes: forms.open.opening_notes,
            device_name: forms.open.device_name || navigator.userAgent.slice(0, 80),
        });
        await refreshBootstrap(forms.selectedRegisterId);
        modal.openSession = false;
        state.success = t('Shift opened', 'تم فتح الوردية');
    } catch (e) {
        setError(e);
    } finally {
        state.loading = false;
    }
}

function openPayment() {
    if (!state.cart.length) {
        setError(t('Cart is empty', 'السلة فارغة'));
        return;
    }
    if (!canSell.value) {
        modal.openSession = true;
        return;
    }
    forms.paymentLines = [{ type: 'cash', amount: money(grandTotal.value), reference: '' }];
    modal.payment = true;
    beep('click');
}

async function checkout() {
    if (state.checkoutBusy) return;
    clearFlash();
    state.checkoutBusy = true;
    const fallbackTotal = money(grandTotal.value);
    try {
        const paid = forms.paymentLines.reduce((s, p) => s + Number(p.amount || 0), 0);
        if (paid + 0.0001 < grandTotal.value) {
            throw new Error(t('Payment is less than total', 'المبلغ المدفوع أقل من الإجمالي'));
        }
        const result = await api.checkout({
            register_id: register.value.id,
            customer_id: state.customer?.id || null,
            notes: state.notes || null,
            discount_total: money(state.discountTotal),
            idempotency_key: `pos-${Date.now()}-${Math.random().toString(16).slice(2)}`,
            items: state.cart.map((l) => ({
                product_id: l.product_id,
                product_variant_id: l.product_variant_id,
                quantity: l.quantity,
                discount: l.discount,
                tax: l.tax,
                notes: l.notes || null,
            })),
            payments: forms.paymentLines.map((p) => ({
                type: p.type,
                amount: money(p.amount),
                reference: p.reference || null,
            })),
        });
        forms.receipt = normalizeReceipt(result, fallbackTotal);
        modal.payment = false;
        modal.receipt = true;
        clearCart();
        state.success = t('Sale completed', 'تم إتمام البيع');
        beep('success');
        await loadProducts(true);
    } catch (e) {
        setError(e);
    } finally {
        state.checkoutBusy = false;
    }
}

async function suspendSale() {
    if (!state.cart.length) return;
    clearFlash();
    try {
        await api.suspend({
            register_id: register.value.id,
            customer_id: state.customer?.id || null,
            notes: state.notes || null,
            discount_total: money(state.discountTotal),
            items: state.cart.map((l) => ({
                product_id: l.product_id,
                product_variant_id: l.product_variant_id,
                quantity: l.quantity,
                discount: l.discount,
                tax: l.tax,
                notes: l.notes || null,
            })),
        });
        clearCart();
        state.success = t('Sale suspended', 'تم تعليق البيع');
    } catch (e) {
        setError(e);
    }
}

async function loadSuspended() {
    clearFlash();
    try {
        forms.suspendedList = await api.suspended(register.value.id);
        modal.suspended = true;
    } catch (e) {
        setError(e);
    }
}

async function resumeSale(sale) {
    try {
        const data = await api.resume(sale.id);
        state.cart = (data.items || []).map((item) => ({
            key: `${item.product_id}:${item.product_variant_id || 0}`,
            product_id: item.product_id,
            product_variant_id: item.product_variant_id,
            name: item.description,
            sku: item.sku,
            unit_price: Number(item.unit_price),
            quantity: Number(item.quantity),
            discount: Number(item.discount || 0),
            tax: Number(item.tax || 0),
            notes: item.notes || '',
            stock: null,
        }));
        state.customer = data.customer_id ? { id: data.customer_id, name: data.customer_name } : null;
        state.discountTotal = Number(data.discount_total || 0);
        state.notes = data.notes || '';
        modal.suspended = false;
        await api.cancelSuspended(sale.id, 'resumed into cart');
        state.success = t('Suspended sale restored', 'تم استرجاع البيع المعلق');
    } catch (e) {
        setError(e);
    }
}

async function cancelSuspendedSale(sale) {
    try {
        await api.cancelSuspended(sale.id);
        await loadSuspended();
    } catch (e) {
        setError(e);
    }
}

async function searchCustomers() {
    try {
        forms.customerResults = await api.customers(forms.customerSearch);
    } catch (e) {
        setError(e);
    }
}

async function createCustomer() {
    try {
        const c = await api.createCustomer(forms.quickCustomer);
        state.customer = c;
        modal.customer = false;
    } catch (e) {
        setError(e);
    }
}

async function submitCash() {
    clearFlash();
    try {
        const payload = {
            register_id: register.value.id,
            amount: forms.cash.amount,
            reason: forms.cash.reason,
            notes: forms.cash.notes,
        };
        if (forms.cash.type === 'in') await api.cashIn(payload);
        else await api.cashOut(payload);
        modal.cash = false;
        state.success = t('Cash movement recorded', 'تم تسجيل حركة النقدية');
    } catch (e) {
        setError(e);
    }
}

async function prepareClose() {
    clearFlash();
    try {
        forms.shiftSummary = await api.shiftSummary(register.value.id);
        forms.close.actual_cash = forms.shiftSummary?.expected_by_tender?.cash || '0';
        forms.close.actual_card = forms.shiftSummary?.expected_by_tender?.card || '0';
        forms.close.actual_transfer = forms.shiftSummary?.expected_by_tender?.transfer || '0';
        forms.close.actual_other = forms.shiftSummary?.expected_by_tender?.other || '0';
        modal.closeShift = true;
    } catch (e) {
        setError(e);
    }
}

async function closeShift() {
    clearFlash();
    try {
        const result = await api.closeSession({
            register_id: register.value.id,
            ...forms.close,
        });
        forms.shiftSummary = result.summary;
        await refreshBootstrap(register.value.id);
        clearCart();
        modal.closeShift = false;
        state.success = t('Shift closed', 'تم إغلاق الوردية');
        modal.openSession = true;
    } catch (e) {
        setError(e);
    }
}

function printReceipt() {
    let url = forms.receipt?.receipt_url
        || (forms.receipt?.sale_id ? `${window.location.origin}/app/pos/receipt/${forms.receipt.sale_id}` : null);
    if (!url) {
        setError(t('Receipt URL is missing', 'رابط الإيصال غير متوفر'));
        return;
    }
    const joiner = url.includes('?') ? '&' : '?';
    url = `${url}${joiner}autoprint=1`;
    beep('click');
    const w = window.open(url, '_blank', 'noopener,noreferrer,width=480,height=720');
    if (!w) {
        setError(t('Pop-up blocked. Allow pop-ups to print.', 'تم حظر النافذة المنبثقة. اسمح بالنوافذ للطباعة.'));
        return;
    }
}

watch(search, () => {
    clearTimeout(searchTimer.value);
    searchTimer.value = setTimeout(() => loadProducts(true), 250);
});

onMounted(async () => {
    applyTheme(state.theme);
    setSoundEnabled(state.soundOn);
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('online', () => { state.online = true; });
    window.addEventListener('offline', () => { state.online = false; });
    if (!session.value || session.value.status !== 'opened') {
        modal.openSession = true;
    }
    await loadProducts(true);
    await nextTick();
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
});
</script>

<template>
  <div class="pos-shell">
    <header class="pos-header">
      <div class="meta">
        <span>{{ t('Branch', 'الفرع') }}: <strong>{{ register?.branch?.name || '—' }}</strong></span>
        <span>{{ t('Register', 'الصندوق') }}: <strong>{{ register?.name || '—' }}</strong></span>
        <span>{{ t('Cashier', 'الكاشير') }}: <strong>{{ bootstrap.user?.name || '—' }}</strong></span>
        <span>{{ t('Shift', 'الوردية') }}: <strong>#{{ session?.id || '—' }}</strong></span>
        <span>{{ t('Opened', 'الفتح') }}: <strong>{{ session?.opened_at || '—' }}</strong></span>
        <span :class="online ? 'pos-online' : 'pos-offline'">{{ online ? t('Online', 'متصل') : t('Offline', 'غير متصل') }}</span>
      </div>
      <div class="pos-toolbar">
        <button class="pos-btn" type="button" @click="toggleTheme">{{ theme === 'dark' ? t('Light', 'فاتح') : t('Dark', 'غامق') }}</button>
        <button class="pos-btn" type="button" @click="toggleSound">{{ soundOn ? t('Sound On', 'الصوت: تشغيل') : t('Sound Off', 'الصوت: إيقاف') }}</button>
        <button class="pos-btn" @click="modal.cash = true; beep('click')" :disabled="!canSell">{{ t('Cash In/Out', 'إدخال/إخراج نقدي') }}</button>
        <button class="pos-btn" @click="loadSuspended(); beep('click')" :disabled="!canSell">{{ t('Suspended', 'المعلّقة') }}</button>
        <button class="pos-btn" @click="prepareClose(); beep('click')" :disabled="!canSell">{{ t('Close Shift', 'إغلاق الوردية') }}</button>
        <a class="pos-btn" :href="dashboardUrl">{{ t('Dashboard', 'لوحة التحكم') }}</a>
      </div>
    </header>

    <div v-if="error" class="pos-error" style="margin: .75rem 1rem 0">{{ error }}</div>
    <div v-if="success" class="pos-success" style="margin: .75rem 1rem 0">{{ success }}</div>

    <main class="pos-main">
      <section class="pos-browser">
        <div class="pos-toolbar">
          <input id="pos-search" class="pos-input" v-model="search" :placeholder="t('Search name / SKU / barcode (F2)', 'بحث بالاسم / SKU / باركود (F2)')" @keyup.enter="loadProducts(true)" />
          <button class="pos-btn" @click="loadProducts(true)">{{ t('Search', 'بحث') }}</button>
        </div>
        <div class="pos-categories">
          <button class="pos-chip" :class="{ active: !categoryId }" @click="categoryId = null; loadProducts(true)">{{ t('All', 'الكل') }}</button>
          <button
            v-for="cat in bootstrap.categories || []"
            :key="cat.id"
            class="pos-chip"
            :class="{ active: categoryId === cat.id }"
            @click="categoryId = cat.id; loadProducts(true)"
          >{{ cat.name }}</button>
        </div>
        <div class="pos-grid">
          <button v-for="p in products" :key="p.id" class="pos-product" @click="addProduct(p)">
            <img v-if="p.image_url" :src="p.image_url" :alt="p.name" />
            <div v-else style="height:84px;border-radius:.5rem;background:#0b1220;display:flex;align-items:center;justify-content:center;color:#64748b">SKU</div>
            <strong>{{ p.name }}</strong>
            <span class="price">{{ money(p.price) }}</span>
            <span class="stock">{{ p.sku || '—' }} · {{ t('Qty', 'الكمية') }}: {{ p.quantity }}</span>
          </button>
        </div>
        <div style="margin-top:1rem;text-align:center" v-if="productsPage < productsLastPage">
          <button class="pos-btn" :disabled="loading" @click="productsPage += 1; loadProducts(false)">{{ t('Load more', 'المزيد') }}</button>
        </div>
      </section>

      <aside class="pos-cart">
        <div class="pos-toolbar">
          <button class="pos-btn" @click="modal.customer = true">{{ customer ? customer.name : t('Customer (F4)', 'العميل (F4)') }}</button>
          <button class="pos-btn" v-if="customer" @click="customer = null">{{ t('Clear', 'إلغاء') }}</button>
        </div>

        <div v-if="!cart.length" class="muted" style="color:var(--pos-muted)">{{ t('Cart is empty', 'السلة فارغة') }}</div>
        <div v-for="line in cart" :key="line.key" class="pos-line">
          <div>
            <strong>{{ line.name }}</strong>
            <div style="color:var(--pos-muted);font-size:.8rem">{{ line.sku }} · {{ money(line.unit_price) }}</div>
            <div class="pos-qty" style="margin-top:.35rem">
              <button class="pos-btn" @click="changeQty(line, -1)">-</button>
              <input class="pos-input" style="width:64px" type="number" min="1" v-model.number="line.quantity" />
              <button class="pos-btn" @click="changeQty(line, 1)">+</button>
              <button class="pos-btn danger" @click="removeLine(line)">×</button>
            </div>
          </div>
          <div style="text-align:end">
            <div>{{ money(line.unit_price * line.quantity - Number(line.discount || 0)) }}</div>
            <input class="pos-input" style="width:90px;margin-top:.35rem" type="number" min="0" step="0.01" v-model.number="line.discount" :placeholder="t('Disc', 'خصم')" />
          </div>
        </div>

        <div class="pos-field" style="margin-top:1rem">
          <label>{{ t('Order discount', 'خصم الفاتورة') }}</label>
          <input class="pos-input" type="number" min="0" step="0.01" v-model.number="discountTotal" />
        </div>
        <div class="pos-field">
          <label>{{ t('Notes', 'ملاحظات') }}</label>
          <input class="pos-input" v-model="notes" />
        </div>

        <div class="pos-totals">
          <div>{{ t('Items', 'الأصناف') }}: {{ cartCount }}</div>
          <div>{{ t('Subtotal', 'المجموع') }}: {{ money(subtotal) }}</div>
          <div>{{ t('Tax', 'الضريبة') }}: {{ money(taxTotal) }}</div>
          <div>{{ t('Discount', 'الخصم') }}: {{ money(discountTotal) }}</div>
          <div class="grand">{{ t('Total', 'الإجمالي') }}: {{ money(grandTotal) }}</div>
        </div>

        <div class="pos-actions">
          <button class="pos-btn" @click="suspendSale" :disabled="!cart.length || !canSell">{{ t('Suspend (F9)', 'تعليق (F9)') }}</button>
          <button class="pos-btn primary" @click="openPayment" :disabled="!cart.length || !canSell">{{ t('Pay (F8)', 'دفع (F8)') }}</button>
        </div>
      </aside>
    </main>

    <div v-if="modal.openSession" class="pos-modal-backdrop">
      <div class="pos-modal">
        <h3>{{ t('Open cashier shift', 'فتح وردية الكاشير') }}</h3>
        <div class="pos-field">
          <label>{{ t('Register', 'الصندوق') }}</label>
          <select class="pos-select" v-model.number="forms.selectedRegisterId">
            <option v-for="r in bootstrap.registers || []" :key="r.id" :value="r.id">{{ r.name }}</option>
          </select>
        </div>
        <div class="pos-field">
          <label>{{ t('Opening balance', 'رصيد الافتتاح') }}</label>
          <input class="pos-input" v-model="forms.open.opening_balance" type="number" min="0" step="0.01" />
        </div>
        <div class="pos-field">
          <label>{{ t('Notes', 'ملاحظات') }}</label>
          <input class="pos-input" v-model="forms.open.opening_notes" />
        </div>
        <button class="pos-btn primary" style="width:100%" :disabled="loading" @click="openSession">{{ t('Open shift', 'فتح الوردية') }}</button>
      </div>
    </div>

    <div v-if="modal.payment" class="pos-modal-backdrop">
      <div class="pos-modal">
        <h3>{{ t('Payment', 'الدفع') }}</h3>
        <div class="pos-field"><label>{{ t('Due', 'المطلوب') }}</label><strong>{{ money(grandTotal) }}</strong></div>
        <div v-for="(line, idx) in forms.paymentLines" :key="idx" class="pos-toolbar">
          <select class="pos-select" v-model="line.type">
            <option v-for="m in bootstrap.payment_methods || []" :key="m.code + '-' + idx" :value="m.type">{{ m.name }}</option>
          </select>
          <input class="pos-input" type="number" min="0" step="0.01" v-model="line.amount" />
          <input class="pos-input" v-model="line.reference" :placeholder="t('Reference', 'مرجع')" />
        </div>
        <button class="pos-btn" @click="forms.paymentLines.push({ type: 'card', amount: '0', reference: '' })">{{ t('Add tender (split)', 'إضافة طريقة (تقسيم)') }}</button>
        <div style="margin-top:.75rem;display:flex;gap:.5rem">
          <button class="pos-btn" @click="modal.payment = false">{{ t('Cancel', 'إلغاء') }}</button>
          <button class="pos-btn primary" :disabled="checkoutBusy" @click="checkout">{{ checkoutBusy ? t('Processing…', 'جاري التنفيذ…') : t('Complete sale', 'إتمام البيع') }}</button>
        </div>
      </div>
    </div>

    <div v-if="modal.customer" class="pos-modal-backdrop">
      <div class="pos-modal">
        <h3>{{ t('Customer', 'العميل') }}</h3>
        <div class="pos-toolbar">
          <input class="pos-input" v-model="forms.customerSearch" :placeholder="t('Search customer', 'بحث عن عميل')" @keyup.enter="searchCustomers" />
          <button class="pos-btn" @click="searchCustomers">{{ t('Search', 'بحث') }}</button>
        </div>
        <button v-for="c in forms.customerResults" :key="c.id" class="pos-btn" style="width:100%;margin-bottom:.35rem;text-align:start" @click="customer = c; modal.customer = false">
          {{ c.name }} <span style="color:var(--pos-muted)">{{ c.phone }}</span>
        </button>
        <hr style="border-color:var(--pos-border)">
        <div class="pos-field"><label>{{ t('Quick create', 'إنشاء سريع') }}</label><input class="pos-input" v-model="forms.quickCustomer.name" /></div>
        <div class="pos-field"><label>{{ t('Phone', 'هاتف') }}</label><input class="pos-input" v-model="forms.quickCustomer.phone" /></div>
        <div style="display:flex;gap:.5rem">
          <button class="pos-btn" @click="modal.customer = false">{{ t('Close', 'إغلاق') }}</button>
          <button class="pos-btn primary" @click="createCustomer">{{ t('Create', 'إنشاء') }}</button>
        </div>
      </div>
    </div>

    <div v-if="modal.cash" class="pos-modal-backdrop">
      <div class="pos-modal">
        <h3>{{ t('Cash movement', 'حركة نقدية') }}</h3>
        <div class="pos-field">
          <label>{{ t('Type', 'النوع') }}</label>
          <select class="pos-select" v-model="forms.cash.type">
            <option value="in">Cash In</option>
            <option value="out">Cash Out</option>
          </select>
        </div>
        <div class="pos-field"><label>{{ t('Amount', 'المبلغ') }}</label><input class="pos-input" v-model="forms.cash.amount" type="number" min="0.01" step="0.01" /></div>
        <div class="pos-field"><label>{{ t('Reason', 'السبب') }}</label><input class="pos-input" v-model="forms.cash.reason" /></div>
        <div class="pos-field"><label>{{ t('Notes', 'ملاحظات') }}</label><input class="pos-input" v-model="forms.cash.notes" /></div>
        <div style="display:flex;gap:.5rem">
          <button class="pos-btn" @click="modal.cash = false">{{ t('Cancel', 'إلغاء') }}</button>
          <button class="pos-btn primary" @click="submitCash">{{ t('Save', 'حفظ') }}</button>
        </div>
      </div>
    </div>

    <div v-if="modal.suspended" class="pos-modal-backdrop">
      <div class="pos-modal wide">
        <h3>{{ t('Suspended sales', 'المبيعات المعلّقة') }}</h3>
        <div v-if="!forms.suspendedList.length" style="color:var(--pos-muted)">{{ t('No suspended sales', 'لا توجد مبيعات معلّقة') }}</div>
        <div v-for="s in forms.suspendedList" :key="s.id" class="pos-line">
          <div>
            <strong>#{{ s.id }} · {{ s.receipt_number || s.document_number }}</strong>
            <div style="color:var(--pos-muted)">{{ s.customer_name || 'Walk-in' }} · {{ money(s.grand_total) }}</div>
          </div>
          <div style="display:flex;gap:.35rem">
            <button class="pos-btn primary" @click="resumeSale(s)">{{ t('Resume', 'استرجاع') }}</button>
            <button class="pos-btn danger" @click="cancelSuspendedSale(s)">{{ t('Cancel', 'إلغاء') }}</button>
          </div>
        </div>
        <button class="pos-btn" style="margin-top:.75rem" @click="modal.suspended = false">{{ t('Close', 'إغلاق') }}</button>
      </div>
    </div>

    <div v-if="modal.closeShift" class="pos-modal-backdrop">
      <div class="pos-modal wide">
        <h3>{{ t('Close shift', 'إغلاق الوردية') }}</h3>
        <div v-if="forms.shiftSummary" style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.75rem;color:var(--pos-muted)">
          <div>{{ t('Sales', 'المبيعات') }}: {{ forms.shiftSummary.sales_count }} / {{ forms.shiftSummary.sales_amount }}</div>
          <div>{{ t('Refunds', 'المرتجعات') }}: {{ forms.shiftSummary.refunds }}</div>
          <div>{{ t('Expected cash', 'النقد المتوقع') }}: {{ forms.shiftSummary.expected_by_tender?.cash }}</div>
          <div>{{ t('Net cash', 'صافي النقد') }}: {{ forms.shiftSummary.net_cash }}</div>
        </div>
        <div class="pos-toolbar">
          <input class="pos-input" v-model="forms.close.actual_cash" :placeholder="t('Actual cash', 'نقد فعلي')" />
          <input class="pos-input" v-model="forms.close.actual_card" :placeholder="t('Actual card', 'بطاقة فعلي')" />
          <input class="pos-input" v-model="forms.close.actual_transfer" :placeholder="t('Actual transfer', 'تحويل فعلي')" />
          <input class="pos-input" v-model="forms.close.actual_other" :placeholder="t('Actual other', 'أخرى')" />
        </div>
        <div class="pos-field"><label>{{ t('Closing notes', 'ملاحظات الإغلاق') }}</label><input class="pos-input" v-model="forms.close.closing_notes" /></div>
        <div class="pos-field"><label>{{ t('Difference reason', 'سبب الفرق') }}</label><input class="pos-input" v-model="forms.close.difference_reason" /></div>
        <div style="display:flex;gap:.5rem">
          <button class="pos-btn" @click="modal.closeShift = false">{{ t('Cancel', 'إلغاء') }}</button>
          <button class="pos-btn danger" @click="closeShift">{{ t('Confirm close', 'تأكيد الإغلاق') }}</button>
        </div>
      </div>
    </div>

    <div v-if="modal.variantPick" class="pos-modal-backdrop">
      <div class="pos-modal">
        <h3>{{ forms.variantProduct?.name }}</h3>
        <button
          v-for="v in forms.variantProduct?.variants || []"
          :key="v.id"
          class="pos-btn"
          style="width:100%;margin-bottom:.35rem;text-align:start"
          @click="addProduct(forms.variantProduct, v); modal.variantPick = false"
        >
          {{ v.sku || ('#' + v.id) }} · {{ money(v.price) }} · Qty {{ v.quantity }}
        </button>
        <button class="pos-btn" @click="modal.variantPick = false">{{ t('Close', 'إغلاق') }}</button>
      </div>
    </div>

    <div v-if="modal.receipt" class="pos-modal-backdrop">
      <div class="pos-modal">
        <h3>{{ t('Sale complete', 'اكتمل البيع') }}</h3>
        <div class="pos-success pos-receipt-meta">
          <div>{{ t('Receipt', 'الإيصال') }}: <strong>{{ forms.receipt?.display_number || '—' }}</strong></div>
          <div>{{ t('Total', 'الإجمالي') }}: <strong>{{ money(forms.receipt?.display_total) }}</strong></div>
          <div>{{ t('Paid', 'المدفوع') }}: <strong>{{ money(forms.receipt?.paid_total ?? forms.receipt?.display_total) }}</strong></div>
          <div>{{ t('Change', 'الباقي') }}: <strong>{{ money(forms.receipt?.display_change) }}</strong></div>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
          <button class="pos-btn primary" type="button" @click="printReceipt">{{ t('Print receipt', 'طباعة الإيصال') }}</button>
          <a
            v-if="forms.receipt?.receipt_url"
            class="pos-btn"
            :href="forms.receipt.receipt_url"
            target="_blank"
            rel="noopener"
          >{{ t('Open receipt', 'فتح الإيصال') }}</a>
          <button class="pos-btn" type="button" @click="modal.receipt = false; beep('click')">{{ t('New sale', 'بيع جديد') }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

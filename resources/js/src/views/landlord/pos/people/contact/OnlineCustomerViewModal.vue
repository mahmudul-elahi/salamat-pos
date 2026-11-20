<script setup>
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import {
    Phone, Mail, MapPin, User, Building, Globe, Calendar,
    Clock, CreditCard, ShieldCheck, AtSign, Home, Star,
    Bag, NumberedListLeft, Heart, ChatBubble, Package, SmartphoneDevice
} from "@iconoir/vue";
import Axios from "@/services/axios/index.js";
import {
    ONLINE_CONTACTS,
    ONLINE_CONTACT_ORDERS,
    ONLINE_CONTACT_ELITE_HISTORIES,
    ONLINE_CONTACT_ADDRESSES,
    ONLINE_CONTACT_REVIEWS,
    ONLINE_CONTACT_CART,
    ONLINE_CONTACT_WISHLIST,
    ONLINE_CONTACT_SELL_DEVICE_REQUESTS,
} from "@/services/endpoints/landlord.js";

const props = defineProps({
    modalId: String,
    customer: {
        type: Object,
        required: true,
    },
});
defineEmits(["close"]);

const { t } = useI18n();

// ── Overview ─────────────────────────────────────────────────────────────────
const orderStats  = ref(null);
const statsLoading = ref(true);

onMounted(async () => {
    try {
        const { data } = await Axios.get(`${ONLINE_CONTACTS}/${props.customer.id}`);
        orderStats.value = data.order_stats;
    } finally {
        statsLoading.value = false;
    }
});

const initials = computed(() => {
    if (!props.customer?.name) return "?";
    return props.customer.name
        .split(" ")
        .slice(0, 2)
        .map((w) => w[0]?.toUpperCase())
        .join("");
});

const genderColor = computed(() => {
    const g = props.customer?.gender;
    if (g === "male")   return "#6366f1";
    if (g === "female") return "#ec4899";
    return "#f59e0b";
});

const genderLabel = computed(() => {
    const g = props.customer?.gender;
    if (g === "male")   return t("male");
    if (g === "female") return t("female");
    return t("other");
});

const statusColor = computed(() =>
    props.customer?.status === "active" ? "#22c55e" : "#ef4444"
);

const statCards = computed(() => [
    { label: t("total_orders"),  value: orderStats.value?.total     ?? 0, color: "#6366f1", bg: "#eef2ff" },
    { label: t("pending"),       value: orderStats.value?.pending   ?? 0, color: "#f59e0b", bg: "#fffbeb" },
    { label: t("delivered"),     value: (orderStats.value?.complete ?? 0) + (orderStats.value?.on_way ?? 0), color: "#22c55e", bg: "#f0fdf4" },
    { label: t("rejected"),      value: orderStats.value?.rejected  ?? 0, color: "#ef4444", bg: "#fef2f2" },
]);

// ── Order items modal ─────────────────────────────────────────────────────────
const selectedOrder = ref(null);

// ── Sell device request image lightbox ────────────────────────────────────────
const lightboxSrc = ref(null);

// ── Tabs ──────────────────────────────────────────────────────────────────────
const TABS = [
    { key: "overview",       label: "Overview",              icon: User },
    { key: "addresses",      label: "Address Book",          icon: Home },
    { key: "elite",          label: "Elite Histories",       icon: Star },
    { key: "bag",            label: "Shopping Bag",          icon: Bag },
    { key: "orders",         label: "Order History",         icon: NumberedListLeft },
    { key: "wishlist",       label: "Wishlist",              icon: Heart },
    { key: "reviews",        label: "Reviews",               icon: ChatBubble },
    { key: "sellRequests",   label: "Sell Device Requests",  icon: SmartphoneDevice },
];

const activeTab    = ref("overview");
const loaded       = ref({ overview: true });
const tabLoading   = ref({});
const tabData      = ref({});
const tabPage      = ref({});

async function switchTab(key) {
    activeTab.value = key;
    if (loaded.value[key]) return;
    await loadTab(key);
}

async function loadTab(key, page = 1) {
    tabLoading.value[key] = true;
    try {
        let url = "";
        if (key === "orders")    url = ONLINE_CONTACT_ORDERS(props.customer.id);
        if (key === "elite")     url = ONLINE_CONTACT_ELITE_HISTORIES(props.customer.id);
        if (key === "addresses") url = ONLINE_CONTACT_ADDRESSES(props.customer.id);
        if (key === "reviews")   url = ONLINE_CONTACT_REVIEWS(props.customer.id);
        if (key === "bag")       url = ONLINE_CONTACT_CART(props.customer.id);
        if (key === "wishlist")  url = ONLINE_CONTACT_WISHLIST(props.customer.id);
        if (key === "sellRequests") url = ONLINE_CONTACT_SELL_DEVICE_REQUESTS(props.customer.id);
        if (!url) { loaded.value[key] = true; tabLoading.value[key] = false; return; }

        const { data } = await Axios.get(url, { params: { page, per_page: 10 } });
        tabData.value[key]  = data;
        tabPage.value[key]  = page;
        loaded.value[key]   = true;
    } finally {
        tabLoading.value[key] = false;
    }
}

function changePage(key, page) {
    loaded.value[key] = false;
    loadTab(key, page);
}

const statusBadgeClass = (status) => {
    const map = {
        Pending:    "badge-pending",
        Approved:   "badge-approved",
        Processing: "badge-processing",
        OnWay:      "badge-on-way",
        Complete:   "badge-complete",
        Rejected:   "badge-rejected",
    };
    return map[status] ?? "badge-default";
};

const paymentBadgeClass = (status) => {
    const map = {
        Successful:    "pay-paid",
        PartiallyPaid: "pay-partial",
        Pending:       "pay-pending",
        Failed:        "pay-failed",
        Canceled:      "pay-failed",
        Unsuccessful:  "pay-failed",
        Refunded:      "pay-refunded",
    };
    return map[status] ?? "pay-pending";
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="extra-large" title="Customer Details" :show-footer="false"
        @close="$emit('close')">
        <template #body>
            <div class="ocm-wrapper">

                <!-- Hero Header -->
                <div class="ocm-hero">
                    <div class="ocm-hero-body">
                        <div class="ocm-avatar-wrap">
                            <img v-if="customer.profile_picture" :src="customer.profile_picture" :alt="customer.name"
                                class="ocm-avatar-img" />
                            <div v-else class="ocm-avatar-initials">{{ initials }}</div>
                            <span class="ocm-status-dot" :style="{ background: statusColor }" />
                        </div>
                        <div class="ocm-hero-info">
                            <h5 class="ocm-name">{{ customer.name }}</h5>
                            <p v-if="customer.email" class="ocm-hero-email">{{ customer.email }}</p>
                            <div class="ocm-badges">
                                <span class="ocm-badge ocm-badge-gender"
                                    :style="{ background: genderColor + '22', color: genderColor, borderColor: genderColor + '44' }">
                                    {{ genderLabel }}
                                </span>
                                <span class="ocm-badge"
                                    :style="statusColor === '#22c55e'
                                        ? 'background:#dcfce7;color:#16a34a;border-color:#bbf7d0'
                                        : 'background:#fee2e2;color:#dc2626;border-color:#fecaca'">
                                    {{ customer.status ?? 'active' }}
                                </span>
                                <span v-if="customer.order_channel" class="ocm-badge ocm-badge-channel">
                                    {{ customer.order_channel }}
                                </span>
                            </div>
                        </div>
                        <div v-if="customer.phone" class="ocm-hero-phone">
                            <Phone class="ocm-hero-phone-icon" />
                            {{ customer.phone }}
                        </div>
                    </div>
                </div>

                <!-- Order Stats -->
                <div class="ocm-stats-section">
                    <div v-if="statsLoading" class="ocm-stats-loading"><app-loader /></div>
                    <div v-else class="ocm-stats-grid">
                        <div v-for="(stat, i) in statCards" :key="i" class="ocm-stat-card"
                            :style="{ '--stat-color': stat.color, '--stat-bg': stat.bg }">
                            <div class="ocm-stat-value">{{ stat.value }}</div>
                            <div class="ocm-stat-label">{{ stat.label }}</div>
                            <div class="ocm-stat-bar" />
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="ocm-tabs">
                    <button
                        v-for="tab in TABS"
                        :key="tab.key"
                        class="ocm-tab-btn"
                        :class="{ active: activeTab === tab.key }"
                        @click="switchTab(tab.key)"
                    >
                        <component :is="tab.icon" class="ocm-tab-icon" />
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Tab Content -->
                <div class="ocm-tab-content">

                    <!-- Overview Tab -->
                    <div v-show="activeTab === 'overview'" class="ocm-grid">
                        <div class="ocm-section">
                            <div class="ocm-section-header">
                                <Phone class="ocm-section-icon" />
                                <span>{{ $t("contact_information") }}</span>
                            </div>
                            <div class="ocm-fields">
                                <div class="ocm-field" v-if="customer.phone">
                                    <span class="ocm-field-icon"><Phone /></span>
                                    <div>
                                        <p class="ocm-label">{{ $t("phone_number") }}</p>
                                        <p class="ocm-value">{{ customer.phone }}</p>
                                    </div>
                                </div>
                                <div class="ocm-field" v-if="customer.secondary_phone">
                                    <span class="ocm-field-icon"><Phone /></span>
                                    <div>
                                        <p class="ocm-label">{{ $t("secondary_phone") }}</p>
                                        <p class="ocm-value">{{ customer.secondary_phone }}</p>
                                    </div>
                                </div>
                                <div class="ocm-field" v-if="customer.email">
                                    <span class="ocm-field-icon"><Mail /></span>
                                    <div>
                                        <p class="ocm-label">{{ $t("email") }}</p>
                                        <p class="ocm-value">{{ customer.email }}</p>
                                    </div>
                                </div>
                                <div class="ocm-field" v-if="customer.address">
                                    <span class="ocm-field-icon"><MapPin /></span>
                                    <div>
                                        <p class="ocm-label">{{ $t("address") }}</p>
                                        <p class="ocm-value">{{ customer.address }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ocm-section">
                            <div class="ocm-section-header">
                                <User class="ocm-section-icon" />
                                <span>{{ $t("personal_information") }}</span>
                            </div>
                            <div class="ocm-fields">
                                <div class="ocm-field" v-if="customer.date_of_birth">
                                    <span class="ocm-field-icon"><Calendar /></span>
                                    <div>
                                        <p class="ocm-label">{{ $t("date_of_birth") }}</p>
                                        <p class="ocm-value">{{ customer.date_of_birth }}</p>
                                    </div>
                                </div>
                                <div class="ocm-field" v-if="customer.gender">
                                    <span class="ocm-field-icon"><User /></span>
                                    <div>
                                        <p class="ocm-label">{{ $t("gender") }}</p>
                                        <p class="ocm-value">{{ genderLabel }}</p>
                                    </div>
                                </div>
                                <div class="ocm-field" v-if="customer.tin_number">
                                    <span class="ocm-field-icon"><CreditCard /></span>
                                    <div>
                                        <p class="ocm-label">{{ $t("tin_number") }}</p>
                                        <p class="ocm-value">{{ customer.tin_number }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ocm-section"
                            v-if="customer.business_name || customer.trade_license_number || customer.platform_url">
                            <div class="ocm-section-header">
                                <Building class="ocm-section-icon" />
                                <span>{{ $t("business_information") }}</span>
                            </div>
                            <div class="ocm-fields">
                                <div class="ocm-field" v-if="customer.business_name">
                                    <span class="ocm-field-icon"><Building /></span>
                                    <div>
                                        <p class="ocm-label">{{ $t("business_name") }}</p>
                                        <p class="ocm-value">{{ customer.business_name }}</p>
                                    </div>
                                </div>
                                <div class="ocm-field" v-if="customer.trade_license_number">
                                    <span class="ocm-field-icon"><ShieldCheck /></span>
                                    <div>
                                        <p class="ocm-label">{{ $t("trade_license_number") }}</p>
                                        <p class="ocm-value">{{ customer.trade_license_number }}</p>
                                    </div>
                                </div>
                                <div class="ocm-field" v-if="customer.platform_url">
                                    <span class="ocm-field-icon"><Globe /></span>
                                    <div>
                                        <p class="ocm-label">{{ $t("platform_url") }}</p>
                                        <p class="ocm-value">
                                            <a :href="customer.platform_url" target="_blank" class="ocm-link">
                                                {{ customer.platform_url }}
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ocm-section">
                            <div class="ocm-section-header">
                                <AtSign class="ocm-section-icon" />
                                <span>{{ $t("account_information") }}</span>
                            </div>
                            <div class="ocm-fields">
                                <div class="ocm-field" v-if="customer.email_verified_at">
                                    <span class="ocm-field-icon ocm-icon-success"><ShieldCheck /></span>
                                    <div>
                                        <p class="ocm-label">{{ $t("email_verified") }}</p>
                                        <p class="ocm-value ocm-verified">{{ $t("verified") }}</p>
                                    </div>
                                </div>
                                <div class="ocm-field" v-else>
                                    <span class="ocm-field-icon ocm-icon-warning"><Mail /></span>
                                    <div>
                                        <p class="ocm-label">{{ $t("email_verified") }}</p>
                                        <p class="ocm-value ocm-unverified">{{ $t("not_verified") }}</p>
                                    </div>
                                </div>
                                <div class="ocm-field" v-if="customer.last_login_at">
                                    <span class="ocm-field-icon"><Clock /></span>
                                    <div>
                                        <p class="ocm-label">{{ $t("last_login") }}</p>
                                        <p class="ocm-value">{{ customer.last_login_at }}</p>
                                    </div>
                                </div>
                                <div class="ocm-field">
                                    <span class="ocm-field-icon"><Calendar /></span>
                                    <div>
                                        <p class="ocm-label">{{ $t("joined") }}</p>
                                        <p class="ocm-value">{{ customer.created_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Book Tab -->
                    <div v-show="activeTab === 'addresses'">
                        <div v-if="tabLoading.addresses" class="ocm-tab-loading"><app-loader /></div>
                        <template v-else-if="tabData.addresses?.length">
                            <div class="ocm-addr-list">
                                <div v-for="addr in tabData.addresses" :key="addr.id" class="ocm-addr-card">
                                    <div class="ocm-addr-header">
                                        <Home class="ocm-addr-icon" />
                                        <span class="ocm-addr-label">{{ addr.label || 'Address' }}</span>
                                        <span v-if="addr.is_default_shipping" class="ocm-addr-badge ocm-badge-shipping">Default Shipping</span>
                                        <span v-if="addr.is_default_billing"  class="ocm-addr-badge ocm-badge-billing">Default Billing</span>
                                    </div>
                                    <p class="ocm-addr-text">{{ addr.full_address }}</p>
                                </div>
                            </div>
                        </template>
                        <div v-else class="ocm-empty">
                            <Home class="ocm-empty-icon" />
                            <p>No addresses found</p>
                        </div>
                    </div>

                    <!-- Elite Histories Tab -->
                    <div v-show="activeTab === 'elite'">
                        <div v-if="tabLoading.elite" class="ocm-tab-loading"><app-loader /></div>
                        <template v-else-if="tabData.elite?.length">
                            <div class="ocm-elite-list">
                                <div v-for="item in tabData.elite" :key="item.id" class="ocm-elite-card">
                                    <img v-if="item.elite?.logo" :src="item.elite.logo" class="ocm-elite-logo" :alt="item.elite.status_name" />
                                    <div v-else class="ocm-elite-logo-placeholder"><Star /></div>
                                    <div class="ocm-elite-info">
                                        <p class="ocm-elite-name">{{ item.elite?.status_name }}</p>
                                        <p class="ocm-elite-tagline">{{ item.elite?.tagline }}</p>
                                        <p class="ocm-elite-date">Achieved: {{ item.achieved_at }}</p>
                                    </div>
                                    <div class="ocm-elite-points">
                                        <span class="ocm-elite-pt-value">{{ item.elite?.point }}</span>
                                        <span class="ocm-elite-pt-label">pts</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div v-else class="ocm-empty">
                            <Star class="ocm-empty-icon" />
                            <p>No elite history found</p>
                        </div>
                    </div>

                    <!-- Shopping Bag Tab -->
                    <div v-show="activeTab === 'bag'">
                        <div v-if="tabLoading.bag" class="ocm-tab-loading"><app-loader /></div>
                        <template v-else-if="tabData.bag?.items?.length">
                            <div class="ocm-product-list">
                                <div v-for="(item, idx) in tabData.bag.items" :key="idx" class="ocm-product-card">
                                    <img v-if="item.thumbnail" :src="item.thumbnail" class="ocm-product-thumb" :alt="item.product_name" />
                                    <div v-else class="ocm-product-thumb-placeholder"><Package class="ocm-pkg-icon" /></div>
                                    <div class="ocm-product-info">
                                        <p class="ocm-product-name">{{ item.product_name }}</p>
                                        <p v-if="item.variant" class="ocm-product-variant">{{ item.variant }}</p>
                                    </div>
                                    <div class="ocm-product-meta">
                                        <span class="ocm-product-qty">× {{ item.quantity }}</span>
                                        <span class="ocm-product-price">৳{{ item.unit_price }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="ocm-cart-total">
                                <span>Sub Total</span>
                                <strong>৳{{ tabData.bag.sub_total }}</strong>
                                <span>Grand Total</span>
                                <strong>৳{{ tabData.bag.grand_total }}</strong>
                            </div>
                        </template>
                        <div v-else class="ocm-empty">
                            <Bag class="ocm-empty-icon" />
                            <p>Shopping bag is empty</p>
                        </div>
                    </div>

                    <!-- Order History Tab -->
                    <div v-show="activeTab === 'orders'">
                        <div v-if="tabLoading.orders" class="ocm-tab-loading"><app-loader /></div>
                        <template v-else-if="tabData.orders?.data?.length">
                            <div class="ocm-table-wrap">
                                <table class="ocm-table">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th class="text-right">Total</th>
                                            <th class="text-right">Due</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="order in tabData.orders.data" :key="order.id">
                                            <td>
                                                <span class="ocm-inv-link" @click="selectedOrder = order">
                                                    #{{ order.invoice_number }}
                                                </span>
                                            </td>
                                            <td>{{ order.created_at }}</td>
                                            <td><span class="ocm-status-badge" :class="statusBadgeClass(order.status)">{{ order.status }}</span></td>
                                            <td><span class="ocm-pay-badge" :class="paymentBadgeClass(order.payment_status)">{{ order.payment_status }}</span></td>
                                            <td class="text-right">৳{{ order.grand_total }}</td>
                                            <td class="text-right">{{ order.due_amount > 0 ? '৳' + order.due_amount : '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="ocm-pagination" v-if="tabData.orders.last_page > 1">
                                <button
                                    v-for="p in tabData.orders.last_page" :key="p"
                                    class="ocm-page-btn" :class="{ active: tabData.orders.current_page === p }"
                                    @click="changePage('orders', p)">{{ p }}</button>
                            </div>
                        </template>
                        <div v-else class="ocm-empty">
                            <NumberedListLeft class="ocm-empty-icon" />
                            <p>No orders found</p>
                        </div>
                    </div>

                    <!-- Wishlist Tab -->
                    <div v-show="activeTab === 'wishlist'">
                        <div v-if="tabLoading.wishlist" class="ocm-tab-loading"><app-loader /></div>
                        <template v-else-if="tabData.wishlist?.items?.length">
                            <div class="ocm-product-list">
                                <div v-for="(item, idx) in tabData.wishlist.items" :key="idx" class="ocm-product-card">
                                    <img v-if="item.thumbnail" :src="item.thumbnail" class="ocm-product-thumb" :alt="item.product_name" />
                                    <div v-else class="ocm-product-thumb-placeholder"><Package class="ocm-pkg-icon" /></div>
                                    <div class="ocm-product-info">
                                        <p class="ocm-product-name">{{ item.product_name }}</p>
                                        <p v-if="item.variant" class="ocm-product-variant">{{ item.variant }}</p>
                                    </div>
                                    <div class="ocm-product-meta">
                                        <span class="ocm-product-qty">× {{ item.quantity }}</span>
                                        <span class="ocm-product-price">৳{{ item.unit_price }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="ocm-cart-total">
                                <span>Grand Total</span>
                                <strong>৳{{ tabData.wishlist.grand_total }}</strong>
                            </div>
                        </template>
                        <div v-else class="ocm-empty">
                            <Heart class="ocm-empty-icon" />
                            <p>Wishlist is empty</p>
                        </div>
                    </div>

                    <!-- Reviews Tab -->
                    <div v-show="activeTab === 'reviews'">
                        <div v-if="tabLoading.reviews" class="ocm-tab-loading"><app-loader /></div>
                        <template v-else-if="tabData.reviews?.data?.length">
                            <div class="ocm-review-list">
                                <div v-for="review in tabData.reviews.data" :key="review.id" class="ocm-review-card">
                                    <div class="ocm-review-product">
                                        <img v-if="review.product?.thumbnail" :src="review.product.thumbnail"
                                            class="ocm-review-thumb" :alt="review.product.name" />
                                        <div v-else class="ocm-review-thumb-placeholder"><Package class="ocm-pkg-icon" /></div>
                                        <div class="ocm-review-product-info">
                                            <p class="ocm-review-product-name">{{ review.product?.name ?? 'Product' }}</p>
                                            <p class="ocm-review-date">{{ review.created_at }}</p>
                                        </div>
                                    </div>
                                    <div class="ocm-review-stars">
                                        <span v-for="s in 5" :key="s" class="ocm-star" :class="{ filled: s <= review.rating }">★</span>
                                    </div>
                                    <p class="ocm-review-comment">{{ review.comment }}</p>
                                    <span class="ocm-review-status" :class="review.status === 'approved' ? 'rs-approved' : 'rs-pending'">
                                        {{ review.status }}
                                    </span>
                                </div>
                            </div>
                            <div class="ocm-pagination" v-if="tabData.reviews.last_page > 1">
                                <button
                                    v-for="p in tabData.reviews.last_page" :key="p"
                                    class="ocm-page-btn" :class="{ active: tabData.reviews.current_page === p }"
                                    @click="changePage('reviews', p)">{{ p }}</button>
                            </div>
                        </template>
                        <div v-else class="ocm-empty">
                            <ChatBubble class="ocm-empty-icon" />
                            <p>No reviews found</p>
                        </div>
                    </div>

                    <!-- Sell Device Requests Tab -->
                    <div v-show="activeTab === 'sellRequests'">
                        <div v-if="tabLoading.sellRequests" class="ocm-tab-loading"><app-loader /></div>
                        <template v-else-if="tabData.sellRequests?.data?.length">
                            <div class="ocm-review-list">
                                <div v-for="req in tabData.sellRequests.data" :key="req.id" class="ocm-review-card">
                                    <div class="ocm-review-product">
                                        <img v-if="req.thumbnail" :src="req.thumbnail" class="ocm-review-thumb" :alt="req.ad_title" />
                                        <div v-else class="ocm-review-thumb-placeholder"><SmartphoneDevice class="ocm-pkg-icon" /></div>
                                        <div class="ocm-review-product-info">
                                            <p class="ocm-review-product-name">{{ req.ad_title }}</p>
                                            <p class="ocm-review-date">{{ req.created_at }}</p>
                                        </div>
                                        <span class="ocm-status-badge" :class="statusBadgeClass(req.status)">{{ req.status }}</span>
                                    </div>
                                    <div class="ocm-fields">
                                        <div class="ocm-field" v-if="req.brand || req.model">
                                            <div>
                                                <p class="ocm-label">Brand / Model</p>
                                                <p class="ocm-value">{{ [req.brand, req.model].filter(Boolean).join(' — ') }}</p>
                                            </div>
                                        </div>
                                        <div class="ocm-field" v-if="req.category">
                                            <div>
                                                <p class="ocm-label">Category</p>
                                                <p class="ocm-value">{{ req.category }}</p>
                                            </div>
                                        </div>
                                        <div class="ocm-field" v-if="req.condition">
                                            <div>
                                                <p class="ocm-label">Condition</p>
                                                <p class="ocm-value text-capitalize">{{ req.condition.replaceAll('_', ' ') }}</p>
                                            </div>
                                        </div>
                                        <div class="ocm-field" v-if="req.ram || req.storage || req.color">
                                            <div>
                                                <p class="ocm-label">Specs</p>
                                                <p class="ocm-value">{{ [req.ram, req.storage, req.color].filter(Boolean).join(' · ') }}</p>
                                            </div>
                                        </div>
                                        <div class="ocm-field" v-if="req.district">
                                            <div>
                                                <p class="ocm-label">Location</p>
                                                <p class="ocm-value">{{ req.district }}</p>
                                            </div>
                                        </div>
                                        <div class="ocm-field">
                                            <div>
                                                <p class="ocm-label">Asking Price</p>
                                                <p class="ocm-value">৳{{ req.asking_price }}<span v-if="req.is_negotiable"> (Negotiable)</span></p>
                                            </div>
                                        </div>
                                        <div class="ocm-field" v-if="req.is_exchange_possible">
                                            <div>
                                                <p class="ocm-value">Exchange possible</p>
                                            </div>
                                        </div>
                                        <div class="ocm-field">
                                            <div>
                                                <p class="ocm-label">Phone</p>
                                                <p class="ocm-value">{{ req.phone_number }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="ocm-review-comment">{{ req.description }}</p>
                                    <div v-if="req.images?.length" class="ocm-sell-gallery">
                                        <div v-for="(img, idx) in req.images" :key="idx" class="ocm-sell-gallery-item"
                                            @click="lightboxSrc = img">
                                            <img :src="img" :alt="`${req.ad_title} photo ${idx + 1}`" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="ocm-pagination" v-if="tabData.sellRequests.last_page > 1">
                                <button
                                    v-for="p in tabData.sellRequests.last_page" :key="p"
                                    class="ocm-page-btn" :class="{ active: tabData.sellRequests.current_page === p }"
                                    @click="changePage('sellRequests', p)">{{ p }}</button>
                            </div>
                        </template>
                        <div v-else class="ocm-empty">
                            <SmartphoneDevice class="ocm-empty-icon" />
                            <p>No sell device requests found</p>
                        </div>
                    </div>

                </div>
            </div>
        </template>
    </app-modal>

    <!-- Order Items Modal -->
    <Teleport to="body">
        <Transition name="ocm-fade">
            <div v-if="selectedOrder" class="ocm-sub-backdrop" @click.self="selectedOrder = null">
                <div class="ocm-sub-modal">
                    <div class="ocm-sub-header">
                        <div>
                            <p class="ocm-sub-title">Order Items</p>
                            <p class="ocm-sub-inv">#{{ selectedOrder.invoice_number }}</p>
                        </div>
                        <div class="ocm-sub-badges">
                            <span class="ocm-status-badge" :class="statusBadgeClass(selectedOrder.status)">{{ selectedOrder.status }}</span>
                            <span class="ocm-pay-badge" :class="paymentBadgeClass(selectedOrder.payment_status)">{{ selectedOrder.payment_status }}</span>
                        </div>
                        <button class="ocm-sub-close" @click="selectedOrder = null">✕</button>
                    </div>
                    <div class="ocm-sub-body">
                        <table class="ocm-items-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Variant</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, idx) in selectedOrder.items" :key="idx">
                                    <td class="ocm-item-num">{{ idx + 1 }}</td>
                                    <td class="ocm-item-name">{{ item.name }}</td>
                                    <td class="ocm-item-variant">{{ item.variant ?? '—' }}</td>
                                    <td class="text-right">{{ item.quantity }}</td>
                                    <td class="text-right">৳{{ item.price }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="ocm-sub-footer">
                        <span>{{ selectedOrder.created_at }}</span>
                        <div class="ocm-sub-totals">
                            <span>Total: <strong>৳{{ selectedOrder.grand_total }}</strong></span>
                            <span v-if="selectedOrder.due_amount > 0" class="ocm-sub-due">Due: <strong>৳{{ selectedOrder.due_amount }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- Sell Device Request Image Lightbox -->
    <Teleport to="body">
        <Transition name="ocm-fade">
            <div v-if="lightboxSrc" class="ocm-lightbox-backdrop" @click="lightboxSrc = null">
                <img :src="lightboxSrc" class="ocm-lightbox-img" />
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped lang="scss">
$primary: #5b50f0;
$primary-light: #eef2ff;
$border: #e8eaf0;
$text: #0f172a;
$muted: #64748b;
$subtle: #f8fafc;
$card-radius: 16px;
$shadow-sm: 0 1px 4px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
$shadow-md: 0 4px 24px rgba(91,80,240,.1), 0 1px 4px rgba(0,0,0,.06);

.ocm-wrapper { font-family: inherit; }

/* ── Hero ────────────────────────────────────────────────────── */
.ocm-hero {
    border-radius: $card-radius;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: $shadow-sm;
    border: 1px solid $border;
    background: #fff;
}

.ocm-hero-body {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 20px 24px;
    background: linear-gradient(120deg, $primary-light 0%, #fff 60%);
    flex-wrap: wrap;
}

.ocm-avatar-wrap {
    position: relative;
    flex-shrink: 0;
}

.ocm-avatar-img,
.ocm-avatar-initials {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 4px 20px rgba(91,80,240,.22);
    object-fit: cover;
}

.ocm-avatar-initials {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: #fff;
    font-size: 24px;
    font-weight: 800;
    letter-spacing: 1px;
}

.ocm-status-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,.15);
}

.ocm-hero-info { flex: 1; min-width: 0; }

.ocm-name {
    font-size: 1.15rem;
    font-weight: 800;
    color: $text;
    margin: 0 0 3px;
    letter-spacing: -0.4px;
}

.ocm-hero-email {
    font-size: 12px;
    color: $muted;
    margin: 0 0 8px;
}

.ocm-badges { display: flex; flex-wrap: wrap; gap: 5px; }

.ocm-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    text-transform: capitalize;
    border: 1px solid transparent;
    letter-spacing: 0.3px;
}

.ocm-badge-channel { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }

.ocm-hero-phone {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: $muted;
    white-space: nowrap;
    background: #fff;
    border: 1px solid $border;
    border-radius: 10px;
    padding: 8px 12px;
    box-shadow: $shadow-sm;
}

.ocm-hero-phone-icon { width: 14px; height: 14px; color: $primary; flex-shrink: 0; }

/* ── Stats ───────────────────────────────────────────────────── */
.ocm-stats-section { margin-bottom: 20px; }
.ocm-stats-loading { display: flex; justify-content: center; padding: 20px; }

.ocm-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    @media (max-width: 576px) { grid-template-columns: repeat(2, 1fr); }
}

.ocm-stat-card {
    position: relative;
    background: #fff;
    border: 1px solid $border;
    border-radius: $card-radius;
    padding: 18px 16px 14px;
    overflow: hidden;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: $shadow-sm;

    &::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: var(--stat-color);
        border-radius: $card-radius $card-radius 0 0;
    }

    &:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 28px color-mix(in srgb, var(--stat-color) 18%, transparent);
    }
}

.ocm-stat-bar { display: none; }

.ocm-stat-value {
    font-size: 2rem;
    font-weight: 900;
    color: var(--stat-color);
    line-height: 1;
    margin-bottom: 6px;
    letter-spacing: -1px;
}

.ocm-stat-label {
    font-size: 10px;
    font-weight: 700;
    color: $muted;
    text-transform: uppercase;
    letter-spacing: 0.7px;
}

/* ── Tabs ────────────────────────────────────────────────────── */
.ocm-tabs {
    display: flex;
    gap: 0;
    margin-bottom: 20px;
    border-bottom: 2px solid $border;
    overflow-x: auto;
    scrollbar-width: none;
    &::-webkit-scrollbar { display: none; }
}

.ocm-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 16px;
    font-size: 12px;
    font-weight: 600;
    color: $muted;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.18s;
    position: relative;

    .ocm-tab-icon {
        width: 14px;
        height: 14px;
        transition: transform 0.18s;
    }

    &:hover {
        color: $primary;
        background: $primary-light;
        border-radius: 8px 8px 0 0;

        .ocm-tab-icon { transform: translateY(-1px); }
    }

    &.active {
        color: $primary;
        font-weight: 700;
        border-bottom-color: $primary;
        border-radius: 8px 8px 0 0;
        background: $primary-light;

        &::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0; right: 0;
            height: 2px;
            background: $primary;
            border-radius: 2px 2px 0 0;
        }

        .ocm-tab-icon { transform: translateY(-1px); color: $primary; }
    }
}

/* ── Tab Content ─────────────────────────────────────────────── */
.ocm-tab-content { min-height: 200px; }
.ocm-tab-loading { display: flex; justify-content: center; padding: 48px; }

/* ── Overview Grid ───────────────────────────────────────────── */
.ocm-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    @media (max-width: 576px) { grid-template-columns: 1fr; }
}

.ocm-section {
    background: #fff;
    border: 1px solid $border;
    border-radius: $card-radius;
    overflow: hidden;
    box-shadow: $shadow-sm;
    transition: box-shadow 0.2s, transform 0.2s;
    &:hover { box-shadow: $shadow-md; transform: translateY(-1px); }
}

.ocm-section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: linear-gradient(90deg, $primary-light, #fff);
    border-bottom: 1px solid $border;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: $primary;
}

.ocm-section-icon { width: 14px; height: 14px; color: $primary; }

.ocm-fields { padding: 4px 0; }

.ocm-field {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 11px 16px;
    border-bottom: 1px solid #f1f5f9;
    &:last-child { border-bottom: none; }
}

.ocm-field-icon {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    border-radius: 9px;
    background: $subtle;
    border: 1px solid $border;
    display: flex;
    align-items: center;
    justify-content: center;
    color: $muted;
    margin-top: 1px;
    svg { width: 14px; height: 14px; }
    &.ocm-icon-success { background: #f0fdf4; border-color: #bbf7d0; color: #16a34a; }
    &.ocm-icon-warning { background: #fefce8; border-color: #fef08a; color: #ca8a04; }
}

.ocm-label { font-size: 10px; color: #94a3b8; font-weight: 600; margin: 0 0 2px; letter-spacing: 0.3px; text-transform: uppercase; }
.ocm-value { font-size: 13px; color: $text; font-weight: 600; margin: 0; word-break: break-word; }
.ocm-verified   { color: #16a34a; }
.ocm-unverified { color: #d97706; }
.ocm-link { color: $primary; text-decoration: none; word-break: break-all; &:hover { text-decoration: underline; } }

/* ── Empty State ─────────────────────────────────────────────── */
.ocm-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 56px 24px;
    color: #cbd5e1;
    p { font-size: 13px; color: $muted; margin: 10px 0 0; font-weight: 500; }
}
.ocm-empty-icon { width: 48px; height: 48px; opacity: 0.35; }

/* ── Address Book ────────────────────────────────────────────── */
.ocm-addr-list { display: flex; flex-direction: column; gap: 10px; }

.ocm-addr-card {
    background: #fff;
    border: 1px solid $border;
    border-radius: $card-radius;
    padding: 16px;
    box-shadow: $shadow-sm;
    transition: box-shadow 0.2s;
    &:hover { box-shadow: $shadow-md; }
}

.ocm-addr-header { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap; }
.ocm-addr-icon { width: 16px; height: 16px; color: $primary; flex-shrink: 0; }
.ocm-addr-label { font-size: 13px; font-weight: 700; color: $text; flex: 1; }

.ocm-addr-badge { font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 20px; }
.ocm-badge-shipping { background: #eff6ff; color: #2563eb; }
.ocm-badge-billing  { background: #f0fdf4; color: #16a34a; }
.ocm-addr-text { font-size: 13px; color: $muted; margin: 0; line-height: 1.5; }

/* ── Elite Histories ─────────────────────────────────────────── */
.ocm-elite-list { display: flex; flex-direction: column; gap: 10px; }

.ocm-elite-card {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #fff;
    border: 1px solid $border;
    border-radius: $card-radius;
    padding: 16px;
    box-shadow: $shadow-sm;
    transition: box-shadow 0.2s, transform 0.2s;
    &:hover { box-shadow: $shadow-md; transform: translateX(3px); }
}

.ocm-elite-logo { width: 56px; height: 56px; object-fit: contain; border-radius: 12px; border: 1px solid $border; flex-shrink: 0; background: $subtle; }

.ocm-elite-logo-placeholder {
    width: 56px; height: 56px;
    border-radius: 12px;
    background: linear-gradient(135deg, #fef9c3, #fde68a);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #ca8a04;
    svg { width: 26px; height: 26px; }
}

.ocm-elite-info { flex: 1; min-width: 0; }
.ocm-elite-name    { font-size: 14px; font-weight: 800; color: $text; margin: 0 0 3px; }
.ocm-elite-tagline { font-size: 12px; color: $muted; margin: 0 0 4px; }
.ocm-elite-date    { font-size: 11px; color: #94a3b8; margin: 0; }

.ocm-elite-points { text-align: center; flex-shrink: 0; }
.ocm-elite-pt-value { display: block; font-size: 22px; font-weight: 900; color: $primary; letter-spacing: -1px; }
.ocm-elite-pt-label { font-size: 9px; color: $muted; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }

/* ── Orders Table ────────────────────────────────────────────── */
.ocm-table-wrap {
    border: 1px solid $border;
    border-radius: $card-radius;
    overflow: hidden;
    box-shadow: $shadow-sm;
}

.ocm-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;

    th {
        padding: 11px 14px;
        text-align: left;
        font-size: 10px;
        font-weight: 800;
        color: $muted;
        text-transform: uppercase;
        letter-spacing: 0.7px;
        background: $subtle;
        border-bottom: 1px solid $border;
    }

    td {
        padding: 11px 14px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle;
    }

    tr:last-child td { border-bottom: none; }
    tr:hover td { background: $primary-light; }
    .text-right { text-align: right; }
}

.ocm-inv-link {
    font-weight: 700;
    color: $primary;
    font-size: 13px;
    cursor: pointer;
    background: $primary-light;
    padding: 3px 8px;
    border-radius: 6px;
    transition: background 0.15s;
    &:hover { background: #ddd6fe; }
}

/* ── Order Items Sub-Modal ───────────────────────────────────── */
.ocm-sub-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,.5);
    backdrop-filter: blur(4px);
    z-index: 1100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.ocm-sub-modal {
    background: #fff;
    border-radius: 20px;
    width: 100%;
    max-width: 620px;
    max-height: 82vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 32px 80px rgba(15,23,42,.25), 0 4px 16px rgba(0,0,0,.08);
    overflow: hidden;
}

.ocm-sub-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 22px;
    border-bottom: 1px solid $border;
    background: linear-gradient(90deg, $primary-light, #fff);
}

.ocm-sub-title {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: $primary;
    margin: 0 0 2px;
}

.ocm-sub-inv { font-size: 16px; font-weight: 900; color: $text; margin: 0; letter-spacing: -0.5px; }

.ocm-sub-badges { display: flex; gap: 6px; flex: 1; flex-wrap: wrap; }

.ocm-sub-close {
    width: 32px; height: 32px;
    border-radius: 50%;
    border: 1px solid $border;
    background: #fff;
    font-size: 13px;
    color: $muted;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: all 0.15s;
    &:hover { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
}

.ocm-sub-body { flex: 1; overflow-y: auto; }

.ocm-items-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;

    th {
        padding: 11px 18px;
        text-align: left;
        font-size: 10px;
        font-weight: 800;
        color: $muted;
        text-transform: uppercase;
        letter-spacing: 0.7px;
        background: $subtle;
        border-bottom: 1px solid $border;
        position: sticky;
        top: 0;
    }

    td { padding: 11px 18px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: $primary-light; }
    .text-right { text-align: right; }
}

.ocm-item-num     { color: #94a3b8; font-size: 11px; width: 32px; font-weight: 700; }
.ocm-item-name    { font-weight: 700; color: $text; }
.ocm-item-variant {
    color: $muted;
    font-size: 11px;
    background: $subtle;
    border: 1px solid $border;
    border-radius: 4px;
    padding: 1px 6px;
    display: inline-block;
}

.ocm-sub-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 22px;
    border-top: 1px solid $border;
    background: $subtle;
    font-size: 12px;
    color: $muted;
    gap: 12px;
}

.ocm-sub-totals { display: flex; gap: 20px; font-size: 13px; color: #334155; }
.ocm-sub-due { color: #ef4444; font-weight: 700; }

/* ── Badges ──────────────────────────────────────────────────── */
.ocm-status-badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    text-transform: capitalize;
    letter-spacing: 0.2px;

    &.badge-pending    { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
    &.badge-approved   { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    &.badge-processing { background: #f0f9ff; color: #0369a1; border: 1px solid #bae6fd; }
    &.badge-on-way     { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    &.badge-complete   { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    &.badge-rejected   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    &.badge-default    { background: #f8fafc; color: $muted;  border: 1px solid $border; }
}

.ocm-pay-badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.2px;

    &.pay-paid     { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    &.pay-partial  { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    &.pay-pending  { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
    &.pay-failed   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    &.pay-refunded { background: #faf5ff; color: #6b21a8; border: 1px solid #e9d5ff; }
}

/* ── Shopping Bag & Wishlist ─────────────────────────────────── */
.ocm-product-list { display: flex; flex-direction: column; gap: 10px; }

.ocm-product-card {
    display: flex;
    align-items: center;
    gap: 14px;
    background: #fff;
    border: 1px solid $border;
    border-radius: $card-radius;
    padding: 14px 16px;
    box-shadow: $shadow-sm;
    transition: box-shadow 0.2s, transform 0.2s;
    &:hover { box-shadow: $shadow-md; transform: translateX(3px); }
}

.ocm-product-thumb { width: 56px; height: 56px; border-radius: 10px; object-fit: cover; border: 1px solid $border; flex-shrink: 0; }

.ocm-product-thumb-placeholder {
    width: 56px; height: 56px;
    border-radius: 10px;
    background: $subtle;
    border: 1px solid $border;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; color: #cbd5e1;
    .ocm-pkg-icon { width: 24px; height: 24px; }
}

.ocm-product-info { flex: 1; min-width: 0; }
.ocm-product-name    { font-size: 13px; font-weight: 700; color: $text; margin: 0 0 3px; }
.ocm-product-variant { font-size: 11px; color: $muted; margin: 0; }

.ocm-product-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
.ocm-product-qty   { font-size: 11px; color: $muted; background: $subtle; border: 1px solid $border; border-radius: 6px; padding: 2px 7px; font-weight: 600; }
.ocm-product-price { font-size: 15px; font-weight: 800; color: $primary; letter-spacing: -0.5px; }

.ocm-cart-total {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 20px;
    margin-top: 14px;
    padding: 12px 18px;
    background: linear-gradient(90deg, $primary-light, #fff);
    border: 1px solid #c7d2fe;
    border-radius: 12px;
    font-size: 13px;
    color: $muted;
    strong { color: $primary; font-size: 15px; font-weight: 800; }
}

/* ── Reviews ─────────────────────────────────────────────────── */
.ocm-review-list { display: flex; flex-direction: column; gap: 12px; }

.ocm-review-card {
    background: #fff;
    border: 1px solid $border;
    border-radius: $card-radius;
    padding: 16px;
    box-shadow: $shadow-sm;
    transition: box-shadow 0.2s;
    &:hover { box-shadow: $shadow-md; }
}

.ocm-review-product { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }

.ocm-review-thumb { width: 48px; height: 48px; border-radius: 10px; object-fit: cover; border: 1px solid $border; flex-shrink: 0; }

.ocm-review-thumb-placeholder {
    width: 48px; height: 48px;
    border-radius: 10px;
    background: $subtle;
    border: 1px solid $border;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; color: #cbd5e1;
    .ocm-pkg-icon { width: 22px; height: 22px; }
}

.ocm-review-product-info { flex: 1; min-width: 0; }
.ocm-review-product-name { font-size: 13px; font-weight: 700; color: $text; margin: 0 0 2px; }
.ocm-review-date         { font-size: 11px; color: #94a3b8; margin: 0; }

.ocm-review-stars { margin-bottom: 8px; }
.ocm-star { font-size: 17px; color: #e2e8f0; &.filled { color: #f59e0b; } }

.ocm-review-comment { font-size: 13px; color: #475569; margin: 0 0 10px; line-height: 1.55; }

/* ── Sell Device Request Gallery ─────────────────────────────── */
.ocm-sell-gallery { display: flex; flex-wrap: wrap; gap: 8px; }

.ocm-sell-gallery-item {
    width: 72px;
    height: 72px;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid $border;
    cursor: pointer;
    flex-shrink: 0;
    transition: transform 0.15s, box-shadow 0.15s;

    img { width: 100%; height: 100%; object-fit: cover; display: block; }

    &:hover { transform: scale(1.05); box-shadow: $shadow-md; }
}

/* ── Lightbox ────────────────────────────────────────────────── */
.ocm-lightbox-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,.88);
    z-index: 1200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    cursor: zoom-out;
}

.ocm-lightbox-img {
    max-width: 92vw;
    max-height: 92vh;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 20px 60px rgba(0,0,0,.5);
}

.ocm-review-status {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    &.rs-approved { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    &.rs-pending  { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
}

/* ── Pagination ──────────────────────────────────────────────── */
.ocm-pagination { display: flex; gap: 4px; justify-content: center; margin-top: 18px; flex-wrap: wrap; }

.ocm-page-btn {
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border-radius: 9px;
    border: 1px solid $border;
    background: #fff;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s;
    box-shadow: $shadow-sm;
    &:hover  { background: $primary-light; color: $primary; border-color: #c7d2fe; }
    &.active { background: $primary; color: #fff; border-color: $primary; box-shadow: 0 2px 8px rgba(91,80,240,.35); }
}

/* ── Transition ──────────────────────────────────────────────── */
.ocm-fade-enter-active, .ocm-fade-leave-active { transition: opacity 0.2s, transform 0.2s; }
.ocm-fade-enter-from, .ocm-fade-leave-to { opacity: 0; transform: scale(0.97); }
</style>

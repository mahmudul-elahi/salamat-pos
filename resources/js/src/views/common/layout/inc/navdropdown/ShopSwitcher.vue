<script setup>
import { ref, onMounted, computed } from "vue";
import { Building, Check, NavArrowDown } from "@iconoir/vue";
import Axios from "@/services/axios";
import { SELECTED_SHOPS } from "@/services/endpoints/landlord.js";
import { setTenant, getTenant } from "@/utils/tenant.js";

const shops = ref([]);
const currentSlug = ref(getTenant());

const currentShop = computed(() =>
    shops.value.find(s => s.slug === currentSlug.value)
);

const shopColors = [
    ['#6366f1', '#8b5cf6'],
    ['#0ea5e9', '#06b6d4'],
    ['#10b981', '#14b8a6'],
    ['#f59e0b', '#f97316'],
    ['#ec4899', '#a855f7'],
];

const getShopColor = (index) => shopColors[index % shopColors.length];

const fetchShops = () => {
    Axios.get(SELECTED_SHOPS, { params: { per_page: 100 } })
        .then(({ data }) => {
            shops.value = data.data ?? [];
        });
};

const switchShop = (shop) => {
    if (shop.slug === currentSlug.value) return;
    setTenant({ slug: shop.slug });
    window.location.reload();
};

onMounted(fetchShops);
</script>

<template>
    <li class="nav-item shop-switcher-wrapper dropdown py-0" @click.stop>
        <a class="shop-trigger dropdown-toggle"
            href="#" id="dropdownShopSwitcher" data-bs-toggle="dropdown" aria-expanded="false"
            aria-label="Switch Shop">
            <span class="shop-icon-wrap">
                <Building />
            </span>
            <span class="shop-label">{{ currentShop?.name ?? '...' }}</span>
            <NavArrowDown class="shop-arrow" />
        </a>

        <div class="dropdown-menu dropdown-menu-end shop-dropdown custom-scrollbar"
            aria-labelledby="dropdownShopSwitcher">

            <div class="shop-dropdown-header">
                <Building />
                <span>Switch Shop</span>
            </div>

            <div class="shop-list">
                <a
                    v-for="(shop, index) in shops"
                    :key="shop.id"
                    href="#"
                    class="shop-item"
                    :class="{ 'shop-item--active': shop.slug === currentSlug }"
                    @click.prevent="switchShop(shop)">
                    <span
                        class="shop-avatar"
                        :style="{
                            background: `linear-gradient(135deg, ${getShopColor(index)[0]}, ${getShopColor(index)[1]})`
                        }">
                        {{ shop.name?.charAt(0)?.toUpperCase() }}
                    </span>
                    <span class="shop-item-name">{{ shop.name }}</span>
                    <span v-if="shop.slug === currentSlug" class="shop-check">
                        <Check />
                    </span>
                </a>
            </div>
        </div>
    </li>
</template>

<style scoped lang="scss">
.shop-switcher-wrapper {
    display: flex;
    align-items: center;
}

/* ── Trigger Button ── */
.shop-trigger {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px 5px 6px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 50px;
    color: #fff !important;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 600;
    box-shadow: 0 2px 10px rgba(99, 102, 241, 0.4);
    transition: all 0.2s ease;
    border: none;

    &::after { display: none; } // remove default bootstrap caret

    &:hover {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        box-shadow: 0 4px 16px rgba(99, 102, 241, 0.55);
        transform: translateY(-1px);
        color: #fff !important;
    }

    .shop-icon-wrap {
        width: 24px;
        height: 24px;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;

        svg { width: 13px; height: 13px; }
    }

    .shop-label {
        max-width: 110px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .shop-arrow {
        width: 13px;
        height: 13px;
        opacity: 0.8;
        flex-shrink: 0;
    }
}

/* ── Dropdown ── */
.shop-dropdown {
    min-width: 220px;
    padding: 0;
    border: none;
    border-radius: 14px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.14);
    overflow: hidden;
    margin-top: 8px !important;
}

.shop-dropdown-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;

    svg { width: 15px; height: 15px; }
}

.shop-list {
    padding: 8px;
}

.shop-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 10px;
    text-decoration: none;
    color: #374151;
    transition: background 0.15s ease;

    &:hover {
        background: #f3f4f6;
        color: #111827;
    }

    &--active {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(139, 92, 246, 0.08));
        color: #4f46e5;

        .shop-item-name { font-weight: 600; }
    }
}

.shop-avatar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 0.85rem;
    font-weight: 700;
    flex-shrink: 0;
}

.shop-item-name {
    flex: 1;
    font-size: 0.875rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.shop-check {
    color: #6366f1;
    display: flex;
    svg { width: 15px; height: 15px; }
}
</style>

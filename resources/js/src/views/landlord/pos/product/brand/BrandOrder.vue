<script setup>
import { ref, onMounted } from "vue";
import draggable from "vuedraggable";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { EmptyPage, List } from "@iconoir/vue";
import Axios from "@/services/axios/index.js";
import { BRANDS, UPDATE_BRANDS_ORDER } from "@/services/endpoints/landlord.js";
import router from "@/router/index.js";

const { t } = useI18n();
const toast = useToast();

const brands = ref([]);
const pageLoader = ref(false);

const fetchBrands = async (page = 1, allBrands = []) => {
    const response = await Axios.get(`${BRANDS}?page=${page}&per_page=100`);
    const data = response.data;

    allBrands.push(...data.data);

    if (page < data.last_page) {
        return fetchBrands(page + 1, allBrands);
    }

    brands.value = allBrands
        .map((brand) => ({ ...brand, imageError: false }))
        .sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
};

const loadBrands = async () => {
    try {
        pageLoader.value = true;
        await fetchBrands();
    } catch (error) {
        toast.error("Failed to load brands");
    } finally {
        pageLoader.value = false;
    }
};

const updateOrder = async () => {
    try {
        const updatedBrands = brands.value.map((brand, index) => ({
            id: brand.id,
            order: index + 1,
        }));

        await Axios.post(UPDATE_BRANDS_ORDER, { brands: updatedBrands });
        toast.success(t('brand_order_updated'));
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to update brand order");
    }
};

const goBack = () => {
    router.push('/brands');
};

onMounted(() => {
    loadBrands();
});
</script>

<template>
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <app-breadcrumb :page-title="t('reorder_brands')" />
            <button class="btn btn-primary-outline" @click="goBack">
                {{ t('back_to_brands') }}
            </button>
        </div>

        <app-loader v-if="pageLoader" />

        <div class="card" v-else>
            <div class="card-body">
                <draggable
                    :list="brands"
                    item-key="id"
                    class="brand-order-list"
                    handle=".drag-indicator"
                    ghost-class="drag-ghost"
                    chosen-class="drag-chosen"
                    drag-class="drag-active"
                    :fallback-tolerance="0"
                    :force-fallback="true"
                    :animation="200"
                    easing="cubic-bezier(0.25, 1, 0.5, 1)"
                    @end="updateOrder">
                    <template #item="{ element }">
                        <li class="list-item-wrapper">
                            <div class="brand-card">
                                <div class="drag-indicator">
                                    <List />
                                </div>

                                <div class="brand-icon-wrapper">
                                    <img
                                        v-if="element.primary_logo && !element.imageError"
                                        :src="element.primary_logo"
                                        @error="element.imageError = true"
                                        class="brand-img" />
                                    <EmptyPage v-else class="icon-placeholder" />
                                </div>

                                <span class="brand-name">{{ element.name }}</span>
                            </div>
                        </li>
                    </template>
                </draggable>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.brand-order-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.list-item-wrapper {
    list-style: none;
}

.brand-card {
    display: flex;
    align-items: center;
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 6px;
    transition: border-color 0.15s ease, background-color 0.15s ease;
    will-change: transform;
    backface-visibility: hidden;

    &:hover {
        border-color: var(--primary);
        background: var(--primary-hover-two);
    }
}

.drag-indicator {
    padding: 10px;
    color: #cbd5e1;
    display: flex;
    align-items: center;
    cursor: grab;

    &:active {
        cursor: grabbing;
    }

    svg { width: 18px; height: 18px; }
}

.brand-icon-wrapper {
    width: 36px;
    height: 36px;
    background: #f1f5f9;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 6px;
    margin-right: 12px;

    img { width: 100%; height: 100%; object-fit: contain; }
}

.brand-name {
    font-weight: 500;
    color: var(--font-color);
}

.drag-ghost {
    opacity: 0.3;
    background: var(--primary) !important;
    pointer-events: none;
}

.drag-chosen {
    box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1) !important;
    opacity: 1 !important;
    background: #ffffff !important;
    transform: scale(1.02) translateZ(0);
    border: 1px solid var(--primary) !important;
}

.drag-active {
    cursor: grabbing !important;
    transition: none !important;
}
</style>

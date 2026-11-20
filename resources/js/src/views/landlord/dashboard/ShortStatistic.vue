<script setup lang="ts">
import { onBeforeMount, ref, computed } from "vue";
import { DASHBOARD_STATISTICS } from "@/services/endpoints/landlord.js";
import { Cart, CartPlus, ArrowEmailForward, PeopleTag } from "@iconoir/vue";
import CardSkeleton from "@/views/common/skeleton/CardSkeleton.vue";
import Axios from "@/services/axios/index.js";
import { formatNumberShortWithCurrency } from "@/utils/helper";
const preLoader = ref(false);
const statistics = ref();
const getStatistics = () => {
    preLoader.value = true;
    Axios.get(DASHBOARD_STATISTICS)
        .then(({ data }) => {
            statistics.value = data;
        }).finally(() => preLoader.value = false)
};

onBeforeMount(() => {
    getStatistics();

})
const iconMap = {
    total_supplier: CartPlus,
    total_purchases_invoice: Cart,
    total_purchases_return_invoice: ArrowEmailForward,
    total_customer: PeopleTag,
    total_sale_invoice: Cart,
    total_return_sale_invoice: ArrowEmailForward,
};

const statisticsTop = computed(() => [
    { key: "total_supplier", label: "total_supplier" },
    { key: "total_purchases_invoice", label: "total_purchases_invoice" },
    { key: "total_purchases_return_invoice", label: "total_purchases_return_invoice" },
]);

const statisticsBottom = computed(() => [
    { key: "total_customer", label: "total_customer" },
    { key: "total_sale_invoice", label: "total_sale_invoice" },
    { key: "total_return_sale_invoice", label: "total_return_sale_invoice" },
]);
</script>

<template>
    <card-skeleton v-if="preLoader" />
    <div class="d-flex flex-column justify-content-between h-100 gap-4" v-else>
        <div class="col-xl-12">
            <div class="card short-card">
                <div
                    class="card-body d-flex gap-3 align-items-center mb-2"
                    v-for="stat in statisticsTop"
                    :key="stat.key"
                >
                    <div class="icon-box">
                        <component :is="iconMap[stat.key]" />
                    </div>
                    <div class="content">
                        <p class="para">{{ $t(stat.label) }}</p>
                        <p class="highlight-para">
                            {{ statistics[stat.key] ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-12">
            <div class="card short-card">
                <div
                    class="card-body d-flex gap-3 align-items-center mb-2"
                    v-for="stat in statisticsBottom"
                    :key="stat.key"
                >
                    <div class="icon-box">
                        <component :is="iconMap[stat.key]" />
                    </div>
                    <div class="content">
                        <p class="para">{{ $t(stat.label) }}</p>
                        <p class="highlight-para">
                            {{ statistics[stat.key] ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss"></style>

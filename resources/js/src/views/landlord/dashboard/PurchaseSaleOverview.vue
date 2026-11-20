<script setup>
import BarChartWidget from "@/views/global/widget/BarChartWidget.vue";
import BarGraphSkeleton from "@/views/common/skeleton/BarGraphSkeleton.vue";
import DropdownMenuFilter from "@/views/common/dropdown-menu/DropdownMenuFilter.vue";
import { onMounted, ref, reactive } from "vue";
import Axios from "@/services/axios/index.js";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { Filter } from "@iconoir/vue";
import { formatNumberShortWithCurrency } from "@/utils/helper.js";
import { DASHBOARD_PURCHASE_SALE_OVERVIEW } from "@/services/endpoints/landlord.js";

const { t } = useI18n();
const toast = useToast();

const preLoader = ref(false);
const selectedFilter = ref(6);

// Chart reactive state
const purchaseSaleData = reactive({
    labels: [],
    datasets: [
        { label: t("purchase"), data: [], backgroundColor: "rgba(200, 50, 50, 0.9)", borderRadius: 6, maxBarThickness: 40 },
        { label: t("sale"), data: [], backgroundColor: "rgba(43, 135, 104, 0.9)", borderRadius: 6, maxBarThickness: 40 },
    ],
});

const purchaseSaleOptions = reactive({
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: { grid: { display: false, drawTicks: false }, ticks: { padding: 10 } },
        y: {
            beginAtZero: true,
            grid: { drawTicks: false },
            ticks: { callback: (value) => formatNumberShortWithCurrency(value) },
        },
    },
    plugins: {
        legend: { display: true, position: "bottom" },
        tooltip: {
            callbacks: { label: (ctx) => formatNumberShortWithCurrency(ctx.raw) },
            titleFont: { weight: "medium", size: 12 },
            bodyFont: { weight: "medium", size: 12 },
        },
    },
});

// Fetch and process data
const getPurchaseSaleOverView = async (filterType = 6) => {
    preLoader.value = true;
    selectedFilter.value = filterType;

    try {
        const { data } = await Axios.get(`${DASHBOARD_PURCHASE_SALE_OVERVIEW}/${filterType}`);

        const purchaseArray = Object.values(data.purchases)
            .map(({ date, context, purchases }) => ({ date, context, value: purchases }))
            .sort((a, b) => new Date(a.date) - new Date(b.date));

        const salesArray = Object.values(data.sales)
            .map(({ date, context, sales }) => ({ date, context, value: sales }))
            .sort((a, b) => new Date(a.date) - new Date(b.date));

        purchaseSaleData.labels = purchaseArray.map((item) => item.context);
        purchaseSaleData.datasets[0].data = purchaseArray.map((item) => item.value);
        purchaseSaleData.datasets[1].data = salesArray.map((item) => item.value);
    } catch (error) {
        toast.error(t("failed_to_fetch_purchase_sale_overview"));
    } finally {
        preLoader.value = false;
    }
};

onMounted(() => getPurchaseSaleOverView());
</script>

<template>
    <bar-graph-skeleton v-if="preLoader" />

    <div class="summary-card" v-else>
        <div class="top-wrapper">
            <h4 class="title">{{ t("purchase_sale_overview") }}</h4>
            
            <!-- Filter Dropdown -->
            <div class="dropdown ms-auto">
                <button
                    class="btn btn-white search-and-filter-btn"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <Filter /> {{ t("filter") }}
                </button>
                <dropdown-menu-filter
                    :selected="selectedFilter"
                    @applyFilter="getPurchaseSaleOverView($event)" />
            </div>
        </div>

        <div class="divider"></div>

        <!-- Chart -->
        <div class="position-relative">
            <BarChartWidget
                :chartData="purchaseSaleData"
                :chartOptions="purchaseSaleOptions"
                :showLegend="true"
                custom-type="purchaseSale"
                legend-id="purchase-sale-legend" />
        </div>
    </div>
</template>

<style scoped lang="scss"></style>

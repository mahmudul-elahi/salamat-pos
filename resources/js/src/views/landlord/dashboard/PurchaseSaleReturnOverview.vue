<script setup>
import LineChartWidget from "@/views/global/widget/LineChartWidget.vue";
import { DASHBOARD_PURCHASE_SALE_RETURN_OVERVIEW } from "@/services/endpoints/landlord.js";
import LineGraphSkeleton from "@/views/common/skeleton/LineGraphSkeleton.vue";
import { Filter } from "@iconoir/vue";
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import Axios from "@/services/axios/index.js";
import DropdownMenuFilter from "@/views/common/dropdown-menu/DropdownMenuFilter.vue";
import { useToast } from "vue-toastification";
import { formatNumberShortWithCurrency } from "@/utils/helper";

const { t } = useI18n();
const preLoader = ref(false);
const toast = useToast();
const selectedFilter = ref(6);

// Chart data
const purchaseSaleReturnData = {
    labels: [],
    datasets: [
        {
            label: t("purchases"),
            data: [],
            borderColor: "#2b8768",
            backgroundColor: "rgba(43, 135, 104, 0.06)",
            borderWidth: 2,
            fill: true,
            tension: 0.4,
        },
        {
            label: t("purchase_returns"),
            data: [],
            borderColor: "#ff3b3b",
            backgroundColor: "rgba(255, 59, 59, 0.06)",
            borderWidth: 2,
            fill: true,
            tension: 0.4,
        },
        {
            label: t("sales"),
            data: [],
            borderColor: "#3b7bff",
            backgroundColor: "rgba(59, 123, 255, 0.06)",
            borderWidth: 2,
            fill: true,
            tension: 0.4,
        },
        {
            label: t("sale_returns"),
            data: [],
            borderColor: "#ff9b00",
            backgroundColor: "rgba(255, 155, 0, 0.06)",
            borderWidth: 2,
            fill: true,
            tension: 0.4,
        },
    ],
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, grid: { display: false } },
    },
    plugins: {
        tooltip: {
            callbacks: {
                label: (ctx) => formatNumberShortWithCurrency(ctx.raw),
            },
            titleFont: { weight: "medium", size: 12 },
            bodyFont: { weight: "medium", size: 12 },
        },
        legend: {
            display: true,
            position: "bottom",
        },
    },
};

// Fetch data
const getPurchaseSaleReturnOverview = async (filterType = 6) => {
    preLoader.value = true;
    selectedFilter.value = filterType;

    try {
        const { data } = await Axios.get(`${DASHBOARD_PURCHASE_SALE_RETURN_OVERVIEW}/${filterType}`);
        processOverviewData(data);
    } catch (error) {
        toast.error("Failed to fetch purchase-sale-return overview");
    } finally {
        preLoader.value = false;
    }
};

// Transform API data into chart format
const processOverviewData = (data) => {
    const labels = Object.keys(data.purchases);

    purchaseSaleReturnData.labels = labels;

    purchaseSaleReturnData.datasets[0].data = labels.map((key) => data.purchases[key].purchases || 0);
    purchaseSaleReturnData.datasets[1].data = labels.map((key) => data.purchase_returns[key].purchases_return || 0);
    purchaseSaleReturnData.datasets[2].data = labels.map((key) => data.sales[key].sales || 0);
    purchaseSaleReturnData.datasets[3].data = labels.map((key) => data.sale_returns[key].sale_return || 0);
};

onMounted(() => getPurchaseSaleReturnOverview());
</script>


<template>
    <line-graph-skeleton v-if="preLoader" />
    <div class="summary-card" v-else>
        <div class="top-wrapper">
            <h4 class="title">{{ $t("purchase_sale_return_overview") }}</h4>
            <div class="dropdown">
                <button
                    class="btn btn-white search-and-filter-btn"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <Filter />
                    {{ $t("filter") }}
                </button>
                <dropdown-menu-filter
                    :selected="selectedFilter"
                    @applyFilter="getPurchaseSaleReturnOverview($event)" />
            </div>
        </div>
        <div class="divider"></div>

        <div class="purchase-sale-overview position-relative">
            <LineChartWidget
                :chartData="purchaseSaleReturnData"
                :chartOptions="chartOptions"
                :showLegend="true"
                custom-type="purchaseSale"
                legend-id="purchase-sale-return-legend" />
        </div>
    </div>
</template>

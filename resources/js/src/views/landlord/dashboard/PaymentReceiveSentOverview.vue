<script setup>
import LineChartWidget from "@/views/global/widget/LineChartWidget.vue";
import {DASHBOARD_PAYMENT_RECEIVE_SENT_OVERVIEW} from "@/services/endpoints/landlord.js";
import LineGraphSkeleton from "@/views/common/skeleton/LineGraphSkeleton.vue";
import {Filter} from "@iconoir/vue";
import {onMounted, ref} from "vue";
import {useI18n} from "vue-i18n";
import Axios from "@/services/axios/index.js";
import DropdownMenuFilter from "@/views/common/dropdown-menu/DropdownMenuFilter.vue";
import {useToast} from "vue-toastification";
import {formatNumberShortWithCurrency} from "@/utils/helper";

const {t} = useI18n();
const preLoader = ref(false);
const toast = useToast();
const selectedFilter = ref(6);

const paymentReceiveSentData = {
    labels: [],
    datasets: [
        {
            label: t("received_payment"),
            data: [],
            borderColor: "#2b8768",
            backgroundColor: "rgba(43, 135, 104, 0.06)",
            borderWidth: 2,
            fill: true,
            tension: 0.4,
        },
        {
            label: t("sent_payment"),
            data: [],
            borderColor: "#FF0000",
            backgroundColor: "rgba(200, 50, 50, 0.06)",
            borderWidth: 2,
            fill: true,
            tension: 0.4,
        },
    ],
};

const paymentReceiveSentOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: {grid: {display: false}},
        y: {beginAtZero: true, grid: {display: false}},
    },
    plugins: {
        tooltip: {
            callbacks: {
                label: (ctx) => {
                    return formatNumberShortWithCurrency(ctx.raw);
                },
            },
            titleFont: {weight: "medium", size: 12},
            bodyFont: {weight: "medium", size: 12},
        },
        legend: {
            display: true,
            position: "bottom",
        },
    },
};


const getPaymentReceiveSentOverview = async (filterType = 6) => {
    preLoader.value = true;
    selectedFilter.value = filterType;
    try {
        const {data} = await Axios.get(`${DASHBOARD_PAYMENT_RECEIVE_SENT_OVERVIEW}/${filterType}`);
        processOverviewData(data, filterType);
    } catch {
        toast.error("Failed to fetch payment receive/sent overview");
    } finally {
        preLoader.value = false;
    }
};

const processOverviewData = (data, filterType) => {
    if (filterType) {
        const receivedArray = Object.values(data.total_received_amount);
        const sentArray = Object.values(data.total_sent_amount);

        paymentReceiveSentData.labels = receivedArray.map(i => i.context);
        paymentReceiveSentData.datasets[0].data = receivedArray.map(i => i.received_payment);
        paymentReceiveSentData.datasets[1].data = sentArray.map(i => i.sent_payment);
    } else {
        paymentReceiveSentData.labels = [...new Set([
            ...Object.keys(data.total_received_amount),
            ...Object.keys(data.total_sent_amount)
        ])];
        paymentReceiveSentData.datasets[0].data = Object.values(data.total_received_amount).map(item => item.received_payment);
        paymentReceiveSentData.datasets[1].data = Object.values(data.total_sent_amount).map(item => item.sent_payment);
    }
};

onMounted(() => getPaymentReceiveSentOverview());
</script>

<template>
    <line-graph-skeleton v-if="preLoader"/>
    <div class="summary-card" v-else>
        <div class="top-wrapper">
            <h4 class="title">{{ $t("payment_receive_sent_overview") }}</h4>
            <div class="dropdown">
                <button
                    class="btn btn-white search-and-filter-btn"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <Filter/>
                    {{ $t("filter") }}
                </button>
                <dropdown-menu-filter
                    :selected="selectedFilter"
                    @applyFilter="getPaymentReceiveSentOverview($event)"/>
            </div>
        </div>
        <div class="divider"></div>
        <div class="purchase-sale-overview position-relative">
            <LineChartWidget
                :chartData="paymentReceiveSentData"
                :chartOptions="paymentReceiveSentOptions"
                :showLegend="true"
                custom-type="purchaseSale"
                legend-id="payment-legend"/>
        </div>
    </div>
</template>

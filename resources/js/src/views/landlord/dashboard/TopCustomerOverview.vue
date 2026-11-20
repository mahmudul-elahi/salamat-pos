<script setup>
import DoughnutChartWidget from "@/views/global/widget/DoughnutChartWidget.vue";
import { onMounted, ref } from "vue";
import Axios from "@/services/axios/index.js";
import { DASHBOARD_TOP_CUSTOMER_OVERVIEW } from "@/services/endpoints/landlord.js";
import { formatNumberShortWithCurrency } from "@/utils/helper.js";
import { Filter } from "@iconoir/vue";
import DoughnutGraphSkeleton from "@/views/common/skeleton/DoughnutGraphSkeleton.vue";

const topProductChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '67%',
    plugins: {
        legend: {
            display: false,
            labels: {
                font: { weight: 'medium', size: 12 },
                color: '#222',
                usePointStyle: true,
                pointStyle: 'circle'
            }
        },
        tooltip: {
            callbacks: {
                label: (ctx) => {
                    return ctx.raw;
                },
            },
            titleFont: { weight: 'medium', size: 12 },
            bodyFont: { weight: 'medium', size: 12 },
        }
    }
};

const topProductChartData = {
    labels: [],
    datasets: [
        {
            data: [],
            backgroundColor: ['#246CF9', '#1B998B', '#FD7238', '#E5EBF8', '#FF6B6B', '#4CDC4',
                '#556270', '#C7F464', '#FFCC5C', '#6C5B7B', '#F67280', '#355C7D',
                '#99B898', '#E84A5F', '#2A363B', '#EDE574', '#00B8A9', '#F8B195',
                '#3F51B5'],
            borderWidth: 0,
        },
    ],
};

const preLoader = ref(false);
const centerTotal = ref(0);

const getTopProduct = async () => {
    preLoader.value = true;
    try {
        const { data } = await Axios.get(DASHBOARD_TOP_CUSTOMER_OVERVIEW);

        // Update chart with product_name and quantity
        centerTotal.value = data.reduce((sum, item) => sum + item.total_sale, 0);
        topProductChartData.labels = data.map(item => item.name);
        topProductChartData.datasets[0].data = data.map(item => item.total_sale);
    } catch (error) {
        console.error("Failed to fetch top product overview", error);
    } finally {
        preLoader.value = false;
    }
}

onMounted(() => {
    getTopProduct();
})
</script>


<template>
    <doughnut-graph-skeleton v-if="preLoader" />
    <div class="summary-card" v-else>
        <div class="top-wrapper">
            <h4 class="title">{{ $t('top_five_customer') }}</h4>

        </div>
        <div class="divider"></div>
        <DoughnutChartWidget type="doughnut"
            :chart-data="topProductChartData"
            :chart-options="topProductChartOptions"
            custom-type="topProduct"
            :showCurrencySymbol="false"
            :center-total="centerTotal" />
    </div>
</template>

<style scoped lang="scss"></style>

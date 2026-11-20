<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { formatNumberShortWithCurrency } from "@/utils/helper.js";
import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    chartData: {
        type: Object,
        required: true,
    },
    chartOptions: {
        type: Object,
        default: () => ({
            responsive: true,
            maintainAspectRatio: false,
        }),
    },
    showLegend: {
        type: Boolean,
        default: true,
    },
    legendId: {
        type: String,
        default: 'line-custom-legend',
    },
    disableTheming: {
        type: Boolean,
        default: false,
    },
    useTheme: { type: Boolean, default: true },
    customType: {
        type: String,
        default: '',
    }
});
let observer;
const getThemedOptions = (enabled) => {
    if (!enabled) return {}; // no themed options if disabled

    const isDark = document.documentElement.getAttribute('theme') === 'dark';
    const fontColor = isDark ? '#b9b8bd' : '#b9b8bd';
    const gridColor = isDark ? '#2D3F58' : '#f3f4f6';

    return {
        scales: {
            x: {
                ticks: {
                    color: fontColor,
                    padding: 10,
                },
                grid: {
                    color: gridColor,
                    drawTicks: false,
                },
                border: { color: gridColor },
            },
            y: {
                ticks: {
                    color: fontColor,
                    callback: (value) => {
                        if (props.customType === 'subscriber') {
                            return value + '  ';
                        }
                        return formatNumberShortWithCurrency(value) + '  ';
                    }
                },
                grid: {
                    color: gridColor,
                    drawTicks: false,
                },
                border: { color: gridColor },
            },
        },
        plugins: {
            tooltip: {
                bodyColor: fontColor,
                backgroundColor: isDark ? '#dddd' : '#F9FAFB',
            },
            legend: { display: false },
        },
    };
};


const chartRef = ref(null);
let chartInstance = null;

const createCustomLegend = (chart) => {
    const legendContainer = document.getElementById(props.legendId);
    if (!legendContainer) return;
    legendContainer.innerHTML = '';

    chart.data.datasets.forEach((dataset) => {
        const total = dataset.data.reduce((acc, val) => acc + val, 0);

        const item = document.createElement('div');
        item.classList.add('legend-item');

        const boxAndTitle = document.createElement('div');
        boxAndTitle.classList.add('legend-box-and-title');

        const box = document.createElement('span');
        box.classList.add('legend-box');
        box.style.borderColor = dataset.borderColor;
        box.style.backgroundColor = dataset.borderColor;

        const labelText = document.createElement('div');
        labelText.classList.add('legend-title');
        labelText.textContent = dataset.label;
        labelText.style.color = dataset.borderColor;

        boxAndTitle.appendChild(box);
        boxAndTitle.appendChild(labelText);

        const totalText = document.createElement('div');
        totalText.classList.add('legend-total');
        totalText.textContent = `${formatNumberShortWithCurrency(total)}`;
        if (props.customType === 'subscriber') {
            totalText.textContent = total;
        }

        item.appendChild(boxAndTitle);
        item.appendChild(totalText);

        legendContainer.appendChild(item);
    });
};

onMounted(() => {
    nextTick(() => {
        renderChart();
    });
    observer = new MutationObserver(() => {
        renderChart();
    });
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['theme'],
    });
});
const themedOptions = props.disableTheming ? {} : getThemedOptions();
const renderChart = () => {
    if (!chartRef.value) return;

    if (chartInstance) chartInstance.destroy();

    const themedOptions = getThemedOptions(props.useTheme && !props.disableTheming);

    chartInstance = new Chart(chartRef.value, {
        type: 'line',
        data: props.chartData,
        options: {
            ...props.chartOptions,
            ...themedOptions,
            plugins: {
                ...props.chartOptions.plugins,
                legend: { display: false },
            },
        },
    });

    if (props.showLegend) createCustomLegend(chartInstance);
};



watch(
    () => props.chartData,
    () => {
        nextTick(() => {
            renderChart();
        });
    },
    { immediate: true }
);

onBeforeUnmount(() => {
    if (chartInstance) chartInstance.destroy();
    if (observer) observer.disconnect();
});
</script>

<template>
    <div class="line-chart-widget" :class="{
        'purchase-sale-chart': customType === 'purchaseSale',
        'subscriber-chart': customType === 'subscriber',
        'invoice-chart': customType === 'invoiceChart',
    }">
        <div :style="{
            height: customType === 'purchaseSale' ? '280px' :
                customType === 'invoiceChart' ? '170px' :
                    customType === 'subscriber' ? '230px' : undefined,
            width: ['purchaseSale', 'invoiceChart', 'subscriber'].includes(customType) ? '100%' : undefined
        }">
            <canvas ref="chartRef"></canvas>
        </div>
        <div :id="legendId" class="line-custom-legend" style="margin-bottom: 10px;" v-if="showLegend"></div>
    </div>
</template>

<style scoped lang="scss"></style>

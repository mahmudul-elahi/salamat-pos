<script setup>
import { onMounted, ref, watch, onBeforeUnmount, nextTick } from 'vue';
import { formatNumberShortWithCurrency } from "@/utils/helper.js";
import {
    Chart,
    LineElement,
    LineController,
    PointElement,
    CategoryScale,
    LinearScale,
    ArcElement,
    Tooltip,
    Legend,
    Filler,
    DoughnutController,
    BarElement,
    BarController,
} from 'chart.js';

Chart.register(
    PointElement,
    LineElement,
    LineController,
    CategoryScale,
    LinearScale,
    ArcElement,
    Tooltip,
    Filler,
    Legend,
    DoughnutController,
    BarElement,
    BarController
);


const props = defineProps({
    type: {
        type: String,
        default: 'line'
    },
    chartData: {
        type: Object,
        required: true
    },
    chartOptions: {
        type: Object,
        default: () => ({
            responsive: true,
            maintainAspectRatio: false
        }),
    },
    centerTotal: {
        type: Number,
        default: 0
    },
    customType: {
        type: String,
        default: ''
    },
    showCurrencySymbol: {
        type: Boolean,
        default: true
    }
});

const chartRef = ref(null);
const legendRef = ref(null);

let chartInstance = null;
const centerTextPlugin = {
    id: 'centerText',
    beforeDraw(chart) {
        const { width, height, ctx } = chart;
        ctx.save();

        // Detect dark mode based on parent class
        const isDark = document.documentElement.getAttribute('theme') === 'dark';

        // Colors for light and dark modes
        const labelColor = isDark ? '#9CA3AF' : '#6B7280';
        const totalColor = isDark ? '#F9FAFB' : '#111827';

        ctx.textBaseline = 'middle';

        // Draw label
        ctx.font = 'normal 12px sans-serif';
        ctx.textBaseline = 'middle';
        const labelText = 'Total';
        ctx.fillStyle = labelColor;
        const labelX = (width - ctx.measureText(labelText).width) / 2;
        ctx.fillText(labelText, labelX, height / 2 - 12);

        // Draw total
        ctx.font = 'bold 20px sans-serif';
        ctx.fillStyle = totalColor;
        let totalText = props.showCurrencySymbol
            ? formatNumberShortWithCurrency(props.centerTotal)
            : props.centerTotal.toLocaleString();

        if (props.centerTotal >= 1000) {
            totalText = formatNumberShortWithCurrency(props.centerTotal);

        }
        const totalX = (width - ctx.measureText(totalText).width) / 2;
        ctx.fillText(totalText, totalX, height / 2 + 12);

        ctx.restore();
    }

};
const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.attributeName === 'theme') {
            if (chartInstance) {
                nextTick(() => {
                    if (chartInstance?.canvas?.ownerDocument) {
                        chartInstance.update();
                    }
                });
            }
        }
    });
});
observer.observe(document.documentElement, { attributes: true });


const renderChart = () => {
    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(chartRef.value, {
        type: props.type,
        data: props.chartData,
        options: props.chartOptions,
        plugins: props.type === 'doughnut' ? [centerTextPlugin] : [],
    });

    if (props.type === 'doughnut' && legendRef.value) {
        legendRef.value.innerHTML = '';

        const bgColors = props.chartData.datasets?.[0]?.backgroundColor || [];
        const labels = props.chartData.labels || [];
        const prices = props.chartData.datasets?.[0]?.data || [];

        // Utility to chunk array into smaller arrays of size n
        function chunkArray(arr, chunkSize) {
            const result = [];
            for (let i = 0; i < arr.length; i += chunkSize) {
                result.push(arr.slice(i, i + chunkSize));
            }
            return result;
        }

        // Split labels into chunks of 4
        const labelChunks = labels.length > 4 ? chunkArray(labels, 4) : [labels];

        labelChunks.forEach(chunkLabels => {
            const ul = document.createElement('ul');
            ul.classList.add('custom-legend-list');
            ul.style.display = 'inline-block';

            chunkLabels.forEach(label => {
                const i = labels.indexOf(label);

                const li = document.createElement('li');
                li.style.display = 'flex';
                li.style.alignItems = 'center';

                const labelContainer = document.createElement('div');
                labelContainer.style.display = 'flex';
                labelContainer.style.alignItems = 'center';

                const box = document.createElement('span');
                box.style.backgroundColor = bgColors[i] || '#000';
                box.style.width = '12px';
                box.style.height = '12px';
                box.style.display = 'inline-block';
                box.style.marginRight = '8px';
                box.style.borderRadius = '50%';

                const text = document.createElement('p');
                text.classList.add('custom-legend-text');
                text.textContent = label;

                labelContainer.appendChild(box);
                labelContainer.appendChild(text);

                li.appendChild(labelContainer);

                if (props.customType === 'payment') {
                    const price = document.createElement('p');
                    price.classList.add('custom-legend-price-text');
                    const rawPrice = prices[i] !== undefined ? prices[i] : 0;
                    let formattedPriceText = '';

                    if (props.showCurrencySymbol) {
                        formattedPriceText = formatNumberShortWithCurrency(rawPrice);
                    } else {
                        formattedPriceText = rawPrice.toLocaleString();
                    }


                    price.textContent = formattedPriceText;
                    price.style.fontSize = '16px';
                    price.style.fontWeight = '500';

                    li.appendChild(price);
                } else if (props.customType === 'customer') {
                    const percentage = document.createElement('span');
                    const total = prices.reduce((acc, val) => acc + val, 0);
                    const percent = total ? ((prices[i] / total) * 100).toFixed(1) : '0';
                    percentage.textContent = percent + '%';
                    percentage.style.fontWeight = 'bold';
                    li.appendChild(percentage);
                }

                ul.appendChild(li);
            });

            legendRef.value.appendChild(ul);
        });
        if (props.customType === 'topProduct') {
            legendRef.value.classList.remove('custom-legend-multiple');
        } else {
            const legendListsCount = legendRef.value.querySelectorAll('.custom-legend-list').length;
            if (legendListsCount > 1) {
                legendRef.value.classList.add('custom-legend-multiple');
            } else {
                legendRef.value.classList.remove('custom-legend-multiple');
            }
        }
    }

};

onMounted(renderChart);
watch(
    () => [props.chartData, props.chartOptions],
    () => {
        renderChart();
    },
    { deep: true }
);

onBeforeUnmount(() => {
    if (chartInstance) {
        chartInstance.destroy();
    }
});
</script>

<template>
    <div class="chart-widget" :class="{
        'payment-chart': customType === 'payment',
        'landlord-plan-chart': customType === 'plan',
        'top-product-chart': customType === 'topProduct',
        'customer-chart': customType === 'customer',
        'category-chart': customType === 'category'
    }">
        <div :style="{
            height: customType === 'payment' ? '280px' : customType === 'plan' ? '230px' : '250px',
            width: customType === 'payment' ? '280px' : customType === 'plan' ? '230px' : '250px'
        }">
            <canvas ref="chartRef"></canvas>
        </div>
        <div ref="legendRef" class="custom-legend"></div>
    </div>
</template>


<style scoped lang="scss"></style>

<script setup>
defineProps({
    showLegend: {
        type: Boolean,
        default: true
    }
});
</script>
<template>
    <div class="line-skeleton-card">

        <div class="chart-area-skeleton shimmer"></div>
        <!-- Skeleton for custom legend -->
        <div class="legend-skeleton" v-if="showLegend">
            <div class="legend-item-skeleton" v-for="i in 2" :key="i">
                <div class="legend-box-title-skeleton">
                    <div class="box-skeleton shimmer"></div>
                    <div class="text-skeleton shimmer" style="width: 60px;"></div>
                </div>
                <div class="value-skeleton shimmer" style="width: 40px;"></div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss"></style>

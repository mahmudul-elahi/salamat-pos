<script setup>
const barHeights = [100, 140, 90, 160, 110, 130, 80];
</script>
<template>
    <div class="bar-graph-skeleton" aria-busy="true" aria-label="Loading chart">
        <div class="skeleton-title shimmer"></div>
        <div class="skeleton-chart-area">
            <div
                class="skeleton-bar shimmer"
                v-for="n in 7"
                :key="n"
                :style="{ height: barHeights[n - 1] + 'px' }"></div>
        </div>
    </div>
</template>

<style scoped lang="scss"></style>

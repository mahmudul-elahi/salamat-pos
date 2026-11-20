<script setup></script>
<template>
    <div class="invoice-skeleton-card">
        <div class="overview-invoice-wrapper" v-for="i in 3" :key="i">
            <div class="overview-title">
                <div class="color-tag shimmer"></div>
                <div class="invoice-skeleton-line w-40 shimmer"></div>
            </div>
            <div class="progress-wrapper">
                <div class="progress-bar shimmer w-100"></div>
            </div>
            <div class="total-count invoice-skeleton-line w-30 shimmer"></div>
            <div class="total-amount invoice-skeleton-line w-50 shimmer"></div>
        </div>
        <div class="chart-summary mt-4">
            <div class="row g-4 justify-content-between">
                <div class="col-md-4">
                    <div class="invoice-skeleton-line w-50 shimmer mb-2"></div>
                    <div class="invoice-skeleton-line w-75 shimmer"></div>
                </div>
                <div class="col-md-6">
                    <div class="chart-placeholder shimmer"></div>
                </div>
            </div>
        </div>
    </div>

</template>

<style scoped lang="scss">
</style>

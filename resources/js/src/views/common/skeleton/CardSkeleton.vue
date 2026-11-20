<script setup>
</script>
<template>
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-md-3 col-lg-6 col-xl-3" v-for="n in 4" :key="n">
            <div class="skeleton-card shimmer">
                <div class="skeleton-icon"></div>
                <div class="line-wrapper">
                    <div class="skeleton-line w-70"></div>
                    <div class="skeleton-line w-50"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss"></style>

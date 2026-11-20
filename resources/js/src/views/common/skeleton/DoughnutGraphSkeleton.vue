<script setup>
const props = defineProps({
  type: {
    type: String,
    default: ''
  }
});
</script>
<template>
    <div class="doughnut-skeleton-card" :class="{ 'centered': type === 'center' }">
        <div class="doughnut-placeholder">
            <div class="circle shimmer"></div>
        </div>
        <div class="doughnut-info d-flex flex-column gap-5 w-100">
            <div class="top-wrapper">
                <div class="skeleton-line w-70 shimmer"></div>
            </div>
            <div class="top-wrapper">
                <div class="skeleton-line w-70 shimmer"></div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss"></style>

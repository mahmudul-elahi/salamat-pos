<script setup>
const props = defineProps({
    data: {}
})
</script>

<template>
    <div class="status-card">
        <div class="card-top-wrapper">
            <div class="icon" :style="{ backgroundColor: data.colorize || '#FD7238' }">
                <component :is="data.icon"/>
            </div>
        </div>

        <div class="d-flex gap-4 justify-content-between align-items-end flex-wrap">
            <div class="items">
                <h4 class="title" v-if="data.title">{{ data.title }}</h4>
                <h4 class="amount">{{ data.amount }}</h4>
            </div>

        </div>
    </div>
</template>

<style scoped lang="scss"></style>

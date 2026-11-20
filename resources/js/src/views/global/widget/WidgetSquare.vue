<script setup>

const props = defineProps({
    data: {}
})
</script>

<template>
    <div class="status-card-square">
        <div class="items">
            <div class="title">
                {{ data.title }}
            </div>
            <div class="sub-title" v-if="data.subTitle">
                {{ data.subTitle }}
            </div>
        </div>
        <div class="items" :class="data.colorize" v-if="data.icon">
            <img :src="data.icon" alt="icon">
        </div>
    </div>
</template>

<style scoped lang="scss"> 
</style>

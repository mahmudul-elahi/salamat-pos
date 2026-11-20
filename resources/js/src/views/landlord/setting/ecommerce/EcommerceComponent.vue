<script setup>
const props = defineProps({
    rowData: {}
})
</script>

<template>
    <div class="name mx-2 fs-6">
        <router-link :to="`/shops/details/${rowData.id}`" class="brand-color">
            {{ rowData.name }}
        </router-link>
    </div>
</template>

<style scoped lang="scss"></style>

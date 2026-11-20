<script setup>
const props = defineProps({
    rowData: {}
})


</script>

<template>

    <div class="name mx-2 fs-6">
        {{ rowData.name }}
    </div>

</template>

<style scoped lang="scss"></style>

<script setup>
const props = defineProps({
    value: {},
    data: {}
})

</script>

<template>
    <input type="email"
           :id="data.id"
           class="form-control"
           :value="value"
           v-bind="$attrs">
</template>

<style scoped lang="scss">

</style>
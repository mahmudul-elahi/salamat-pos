<script setup>
// Define props using defineProps macro
const props = defineProps({
    value: [Number, String], // Accepts both Number and String types
    data: {}
})
</script>

<template>
    <!-- Input field for numbers -->
    <input type="number"
           :id="data.id"
           class="form-control"
           :value="value"
           v-bind="$attrs">
</template>


<style scoped lang="scss">

</style>

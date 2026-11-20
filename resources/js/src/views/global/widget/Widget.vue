<script setup>
import WidgetNormal from "@/views/global/widget/WidgetNormal.vue";
import WidgetSquare from "@/views/global/widget/WidgetSquare.vue";

const props = defineProps({
    type: {
        type: String,
        default: 'normal'
    },
    title: {
        type: [String, Number],
        required: true
    },
    amount: {
        type: [String, Number],
        required: true
    },
    icon: {
        type: [String, Object],
        required: true
    },
    colorize: {
        type: String
    },

})
</script>

<template>
    <widget-square v-if="type === 'square'" :data="props"></widget-square>
    <widget-normal v-else :data="props"></widget-normal>
</template>

<style scoped lang="scss">

</style>
